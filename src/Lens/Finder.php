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

namespace _Lens\Lens;

use _Lens\Lens\Commands\ComposerInstall;
use _Lens\SpencerMortensen\Filesystem\File;
use _Lens\SpencerMortensen\Filesystem\Filesystem;
use _Lens\SpencerMortensen\Filesystem\Path;

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

	/** @var Path */
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
		$this->core = Path::fromString(__DIR__ . '/../../files');
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
		$componentsArray = $this->getComponentsArray($paths);
		$umbrellaComponents = $this->getUmbrellaComponents($componentsArray);

		$firstPath = $paths[0];
		return $firstPath->setComponents($umbrellaComponents);
	}

	private function getComponentsArray(array $paths)
	{
		$array = [];

		foreach ($paths as $path) {
			$array[] = $path->getComponents();
		}

		return $array;
	}

	private function getUmbrellaComponents(array $paths)
	{
		$n = count($paths);

		if ($n === 1) {
			return $paths[0];
		}

		$m = min(array_map('count', $paths));

		$umbrella = [];

		for ($i = 0; $i < $m; ++$i) {
			$component = $paths[0][$i];

			for ($j = 1; $j < $n; ++$j) {
				if ($paths[$j][$i] !== $component) {
					return $umbrella;
				}
			}

			$umbrella[$i] = $component;
		}

		return $umbrella;
	}

	private function getAncestor(Path $path)
	{
		$components = $path->getComponents();

		for ($i = count($components) - 1; 0 < $i; --$i) {
			if (($components[$i - 1] !== self::LENS) || ($components[$i] !== self::TESTS)) {
				continue;
			}

			$components = array_slice($components, 0, $i);
			$this->lens = $path->setComponents($components);

			$components[] = self::TESTS;
			$this->tests = $path->setComponents($components);

			return true;
		}

		return false;
	}

	private function getDirectory(Path $path)
	{
		$components = $path->getComponents();
		$component = end($components);

		if (is_string($component) && (substr($component, -4) === '.php')) {
			array_pop($components);
			return $path->setComponents($components);
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
		$components = $path->getComponents();
		$components[] = self::LENS;
		$components[] = self::TESTS;

		$i = count($components) - 3;

		do {
			$childPath = $path->setComponents($components);

			if ($this->filesystem->isDirectory($childPath)) {
				$this->tests = $childPath;

				array_pop($components);
				$this->lens = $path->setComponents($components);

				return true;
			}

			array_splice($components, $i, 1);
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
