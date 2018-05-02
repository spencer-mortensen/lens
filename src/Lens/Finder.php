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

namespace Lens_0_0_56\Lens;

use Lens_0_0_56\Lens\Commands\ComposerInstall;
use Lens_0_0_56\Lens\Updates\Updater;
use Lens_0_0_56\SpencerMortensen\Paths\Paths;

class Finder
{
	/** @var string */
	const LENS = 'lens';

	/** @var string */
	const CACHE = 'cache';

	/** @var string */
	const MOCKS = 'mocks';

	/** @var string */
	const CLASSES = 'classes';

	/** @var string */
	const FUNCTIONS = 'functions.php';

	/** @var string */
	const COVERAGE = 'coverage';

	/** @var string */
	const TESTS = 'tests';

	/** @var string */
	const AUTOLOAD = 'autoload.php';

	/** @var string */
	const SETTINGS = 'settings.yml';

	/** @var string */
	const SRC = 'src';

	/** @var string */
	const VENDOR = 'vendor';

	/** @var string */
	const COMPOSER_SETTINGS = 'composer.json';

	/** @var string */
	const COMPOSER_DIRECTORY = 'vendor';

	/** @var Paths */
	private $paths;

	/** @var Filesystem */
	private $filesystem;

	/** @var  string|null */
	private $project;

	/** @var string|null */
	private $lens;

	/** @var string|null */
	private $cache;

	/** @var string|null */
	private $coverage;

	/** @var string|null */
	private $tests;

	/** @var string|null */
	private $autoload;

	/** @var string|null */
	private $settings;

	/** @var string|null */
	private $src;

	public function __construct(Paths $paths, Filesystem $filesystem)
	{
		$this->paths = $paths;
		$this->filesystem = $filesystem;
	}

	public function find(array &$paths)
	{
		$paths = array_map(array($this, 'getAbsoluteTestsPath'), $paths);

		try {
			$this->findPaths($paths);
		} catch (LensException $exception) {
			$updater = new Updater($this->paths, $this->filesystem);
			$updater->update($paths);

			$this->findPaths($paths);
		}
	}

	private function getAbsoluteTestsPath($relativePath)
	{
		$absolutePath = $this->filesystem->getAbsolutePath($relativePath);

		if ($absolutePath === null) {
			throw LensException::invalidTestsPath($relativePath);
		}

		return $absolutePath;
	}

	private function findPaths(array &$paths)
	{
		$this->findLensTests($paths);

		// Simple tests might exist outside the usual Lens directory
		if ($this->lens === null) {
			return;
		}

		$this->findProject();
		$this->checkComposer();
		$this->findSettings();

		$settings = new Settings($this->filesystem, $this->settings);

		$this->findSrc($settings);
		$this->findAutoload($settings);
		$this->findCache($settings);
		$this->findCoverage();
	}

	private function findLensTests(array &$paths)
	{
		if (0 < count($paths)) {
			$this->findLensAndTestsByPaths($paths);
		} else {
			$this->findLensAndTestsByCurrentDirectory();
			$paths[] = $this->tests;
		}
	}

	private function findLensAndTestsByPaths(array $paths)
	{
		foreach ($paths as &$path) {
			$data = $this->paths->deserialize($path);
			$path = $data->getAtoms();
		}

		$umbrella = $this->getUmbrella($paths);

		if ($this->getAncestor($data, $umbrella)) {
			return;
		}

		// Simple tests might exist outside the usual Lens directory
		$atoms = $this->getDirectory($umbrella);
		$data->setAtoms($atoms);
		$this->tests = $this->paths->serialize($data);
	}

	private function getUmbrella(array $paths)
	{
		$n = count($paths);

		if ($n === 1) {
			return $paths[0];
		}

		$m = min(array_map('count', $paths));

		$umbrella = array();

		for ($i = 0; $i < $m; ++$i) {
			$atom = $paths[0][$i];

			for ($j = 1; $j < $n; ++$j) {
				if ($paths[$j][$i] !== $atom) {
					return $umbrella;
				}
			}

			$umbrella[$i] = $atom;
		}

		return $umbrella;
	}

	private function getDirectory(array $atoms)
	{
		$atom = end($atoms);

		if (is_string($atom) && (substr($atom, -4) === '.php')) {
			return array_slice($atoms, 0, -1);
		}

		return $atoms;
	}

	private function findLensAndTestsByCurrentDirectory()
	{
		$basePath = $this->filesystem->getCurrentDirectory();

		$data = $this->paths->deserialize($basePath);
		$atoms = $data->getAtoms();

		if (!$this->getAncestor($data, $atoms) && !$this->getDescendant($data, $atoms)) {
			throw LensException::unknownLensDirectory();
		}
	}

	private function getAncestor($data, array $atoms)
	{
		for ($i = count($atoms) - 1; 0 < $i; --$i) {
			if (($atoms[$i - 1] !== self::LENS) || ($atoms[$i] !== self::TESTS)) {
				continue;
			}

			$atoms = array_slice($atoms, 0, $i);
			$data->setAtoms($atoms);
			$this->lens = $this->paths->serialize($data);

			$atoms[] = self::TESTS;
			$data->setAtoms($atoms);
			$this->tests = $this->paths->serialize($data);

			return true;
		}

		return false;
	}

