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
use SpencerMortensen\Parser\ParserException;
use SpencerMortensen\Paths\Paths;

class Runner implements Command
{
	/** @var string */
	private static $srcDirectoryName = 'src';

	/** @var string */
	private static $lensDirectoryName = 'lens';

	/** @var string */
	private static $testsDirectoryName = 'tests';

	/** @var string */
	private static $coverageDirectoryName = 'coverage';

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

		if (count($paths) === 0) {
			$lensDirectoryPath = $this->findLensByCurrentDirectory();

			if ($lensDirectoryPath === null) {
				throw LensException::unknownLensDirectory();
			}

			$testsDirectoryPath = $this->paths->join($lensDirectoryPath, self::$testsDirectoryName);
			$paths[] = $testsDirectoryPath;

			$this->findPaths($lensDirectoryPath, $projectDirectoryPath, $srcDirectoryPath, $autoloadFilePath, $coverageDirectoryPath);
		} else {
			$this->findByPaths($paths, $lensDirectoryPath, $testsDirectoryPath);
			$this->findPaths($lensDirectoryPath, $projectDirectoryPath, $srcDirectoryPath, $autoloadFilePath, $coverageDirectoryPath);
		}

		$executable = $this->arguments->getExecutable();
		$suites = $this->getSuites($paths);

		$evaluator = new Evaluator($executable, $this->filesystem);
		list($suites, $code, $coverage) = $evaluator->run($srcDirectoryPath, $autoloadFilePath, $suites);

		$project = array(
			'name' => 'Lens', // TODO: let the user provide the project name in the configuration file
			'suites' => $this->useRelativePaths($testsDirectoryPath, $suites)
		);

		$summarizer = new Summarizer();
		$summarizer->summarize($project);


		$stdout = $report->getReport($project);
		$stderr = null;

