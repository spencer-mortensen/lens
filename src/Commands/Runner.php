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

namespace Lens\Commands;

use Lens\Arguments;
use Lens\Browser;
use Lens\Evaluator\Evaluator;
use Lens\Filesystem;
use Lens\IniFile;
use Lens\LensException;
use Lens\Logger;
use Lens\Reports\Tap;
use Lens\Reports\Text;
use Lens\Reports\XUnit;
use Lens\Settings;
use Lens\SuiteParser;
use Lens\Summarizer;
use Lens\Web;
use SpencerMortensen\ParallelProcessor\Processor;
use SpencerMortensen\Parser\ParserException;
use SpencerMortensen\Paths\Paths;

class Runner implements Command
{
	/** @var string */
	private static $testsDirectoryName = 'tests';

	/** @var string */
	private static $srcDirectoryName = 'src';

	/** @var string */
	private static $settingsFileName = 'settings.ini';

	/** @var Arguments */
	private $arguments;

	/** @var Logger */
	private $logger;

	/** @var Filesystem */
	private $filesystem;

	/** @var Paths */
	private $paths;

	public function __construct(Arguments $arguments, Logger $logger)
	{
		$this->arguments = $arguments;
		$this->logger = $logger;
		$this->filesystem = new Filesystem();
		$this->paths = Paths::getPlatformPaths();
	}

	public function run(&$stdout, &$stderr, &$exitCode)
	{
		$options = $this->arguments->getOptions();
		$paths = $this->arguments->getValues();

		$report = $this->getReport($options);

		// TODO: if there are any options other than "report", then throw a usage exception

		$paths = array_map(array($this, 'getAbsoluteTestsPath'), $paths);

		if ($this->findTests($paths, $testsDirectory)) {
			$lensDirectory = dirname($testsDirectory);
			$coverageDirectory = $this->paths->join($lensDirectory, 'coverage');

			$settingsFilePath = $this->paths->join($lensDirectory, self::$settingsFileName);
			$settingsFile = new IniFile($this->filesystem);
			$settings = new Settings($settingsFile, $this->logger);
			$settings->setPath($settingsFilePath); // TODO: move this to the constructor
			$options = $settings->read();

			$this->findSrc($options, $lensDirectory, $srcDirectory);
			$this->findAutoloader($options, $lensDirectory, $srcDirectory, $autoloadPath);
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

		$browser = new Browser($this->filesystem, $this->paths);
		$testFiles = $browser->browse($testsDirectory, $paths);

		$suites = $this->getSuites($testsDirectory, $testFiles);

		$executable = $this->arguments->getExecutable();
		$processor = new Processor();
		$evaluator = new Evaluator($executable, $this->filesystem, $processor);
		list($suites, $code, $coverage) = $evaluator->run($srcDirectory, $autoloadPath, $suites);

		$project = array(
			'name' => 'Lens', // TODO: let the user provide the project name
			'suites' => $suites
		);

		$summarizer = new Summarizer();
		$summarizer->summarize($project);

		$stdout = $report->getReport($project);
		$stderr = null;

		if (isset($code, $coverage)) {
			$web = new Web($this->filesystem);
			$web->coverage($srcDirectory, $coverageDirectory, $code, $coverage);
		}

		$isSuccessful = ($project['summary']['failed'] === 0);

		if ($isSuccessful) {
			$exitCode = 0;
		} else {
			$exitCode = LensException::CODE_FAILURES;
		}

		return true;
	}

	private function getReport(array $options)
	{
		$type = &$options['report'];

		if ($type === null) {
			return new Text();
		}

		switch ($type) {
			case 'xunit':
				return new XUnit();

			case 'tap':
				return new Tap();

			default:
				throw LensException::invalidReport($type);
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

	private function getSuites($testsDirectory, array $files)
	{
		$suites = array();

		foreach ($files as $path => $contents) {
			try {
				$parser = new SuiteParser();
				$suites[$path] = $parser->parse($contents);
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
