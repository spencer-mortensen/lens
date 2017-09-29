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

use Lens\Evaluator\Evaluator;
use Lens\Evaluator\Processor;
use Lens\Evaluator\Jobs\CoverageJob;
use Lens\Evaluator\Jobs\TestJob;
use SpencerMortensen\ParallelProcessor\Shell\ShellSlave;

class Command
{
	/** @var string */
	private $executable;

	/** @var Logger */
	private $logger;

	public function __construct()
	{
		$this->logger = new Logger('lens');
		$this->executable = $GLOBALS['argv'][0];

		$options = array();

		$parser = new OptionsParser($GLOBALS['argv']);

		if ($parser->getLongKeyValue($options)) {
			list($key, $value) = each($options);

			$this->getWorker($key, $value);
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

		$paths = array();

		while ($parser->getValue($paths));

		$this->getRunner($paths);
	}

	private function getWorker($name, $value)
	{
		$decoded = base64_decode($value);
		$decompressed = gzinflate($decoded);
		$arguments = unserialize($decompressed);

		switch ($name) {
			case 'coverage':
				list($srcDirectory, $relativePaths, $bootstrapPath) = $arguments;
				$job = new CoverageJob($this->executable, $srcDirectory, $relativePaths, $bootstrapPath, $code, $coverage);
				break;

			case 'test':
				list($lensDirectory, $srcDirectory, $bootstrapPath, $contextPhp, $beforePhp, $afterPhp, $script) = $arguments;
				$job = new TestJob($this->executable, $lensDirectory, $srcDirectory, $bootstrapPath, $contextPhp, $beforePhp, $afterPhp, $script, $preState, $postState, $coverage);
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
		echo "lens 0.0.24\n";
		exit(0);
	}

	private function getRunner(array $paths)
	{
		$filesystem = new Filesystem();
		$settingsFile = new IniFile($filesystem);
		$settings = new Settings($settingsFile, $this->logger);
		$browser = new Browser($filesystem);
		$parser = new SuiteParser();
		$processor = new Processor();
		$evaluator = new Evaluator($this->executable, $filesystem, $processor);
		$console = new Console();
		$web = new Web($filesystem);

		$runner = new Runner($settings, $filesystem, $browser, $parser, $evaluator, $console, $web, $this->logger);
		$runner->run($paths);
	}
}
