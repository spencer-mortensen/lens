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
use Lens\LensException;
use SpencerMortensen\ParallelProcessor\ParallelProcessorException;

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

	public function run($srcDirectory, $autoloadPath, array $suites)
	{
		// TODO: run this only if coverage is enabled:
		try {
			$this->startCoverage($srcDirectory, $autoloadPath, $code, $executableLines);
			$this->startTests($srcDirectory, $autoloadPath, $suites, $executedLines);
			$this->processor->finish();
		} catch (ParallelProcessorException $exception) {
			throw LensException::processor($exception);
		}

		if (isset($executableLines, $executedLines)) {
			$coverage = self::getCoverage($executableLines, $executedLines);
		} else {
			$coverage = null;
		}

		return array($suites, $code, $coverage);
	}

	private function startCoverage($srcDirectory, $autoloadPath, array &$code = null, array &$coverage = null)
	{
		if ($srcDirectory === null) {
			return;
		}

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

	private function startTests($srcDirectory, $autoloadPath, array &$suites, array &$coverage = null)
	{
		$coverage = array();

		foreach ($suites as $file => &$suite) {
			foreach ($suite['tests'] as $testLine => &$test) {
				foreach ($test['cases'] as $caseLine => &$case) {
					$this->startTest(
						$srcDirectory,
						$autoloadPath,
						$suite['namespace'],
						$suite['uses'],
						$case['code']['fixture'],
						$test['actual'],
						$case['code']['expected'],
						$case['code']['script'],
						$case['results']['actual'],
						$case['results']['expected'],
						$coverage[]
					);
				}
			}
		}
	}

	private function startTest($srcDirectory, $autoloadPath, $namespace, array $uses, $fixturePhp, $actualPhp, $expectedPhp, array $script, &$actualResults, &$expectedResults, &$actualCoverage)
	{
		$actualJob = new TestJob(
			$this->executable,
			$srcDirectory,
			$autoloadPath,
			$namespace,
			$uses,
			$fixturePhp,
			$script,
			$actualPhp,
			$actualResults,
			$actualCoverage
		);

		$this->processor->start($actualJob);

		$expectedCoverage = null;

		$expectedJob = new TestJob(
			$this->executable,
			$srcDirectory,
			$autoloadPath,
			$namespace,
			$uses,
			$fixturePhp,
			null,
			$expectedPhp,
			$expectedResults,
			$expectedCoverage
		);

		$this->processor->start($expectedJob);
	}
}
