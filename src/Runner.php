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
use Lens\Reports\Report;
use SpencerMortensen\Parser\ParserException;
use SpencerMortensen\Paths\Paths;

class Runner
{
	/** @var string */
	private static $testsDirectoryName = 'tests';

	/** @var string */
	private static $srcDirectoryName = 'src';

	/** @var string */
	private static $settingsFileName = 'settings.ini';

	/** @var Settings */
	private $settings;

	/** @var Filesystem */
	private $filesystem;

	/** @var Paths */
	private $paths;

	/** @var Browser */
	private $browser;

	/** @var SuiteParser */
	private $parser;

	/** @var Evaluator */
	private $evaluator;

	/** @var Summarizer */
	private $summarizer;

	/** @var Report */
	private $report;

	/** @var Web */
	private $web;

	public function __construct(Settings $settings, Filesystem $filesystem, Paths $paths, Browser $browser, SuiteParser $parser, Evaluator $evaluator, Summarizer $summarizer, Report $report, Web $web)
	{
		$this->settings = $settings;
		$this->filesystem = $filesystem;
		$this->paths = $paths;
		$this->browser = $browser;
		$this->parser = $parser;
		$this->evaluator = $evaluator;
		$this->summarizer = $summarizer;
		$this->report = $report;
		$this->web = $web;
	}

	public function run(array $paths)
	{
		$paths = array_map(array($this, 'getAbsoluteTestsPath'), $paths);

		if ($this->findTests($paths, $testsDirectory)) {
			$lensDirectory = dirname($testsDirectory);
			$coverageDirectory = $this->paths->join($lensDirectory, 'coverage');

			$settingsFilePath = $this->paths->join($lensDirectory, self::$settingsFileName);
			$this->settings->setPath($settingsFilePath);
			$settings = $this->settings->read();

			$this->findSrc($settings, $lensDirectory, $srcDirectory);
			$this->findAutoloader($settings, $lensDirectory, $srcDirectory, $autoloadPath);
		} else {
			$lensDirectory = null;
			$testsDirectory = null;
			$coverageDirectory = null;

			$srcDirectory = null;
			$autoloadPath = null;
		}

		if (count($paths) === 0) {
			if ($testsDirectory === null) {
				throw LensException::unknownTestsDirectory();
			}

			$paths[] = $testsDirectory;
		}

		$testFiles = $this->browser->browse($testsDirectory, $paths);

		$suites = $this->getSuites($testsDirectory, $testFiles);

		list($suites, $code, $coverage) = $this->evaluator->run($srcDirectory, $autoloadPath, $suites);

		$project = array(
			'name' => 'Lens', // TODO: let the user provide the project name
			'suites' => $suites
		);

		$this->summarizer->summarize($project);

		echo $this->report->getReport($project);

		if (isset($code, $coverage)) {
			$this->web->coverage($srcDirectory, $coverageDirectory, $code, $coverage);
		}

		return $project['summary']['failed'] === 0;
	}

	private function getSuites($testsDirectory, array $files)
	{
		$suites = array();

		foreach ($files as $path => $contents) {
			try {
				$suites[$path] = $this->parser->parse($contents);
			} catch (ParserException $exception) {
				$absolutePath = $this->paths->join($testsDirectory, $path);

				$this->invalidTestsFileSyntax($absolutePath, $contents, $exception);
			}
		}

		return $suites;
	}

	private function invalidTestsFileSyntax($absolutePath, $contents, ParserException $exception)
	{
		$currentDirectory = $this->filesystem->getCurrentDirectory();
		$relativePath = $this->paths->getRelativePath($currentDirectory, $absolutePath);

		$position = $exception->getState();
		$rule = $exception->getRule();
		throw LensException::invalidTestsFileSyntax($relativePath, $contents, $position, $rule);
	}

	private function getAbsoluteTestsPath($relativePath)
	{
		$absolutePath = $this->filesystem->getAbsolutePath($relativePath);

		if ($absolutePath === null) {
			throw LensException::invalidTestsPath($relativePath);
		}

		return $absolutePath;
	}

