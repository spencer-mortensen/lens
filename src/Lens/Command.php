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

use Lens\Evaluator\Engines\Fork;
use Lens\Evaluator\Engines\Shell;
use Lens\Evaluator\Engines\Shell\Workers\ActualWorker;
use Lens\Evaluator\Engines\Shell\Workers\CoverageWorker;
use Lens\Evaluator\Engines\Shell\Workers\ExpectedWorker;
use Lens\Evaluator\Evaluator;
use Lens\Evaluator\Processor;
use Lens\Evaluator\Jobs\ActualJob;
use Lens\Evaluator\Jobs\CoverageJob;
use Lens\Evaluator\Jobs\ExpectedJob;
use SpencerMortensen\ParallelProcessor\Shell\ShellSlave;

class Command
{
	/** @var string */
	private $executable;

	public function __construct()
	{
		$this->executable = $GLOBALS['argv'][0];

		$options = array();

		$parser = new OptionsParser($GLOBALS['argv']);

		if ($parser->getLongKeyValue($options)) {
			list($key, $value) = each($options);
			$decoded = base64_decode($value);
			$decompressed = gzinflate($decoded);
			$arguments = unserialize($decompressed);

			$this->getWorker($key, $arguments);
			exit;
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

		$this->getRunner($paths);
	}

	private function getWorker($name, array $arguments)
	{
		switch ($name) {
			case 'actual':
				list($lensDirectory, $srcDirectory, $fixture, $input, $output, $subject) = $arguments;
				$job = new ActualJob($this->executable, $lensDirectory, $srcDirectory, $fixture, $input, $output, $subject, $results, $coverage);
				break;

			case 'coverage':
				list($srcDirectory, $relativePaths) = $arguments;
				$job = new CoverageJob($this->executable, $srcDirectory, $relativePaths, $code, $coverage);
				break;

			case 'expected':
				list($lensDirectory, $fixture, $input, $output) = $arguments;
				$job = new ExpectedJob($this->executable, $lensDirectory, $fixture, $input, $output, $preState, $postState, $script);
				break;

			default:
				// TODO: error
				return null;
		}

		$slave = new ShellSlave($job);
		$slave->run();
	}

	private function getVersion()
	{
		echo "lens 0.0.20\n";
		exit(0);
	}

	private function getRunner(array $paths)
	{
		$filesystem = new Filesystem();
		$parser = new Parser();
		$browser = new Browser($filesystem, $parser);
		$processor = new Processor();
		$evaluator = new Evaluator($this->executable, $filesystem, $processor);
		$console = new Console();
		$web = new Web($filesystem);

		$runner = new Runner($filesystem, $browser, $evaluator, $console, $web);
		$runner->run($paths);
	}
}
