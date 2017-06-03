<?php

/**
 * Copyright (C) 2017 Spencer Mortensen
 *
 * This file is part of TestPHP.
 *
 * TestPHP is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * TestPHP is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with TestPHP. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Spencer Mortensen <spencer@spencermortensen.com>
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL-3.0
 * @copyright 2017 Spencer Mortensen
 */

namespace TestPhp;

class Evaluator
{
	/** @var Filesystem */
	private $filesystem;

	/** @var string */
	private $executable;

	/** @var string */
	private $testsDirectory;

	/** @var string */
	private $codeDirectory;

	/** @var boolean */
	private $isCoverageEnabled;

	/** @var array */
	private $code;

	/** @var array */
	private $status;

	public function __construct(Filesystem $filesystem, $executable)
	{
		$this->filesystem = $filesystem;
		$this->executable = $executable;
	}

	public function evaluate(array $suites, $testsDirectory, $codeDirectory)
	{
		$this->testsDirectory = $testsDirectory;
		$this->codeDirectory = $codeDirectory;

		$this->isCoverageEnabled = function_exists('xdebug_get_code_coverage') && is_string($this->codeDirectory);
		$this->code = array();
		$this->status = array();

		foreach ($suites as &$suite) {
			list($preambleExpected, $preambleActual, $fixture) = $this->getFixtures($suite['fixture']);
			unset($suite['fixture']);

			foreach ($suite['tests'] as &$test) {
				$subject = $test['subject'];

				foreach ($test['cases'] as &$case) {
					$case = $this->evaluateCase($preambleExpected, $preambleActual, $fixture, $subject, $case);
				}
			}
		}

		$coverage = $this->getCodeCoverage();

		return array(
			'suites' => $suites,
			'coverage' => $coverage
		);
	}

	private function evaluateCase($expectedPreamble, $actualPreamble, $fixture, $subject, $case)
	{
		$expectedCode = self::combine($expectedPreamble, $fixture, $case['input'], $case['output']);
		$expectedResults = $this->evaluateExpectedCode($expectedCode, $mock);

		$actualCode = self::combine($actualPreamble, $mock, $fixture, $case['input'], $subject);
		$resultsActual = $this->evaluateActualCode($actualCode);

		return array(
			'input' => $case['input'],
			'output' => $case['output'],
			'expected' => $expectedResults,
			'actual' => $resultsActual
		);
	}

	private static function combine()
	{
		return implode("\n\n", array_filter(func_get_args(), 'is_string'));
	}

	private function evaluateExpectedCode($code, &$mockPhp)
	{
		$output = $this->run($code, false);

		if (isset($output['results'])) {
			$calls = $output['results']['calls'];

			if (0 < count($calls)) {
				$callsPhp = var_export(json_encode($calls), true);
				$mockPhp = "\\TestPhp\\Agent::setExpected({$callsPhp});";
			}

			// TODO: this constant name is duplicated elsewhere:
			unset($output['results']['constants']['TESTPHP_TESTS_DIRECTORY']);
		}

		return $output;
	}

	private function evaluateActualCode($code)
	{
		$output = $this->run($code, $this->isCoverageEnabled);

		if (isset($output['coverage'])) {
			$this->mergeCoverage($output['coverage']);
		}

		unset(
			$output['coverage'],
			$output['results']['constants']['TESTPHP_TESTS_DIRECTORY']
		);

		return $output;
	}

	private function run($sourceCode, $enableCodeCoverage)
	{
		$arguments = "--mode='test' --code=" . escapeshellarg($sourceCode);

		if ($enableCodeCoverage) {
			$arguments .= " --coverage";
		}

		$command = "{$this->executable} {$arguments} 2>/dev/null";

		// TODO: abstract this away:
		exec($command, $output, $exitCode);

		$stdout = implode("\n", $output);
		list($results, $coverage) = json_decode($stdout, true);

		$output = array(
			'results' => $results,
			'exit' => $exitCode
		);

		if ($enableCodeCoverage) {
			$output['coverage'] = $coverage;
		}

		return $output;
	}

	private function mergeCoverage(array $coverage)
	{
		$length = strlen($this->codeDirectory);

		foreach ($coverage as $absolutePath => $lines) {
			if (self::isEvaluatedCode($absolutePath)) {
				continue;
			}

			// Ignore files that are outside the source-code directory under scrutiny
			if (strncmp($absolutePath, $this->codeDirectory, $length) !== 0) {
				continue;
			}

			$relativePath = substr($absolutePath, $length + 1);
			$fileCoverage = &$this->status[$relativePath];

			foreach ($lines as $i => $testStatus) {
				$suiteStatus = &$fileCoverage[$i];
				$suiteStatus = ($testStatus === 1) || ($suiteStatus === true);
			}
		}
	}

	private static function isEvaluatedCode($file)
	{
		$evaluatedCodePattern = '~\(\d+\) : eval\(\)\'d code$~';

		return preg_match($evaluatedCodePattern, $file) === 1;
	}

	private function getCodeCoverage()
	{
		if (!$this->isCoverageEnabled) {
			return null;
		}

		$contents = $this->filesystem->read($this->codeDirectory);
		$this->getDirectoryCode('', $contents);

		$files = array_keys($this->code);

		foreach ($files as $file) {
			if (!isset($this->status[$file])) {
				$absoluteFile = "{$this->codeDirectory}/{$file}";
				$this->status[$file] = $this->getMissingCoverage($absoluteFile);
			}
		}

		self::cleanCoverage($this->status, $this->code);

		return array(
			'code' => $this->code,
			'status' => $this->status
		);
	}