	private function getDescendant($data, array $atoms)
	{
		$atoms[] = self::LENS;
		$atoms[] = self::TESTS;

		$i = count($atoms) - 3;

		do {
			$data->setAtoms($atoms);
			$path = $this->paths->serialize($data);

			if ($this->filesystem->isDirectory($path)) {
				$this->tests = $path;

				array_pop($atoms);
				$data->setAtoms($atoms);
				$this->lens = $this->paths->serialize($data);

				return true;
			}

			unset($atoms[$i]);
		} while (0 <= $i--);

		return false;
	}

	private function findProject()
	{
		$this->project = dirname($this->lens);
	}

	private function checkComposer()
	{
		$composerDirectory = $this->paths->join($this->project, self::COMPOSER_DIRECTORY);

		if ($this->filesystem->isDirectory($composerDirectory)) {
			return;
		}

		$composerSettings = $this->paths->join($this->project, self::COMPOSER_SETTINGS);

		if (!$this->filesystem->isFile($composerSettings)) {
			return;
		}

		$composerInstall = new ComposerInstall($this->project);
		$composerInstall->run();

		if (!$this->filesystem->isDirectory($composerDirectory)) {
			throw LensException::missingComposerDirectory($this->project);
		}
	}

	private function findSettings()
	{
		$this->settings = $this->paths->join($this->lens, self::SETTINGS);
	}

	private function findSrc(Settings $settings)
	{
		$this->findSrcFromSettings($settings) ||
		$this->findSrcFromProject();

		if ($this->src === null) {
			throw LensException::unknownSrcDirectory();
		}

		$this->setSrc($settings);
	}

	private function findSrcFromSettings(Settings $settings)
	{
		$srcValue = $settings->get('src');

		if ($srcValue === null) {
			return false;
		}

		$srcPath = $this->paths->join($this->project, $srcValue);
		$srcPath = $this->filesystem->getAbsolutePath($srcPath);
		return $this->isDirectory($srcPath, $this->src);
	}

	private function findSrcFromProject()
	{
		$srcPath = $this->paths->join($this->project, self::SRC);

		return $this->isDirectory($srcPath, $this->src);
	}

	private function isDirectory($path, &$variable)
	{
		if (!$this->filesystem->isDirectory($path)) {
			return false;
		}

		$variable = $path;
		return true;
	}

	private function setSrc(Settings $settings)
	{
		$srcValue = $this->paths->getRelativePath($this->project, $this->src);

		$settings->set('src', $srcValue);
	}

	private function findAutoload(Settings $settings)
	{
		$this->autoload = $this->getAutoloadPath($settings);

		$this->setAutoload($settings);
	}

	private function getAutoloadPath(Settings $settings)
	{
		$settingsAutoload = $this->getAutoloadPathFromSettings($settings);
		$lensAutoload = $this->paths->join($this->lens, self::AUTOLOAD);
		$composerAutoload = $this->paths->join($this->project, self::VENDOR, self::AUTOLOAD);

		$paths = self::getPaths($settingsAutoload, $lensAutoload, $composerAutoload);

		foreach ($paths as $path) {
			if ($this->filesystem->isFile($path)) {
				return $path;
			}
		}

		throw LensException::unknownAutoloadFile();
	}

	private function getAutoloadPathFromSettings(Settings $settings)
	{
		$autoloadValue = $settings->get('autoload');

		if ($autoloadValue === null) {
			return null;
		}

		return $this->paths->join($this->project, $autoloadValue);
	}

	private static function getPaths()
	{
		$paths = array();

		foreach (func_get_args() as $path) {
			if ($path !== null) {
				$paths[$path] = $path;
			}
		}

		return $paths;
	}

	private function setAutoload(Settings $settings)
	{
		$value = $this->paths->getRelativePath($this->project, $this->autoload);

		$settings->set('autoload', $value);
	}

	private function findCache(Settings $settings)
	{
		$this->findCacheFromSettings($settings) ||
		$this->findCacheFromLens();

		$this->setCache($settings);
	}

	private function findCacheFromSettings(Settings $settings)
	{
		$cacheValue = $settings->get('cache');

		if ($cacheValue === null) {
			return false;
		}

		// TODO: accept absolute paths
		$this->cache = $this->paths->join($this->project, $cacheValue);
		return true;
	}

	private function findCacheFromLens()
	{
		$this->cache = $this->paths->join($this->lens, self::CACHE);
		return true;
	}

	private function setCache(Settings $settings)
	{
		// TODO: accept absolute paths
		$cacheValue = $this->paths->getRelativePath($this->project, $this->cache);

		$settings->set('cache', $cacheValue);
	}

	private function findCoverage()
	{
		$this->coverage = $this->paths->join($this->lens, self::COVERAGE);
	}

	public function getProject()
	{
		return $this->project;
	}

	public function getLens()
	{
		return $this->lens;
	}

	public function getCache()
	{
		return $this->cache;
	}

	public function getCoverage()
	{
		return $this->coverage;
	}

	public function getTests()
	{
		return $this->tests;
	}

	public function getAutoload()
	{
		return $this->autoload;
	}

	public function getSettings()
	{
		return $this->settings;
	}

	public function getSrc()
	{
		return $this->src;
	}
}
