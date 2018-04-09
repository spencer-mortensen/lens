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

namespace Lens_0_0_56\Lens\Evaluator;

use Lens_0_0_56\Lens\Jobs\CacheJob;
use Lens_0_0_56\Lens\Jobs\CoverageJob;
use Lens_0_0_56\Lens\Jobs\TestJob;
use Lens_0_0_56\Lens\Filesystem;

class Evaluator
{
	/** @var string */
	private $executable;

	/** @var Filesystem */
	private $filesystem;

	/** @var Processor */
	private $processor;

	public function __construct($executable, $filesystem)
	{
		$this->executable = $executable;
		$this->filesystem = $filesystem;
		$this->processor = new Processor();
	}

	public function run($project, $src, $autoload, $cache, array $suites)
	{
		$this->updateCache($project, $src, $autoload, $cache);

		// TODO: run this only if coverage is enabled:
		$this->startCoverage($src, $autoload, $code, $executableLines);
		$this->startTests($src, $cache, $suites, $executedLines);
		$this->processor->finish();

		if (isset($executableLines, $executedLines)) {
			$coverage = self::getCoverage($executableLines, $executedLines);
		} else {
			$coverage = null;
		}

		return array($suites, $code, $coverage);
	}

	private function updateCache($project, $src, $autoload, $cache)
	{
		$job = new CacheJob($this->executable, $project, $src, $autoload, $cache);
		$process = $this->processor->getProcess($job);

		$this->processor->start($process);
		$this->processor->finish();
	}

	private function startCoverage($srcDirectory, $autoloadPath, array &$code = null, array &$coverage = null)
	{
		if ($srcDirectory === null) {
			return;
		}

		$relativePaths = $this->getRelativePaths($srcDirectory);

		$job = new CoverageJob($this->executable, $srcDirectory, $relativePaths, $autoloadPath, $process, $code, $coverage);

		$process = $this->processor->getProcess($job);

		$this->processor->start($process);
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

	private function startTests($src, $cache, array &$suites, array &$coverage = null)
	{
		$coverage = array();

		foreach ($suites as $file => &$suite) {
			foreach ($suite['tests'] as $testLine => &$test) {
				foreach ($test['cases'] as $caseLine => &$case) {
					$this->startTest(
						$src,
						$cache,
						$suite['namespace'],
						$suite['uses'],
						$case['input'],
						$test['code'],
						$case['output'],
						$case['script'],
						$case['results']['actual'],
						$case['results']['expected'],
						$coverage[]
					);
				}
			}
		}
	}

	private function startTest($src, $cache, $namespace, array $uses, $fixturePhp, $actualPhp, $expectedPhp, array $script = null, &$actualResults, &$expectedResults, &$actualCoverage)
	{
		$this->startTestJob(
			$src,
			$cache,
			$namespace,
			$uses,
			$fixturePhp,
			$script,
			$actualPhp,
			$actualResults,
			$actualCoverage
		);

		$this->startTestJob(
			$src,
			$cache,
			$namespace,
			$uses,
			$fixturePhp,
			null,
			$expectedPhp,
			$expectedResults
		);
	}

	private function startTestJob($src, $cache, $namespace, array $uses, $fixturePhp, array $script = null, $php, &$results, &$coverage = null)
	{
		$job = new TestJob(
			$this->executable,
			$src,
			$cache,
			$namespace,
			$uses,
			$fixturePhp,
			$script,
			$php,
			$this->processor,
			$process,
			$results,
			$coverage
		);

		$process = $this->processor->getProcess($job);

		$this->processor->start($process);
	}
}
