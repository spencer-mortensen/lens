<?php

/**
 * Copyright (C) 2017 Spencer Mortensen
 *
 * This file is part of Lens.
 *
 * Lens is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Lens is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Lens. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Spencer Mortensen <spencer@lens.guide>
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL-3.0
 * @copyright 2017 Spencer Mortensen
 */

namespace Lens;

use Lens\Engine\Coverage;
use Lens\Engine\Shell\Evaluator;
use Lens\Engine\Shell\TestExpected;
use Lens\Engine\Shell\TestActual;

class Command
{
	public function __construct()
	{
		$options = array();

		$parser = new OptionsParser($GLOBALS['argv']);

		if ($parser->getLongKeyValue($options)) {
			list($key, $value) = each($options);

			$decoded = base64_decode($value);
			$decompressed = gzinflate($decoded);
			$arguments = unserialize($decompressed);

			switch ($key) {
				case 'actual':
					$this->getActual($arguments);
					break;

				case 'expected':
					$this->getExpected($arguments);
					break;

				case 'coverage':
					$this->getCoverage($arguments);
					break;

				default:
					// TODO: error
			}

			return;
		}

		if ($parser->getLongFlag($options)) {
			// lens --version  # get the installed version of Lens
			if (isset($options['version'])) {
				$this->getVersion();
			} else {
				// TODO: error
			}

			return;
		}

		/*
		// EXAMPLES:
		lens  # run all tests (based on the current working directory)
		lens tests/Archivist/ tests/Parser.php  # run just these tests
		*/

		$paths = array();

		while ($parser->getValue($paths));

		$executable = $GLOBALS['argv'][0];
		$this->getRunner($executable, $paths);
	}

	private function getActual(array $arguments)
	{
		$executable = $GLOBALS['argv'][0];
		list($lensDirectory, $srcDirectory, $fixture, $input, $output, $subject) = $arguments;

		$test = new TestActual($executable, $lensDirectory, $srcDirectory);
		$test->run($fixture, $input, $output, $subject);
	}

	private function getExpected(array $arguments)
	{
		list($lensDirectory, $fixture, $input, $output) = $arguments;

		$test = new TestExpected($lensDirectory);
		$test->run($fixture, $input, $output);
	}

	private function getCoverage(array $arguments)
	{
		list($srcDirectory, $relativePaths) = $arguments;

		$coverage = new Coverage();
		$coverage->run($srcDirectory, $relativePaths);
	}

	private function getVersion()
	{
		echo "lens 0.0.17\n";
		exit(0);
	}

	private function getRunner($executable, array $paths)
	{
		$filesystem = new Filesystem();
		$parser = new Parser();
		$browser = new Browser($filesystem, $parser);
		$evaluator = new Evaluator($executable);
		$console = new Console();
		$web = new Web($filesystem);

		$runner = new Runner($filesystem, $browser, $evaluator, $console, $web);
		$runner->run($paths);
	}
}
