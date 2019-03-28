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

namespace _Lens\Lens\Phases;

use _Lens\Lens\Files\SettingsFile;
use _Lens\Lens\LensException;
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
	const SETTINGS = 'settings.yml';

	/** @var string */
	const SRC = 'src';

	/** @var Filesystem */
	private $filesystem;

	/** @var Path|null */
	private $projectPath;

	/** @var Path|null */
	private $lensPath;

	/** @var Path|null */
	private $cachePath;

	/** @var Path */
	private $testsPath;

	/** @var Path|null */
	private $settingsPath;

	/** @var Path|null */
	private $srcPath;

	/** @var Path|null */
	private $livePath;

	/** @var Path|null */
	private $mockPath;

	/** @var Path|null */
	private $xdebugPath;

	/** @var Path|null */
	private $indexPath;

	public function __construct(Filesystem $filesystem)
	{
		$this->filesystem = $filesystem;
	}

	public function find()
	{
		$this->findLensTests();

		// Simple tests can be run without a project
		if ($this->lensPath === null) {
			return;
		}

		$this->findProject();
		$this->findSettings();

		$settings = new SettingsFile(new File($this->settingsPath));

		$this->findSrc($settings);
		$this->findCache($settings);

		$code = $this->cachePath->add('code');
		$this->livePath = $code->add('live');
		$this->mockPath = $code->add('mock');
		$this->xdebugPath = $code->add('xdebug');
		$this->indexPath = $this->cachePath->add('index');
	}

	private function findLensTests()
	{
		$path = $this->filesystem->getCurrentDirectoryPath();

		if (!$this->getAncestor($path) && !$this->getDescendant($path)) {
			throw LensException::unknownLensDirectory();
		}
	}

	private function getAncestor(Path $path)
	{
		$components = $path->getComponents();

		for ($i = count($components) - 1; 0 < $i; --$i) {
			if (($components[$i - 1] !== self::LENS) || ($components[$i] !== self::TESTS)) {
				continue;
			}

			$components = array_slice($components, 0, $i);
			$this->lensPath = $path->setComponents($components);

			$components[] = self::TESTS;
			$this->testsPath = $path->setComponents($components);

			return true;
		}

		return false;
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
				$this->testsPath = $childPath;

				array_pop($components);
				$this->lensPath = $path->setComponents($components);

				return true;
			}

			array_splice($components, $i, 1);
		} while (0 <= $i--);

		return false;
	}

	private function findProject()
	{
		$this->projectPath = $this->lensPath->add('..');
	}

	private function findSettings()
	{
		$this->settingsPath = $this->lensPath->add(self::SETTINGS);
	}

	private function findSrc(SettingsFile $settings)
	{
		$this->findSrcFromSettings($settings) ||
		$this->findSrcFromProject();

		if ($this->srcPath === null) {
			throw LensException::unknownSrcDirectory();
		}

		$this->setSettingsPath('src', $this->srcPath, $settings);
	}

	private function findSrcFromSettings(SettingsFile $settings)
	{
		$value = $settings->get('src');

		if ($value === null) {
			return false;
		}

		$src = $this->projectPath->add($value);

		return $this->isDirectory($src, $this->srcPath);
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
		$src = $this->projectPath->add(self::SRC);

		return $this->isDirectory($src, $this->srcPath);
	}

	private function setSettingsPath($key, Path $path, SettingsFile $settings)
	{
		$value = $this->getCanonicalSettingsPath($path);

		$settings->set($key, $value);
	}

	private function getCanonicalSettingsPath(Path $path)
	{
		if ($this->projectPath->contains($path)) {
			return (string)$this->projectPath->getRelativePath($path);
		}

		return (string)$path;
	}

	private function findCache(SettingsFile $settings)
	{
		$this->findCacheFromSettings($settings) ||
		$this->findCacheFromLens();

		$this->setSettingsPath('cache', $this->cachePath, $settings);
	}

	private function findCacheFromSettings(SettingsFile $settings)
	{
		$value = $settings->get('cache');

		if ($value === null) {
			return false;
		}

		$this->cachePath = $this->projectPath->add($value);
		return true;
	}

	private function findCacheFromLens()
	{
		$this->cachePath = $this->lensPath->add(self::CACHE);
		return true;
	}

	public function getProjectPath()
	{
		return $this->projectPath;
	}

	public function getLensPath()
	{
		return $this->lensPath;
	}

	public function getCachePath()
	{
		return $this->cachePath;
	}

	public function getTestsPath()
	{
		return $this->testsPath;
	}

	public function getSettingsPath()
	{
		return $this->settingsPath;
	}

	public function getSrcPath()
	{
		return $this->srcPath;
	}

	public function getLivePath()
	{
		return $this->livePath;
	}

	public function getMockPath()
	{
		return $this->mockPath;
	}

	/**
	 * @return Path|null
	 */
	public function getIndexPath()
	{
		return $this->indexPath;
	}

	public function getXdebugPath()
	{
		return $this->xdebugPath;
	}

	public function getClassPath(Path $basePath, $class)
	{
		$components = explode('\\', "{$class}.php");
		// TODO: extend the "add" method to support arrays:
		return call_user_func_array([$basePath, 'add'], $components);
	}

	public function getFunctionPath(Path $basePath, $function)
	{
		$components = explode('\\', "{$function}.function.php");
		// TODO: extend the "add" method to support arrays:
		return call_user_func_array([$basePath, 'add'], $components);
	}
}
