<?php

/**
 * Copyright (C) 2017 Spencer Mortensen
 *
 * This file is part of testphp.
 *
 * Testphp is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Testphp is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with testphp. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Spencer Mortensen <spencer@testphp.org>
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL-3.0
 * @copyright 2017 Spencer Mortensen
 */

namespace TestPhp;

class Evaluator
{
	private static $testphpConstant = 'TESTPHP';

	/** @var Filesystem */
	private $filesystem;

	/** @var Shell */
	private $shell;

	/** @var string */
	private $testphpDirectory;

	/** @var string */
	private $srcDirectory;

	/** @var string */
	private $executable;

	/** @var boolean */
	private $isCoverageEnabled;

	/** @var array */
	private $code;

	/** @var array */
	private $status;

	public function __construct(Filesystem $filesystem, Shell $shell)
	{
		$this->filesystem = $filesystem;
		$this->shell = $shell;
	}

	public function evaluate(array $suites, $testphpDirectory, $srcDirectory, $executable)
	{
		$this->testphpDirectory = $testphpDirectory;
		$this->srcDirectory = $srcDirectory;
		$this->executable = $executable;

		$this->isCoverageEnabled = function_exists('xdebug_get_code_coverage') && is_string($this->srcDirectory);
		$this->code = array();
		$this->status = array();

		foreach ($suites as &$suite) {
			list($preamble, $fixture) = $this->getFixtures($suite['fixture']);
			unset($suite['fixture']);

			foreach ($suite['tests'] as &$test) {
				$subject = $test['subject'];

				foreach ($test['cases'] as &$case) {
					$case = $this->evaluateCase($preamble, $fixture, $subject, $case);
				}
			}
		}

		$coverage = $this->getCodeCoverage();

		return array(
			'suites' => $suites,
			'coverage' => $coverage
		);
	}

	private function evaluateCase($preamble, $fixture, $subject, $case)
	{
		$expectedOutputCode = $case['output'];
		$expectedCode = self::combine($preamble, $fixture, $case['input'], $expectedOutputCode);
		$expectedResults = $this->evaluateExpectedCode($expectedCode, $mock);

		$actualCode = self::combine($preamble, $mock, $fixture, $case['input'], $subject);
		$resultsActual = $this->evaluateActualCode($actualCode);

		return array(
			'text' => $case['text'],
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

	private function evaluateExpectedCode($code, &$scriptPhp)
	{
		$output = $this->run($code, false);

		if (isset($output['script'])) {
			$script = $output['script'];

			if (0 < count($script)) {
				$scriptArgumentPhp = var_export(serialize($script), true);
				$scriptPhp = "\\TestPhp\\Agent::setScript({$scriptArgumentPhp});";
			}
		}

		return $output;
	}

	private function evaluateActualCode($code)
	{
		$output = $this->run($code, $this->isCoverageEnabled);

		if (isset($output['coverage'])) {
			$this->mergeCoverage($output['coverage']);
		}

		unset($output['coverage']);

		return $output;
	}

	private function run($sourceCode, $enableCodeCoverage)
	{
		$command = $this->executable .
			" --mode='test'" .
			" --code=" . escapeshellarg($sourceCode);

		if ($enableCodeCoverage) {
			$command .= " --coverage";
		}

		$this->shell->run($command, $stdout, $stderr, $exit);

		list($state, $script, $coverage) = @unserialize($stdout);

		if ($state === null) {
			$state = array(
				'variables' => array(),
				'globals' => array(),
				'constants' => array(),
				'output' => '',
				'calls' => array(),
				'exception' => null,
				'errors' => array(),
				'fatalError' => null
			);
		}

		$state['stderr'] = $stderr;
		$state['exit'] = $exit;

		unset($state['constants'][self::$testphpConstant]);

		return array(
			'state' => $state,
			'script' => $script,
			'coverage' => $coverage
		);
	}

	private function mergeCoverage(array $coverage)
	{
		$length = strlen($this->srcDirectory);

		foreach ($coverage as $absolutePath => $lines) {
			if (self::isEvaluatedCode($absolutePath)) {
				continue;
			}

			// Ignore files that are outside the source-code directory under scrutiny
			if (strncmp($absolutePath, $this->srcDirectory, $length) !== 0) {
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

		$contents = $this->filesystem->read($this->srcDirectory);
		$this->getDirectoryCode('', $contents);

		$files = array_keys($this->code);

		foreach ($files as $file) {
			if (!isset($this->status[$file])) {
				$absoluteFile = "{$this->srcDirectory}/{$file}";
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
		$command = $this->executable .
			" --mode='coverage'" .
			" --file=" . escapeshellarg($filePath);

		$this->shell->run($command, $stdout, $stderr, $exit);

		$results = json_decode($stdout, true);

		if (!is_array($results)) {
			// TODO: throw exception:
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
		$coreAutoloader = $this->getCoreAutoloader();
		$mocks = $this->getMockCode($preamble);

		return array(
			self::combine($namespace, $constant, $coreAutoloader, $mocks),
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
		if (!self::usesConstant($code, self::$testphpConstant)) {
			return null;
		}

		return self::getConstantDeclaration(self::$testphpConstant, $this->testphpDirectory);
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

	private function getMockCode($code)
	{
		if (!self::usesMock($code)) {
			return null;
		}

		return self::getMockAutoloader();
	}

	private static function usesMock($code)
	{
		return is_integer(strpos($code, 'use TestPhp\\Mock\\'));
	}

	private static function getCoreAutoloader()
	{
		return <<<'EOS'
spl_autoload_register(
	function ($class) {
		$namespacePrefix = 'TestPhp\\';
		$namespacePrefixLength = strlen($namespacePrefix);

		if (strncmp($class, $namespacePrefix, $namespacePrefixLength) !== 0) {
			return;
		}

		$relativeClassName = substr($class, $namespacePrefixLength);
		$filePath = dirname(__DIR__) . '/src/' . strtr($relativeClassName, '\\', '/') . '.php';

		if (is_file($filePath)) {
			include $filePath;
		}
	}
);
EOS;
	}

	private static function getMockAutoloader()
	{
		return <<<'EOS'
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
EOS;
	}
}