		if (isset($code, $coverage)) {
			$web = new Web($this->filesystem);
			$web->coverage($srcDirectoryPath, $coverageDirectoryPath, $code, $coverage);
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

	private function getAbsoluteTestsPath($relativePath)
	{
		$absolutePath = $this->filesystem->getAbsolutePath($relativePath);

		if ($absolutePath === null) {
			throw LensException::invalidTestsPath($relativePath);
		}

		return $absolutePath;
	}

	private function findLensByCurrentDirectory()
	{
		$basePath = $this->filesystem->getCurrentDirectory();

		$data = $this->paths->deserialize($basePath);
		$atoms = $data->getAtoms();
		$atoms[] = self::$lensDirectoryName;

		$i = count($atoms) - 2;

		do {
			$data->setAtoms($atoms);
			$path = $this->paths->serialize($data);

			if ($this->filesystem->isDirectory($path)) {
				return $path;
			}

			unset($atoms[$i]);
		} while (0 <= $i--);

		return null;
	}

	private function findByPaths(array $paths, &$lensDirectory, &$testsDirectory)
	{
		foreach ($paths as &$path) {
			$data = $this->paths->deserialize($path);
			$path = $data->getAtoms();
		}

		$parent = $this->getParent($paths);

		if ($this->getTarget($parent, $atoms)) {
			$data->setAtoms($atoms);
			$lensDirectory = $this->paths->serialize($data);

			$atoms[] = self::$testsDirectoryName;
			$data->setAtoms($atoms);
			$testsDirectory = $this->paths->serialize($data);
		} else {
			$atoms = $this->getDirectory($parent);
			$data->setAtoms($atoms);

			$lensDirectory = null;
			$testsDirectory = $this->paths->serialize($data);
		}
	}

	private function getParent(array $paths)
	{
		$n = count($paths);

		if ($n === 1) {
			return $paths[0];
		}

		$m = min(array_map('count', $paths));

		$parent = array();

		for ($i = 0; $i < $m; ++$i) {
			$atom = $paths[0][$i];

			for ($j = 1; $j < $n; ++$j) {
				if ($paths[$j][$i] !== $atom) {
					return $parent;
				}
			}

			$parent[$i] = $atom;
		}

		return $parent;
	}

	private function getTarget(array $atoms, &$output)
	{
		for ($i = count($atoms) - 1; 0 < $i; --$i) {
			if (($atoms[$i - 1] === self::$lensDirectoryName) && ($atoms[$i] === self::$testsDirectoryName)) {
				$output = array_slice($atoms, 0, $i);
				return true;
			}
		}

		return false;
	}

	private function getDirectory(array $atoms)
	{
		$atom = end($atoms);

		if (is_string($atom) && (substr($atom, -4) === '.php')) {
			return array_slice($atoms, 0, -1);
		}

		return $atoms;
	}

	public function findPaths($lensDirectoryPath, &$projectDirectoryPath, &$srcDirectoryPath, &$autoloadFilePath, &$coverageDirectoryPath)
	{
		if ($lensDirectoryPath === null) {
			return;
		}

		$projectDirectoryPath = dirname($lensDirectoryPath);
		$coverageDirectoryPath = $this->paths->join($lensDirectoryPath, self::$coverageDirectoryName);
		$settingsFilePath = $this->paths->join($lensDirectoryPath, self::$settingsFileName);

		$settingsFile = new IniFile($this->filesystem, $settingsFilePath);
		$oldSettings = $settingsFile->read();
		$newSettings = $oldSettings;

		$this->findSrcDirectory($newSettings, $projectDirectoryPath, $srcDirectoryPath);
		$this->findAutoloadFile($newSettings, $projectDirectoryPath, $lensDirectoryPath, $autoloadFilePath);

		if ($newSettings !== $oldSettings) {
			$settingsFile->write($newSettings);
		}
	}

	private function findSrcDirectory(array &$settings = null, $projectDirectoryPath, &$output)
	{
		$src = &$settings['src'];

		if (is_string($src) && $this->getAbsoluteDirectoryPath($projectDirectoryPath, $src, $output)) {
			return true;
		}

		$src = self::$srcDirectoryName;

		if ($this->getAbsoluteDirectoryPath($projectDirectoryPath, $src, $output)) {
			return true;
		}

		$src = null;
		return false;
	}

	private function getAbsoluteDirectoryPath($basePath, $relativePath, &$output)
	{
		$absolutePath = $this->paths->join($basePath, $relativePath);

		if (!$this->filesystem->isDirectory($absolutePath)) {
			return false;
		}

		$output = $absolutePath;
		return true;
	}

	private function findAutoloadFile(array &$settings = null, $projectDirectoryPath, $lensDirectoryPath, &$output)
	{
		$autoload = &$settings['autoload'];

		if (is_string($autoload) && $this->getAbsoluteFilePath($projectDirectoryPath, $autoload, $output)) {
			return true;
		}

		$autoload = $this->paths->join('vendor', 'autoload.php');

		if ($this->getAbsoluteFilePath($projectDirectoryPath, $autoload, $output)) {
			return true;
		}

		if ($this->getAbsoluteFilePath($lensDirectoryPath, 'autoload.php', $output)) {
			$autoload = $this->paths->getRelativePath($projectDirectoryPath, $output);
			return true;
		}

		$autoload = null;
		return false;
	}

	private function getAbsoluteFilePath($basePath, $relativePath, &$output)
	{
		$absolutePath = $this->paths->join($basePath, $relativePath);

		if (!$this->filesystem->isFile($absolutePath)) {
			return false;
		}

		$output = $absolutePath;
		return true;
	}

	private function getSuites(array $paths)
	{
		$browser = new Browser($this->filesystem, $this->paths);
		$files = $browser->browse($paths);

		$suites = array();
		$parser = new SuiteParser();

		foreach ($files as $path => $contents) {
			try {
				$suites[$path] = $parser->parse($contents);
			} catch (ParserException $exception) {
				throw LensException::invalidTestsFileSyntax($path, $contents, $exception);
			}
		}

		return $suites;
	}

	private function useRelativePaths($testsDirectory, array $input)
	{
		$output = array();

		foreach ($input as $absolutePath => $value) {
			$relativePath = $this->paths->getRelativePath($testsDirectory, $absolutePath);
			$output[$relativePath] = $value;
		}

		return $output;
	}
}
