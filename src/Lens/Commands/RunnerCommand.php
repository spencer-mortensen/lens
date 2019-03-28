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

namespace _Lens\Lens\Commands;

use _Lens\Lens\Arguments;
use _Lens\Lens\Environment;
use _Lens\Lens\Files\SettingsFile;
use _Lens\Lens\LensException;
use _Lens\Lens\Phases\Analyzer;
use _Lens\Lens\Phases\Execution\Executor;
use _Lens\Lens\Phases\Finder;
use _Lens\Lens\Reports\ReportsBuilder;
use _Lens\Lens\Tests\GetSuites;
use _Lens\Lens\Tests\GetResults;
use _Lens\Lens\Url;
use _Lens\SpencerMortensen\Exceptions\Exceptions;
use _Lens\SpencerMortensen\Filesystem\File;
use _Lens\SpencerMortensen\Filesystem\Filesystem;
use _Lens\SpencerMortensen\RegularExpressions\Re;

class RunnerCommand implements Command
{
	/** @var Arguments */
	private $arguments;

	public function __construct(Arguments $arguments)
	{
		$this->arguments = $arguments;
	}

	public function run(&$stdout = null, &$stderr = null, &$exitCode = null)
	{
		$filesystem = new Filesystem();

		$options = $this->getValidOptions();
		$executable = $this->arguments->getExecutable();

		$finder = new Finder($filesystem);
		$finder->find();

		$project = $finder->getProjectPath();
		$src = $finder->getSrcPath();
		// TODO: add "external library" directories to the configuration file
		$tests = $finder->getTestsPath();
		$cache = $finder->getCachePath();

		$analyzer = new Analyzer();
		$analyzer->analyze($finder);

		$executor = new Executor($filesystem);
		$executor->execute($finder);

		$settings = $this->getSettings($finder);
		$mockClasses = $this->getMockClasses($settings);
		$mockFunctions = $this->getMockFunctions($settings);

		exit;

		$getSuites = new GetSuites($tests);
		$suites = $getSuites->getSuites($paths);

		$getResults = new GetResults($executable, $core, $cache);
		$results = $getResults->getResults($suites, $mockClasses, $mockFunctions);

		exit;

		$isUpdateAvailable = $this->isUpdateAvailable($settings);

		$reportsBuilder = new ReportsBuilder($core, $project, $autoload, $cache, $filesystem);
		list($stdout, $stderr, $exitCode) = $reportsBuilder->run($options, $results, $executableStatements, $isUpdateAvailable);
		return true;
	}

	private function getValidOptions()
	{
		$options = $this->arguments->getOptions();

		if (count($options) === 0) {
			return [
				'issues' => 'stdout',
				'coverage' => 'lens/coverage'
			];
		}

		$validOptions = [
			'clover' => 'clover',
			'coverage' => 'coverage',
			'tap' => 'tap',
			'issues' => 'issues',
			'xunit' => 'xunit'
		];

		foreach ($options as $option => $value) {
			if (!isset($validOptions[$option])) {
				throw LensException::usage();
			}

			if (($option === 'coverage') && ($value === 'stdout')) {
				throw LensException::usage();
			}
		}

		return $options;
	}

	private function getSettings(Finder $finder)
	{
		$settingsPath = $finder->getSettingsPath();

		if ($settingsPath === null) {
			return null;
		}

		$settingsFile = new File($settingsPath);
		return new SettingsFile($settingsFile);
	}

	private function getMockClasses(SettingsFile $settings = null)
	{
		if ($settings === null) {
			return [];
		}

		$mockClasses = $settings->get('mockClasses');

		if ($mockClasses === null) {
			return [];
		}

		return array_combine($mockClasses, $mockClasses);
	}

	private function getMockFunctions(SettingsFile $settings = null)
	{
		if ($settings === null) {
			return [];
		}

		$mockFunctions = $settings->get('mockFunctions');

		if ($mockFunctions === null) {
			return [];
		}

		return array_combine($mockFunctions, $mockFunctions);
	}

	private function isUpdateAvailable(SettingsFile $settings = null)
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

		$data = [
			'os' => $os,
			'php' => $php,
			'lens' => $lens
		];

		$url = Url::LENS_CHECK_FOR_UPDATES;
		$query = http_build_query($data);

		Exceptions::on(function () {});

		$latestVersion = file_get_contents("{$url}?{$query}");

		Exceptions::off();

		if ($latestVersion === false) {
			return false;
		}

		return Re::match('^[0-9]+\\.[0-9]+\\.[0-9]+$', $latestVersion) &&
			(VersionCommand::VERSION !== $latestVersion);
	}
}
