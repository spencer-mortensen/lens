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

class Command
{
	/** @var string */
	private $executable;

	public function __construct()
	{
		$this->executable = realpath($GLOBALS['argv'][0]);

		$options = getopt('', array('tests::', 'src::', 'mode::', 'code::', 'file::', 'coverage', 'version'));

		if (isset($options['mode'])) {
			if ($options['mode'] === 'test') {
				$this->getTest(@$options['code'], isset($options['coverage']));
			} else {
				$this->getCoverage(@$options['file']);
			}
		} elseif (isset($options['version'])) {
			$this->getVersion();
		} else {
			$this->getRunner(@$options['src'], @$options['tests']);
		}
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

	private function getVersion()
	{
		echo "testphp 0.0.8\n";
		exit(0);
	}

	private function getRunner($code, $tests)
	{
		$filesystem = new Filesystem();
		$parser = new Parser();
		$browser = new Browser($filesystem, $parser);
		$evaluator = new Evaluator($filesystem, $this->executable);
		$console = new Console();
		$web = new Web($filesystem);
		$runner = new Runner($browser, $evaluator, $console, $web);

		$currentDirectory = self::getCurrentDirectory();
		$testsDirectory = self::getTestsDirectory($tests, $currentDirectory);
		$codeDirectory = self::getCodeDirectory($code, $testsDirectory, $currentDirectory);

		$runner->run($codeDirectory, $testsDirectory);
	}

	private static function getCurrentDirectory()
	{
		return self::getString(getcwd());
	}

	private static function getTestsDirectory($testsDirectory, $currentDirectory)
	{
		if ($testsDirectory !== null) {
			return self::getString(realpath($testsDirectory));
		}

		if (substr($currentDirectory, -6) === '/tests') {
			return substr($currentDirectory, 0, -6);
		}

		return $currentDirectory;
	}

	private static function getCodeDirectory($codeDirectory, $testsDirectory, $currentDirectory)
	{
		if ($codeDirectory !== null) {
			return self::getString(realpath($codeDirectory));
		}

		$codeDirectory = $testsDirectory . '/src';

		if (is_dir($codeDirectory)) {
			return $codeDirectory;
		}

		$codeDirectory = dirname($testsDirectory) . '/src';

		if (is_dir($codeDirectory)) {
			return $codeDirectory;
		}

		return null;
	}

	private static function getString($value)
	{
		if (is_string($value)) {
			return $value;
		}

		return null;
	}
}
