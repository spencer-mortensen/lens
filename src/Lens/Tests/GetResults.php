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

namespace _Lens\Lens\Tests;

use _Lens\Lens\Archivist\Comparer;
use _Lens\Lens\Coverage;
use _Lens\Lens\Jobs\CoverageJob;
use _Lens\Lens\Jobs\TestJob;
use _Lens\Lens\Php\Namespacing;
use _Lens\Lens\Php\Semantics;
use _Lens\Lens\Processor;
use _Lens\Lens\Sanitizer;
use _Lens\Lens\SourcePaths;
use _Lens\Lens\Xdebug;
use _Lens\SpencerMortensen\Filesystem\Filesystem;
use _Lens\SpencerMortensen\Filesystem\Path;

class GetResults
{
	/** @var string */
	private $executable;

	/** @var Path */
	private $core;

	/** @var Path|null */
	private $cache;

	/** @var Processor */
	private $processor;

	/** @var Filesystem */
	private $filesystem;

	/** @var SourcePaths */
	private $sourcePaths;

	/** @var Namespacing */
	private $namespacing;

	/** @var Coverage|null */
	private $coverage;

	/** @var array */
	private $project;

	/** @var array */
	private $results;

	/** @var array */
	private $missing;

	public function __construct($executable, Path $core, Path $cache = null)
	{
		$this->executable = $executable;
		$this->core = $core;
		$this->cache = $cache;
		$this->processor = new Processor();
		$this->filesystem = new Filesystem();
		$this->sourcePaths = new SourcePaths($core, $cache);
		$this->namespacing = new Namespacing([$this, 'isfunction']);
		$this->coverage = $this->getCoverageObject($cache);
	}

	private function getCoverageObject(Path $cache = null)
	{
		if ($cache === null) {
			return null;
		}

		return new Coverage($cache);
	}

	public function getResults(array $project, array $mockClasses, array $mockFunctions)
	{
		$this->project = $project;

		$this->startAllTests($mockClasses, $mockFunctions);
		$this->startAllCoverage();

		$this->processor->finish();

		$this->stopAllCoverage();

		return $this->getSummary();
	}

	private function startAllTests(array $mockClasses, array $mockFunctions)
	{
		$this->results = [];

		$getTest = new GetTest($this->namespacing, $mockFunctions);

		foreach ($this->project['suites'] as $file => &$suite) {
			$getTest->setContext($suite['namespace'], $suite['uses']);

			foreach ($suite['tests'] as $testLine => &$test) {
				$getTest->setTest($test['test']);

				foreach ($test['cases'] as $caseLine => &$case) {
					$getTest->setCase($case['cause'], $case['effect']);

					$results = &$this->results[$file][$testLine][$caseLine];
					$this->startTest($getTest, $mockClasses, $results['script'], $results['actual']['pre'], $results['actual']['post'], $results['expected']['pre'], $results['expected']['post'], $results['coverage']);
				}
			}
		}
	}

	private function startTest(GetTest $getTest, array $mockClasses, array &$script = null, array &$actualPreState = null, array &$actualPostState = null, array &$expectedPreState = null, array &$expectedPostState = null, array &$actualCoverage = null)
	{
		$contextPhp = $getTest->getContextPhp();
		$causePhp = $getTest->getCausePhp();
		$testPhp = $getTest->getTestPhp();
		$effectPhp = $getTest->getEffectPhp();
		$script = $getTest->getScript();

		echo "contextPhp: ", json_encode($contextPhp), "\n";
		echo "testPhp: ", json_encode($testPhp), "\n";
		echo "causePhp: ", json_encode($causePhp), "\n";
		echo "effectPhp: ", json_encode($effectPhp), "\n";
		echo "script: ", json_encode($script), "\n";

		exit;

		$this->startTestJob($contextPhp, $causePhp, $testPhp, $script, $mockClasses, true, $actualPreState, $actualPostState, $actualCoverage);
		$this->startTestJob($contextPhp, $causePhp, $effectPhp, $script, $mockClasses, false, $expectedPreState, $expectedPostState, $expectedCoverage);
	}

