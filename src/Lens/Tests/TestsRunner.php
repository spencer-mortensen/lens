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
use _Lens\Lens\LensException;
use _Lens\Lens\Php\Code;
use _Lens\Lens\Php\Namespacing;
use _Lens\Lens\Php\Semantics;
use _Lens\Lens\Processor;
use _Lens\Lens\Sanitizer;
use _Lens\Lens\SourcePaths;
use _Lens\Lens\Xdebug;
use _Lens\SpencerMortensen\Parser\ParserException;
use _Lens\SpencerMortensen\Filesystem\Filesystem;
use _Lens\SpencerMortensen\Filesystem\Path;

class TestsRunner
{
	/** @var string */
	private $executable;

	/** @var Path */
	private $core;

	/** @var Path|null */
	private $src;

	/** @var Path|null */
	private $cache;

	/** @var Path */
	private $tests;

	/** @var array */
	private $suites;

	/** @var array */
	private $results;

	/** @var array */
	private $missing;

	/** @var Coverage|null */
	private $coverage;

	/** @var Processor */
	private $processor;

	/** @var Filesystem */
	private $filesystem;

	public function __construct($executable, Path $core, Path $src = null, Path $cache = null, Path $tests)
	{
		$this->executable = $executable;
		$this->core = $core;
		$this->src = $src;
		$this->cache = $cache;
		$this->tests = $tests;
		$this->coverage = $this->getCoverageObject($cache);
		$this->processor = new Processor();
		$this->filesystem = new Filesystem();
	}

	private function getCoverageObject(Path $cache = null)
	{
		if ($cache === null) {
			return null;
		}

		return new Coverage($cache);
	}

	public function run(array $paths, array $mockClasses, array $mockFunctions)
	{
		$this->startAllTests($paths, $mockClasses, $mockFunctions);
		$this->startAllCoverage();

		$this->processor->finish();

		$this->stopAllTests();
		$this->stopAllCoverage();
	}

	private function startAllTests(array $paths, array $mockClasses, array $mockFunctions)
	{
		$this->suites = [];
		$this->results = [];

		// TODO: get suites from cache (watching for changes to the original tests files)
		$this->suites = $this->getSuitesFromPaths($paths);

		$this->startTests($this->suites, $mockClasses, $mockFunctions);
	}

	private function getSuitesFromPaths(array $paths)
	{
		$browser = new Browser($this->filesystem);
		$files = $browser->browse($paths);

		$suites = [];
		$parser = new SuiteParser();

		foreach ($files as $absolutePath => $contents) {
			$relativePath = $this->tests->getRelativePath($absolutePath);

			try {
				$suites[(string)$relativePath] = $parser->parse($contents);
			} catch (ParserException $exception) {
				throw LensException::invalidTestsFileSyntax(Path::fromString($absolutePath), $contents, $exception);
			}
		}

		return $suites;
	}

	private function startTests(array &$suites, array $mockClasses, array $mockFunctions, array &$coverage = null)
	{
		$isFunction = [$this, 'isFunction'];
		$sanitizer = new Sanitizer($isFunction, $mockFunctions);

		$coverage = [];

		foreach ($suites as $file => &$suite) {
			foreach ($suite['tests'] as $testLine => &$test) {
				$namespace = $suite['namespace'];
				$uses = $suite['uses'];
				$actualPhp = $sanitizer->sanitize('code', $namespace, $uses, $test['code']);
				$contextPhp = Code::getContextPhp($namespace, $uses);

				foreach ($test['cases'] as $caseLine => &$case) {
					$causePhp = $sanitizer->sanitize('code', $namespace, $uses, $case['cause']);
					$effectPhp = $sanitizer->sanitize('code', $namespace, $uses, $case['effect']);

					$results = &$this->results[$file][$testLine][$caseLine];

					$this->startTest(
						$contextPhp,
						$causePhp,
						$actualPhp,
						$effectPhp,
						$case['script'],
						$mockClasses,
						$results['actual']['pre'],
						$results['actual']['post'],
						$results['expected']['pre'],
						$results['expected']['post'],
						$results['coverage']
					);
				}
			}
		}
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

		$sourcePaths = new SourcePaths($this->core, $this->cache);

		$absolutePath = $sourcePaths->getLiveFunctionPath($function);
		return $this->filesystem->isFile($absolutePath);
	}

