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

namespace TestPhp\Executable;

use TestPhp\Display\Console;
use TestPhp\Display\Web;
use TestPhp\Browser;
use TestPhp\Evaluator;
use TestPhp\Filesystem;

/*
# Normal usage:
testphp --tests="..." --src="..."

# Evaluate PHP code:
testphp --mode="test" --code="..." (--coverage)

# Get code coverage:
testphp --mode="coverage" --file="..."
*/
class Command
{
	const ERROR_RUNNER_NO_TESTS_DIRECTORY = 1;
	const ERROR_RUNNER_NO_CODE_DIRECTORY = 1;

	/** @var string */
	private $executable;

	public function __construct()
	{
		$this->executable = realpath($GLOBALS['argv'][0]);
		$options = getopt('', array('tests::', 'src::', 'mode::', 'code::', 'file::', 'coverage'));

		$mode = &$options['mode'];

		if ($mode === 'test') {
			$this->getTest(@$options['code'], isset($options['coverage']));
		} elseif ($mode === 'coverage') {
			$this->getCoverage(@$options['file']);
		} else {
			$this->getRunner(@$options['tests'], @$options['src']);
		}
	}

	private function getRunner($tests, $src)
	{
		list($testsDirectory, $codeDirectory, $coverageDirectory, $currentDirectory) = $this->getDirectories($tests, $src);

		$filesystem = new Filesystem();
		$browser = new Browser($filesystem);
		$tests = $browser->browse($testsDirectory);

		$evaluator = new Evaluator($filesystem, $this->executable);
		$results = $evaluator->evaluate($tests, $codeDirectory);

		$console = new Console();
		echo $console->summarize($testsDirectory, $currentDirectory, $results['tests']);

		$web = new Web($filesystem);
		$web->coverage($codeDirectory, $coverageDirectory, $results['coverage']);
	}

	private function getDirectories($tests, $src)
	{
		if (!isset($tests)) {
			self::errorRunnerNoTestsDirectory();
		}

		$testsDirectory = realpath($tests);
		// TODO: require a valid tests directory
		// TODO: see if the corresponding "coverage" directory can be safely overwritten

		if (!isset($src)) {
			self::errorRunnerNoCodeDirectory();
		}

		$codeDirectory = realpath($src);
		// TODO: require a valid code directory (must be a directory and contain at least one PHP file)

		$coverageDirectory = dirname($testsDirectory) . '/coverage';

		$currentDirectory = getcwd();

		if (!is_string($currentDirectory)) {
			$currentDirectory = '';
		}

		return array($testsDirectory, $codeDirectory, $coverageDirectory, $currentDirectory);
	}

	private function getTest($code, $enableCoverage)
	{
		$test = new Test($code, $enableCoverage);
		$test->run();
	}

	private function getCoverage($file)
	{
		$filePath = realpath($file);
		// TODO: require a valid PHP source-code file

		$coverage = new Coverage();
		$coverage->run($filePath);
	}

	private static function errorRunnerNoTestsDirectory()
	{
		// TODO: make the string "--tests='./tests'" bold
		$code = self::ERROR_RUNNER_NO_TESTS_DIRECTORY;
		$message = "Please provide the path to your tests directory\n" .
		" * Example: testphp --tests=./tests --src=./src";

		self::error($code, $message);
	}

	private static function errorRunnerNoCodeDirectory()
	{
		// TODO: make the string "--src='./src'" bold
		$code = self::ERROR_RUNNER_NO_CODE_DIRECTORY;
		$message = "Please provide the path to your code directory\n" .
		" * Example: testphp --tests='./tests' --src='./src'";

		self::error($code, $message);
	}

	private static function error($code, $message)
	{
		file_put_contents('php://stderr', "Error {$code}: {$message}\n");
		exit($code);
	}
}