	private function getDirectoryCode($path, $contents)
	{
		foreach ($contents as $childName => $childContents) {
			$childPath = self::mergePaths($path, $childName);

			if (is_array($childContents)) {
				$this->getDirectoryCode($childPath, $childContents);
			}

			if (is_string($childContents) && self::isPhpFile($childName)) {
				$this->getFileCode($childPath, $childContents);
			}
		}
	}

	private static function mergePaths($a, $b)
	{
		if ($a === '') {
			return $b;
		}

		return "{$a}/{$b}";
	}

	private function getFileCode($path, $contents)
	{
		$lines = explode("\n", "\n{$contents}");
		unset($lines[0]);

		$this->code[$path] = $lines;
	}

	private static function isPhpFile($path)
	{
		return substr($path, -4) === '.php';
	}

	private function getMissingCoverage($filePath)
	{
		$arguments = "--mode='coverage' --file=" . escapeshellarg($filePath);
		$command = "{$this->executable} {$arguments} 2>/dev/null";

		// TODO: abstract this away:
		exec($command, $output, $exitCode);

		$stdout = implode("\n", $output);
		$results = json_decode($stdout, true);

		if (!is_array($results)) {
			return null;
		}

		$coverage = array();

		foreach ($results as $i => $status) {
			$coverage[$i] = ($status === 1);
		}

		return $coverage;
	}

	private static function cleanCoverage(&$coverage, $lines)
	{
		foreach ($coverage as $file => &$fileCoverage) {
			unset($fileCoverage[0]);

			foreach ($fileCoverage as $i => $status) {
				$text = $lines[$file][$i];

				if (!self::isTestableCode($text)) {
					unset($fileCoverage[$i]);
				}
			}
		}
	}

	private static function isTestableCode($text)
	{
		$text = trim($text);

		if (strlen(trim($text, '{}')) === 0) {
			return false;
		}

		if (substr($text, 0, 6) === 'class ') {
			return false;
		}

		return true;
	}

	private function getFixtures($fixture)
	{
		$preamble = $fixture;
		$namespace = $this->extractNamespaceCode($preamble);
		$constant = $this->getConstantCode($preamble);
		list($expectedMock, $actualMock) = $this->getMocks($preamble);

		return array(
			self::combine($namespace, $constant, $expectedMock),
			self::combine($namespace, $constant, $actualMock),
			$preamble
		);
	}

	private function extractNamespaceCode(&$code)
	{
		$namespacePattern = '~^\s*namespace\h+[^\n\r]+~';

		if (preg_match($namespacePattern, $code, $match, PREG_OFFSET_CAPTURE) !== 1) {
			return null;
		}

		$code = trim(substr($code, $match[0][1] + strlen($match[0][0])));
		return $match[0][0];
	}

	private function getConstantCode($code)
	{
		$constant = 'TESTPHP_TESTS_DIRECTORY';

		if (!self::usesConstant($code, $constant)) {
			return null;
		}

		return self::getConstantDeclaration($constant, $this->testsDirectory);
	}

	private static function usesConstant($code, $name)
	{
		return is_integer(strpos($code, $name));
	}

	private static function getConstantDeclaration($name, $value)
	{
		$nameCode = var_export($name, true);
		$valueCode = var_export($value, true);

		return "define({$nameCode}, {$valueCode});";
	}

	private function getMocks($code)
	{
		if (!self::usesMock($code)) {
			return null;
		}

		return array(
			$this->getAutoloaderExpected(),
			$this->getAutoloaderActual()
		);
	}

	private static function usesMock($code)
	{
		return is_integer(strpos($code, 'use TestPhp\\Mock\\'));
	}

	private static function getAutoloaderActual()
	{
		return <<<'EOT'
spl_autoload_register(
	function ($path)
	{
		$mockPrefix = 'TestPhp\\Mock\\';
		$mockPrefixLength = strlen($mockPrefix);

		if (strncmp($path, $mockPrefix, $mockPrefixLength) !== 0) {
			return;
		}

		$parentClass = substr($path, $mockPrefixLength);

		$mockBuilder = new \TestPhp\MockBuilder($mockPrefix, $parentClass);
		$mockCode = $mockBuilder->getMock();

		eval($mockCode);
	}
);
EOT;
	}

	private static function getAutoloaderExpected()
	{
		return <<<'EOT'
spl_autoload_register(
	function ($path)
	{
		$mockPrefix = 'TestPhp\\Mock\\';
		$mockPrefixLength = strlen($mockPrefix);

		if (strncmp($path, $mockPrefix, $mockPrefixLength) !== 0) {
			return;
		}

		$slash = strrpos($path, '\\');
		$namespace = substr($path, 0, $slash);
		$class = substr($path, $slash + 1);

		$mockTemplate = <<<'EOS'
namespace %s;
		
class %s {
	public function __construct()
	{
		$callable = array($this, __FUNCTION__);
		$arguments = func_get_args();
		$result = null;
		
		\TestPhp\Agent::record($callable, $arguments, $result);
	}
		
	public function __call($name, $input)
	{
		$callable = array($this, $name);

		if (is_array($input) && (count($input) === 2)) {
			list($arguments, $result) = $input;
		} else {
			$arguments = $input;
			$result = null;
		}

		return \TestPhp\Agent::record($callable, $arguments, $result);
	}
}
EOS;

		$code = sprintf($mockTemplate, $namespace, $class);

		eval($code);
	}
);
EOT;
	}
}
