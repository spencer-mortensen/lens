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
use SpencerMortensen\Parser;

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

	/** @var Browser */
	private $browser;

	/** @var SuiteParser */
	private $parser;

	/** @var Evaluator */
	private $evaluator;

	/** @var Console */
	private $console;

	/** @var Web */
	private $web;

	public function __construct(Settings $settings, Filesystem $filesystem, Browser $browser, SuiteParser $parser, Evaluator $evaluator, Console $console, Web $web)
	{
		$this->settings = $settings;
		$this->filesystem = $filesystem;
		$this->browser = $browser;
		$this->parser = $parser;
		$this->evaluator = $evaluator;
		$this->console = $console;
		$this->web = $web;
	}

	public function run(array $paths)
	{
		$paths = array_map(array($this, 'getAbsoluteTestsPath'), $paths);

		if (!$this->findTests($paths, $tests)) {
			throw Exception::unknownTestsDirectory();
		}

		$lensDirectory = dirname($tests);
		$testsDirectory = "{$lensDirectory}/tests";
		$coverageDirectory = "{$lensDirectory}/coverage";

		$this->settings->setPath("{$lensDirectory}/" . self::$settingsFileName);
		$settings = $this->settings->read();

		$this->findSrc($settings, $lensDirectory, $srcDirectory);
		$this->findAutoloader($settings, $lensDirectory, $srcDirectory, $bootstrapPath);

		if (count($paths) === 0) {
			$paths[] = $testsDirectory;
		}

		$testFiles = $this->browser->browse($testsDirectory, $paths);
		$suites = $this->getSuites($testsDirectory, $testFiles);

		list($suites, $code, $coverage) = $this->evaluator->run($lensDirectory, $srcDirectory, $bootstrapPath, $suites);

		echo $this->console->summarize($suites);

		if (isset($code, $coverage)) {
			$this->web->coverage($srcDirectory, $coverageDirectory, $code, $coverage);
		}
	}

	private function getSuites($testsDirectory, $files)
	{
		$suites = array();

		foreach ($files as $path => $contents) {
			try {
				$suites[$path] = $this->parser->parse($contents);
			} catch (Parser\Exception $exception) {
				$currentDirectory = $this->filesystem->getCurrentDirectory();
				$absolutePath = "{$testsDirectory}/{$path}";
				$relativePath = self::getRelativePath($currentDirectory, $absolutePath);

				$data = $exception->getData();
				throw Exception::invalidTestsFileSyntax($relativePath, $contents, $data['position'], $data['expectation']);
			}
		}

		return $suites;
	}

	public static function getRelativePath($aPath, $bPath)
	{
		$aTrail = explode('/', trim($aPath, '/'));
		$bTrail = explode('/', trim($bPath, '/'));

		$aCount = count($aTrail);
		$bCount= count($bTrail);

		for ($i = 0, $n = min($aCount, $bCount); ($i < $n) && ($aTrail[$i] === $bTrail[$i]); ++$i);

		return str_repeat('../', $aCount - $i) . implode('/', array_slice($bTrail, $i));
	}


	private function getAbsoluteTestsPath($relativePath)
	{
		$absolutePath = $this->filesystem->getAbsolutePath($relativePath);

		if ($absolutePath === null) {
			throw Exception::invalidTestsPath($relativePath);
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
		$trail = self::getTrail($basePath);

		for ($i = count($trail) - 1; -1 < $i; --$i) {
			if ($trail[$i] === $targetName) {
				$output = '/' . implode('/', array_slice($trail, 0, $i + 1));
				return true;
			}
		}

		return false;
	}

	private static function getTrail($path)
	{
		$path = trim($path, '/');

		if (strlen($path) === 0) {
			return array();
		}

		return explode('/', $path);
	}

	private function findTestsFromDirectory($directory, &$output)
	{
		return ($directory !== '/') && (
			$this->findChild($directory, self::$testsDirectoryName, $output) ||
			$this->findGrandchild($directory, self::$testsDirectoryName, $output) ||
			$this->findTestsFromDirectory(dirname($directory), $output)
		);
	}

	private function findChild($basePath, $targetName, &$output)
	{
		$path = "{$basePath}/{$targetName}";

		if (!$this->filesystem->isDirectory($path)) {
			return false;
		}

		$output = $path;
		return true;
	}

	private function findFile($basePath, $targetNames, &$output)
	{
		foreach ($targetNames as $targetName) {
			$path = "{$basePath}/{$targetName}";

			if ($this->filesystem->isFile($path)) {
				$output = $path;
				return true;
			}
		}

		return false;
	}

	private function findGrandchild($basePath, $targetName, &$output)
	{
		$paths = $this->filesystem->search("{$basePath}/*/{$targetName}");
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

	private function findSrcFromSettings($lens, &$relativePath, &$output)
	{
		if ($relativePath === null) {
			return false;
		}

		$absolutePath = $this->filesystem->getAbsolutePath("{$lens}/{$relativePath}");

		if ($absolutePath === null) {
			throw Exception::invalidSrcDirectory($relativePath);
		}

		$output = $absolutePath;
		return true;
	}

	private function findSrcFromLens($lens, &$output)
	{
		return $this->findChild(dirname($lens), self::$srcDirectoryName, $output) ||
			$this->findChild($lens, self::$srcDirectoryName, $output);
	}

	private function findAutoloader(array $settings, $lensDirectory, $srcDirectory, &$bootstrapPath)
	{
		return $this->findAutoloaderFromSettings($settings['bootstrap'], $bootstrapPath) ||
			$this->findAutoloaderFromLens($lensDirectory, $bootstrapPath) ||
			$this->findAutoloaderFromSrc($srcDirectory, $bootstrapPath);
	}

	private function findAutoloaderFromSettings(&$bootstrapPathSettings, &$bootstrapPath)
	{
		if ($bootstrapPathSettings === null) {
			return false;
		}

		$bootstrapPath = $bootstrapPathSettings;
		return true;
	}

	private function findAutoloaderFromLens($lensDirectory, &$bootstrapPath)
	{
		$path = "{$lensDirectory}/bootstrap.php";

		if (!$this->filesystem->isFile($path)) {
			return false;
		}

		$bootstrapPath = $path;
		return true;
	}

	private function findAutoloaderFromSrc($srcDirectory, &$bootstrapPath)
	{
		if ($srcDirectory === null) {
			return false;
		}

		$projectDirectory = dirname($srcDirectory);
		$path = "{$projectDirectory}/vendor/autoload.php";

		if (!$this->filesystem->isFile($path)) {
			return false;
		}

		$bootstrapPath = $path;
		return true;
	}
}
