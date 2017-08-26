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

use Lens\Engine\Evaluator;

class Command
{
	/** @var Logger */
	private $logger;

	// TODO: check for the PHP 5.4 and pcntl dependencies, and add a note if a dependency is not met
	public function __construct()
	{
		$this->logger = new Logger('lens');

		$options = array();

		$parser = new OptionsParser($GLOBALS['argv']);

		/*
		// EXAMPLE:
		lens --version  # get the installed version of lens
		*/

		$parser->getLongFlag($options);

		if (isset($options['version'])) {
			$this->getVersion();

			return;
		}

		/*
		// INTERNAL COMMANDS (not intended for end users):
		lens --mode='test' --file='...  # get code coverage for source-code file
		*/

		while ($parser->getLongKeyValue($options));

		if (isset($options['mode'])) {
			$this->getCoverage(@$options['file']);
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
		$this->logger->info('lens 0.0.17');
		exit(0);
	}

	private function getRunner(array $paths)
	{
		$filesystem = new Filesystem();
		$parser = new Parser();
		$browser = new Browser($filesystem, $parser);
		$evaluator = new Evaluator();
		$console = new Console();
		$web = new Web($filesystem);

		$runner = new Runner($filesystem, $browser, $evaluator, $console, $web, $this->logger);
		$runner->run($paths);
	}
}
