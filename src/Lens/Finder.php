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
use Lens_0_0_56\SpencerMortensen\Filesystem\File;
use Lens_0_0_56\SpencerMortensen\Filesystem\Filesystem;
use Lens_0_0_56\SpencerMortensen\Filesystem\Paths\Path;

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

	/** @var Filesystem */
	private $filesystem;

	/** @var Path */
	private $core;

	/** @var Path|null */
	private $project;

	/** @var Path|null */
	private $lens;

	/** @var Path|null */
	private $cache;

	/** @var Path|null */
	private $tests;

	/** @var Path|null */
	private $autoload;

	/** @var Path|null */
	private $settings;

	/** @var Path|null */
	private $src;

	public function __construct(Filesystem $filesystem)
	{
		$this->filesystem = $filesystem;
	}

	public function find(array &$paths)
	{
		$this->findCore();
		$this->findLensTests($paths);

		// Simple tests can be run without a project
		if ($this->lens === null) {
			return;
		}

		$this->findSettings();

		$settings = new Settings(new File($this->settings));

		$this->findProject();
		$this->findSrc($settings);
		$this->findAutoload($settings);
		$this->findCache($settings);
	}

	private function findCore()
	{
		$this->core = $this->filesystem->getPath(__DIR__ . '/../../files');
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

	/**
	 * @param Path[] $paths
	 */
	private function findLensAndTestsByPaths(array $paths)
	{
		$umbrella = $this->getUmbrella($paths);

		if (!$this->getAncestor($umbrella)) {
			$this->tests = $this->getDirectory($umbrella);
		}
	}

	private function getUmbrella(array $paths)
	{
		$atomsArray = $this->getAtomsArray($paths);
		$umbrellaAtoms = $this->getUmbrellaAtoms($atomsArray);

		$firstPath = $paths[0];
		return $firstPath->setAtoms($umbrellaAtoms);
	}

	private function getAtomsArray(array $paths)
	{
		$array = [];

		foreach ($paths as $path) {
			$array[] = $path->getAtoms();
		}

		return $array;
	}

	private function getUmbrellaAtoms(array $paths)
	{
		$n = count($paths);

		if ($n === 1) {
			return $paths[0];
		}

		$m = min(array_map('count', $paths));

		$umbrella = [];

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

	private function getAncestor(Path $path)
	{
		$atoms = $path->getAtoms();

		for ($i = count($atoms) - 1; 0 < $i; --$i) {
			if (($atoms[$i - 1] !== self::LENS) || ($atoms[$i] !== self::TESTS)) {
				continue;
			}

			$atoms = array_slice($atoms, 0, $i);
			$this->lens = $path->setAtoms($atoms);

			$atoms[] = self::TESTS;
			$this->tests = $path->setAtoms($atoms);

			return true;
		}

		return false;
	}

	private function getDirectory(Path $path)
	{
		$atoms = $path->getAtoms();
		$atom = end($atoms);

		if (is_string($atom) && (substr($atom, -4) === '.php')) {
			array_pop($atoms);
			return $path->setAtoms($atoms);
		}

		return $path;
	}

	private function findLensAndTestsByCurrentDirectory()
	{
		$path = $this->filesystem->getCurrentDirectoryPath();

		if (!$this->getAncestor($path) && !$this->getDescendant($path)) {
			throw LensException::unknownLensDirectory();
		}
	}

	private function getDescendant(Path $path)
	{
		$atoms = $path->getAtoms();
		$atoms[] = self::LENS;
		$atoms[] = self::TESTS;

		$i = count($atoms) - 3;

		do {
			$childPath = $path->setAtoms($atoms);

			if ($this->filesystem->isDirectory($childPath)) {
				$this->tests = $childPath;

				array_pop($atoms);
				$this->lens = $path->setAtoms($atoms);

				return true;
			}

			array_splice($atoms, $i, 1);
		} while (0 <= $i--);

		return false;
	}

	private function findSettings()
	{
		$this->settings = $this->lens->add(self::SETTINGS);
	}

	private function findProject()
	{
		$this->project = $this->lens->add('..');
	}

	private function findSrc(Settings $settings)
	{
		$this->findSrcFromSettings($settings) ||
		$this->findSrcFromProject();

		if ($this->src === null) {
			throw LensException::unknownSrcDirectory();
		}

		$this->setSettingsPath('src', $this->src, $settings);
	}

	private function findSrcFromSettings(Settings $settings)
	{
		$value = $settings->get('src');

		if ($value === null) {
			return false;
		}

		$src = $this->project->add($value);

		return $this->isDirectory($src, $this->src);
	}

	private function isDirectory(Path $path, &$variable)
	{
		if (!$this->filesystem->isDirectory($path)) {
			return false;
		}

		$variable = $path;
		return true;
	}

	private function findSrcFromProject()
	{
		$src = $this->project->add(self::SRC);

		return $this->isDirectory($src, $this->src);
	}

	private function setSettingsPath($key, Path $path, Settings $settings)
	{
		$value = $this->getCanonicalSettingsPath($path);

		$settings->set($key, $value);
	}

	private function getCanonicalSettingsPath(Path $path)
	{
		if ($this->project->contains($path)) {
			return (string)$this->project->getRelativePath($path);
		}

		return (string)$path;
	}

	private function findAutoload(Settings $settings)
	{
		$this->checkComposer();

		$this->autoload = $this->getAutoloadPath($settings);

		$this->setSettingsPath('autoload', $this->autoload, $settings);
	}

	private function checkComposer()
	{
		$composerDirectory = $this->project->add(self::COMPOSER_DIRECTORY);

		if ($this->filesystem->isDirectory($composerDirectory)) {
			return;
		}

		$composerSettings = $this->project->add(self::COMPOSER_SETTINGS);

		if (!$this->filesystem->isFile($composerSettings)) {
			return;
		}

		$composerInstall = new ComposerInstall($this->project);
		$composerInstall->run();

		if (!$this->filesystem->isDirectory($composerDirectory)) {
			throw LensException::missingComposerDirectory($this->project);
		}
	}

	private function getAutoloadPath(Settings $settings)
	{
		$settingsAutoload = $this->getAutoloadPathFromSettings($settings);
		$lensAutoload = $this->lens->add(self::AUTOLOAD);
		$composerAutoload = $this->project->add(self::VENDOR, self::AUTOLOAD);

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
		$value = $settings->get('autoload');

		if ($value === null) {
			return null;
		}

		return $this->project->add($value);
	}

	private static function getPaths(...$arguments)
	{
		$paths = [];

		foreach ($arguments as $argument) {
			if ($argument !== null) {
				$paths[(string)$argument] = $argument;
			}
		}

		return $paths;
	}

	private function findCache(Settings $settings)
	{
		$this->findCacheFromSettings($settings) ||
		$this->findCacheFromLens();

		$this->setSettingsPath('cache', $this->cache, $settings);
	}

	private function findCacheFromSettings(Settings $settings)
	{
		$value = $settings->get('cache');

		if ($value === null) {
			return false;
		}

		$this->cache = $this->project->add($value);
		return true;
	}

	private function findCacheFromLens()
	{
		$this->cache = $this->lens->add(self::CACHE);
		return true;
	}


	public function getCore()
	{
		return $this->core;
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
