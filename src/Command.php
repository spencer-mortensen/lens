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
	public function __construct()
	{
		$options = array();

		$parser = new OptionsParser($GLOBALS['argv']);

		/*
		// EXAMPLE:
		testphp --version  # get the current version of testphp
		*/

		$parser->getLongFlag($options);

		if (isset($options['version'])) {
			$this->getVersion();

			return;
		}

		/*
		// INTERNAL COMMANDS (not intended for end users):
		testphp --mode='test' --code='...'  # execute a test
		testphp --mode='test' --code='...' --coverage  # execute a test (with code coverage enabled)
		testphp --mode='test' --file='...  # get code coverage for source-code file
		*/

		while ($parser->getLongKeyValue($options));

		if (isset($options['mode'])) {
			if ($options['mode'] === 'test') {
				$this->getTest(@$options['code'], isset($options['coverage']));
			} else {
				$this->getCoverage(@$options['file']);
			}

			return;
		}

		/*
		// EXAMPLES:
		testphp  # run all tests (based on the current working directory)
		testphp tests/Archivist/ tests/Parser.php  # run just these tests
		*/

		$executable = $GLOBALS['argv'][0];
		$paths = array();

		while ($parser->getValue($paths));

		$this->getRunner($executable, $paths);
	}

	private function getTest($code, $enableCoverage)
	{
		$test = new Test($code, $enableCoverage);
		$test->run();
	}

	private function getCoverage($file)
	{
		// TODO: use the filesystem
		$filePath = realpath($file);

		// TODO: require a valid PHP source-code file
		$coverage = new Coverage();
		$coverage->run($filePath);
	}

	private function getVersion()
	{
		echo "testphp \n";
		exit(0);
	}

	private function getRunner($executable, array $paths)
	{
		$filesystem = new Filesystem();
		$parser = new Parser();
		$browser = new Browser($filesystem, $parser);
		$shell = new Shell();
		$evaluator = new Evaluator($filesystem, $shell);
		$console = new Console();
		$web = new Web($filesystem);

		$runner = new Runner($filesystem, $browser, $evaluator, $console, $web);
		$runner->run($executable, $paths);
	}
}
