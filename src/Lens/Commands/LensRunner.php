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

namespace Lens_0_0_56\Lens\Commands;

use Lens_0_0_56\Lens\Arguments;
use Lens_0_0_56\Lens\CaseText;
use Lens_0_0_56\Lens\Environment;
use Lens_0_0_56\Lens\Evaluator\CoverageBuilder;
use Lens_0_0_56\Lens\Evaluator\Processor;
use Lens_0_0_56\Lens\Evaluator\TestsBuilder;
use Lens_0_0_56\Lens\Filesystem;
use Lens_0_0_56\Lens\Finder;
use Lens_0_0_56\Lens\Jobs\CacheJob;
use Lens_0_0_56\Lens\LensException;
use Lens_0_0_56\Lens\Php\Semantics;
use Lens_0_0_56\Lens\Reports\TapReport;
use Lens_0_0_56\Lens\Reports\TextReport;
use Lens_0_0_56\Lens\Reports\XUnitReport;
use Lens_0_0_56\Lens\Settings;
use Lens_0_0_56\Lens\Url;
use Lens_0_0_56\Lens\Web;
use Lens_0_0_56\SpencerMortensen\Paths\Paths;
use Lens_0_0_56\SpencerMortensen\RegularExpressions\Re;

class LensRunner implements Command
{
	/** @var Arguments */
	private $arguments;

	/** @var Paths */
	private $paths;

	/** @var Filesystem */
	private $filesystem;

	/** @var Finder */
	private $finder;

	public function __construct(Arguments $arguments)
	{
		$this->arguments = $arguments;
		$this->paths = Paths::getPlatformPaths();
		$this->filesystem = new Filesystem();
		$this->finder = new Finder($this->paths, $this->filesystem);
	}

	public function run(&$stdout = null, &$stderr = null, &$exitCode = null)
	{
		$options = $this->arguments->getOptions();
		$paths = $this->arguments->getValues();
		$reportType = $this->getReportType($options);

		// TODO: if there are any options other than "report", then throw a usage exception

		$this->finder->find($paths);

		$executable = $this->arguments->getExecutable();
		$project = $this->finder->getProject();
		$src = $this->finder->getSrc();
		$cache = $this->finder->getCache();
		$tests = $this->finder->getTests();
		$autoload = $this->finder->getAutoload();

		// TODO: move this to the "Finder":
		$lensCore = $this->paths->join(dirname(dirname(dirname(__DIR__))), 'files');

		$settings = $this->getSettings();
		$mockClasses = $this->getMockClasses($settings);
		$mockFunctions = $this->getMockFunctions($settings);

		$processor = new Processor();

		// Update the source-code cache
		// TODO: make this robust against compile-time fatal syntax errors:
		$job = new CacheJob($executable, $project, $src, $autoload, $cache, $mockFunctions);
		$processor->run($job);
		// TODO: create blank entries in the "coverage.json" file (where coverage information is needed)

		$processor->finish();

		// Build the tests cache
		$isFunction = array($this, 'isFunction');
		$testsBuilder = new TestsBuilder($executable, $lensCore, $src, $tests, $cache, $isFunction, $processor, $this->paths, $this->filesystem);
		$testsBuilder->start($paths, $mockClasses, $mockFunctions);

		// Build the executable-lines cache
		$coverageBuilder = new CoverageBuilder($executable, $lensCore, $src, $cache, $processor);
		$coverageBuilder->start();

		$processor->finish();

		$coverageBuilder->stop();
		$testsBuilder->stop();

		$suites = $testsBuilder->getSuites();

		// TODO: move this to the "TestsBuilder"
		// TODO: let the user provide the project name in the configuration file
		$results = array(
			'name' => 'Lens',
			'suites' => $suites
		);

		/*
		$report = $this->getReport($reportType, $autoload);
		$stdout = $report->getReport($results);
		$stderr = null;

		if ($this->isUpdateAvailable($settings) && ($reportType === 'text')) {
			$stdout .= "\n\nA newer version of Lens is available:\n" . Url::LENS_INSTALLATION;
		}
		*/

		$executableStatements = $coverageBuilder->getCoverage();

		echo json_encode($executableStatements), "\n";
		exit;

		if (isset($code, $coverage)) {
			$web = new Web($this->filesystem);
			$coveragePath = $this->finder->getCoverage();
			$web->coverage($src, $coveragePath, $code, $coverage);
		}

		if ($this->isSuccessful($results)) {
			$exitCode = 0;
		} else {
			$exitCode = LensException::CODE_FAILURES;
		}

		return true;
	}