	private function startTest($contextPhp, $fixturePhp, $actualPhp, $expectedPhp, array $script, array $mockClasses, array &$actualPreState = null, array &$actualPostState = null, array &$expectedPreState = null, array &$expectedPostState = null, array &$actualCoverage = null)
	{
		$this->startTestJob($contextPhp, $fixturePhp, $actualPhp, $script, $mockClasses, true, $actualPreState, $actualPostState, $actualCoverage);
		$this->startTestJob($contextPhp, $fixturePhp, $expectedPhp, $script, $mockClasses, false, $expectedPreState, $expectedPostState, $expectedCoverage);
	}

	private function startTestJob($contextPhp, $fixturePhp, $php, array $script, array $mockClasses, $isActual, array &$preState = null, array &$postState = null, array &$coverage = null)
	{
		$job = new TestJob($this->executable, $this->core, $this->cache, $contextPhp, $fixturePhp, $php, $script, $mockClasses, $isActual, $process, $preState, $postState, $coverage);
		$process = $this->processor->getProcess($job);
		$this->processor->start($process);
	}

	private function startAllCoverage()
	{
		if (($this->cache === null) || !Xdebug::isEnabled()) {
			return;
		}

		$sourcePaths = new SourcePaths($this->core, $this->cache);

		$this->missing = [
			'classes' => [],
			'functions' => [],
			'traits' => []
		];

		$data = $this->coverage->getCoverage();

		foreach ($data['classes'] as $class => $coverage) {
			if ($coverage === null) {
				$path = $sourcePaths->getLiveClassPath($class);
				$job = new CoverageJob($this->executable, $this->core, $this->cache, $path, $this->missing['classes'][$class]);
				$this->processor->run($job);
			}
		}

		foreach ($data['functions'] as $function => $coverage) {
			if ($coverage === null) {
				$path = $sourcePaths->getLiveFunctionPath($function);
				$job = new CoverageJob($this->executable, $this->core, $this->cache, $path, $this->missing['functions'][$function]);
				$this->processor->run($job);
			}
		}

		foreach ($data['traits'] as $trait => $coverage) {
			if ($coverage === null) {
				$path = $sourcePaths->getLiveTraitPath($trait);
				$job = new CoverageJob($this->executable, $this->core, $this->cache, $path, $this->missing['traits'][$trait]);
				$this->processor->run($job);
			}
		}
	}

	private function stopAllTests()
	{
		$isFunction = [$this, 'isFunction'];

		foreach ($this->results as $file => &$suiteResults) {
			$suiteTests = &$this->suites[$file];
			$namespace = $suiteTests['namespace'];
			$uses = $suiteTests['uses'];

			$namespacing = new Namespacing($isFunction, $namespace, $uses);
			$comparer = new Comparer();
			$analyzer = new Analyzer($namespacing, $comparer);

			foreach ($suiteResults as $testLine => &$testResults) {
				$testTests = &$suiteTests['tests'][$testLine];

				foreach ($testResults as $caseLine => &$caseResults) {
					$case = &$testTests['cases'][$caseLine];

					$case['issues'] = $analyzer->analyze(
						$case['script'],
						$caseResults['expected']['post'],
						$caseResults['actual']['pre'],
						$caseResults['actual']['post']
					);

					$case['coverage'] = $caseResults['coverage'];
				}
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

	public function getResults()
	{
		// TODO: let the user provide the project name in the configuration file
		return [
			'name' => 'Lens',
			'suites' => $this->suites
		];
	}

	public function getCoverage()
	{
		if ($this->coverage === null) {
			return null;
		}

		return $this->coverage->getCoverage();
	}
}