	private function findTests(array $paths, &$output)
	{
		$current = $this->filesystem->getCurrentDirectory();

		return $this->findTestsFromPaths($paths, $output) ||
			$this->findTestsFromDirectory($current, $output);
	}

	private function findTestsFromPaths(array $paths, &$output)
	{
		return (0 < count($paths)) &&
			$this->findAncestor(current($paths), self::$testsDirectoryName, $output);
	}

	private function findAncestor($basePath, $targetName, &$output)
	{
		$data = $this->paths->deserialize($basePath);
		$atoms = $data->getAtoms();

		for ($i = count($atoms) - 1; -1 < $i; --$i) {
			if ($atoms[$i] === $targetName) {
				$atoms = array_slice($atoms, 0, $i + 1);
				$data->setAtoms($atoms);

				$output = $this->paths->serialize($data);
				return true;
			}
		}

		return false;
	}

	private function findTestsFromDirectory($directory, &$output)
	{
		$data = $this->paths->deserialize($directory);
		$atoms = $data->getAtoms();

		return (0 < count($atoms)) && (
			$this->findChild($directory, self::$testsDirectoryName, $output) ||
			$this->findGrandchild($directory, self::$testsDirectoryName, $output) ||
			$this->findTestsFromDirectory(dirname($directory), $output)
		);
	}

	private function findChild($basePath, $targetName, &$output)
	{
		$path = $this->paths->join($basePath, $targetName);

		if (!$this->filesystem->isDirectory($path)) {
			return false;
		}

		$output = $path;
		return true;
	}

	private function findGrandchild($basePath, $targetName, &$output)
	{
		$targetPath = $this->paths->join($basePath, '*', $targetName);
		$paths = $this->filesystem->search($targetPath);
		$paths = array_filter($paths, array($this->filesystem, 'isDirectory'));

		if (count($paths) !== 1) {
			return false;
		}

		$output = $paths[0];
		return true;
	}

	private function findSrc(array $settings, $lens, &$srcDirectory)
	{
		// TODO: refactor this:
		return $this->findSrcFromSettings($lens, $settings['src'], $srcDirectory) ||
			$this->findSrcFromLens($lens, $srcDirectory);
	}

	private function findSrcFromSettings($lens, $relativePath, &$output)
	{
		if ($relativePath === null) {
			return false;
		}

		$relativePath = $this->paths->join($lens, $relativePath);
		// TODO: remove this line (when the "Paths" class can handle '..' directories):
		$absolutePath = $this->filesystem->getAbsolutePath($relativePath);

		if ($absolutePath === null) {
			throw LensException::invalidSrcDirectory($relativePath);
		}

		$output = $absolutePath;
		return true;
	}

	private function findSrcFromLens($lens, &$output)
	{
		return $this->findChild(dirname($lens), self::$srcDirectoryName, $output) ||
			$this->findChild($lens, self::$srcDirectoryName, $output);
	}

	private function findAutoloader(array $settings, $lensDirectory, $srcDirectory, &$autoloadPath)
	{
		return $this->findAutoloaderFromSettings($settings['autoload'], $autoloadPath) ||
			$this->findAutoloaderFromLens($lensDirectory, $autoloadPath) ||
			$this->findAutoloaderFromSrc($srcDirectory, $autoloadPath);
	}

	private function findAutoloaderFromSettings(&$autoloadPathSettings, &$autoloadPath)
	{
		if ($autoloadPathSettings === null) {
			return false;
		}

		$autoloadPath = $autoloadPathSettings;
		return true;
	}

	private function findAutoloaderFromLens($lensDirectory, &$autoloadPath)
	{
		$path = $this->paths->join($lensDirectory, 'autoload.php');

		if (!$this->filesystem->isFile($path)) {
			return false;
		}

		$autoloadPath = $path;
		return true;
	}

	private function findAutoloaderFromSrc($srcDirectory, &$autoloadPath)
	{
		if ($srcDirectory === null) {
			return false;
		}

		$projectDirectory = dirname($srcDirectory);
		$path = $this->paths->join($projectDirectory, 'vendor', 'autoload.php');

		if (!$this->filesystem->isFile($path)) {
			return false;
		}

		$autoloadPath = $path;
		return true;
	}
}
