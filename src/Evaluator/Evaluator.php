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

namespace Lens\Evaluator;

use Lens\Evaluator\Jobs\CoverageJob;
use Lens\Evaluator\Jobs\TestJob;
use Lens\Filesystem;

class Evaluator
{
	/** @var string */
	private $executable;

	/** @var Filesystem */
	private $filesystem;

	/** @var Processor */
	private $processor;

	public function __construct($executable, $filesystem, $processor)
	{
		$this->executable = $executable;
		$this->filesystem = $filesystem;
		$this->processor = $processor;
	}

	public function run($lensDirectory, $srcDirectory, $autoloadPath, array $suites)
	{
		// TODO: run this only if coverage is enabled:
		$this->startCoverage($srcDirectory, $autoloadPath, $code, $executableLines);
		$this->startTests($lensDirectory, $srcDirectory, $autoloadPath, $suites, $executedLines);
		$this->processor->finish();

		if (isset($executableLines, $executedLines)) {
			$coverage = self::getCoverage($executableLines, $executedLines);
		} else {
			$coverage = null;
		}

		return array($suites, $code, $coverage);
	}

	private function startCoverage($srcDirectory, $autoloadPath, array &$code = null, array &$coverage = null)
	{
		$relativePaths = $this->getRelativePaths($srcDirectory);

		$job = new CoverageJob(
			$this->executable,
			$srcDirectory,
			$relativePaths,
			$autoloadPath,
			$code,
			$coverage
		);

		$this->processor->start($job);
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

	private static function getCoverage(array $lines, array $results)
	{
		// TODO: run this only if coverage is enabled:
		$coverage = array();

		foreach ($lines as $path => $lineNumbers) {
			$coverage[$path] = array();

			foreach ($lineNumbers as $lineNumber) {
				$coverage[$path][$lineNumber] = false;
			}
		}

		$results = array_filter($results, 'is_array');

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

	private function startTests($lensDirectory, $srcDirectory, $autoloadPath, array &$suites, array &$coverage = null)
	{
		$coverage = array();

		foreach ($suites as $file => &$suite) {
			foreach ($suite['tests'] as $testLine => &$test) {
				foreach ($test['cases'] as $caseLine => &$case) {
					$this->startTest(
						$lensDirectory,
						$srcDirectory,
						$autoloadPath,
						$suite['fixture'],
						$case['input'],
						$case['output'],
						$test['subject'],
						$case['results'],
						$coverage[]
					);
				}
			}
		}
	}

	private function startTest($lensDirectory, $srcDirectory, $autoloadPath, $fixturePhp, $inputPhp, $outputPhp, $testPhp, &$results, &$coverage)
	{
		$code = new Code();
		$php = $code->getPhp($fixturePhp, $inputPhp, $outputPhp, $testPhp);
		list($contextPhp, $beforePhp, $expectedPhp, $actualPhp, $script) = $php;

		$actualJob = new TestJob(
			$this->executable,
			$lensDirectory,
			$srcDirectory,
			$autoloadPath,
			$contextPhp,
			$beforePhp,
			$actualPhp,
			$script,
			$results['fixture'],
			$results['actual'],
			$coverage
		);

		$this->processor->start($actualJob);

		$unused = null;

		$expectedJob = new TestJob(
			$this->executable,
			$lensDirectory,
			$srcDirectory,
			$autoloadPath,
			$contextPhp,
			$beforePhp,
			$expectedPhp,
			null,
			$unused,
			$results['expected']
		);

		$this->processor->start($expectedJob);
	}
}