	private function startTestJob($contextPhp, $prePhp, $postPhp, array $script, array $mockClasses, $isActual, array &$preState = null, array &$postState = null, array &$coverage = null)
	{
		$job = new TestJob($this->executable, $this->core, $this->cache, $contextPhp, $prePhp, $postPhp, $script, $mockClasses, $isActual, $process, $preState, $postState, $coverage);
		$process = $this->processor->getProcess($job);
		$this->processor->start($process);
	}

	private function startAllCoverage()
	{
		if (($this->cache === null) || !Xdebug::isEnabled()) {
			return;
		}

		$this->missing = [
			'classes' => [],
			'functions' => [],
			'traits' => []
		];

		$data = $this->coverage->getCoverage();

		foreach ($data['classes'] as $class => $coverage) {
			if ($coverage === null) {
				$path = $this->sourcePaths->getLiveClassPath($class);
				$job = new CoverageJob($this->executable, $this->core, $this->cache, $path, $this->missing['classes'][$class]);
				$this->processor->run($job);
			}
		}

		foreach ($data['functions'] as $function => $coverage) {
			if ($coverage === null) {
				$path = $this->sourcePaths->getLiveFunctionPath($function);
				$job = new CoverageJob($this->executable, $this->core, $this->cache, $path, $this->missing['functions'][$function]);
				$this->processor->run($job);
			}
		}

		foreach ($data['traits'] as $trait => $coverage) {
			if ($coverage === null) {
				$path = $this->sourcePaths->getLiveTraitPath($trait);
				$job = new CoverageJob($this->executable, $this->core, $this->cache, $path, $this->missing['traits'][$trait]);
				$this->processor->run($job);
			}
		}
	}

	private function stopAllCoverage()
	{
		if ($this->missing === null) {
			return;
		}

		foreach ($this->missing['classes'] as $class => $classCoverage) {
			$this->coverage->setClass($class, $classCoverage);
		}

		foreach ($this->missing['functions'] as $function => $functionCoverage) {
			$this->coverage->setFunction($function, $functionCoverage);
		}

		foreach ($this->missing['traits'] as $trait => $traitCoverage) {
			$this->coverage->setTrait($trait, $traitCoverage);
		}
	}

	public function getCoverage()
	{
		if ($this->coverage === null) {
			return null;
		}

		return $this->coverage->getCoverage();
	}

	public function getSummary()
	{
		$summary = [];

		foreach ($this->results as $file => $resultsSuite) {
			$testsSuite = $this->project['suites'][$file];
			// TODO: the $uses format has changed (we now resolve "functions" and "classes" separately):
			$this->namespacing->setContext($testsSuite['namespace'], $testsSuite['uses']);

			$comparer = new Comparer();
			$analyzer = new Analyzer($this->namespacing, $comparer);

			foreach ($resultsSuite as $testLine => $resultsTest) {
				foreach ($resultsTest as $caseLine => $resultsCase) {
					$summaryCase = &$summary[$file][$testLine][$caseLine];

					$summaryCase['issues'] = $analyzer->analyze(
						$summaryCase['script'],
						$resultsCase['expected']['post'],
						$resultsCase['actual']['pre'],
						$resultsCase['actual']['post']
					);

					$summaryCase['coverage'] = $resultsCase['coverage'];
				}
			}
		}

		return $summary;
	}

	public function isFunction($function)
	{
		return Semantics::isPhpFunction($function) || $this->isUserFunction($function);
	}

	private function isUserFunction($function)
	{
		if ($this->cache === null) {
			return false;
		}

		$absolutePath = $this->sourcePaths->getLiveFunctionPath($function);
		return $this->filesystem->isFile($absolutePath);
	}
}