	private function getMockClasses(Settings $settings = null)
	{
		if ($settings === null) {
			return array();
		}

		$mockClasses = $settings->get('mockClasses');

		if ($mockClasses === null) {
			return array();
		}

		return array_combine($mockClasses, $mockClasses);
	}

	private function getMockFunctions(Settings $settings = null)
	{
		if ($settings === null) {
			return array();
		}

		$mockFunctions = $settings->get('mockFunctions');

		if ($mockFunctions === null) {
			return array();
		}

		return array_combine($mockFunctions, $mockFunctions);
	}

	public function isFunction($function)
	{
		return Semantics::isPhpFunction($function) || $this->isUserFunction($function);
	}

	private function isUserFunction($function)
	{
		$cache = $this->finder->getCache();

		if ($cache === null) {
			return false;
		}

		// TODO: this is repeated elsewhere:
		$names = explode('\\', $function);
		$relativePath = $this->paths->join($names) . '.php';
		$absolutePath = $this->paths->join($cache, 'functions', 'live', $relativePath);

		return $this->filesystem->isFile($absolutePath);
	}

	private function isUpdateAvailable(Settings $settings = null)
	{
		if ($settings === null) {
			return false;
		}

		$checkForUpdates = $settings->get('checkForUpdates');

		if (!$checkForUpdates) {
			return false;
		}

		$environment = new Environment();
		$os = $environment->getOperatingSystemName();
		$php = $environment->getPhpVersion();
		$lens = $environment->getLensVersion();

		$data = array(
			'os' => $os,
			'php' => $php,
			'lens' => $lens
		);

		$url = Url::LENS_CHECK_FOR_UPDATES;
		$query = http_build_query($data);

		set_error_handler(function () {});

		$latestVersion = file_get_contents("{$url}?{$query}");

		restore_error_handler();

		if ($latestVersion === false) {
			return false;
		}

		return Re::match('^[0-9]+\\.[0-9]+\\.[0-9]+$', $latestVersion) &&
			(LensVersion::VERSION !== $latestVersion);
	}

	private function getReportType(array $options)
	{
		$type = &$options['report'];

		if ($type === null) {
			return 'text';
		}

		switch ($type) {
			case 'xunit':
				return 'xunit';

			case 'tap':
				return 'tap';

			default:
				throw LensException::invalidReport($type);
		}
	}

	private function getReport($type, $autoload)
	{
		$caseText = new CaseText();
		$caseText->setAutoload($autoload);

		switch ($type) {
			case 'xunit':
				return new XUnitReport($caseText);

			case 'tap':
				return new TapReport($caseText);

			default:
				return new TextReport($caseText);
		}
	}

	/*
	private function getCoverage()
	{
		$options = $this->options;
		$type = &$options['coverage'];

		switch ($type) {
			// TODO: case 'none'
			// TODO: case 'clover'
			// TODO: case 'crap4j'
			// TODO: case 'text'

			default:
				return 'html';
		}

	}
	*/

	// TODO: limit this to just the tests that were specified by the user
	private function isSuccessful(array $project)
	{
		foreach ($project['suites'] as $suite) {
			foreach ($suite['tests'] as $test) {
				foreach ($test['cases'] as $case) {
					if (!$this->isPassing($case['issues'])) {
						return false;
					}
				}
			}
		}

		return true;
	}

	// TODO: this is duplicated elsewhere
	private function isPassing(array $issues)
	{
		foreach ($issues as $issue) {
			if (is_array($issue)) {
				return false;
			}
		}

		return true;
	}

	private function getSettings()
	{
		$path = $this->finder->getSettings();

		if ($path === null) {
			return null;
		}

		return new Settings($this->filesystem, $path);
	}
}
