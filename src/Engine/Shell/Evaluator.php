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

namespace Lens\Engine\Shell;

use Lens\Engine\Shell\Jobs\ActualJob;
use Lens\Engine\Shell\Jobs\CoverageJob;
use Lens\Filesystem;

class Evaluator implements \Lens\Evaluator
{
	/** @var string */
	private $executable;

	/** @var Filesystem */
	private $filesystem;

	/** @var Processor */
	private $processor;

	public function __construct($executable)
	{
		$this->executable = $executable;

		// TODO: dependency injection
		$this->filesystem = new Filesystem();
		$this->processor = new Processor();
	}

	public function run($lensDirectory, $srcDirectory, array $suites)
	{
		$relativePaths = $this->getRelativePaths($srcDirectory);

		$this->startCoverage($srcDirectory, $relativePaths, $code, $executableLines);
		$this->startTests($lensDirectory, $srcDirectory, $suites, $executedLines);
		$this->processor->halt();

		if (isset($executableLines, $executedLines)) {
			$coverage = self::getCoverage($executableLines, $executedLines);
		} else {
			$coverage = null;
		}

		return array($suites, $code, $coverage);
	}

	private function getRelativePaths($srcDirectory)
	{
		// TODO: sample only those files that have changed since the last sampling
		$paths = $this->filesystem->listFiles($srcDirectory);

		foreach ($paths as $key => $path) {
			if (substr($path, -4) !== '.php') {
				unset($paths[$key]);
			}
		}

		return array_values($paths);
	}

	private function startCoverage($srcDirectory, $relativePaths, &$code, &$executableLines)
	{
		// TODO: run this only if coverage is enabled
		$job = new CoverageJob(
			$this->executable,
			$srcDirectory,
			$relativePaths,
			$code,
			$executableLines
		);

		$this->processor->start($job);
	}

	private function startTests($lensDirectory, $srcDirectory, array &$suites, array &$coverage = null)
	{
		$coverage = array();

		foreach ($suites as $file => &$suite) {
			foreach ($suite['tests'] as $testLine => &$test) {
				foreach ($test['cases'] as $caseLine => &$case) {
					$job = new ActualJob(
						$this->executable,
						$lensDirectory,
						$srcDirectory,
						$suite['fixture'],
						$case['input'],
						$case['output'],
						$test['subject'],
						$case['results'],
						$coverage[]
					);

					$this->processor->start($job);
				}
			}
		}
	}

	private static function getCoverage(array $lines, array $results)
	{
		// TODO: run this only if coverage is enabled:
		$coverage = array();

		foreach ($lines as $path => $lineNumbers) {
			foreach ($lineNumbers as $lineNumber) {
				$coverage[$path][$lineNumber] = false;
			}
		}

		foreach ($results as $result) {
			foreach ($result as $path => $fileCoverage) {
				foreach ($fileCoverage as $lineNumber) {
					if (isset($coverage[$path][$lineNumber])) {
						$coverage[$path][$lineNumber] = true;
					}
				}
			}
		}

		return $coverage;
	}
}
