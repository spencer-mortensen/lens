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
	private $codeDirectory;

	/** @var array */
	private $code;

	/** @var array */
	private $status;

	/** @var string */
	private $effectAutoloader;

	/** @var string */
	private $causeAutoloader;

	public function __construct(Filesystem $filesystem, $executable)
	{
		$this->filesystem = $filesystem;
		$this->executable = $executable;
	}

	public function evaluate(array $tests, $codeDirectory)
	{
		$this->codeDirectory = $codeDirectory;
		$this->code = array();
		$this->status = array();
		$this->causeAutoloader = self::getCauseAutoloader();
		$this->effectAutoloader = self::getEffectAutoloader();

		foreach ($tests as &$suite) {
			foreach ($suite['cases'] as &$case) {
				$this->evaluateCase($suite['fixture'], $case);
			}
		}

		$coverage = $this->getCodeCoverage();

		return array(
			'tests' => $tests,
			'coverage' => $coverage
		);
	}

	private function evaluateCase($fixture, array &$case)
	{
		$effectCode = self::getCode($fixture, $this->effectAutoloader, $case['effect']['code']);
		$case['effect'] = array_merge($case['effect'], $this->evaluateEffect($effectCode, $mockPhp));

		$causeCode = self::getCode($fixture, $this->causeAutoloader, $mockPhp, $case['cause']['code']);
		$case['cause'] = array_merge($case['cause'], $this->evaluateCause($causeCode));
	}

	private static function getCode()
	{
		return implode("\n\n", array_filter(func_get_args(), 'is_string'));
	}

	private function evaluateEffect($code, &$mockPhp)
	{
		$output = $this->run($code, false);

		if (isset($output['results'])) {
			$calls = $output['results']['calls'];

			if (0 < count($calls)) {
				$callsPhp = var_export($calls, true);
				$mockPhp = "\\TestPhp\\Agent::setExpected({$callsPhp});";
			}
		}

		return $output;
	}

	private function evaluateCause($code)
	{
		$output = $this->run($code, true);

		if (isset($output['coverage'])) {
			$this->mergeCoverage($output['coverage']);
		}

		unset($output['coverage']);

		return $output;
	}

	private function run($sourceCode, $enableCodeCoverage)
	{
		$arguments = "--mode='test' --code=" . escapeshellarg($sourceCode);

		if ($enableCodeCoverage) {
			$arguments .= " --coverage";
		}

		$command = "{$this->executable} {$arguments} 2>/dev/null";

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
		if (!function_exists('xdebug_get_code_coverage')) {
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

	private static function getCauseAutoloader()
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

		\TestPhp\Agent::recall($callable, $arguments);
	}

	public function __call($name, $arguments)
	{
		$callable = array($this, $name);

		return \TestPhp\Agent::recall($callable, $arguments);
	}
}
EOS;

		$code = sprintf($mockTemplate, $namespace, $class);

		eval($code);
	}
);
EOT;
	}

	private static function getEffectAutoloader()
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
		list($arguments, $result) = $input;

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
