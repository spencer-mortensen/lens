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

class Runner
{
	/** @var Filesystem */
	private $filesystem;

	/** @var string */
	private $testExecutable;

	/** @var string */
	private $coverageExecutable;

	/** @var string */
	private $codeDirectory;

	/** @var array */
	private $tests;

	/** @var array */
	private $lines;

	/** @var array */
	private $coverage;

	public function __construct(Filesystem $filesystem, $testExecutable, $coverageExecutable)
	{
		$this->filesystem = $filesystem;
		$this->testExecutable = $testExecutable;
		$this->coverageExecutable = $coverageExecutable;
	}

	public function run($codeDirectory, $testsDirectory)
	{
		$this->codeDirectory = $codeDirectory;
		$this->tests = array();
		$this->lines = array();
		$this->coverage = array();

		$this->runTestDirectory($testsDirectory);

		$this->getCodeCoverage();

		return array($this->tests, $this->lines, $this->coverage);
	}

	private function runTestDirectory($directory)
	{
		$files = @scandir($directory, SCANDIR_SORT_NONE);

		if ($files === false) {
			throw Exception::invalidTestsDirectory($directory);
		}

		foreach ($files as $file) {
			if (($file === '.') || ($file === '..')) {
				continue;
			}

			$child = "{$directory}/{$file}";

			if (is_dir($child)) {
				$this->runTestDirectory($child);
			} elseif (is_file($child) && (substr($child, -4) === '.php')) {
				$this->runTestFile($child);
			}
		}
	}

	private function runTestFile($file)
	{
		$input = file_get_contents($file);

		if (!is_string($input)) {
			return;
		}

		$input = self::getStandardizedInput($input);
		$scenario = self::getScenario($input);
		$tests = self::getTests($input);

		foreach ($tests as $test) {
			list($cause, $effect) = $test;

			$expectedCode = self::getCode($scenario, $effect);
			$expectedResults = $this->evaluate($expectedCode);

			if (isset($expectedResults['state']['calls'])) {
				$calls = &$expectedResults['state']['calls'];

				if (0 < count($calls)) {
					$callsPhp = var_export($calls, true);
					$scenario .= "\n\n\\TestPhp\\Agent::setExpected({$callsPhp});";
				}
			}

			$actualCode = self::getCode($scenario, $cause);
			$actualResults = $this->evaluate($actualCode);

			$actualState = &$actualResults['state'];

			if (isset($actualState['coverage'])) {
				$this->mergeCoverage($actualState['coverage']);
				unset($actualState['coverage']);
			}

			unset($expectedResults['state']['coverage']);

			$this->tests[] = array(
				'file' => $file,
				'actual' => $actualResults,
				'expected' => $expectedResults,
			);
		}
	}

	private static function getStandardizedInput($input)
	{
		return preg_replace('~^\s*\<\?php\s+|\s*\?\>\s*$|\n$~D', '', $input);
	}

	private static function getScenario($input)
	{
		if (preg_match('~(.*?)\s*//\h*Actual\n~Ais', $input, $matches) !== 1) {
			return null;
		}

		$scenario = $matches[1];

		if (strlen($scenario) === 0) {
			return null;
		}

		return $scenario;
	}

	private static function getTests($input)
	{
		$pattern = '~(?:^|\n)//\h*Actual\n\s*(.*?)\s*\n//\h*Expected(?:\n\s*|$)(.*?)(?=\s*\n//\h*Actual\n|$)~Dis';

		preg_match_all($pattern, $input, $matches, PREG_SET_ORDER);

		$tests = array();

		foreach ($matches as $match) {
			list(, $cause, $effect) = $match;

			$tests[] = array($cause, $effect);
		}

		return $tests;
	}

	private static function getCode($scenario, $code)
	{
		if ($scenario === null) {
			return $code;
		}

		return "{$scenario}\n\n{$code}";
	}

	private function evaluate($sourceCode)
	{
		$argument = escapeshellarg($sourceCode);

		$command = "{$this->testExecutable} {$argument} 2>/dev/null";

		exec($command, $output, $exitCode);

		$stdout = implode("\n", $output);

		$state = json_decode($stdout, true);

		return array(
			'code' => $sourceCode,
			'state' => $state,
			'exit' => $exitCode
		);
	}

	private function mergeCoverage(array $coverage)
	{
		$length = strlen($this->codeDirectory);

		foreach ($coverage as $absolutePath => $lines) {
			if (self::isEvaluatedCode($absolutePath)) {
				continue;
			}

			// Ignore files that are outside the directory under scrutiny
			if (strncmp($absolutePath, $this->codeDirectory, $length) !== 0) {
				continue;
			}

			$relativePath = substr($absolutePath, $length + 1);
			$fileCoverage = &$this->coverage[$relativePath];

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
		$contents = $this->filesystem->read($this->codeDirectory);
		$this->getDirectoryLines('', $contents);

		$files = array_keys($this->lines);

		foreach ($files as $file) {
			if (!isset($this->coverage[$file])) {
				$this->coverage[$file] = $this->getMissingCoverage($file);
			}
		}

		self::cleanCoverage($this->coverage, $this->lines);
	}

	private function getDirectoryLines($path, $contents)
	{
		foreach ($contents as $childName => $childContents) {
			$childPath = self::mergePaths($path, $childName);

			if (is_array($childContents)) {
				$this->getDirectoryLines($childPath, $childContents);
			}

			if (is_string($childContents) && self::isPhpFile($childName)) {
				$this->getFileLines($childPath, $childContents);
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

	private function getFileLines($path, $contents)
	{
		$lines = explode("\n", "\n{$contents}");
		unset($lines[0]);

		$this->lines[$path] = $lines;
	}

	private static function isPhpFile($path)
	{
		return substr($path, -4) === '.php';
	}

	private function getMissingCoverage($relativeFilePath)
	{
		$absoluteFilePath = "{$this->codeDirectory}/{$relativeFilePath}";
		$argument = escapeshellarg($absoluteFilePath);
		$command = "{$this->coverageExecutable} {$argument} 2>/dev/null";

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

	public static function autoload($mock)
	{
		if (self::isMockClass($mock)) {
			$class = self::getClass($mock);
			Mock::define($mock, $class);
		}
	}

	private static function isMockClass($class)
	{
		return substr($class, 0, 13) === 'TestPhp\\Mock\\';
	}

	private static function getClass($mock)
	{
		return substr($mock, 13);
	}
}
