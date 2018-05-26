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

use Lens_0_0_56\Lens\Archivist\Comparer;
use Lens_0_0_56\Lens\Browser;
use Lens_0_0_56\Lens\Filesystem;
use Lens_0_0_56\Lens\Jobs\TestJob;
use Lens_0_0_56\Lens\LensException;
use Lens_0_0_56\Lens\Php\Code;
use Lens_0_0_56\Lens\Php\Namespacing;
use Lens_0_0_56\Lens\Php\SuiteParser;
use Lens_0_0_56\SpencerMortensen\Parser\ParserException;
use Lens_0_0_56\SpencerMortensen\Paths\Paths;

class TestsBuilder
{
	/** @var string */
	private $executable;

	/** @var string */
	private $lensCore;

	/** @var string */
	private $src;

	/** @var string */
	private $tests;

	/** @var string */
	private $cache;

	/** @var callable */
	private $isFunction;

	/** @var array */
	private $suites;

	/** @var array */
	private $results;

	/** @var Processor */
	private $processor;

	/** @var Paths */
	private $paths;

	/** @var Filesystem */
	private $filesystem;

	public function __construct($executable, $lensCore, $src, $tests, $cache, $isFunction, Processor $processor, Paths $paths, Filesystem $filesystem)
	{
		$this->executable = $executable;
		$this->lensCore = $lensCore;
		$this->src = $src;
		$this->tests = $tests;
		$this->cache = $cache;
		$this->isFunction = $isFunction;
		$this->suites = array();
		$this->results = array();
		$this->processor = $processor;
		$this->paths = $paths;
		$this->filesystem = $filesystem;
	}

	public function start(array $paths, array $mockClasses, $mockFunctions)
	{
		// TODO: get suites from cache (watching for changes to the original tests files)
		$this->suites = $this->getSuitesFromPaths($paths);

		$this->startTests($this->suites, $mockClasses, $mockFunctions);
	}

	private function getSuitesFromPaths(array $paths)
	{
		$browser = new Browser($this->filesystem, $this->paths);
		$files = $browser->browse($paths);

		$suites = array();
		$parser = new SuiteParser();

		foreach ($files as $absolutePath => $contents) {
			$relativePath = $this->paths->getRelativePath($this->tests, $absolutePath);

			try {
				$suites[$relativePath] = $parser->parse($contents);
			} catch (ParserException $exception) {
				throw LensException::invalidTestsFileSyntax($absolutePath, $contents, $exception);
			}
		}

		return $suites;
	}

	private function startTests(array &$suites, array $mockClasses, array $mockFunctions, array &$coverage = null)
	{
		$sanitizer = new Sanitizer($this->isFunction, $mockFunctions);

		$coverage = array();

		foreach ($suites as $file => &$suite) {
			foreach ($suite['tests'] as $testLine => &$test) {
				$namespace = $suite['namespace'];
				$uses = $suite['uses'];
				$actualPhp = $sanitizer->sanitize('code', $namespace, $uses, $test['code']);
				$contextPhp = Code::getContextPhp($namespace, $uses);

				foreach ($test['cases'] as $caseLine => &$case) {
					$inputPhp = $sanitizer->sanitize('code', $namespace, $uses, $case['input']);
					$expectedPhp = $sanitizer->sanitize('code', $namespace, $uses, $case['output']);

					$results = &$this->results[$file][$testLine][$caseLine];

					$this->startTest(
						$contextPhp,
						$inputPhp,
						$actualPhp,
						$expectedPhp,
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

	private function startTest($contextPhp, $fixturePhp, $actualPhp, $expectedPhp, array $script, array $mockClasses, array &$actualPreState = null, array &$actualPostState = null, array &$expectedPreState = null, array &$expectedPostState = null, array &$actualCoverage = null)
	{
		$this->startTestJob($contextPhp, $fixturePhp, $actualPhp, $script, $mockClasses, true, $actualPreState, $actualPostState, $actualCoverage);
		$this->startTestJob($contextPhp, $fixturePhp, $expectedPhp, $script, $mockClasses, false, $expectedPreState, $expectedPostState, $expectedCoverage);
	}

	private function startTestJob($contextPhp, $fixturePhp, $php, array $script, array $mockClasses, $isActual, array &$preState = null, array &$postState = null, array &$coverage = null)
	{
		$job = new TestJob($this->executable, $this->lensCore, $this->src, $this->cache, $contextPhp, $fixturePhp, $php, $script, $mockClasses, $isActual, $process, $preState, $postState, $coverage);
		$process = $this->processor->getProcess($job);
		$this->processor->start($process);
	}

	public function stop()
	{
		foreach ($this->results as $file => &$suiteResults) {
			$suiteTests = &$this->suites[$file];
			$namespace = $suiteTests['namespace'];
			$uses = $suiteTests['uses'];

			$namespacing = new Namespacing($this->isFunction, $namespace, $uses);
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

					// TODO: filter extraneous coverage information, using the master coverage file
					$case['coverage'] = null;
				}
			}
		}
	}

	public function getSuites()
	{
		return $this->suites;
	}
}
