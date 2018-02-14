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

use SpencerMortensen\Paths\Paths;

class Finder
{
	/** @var string */
	private static $lensDirectoryName = 'lens';

	/** @var string */
	private static $coverageDirectoryName = 'coverage';

	/** @var string */
	private static $testsDirectoryName = 'tests';

	/** @var string */
	private static $autoloadFileName = 'autoload.php';

	/** @var string */
	private static $settingsFileName = 'settings.ini';

	/** @var string */
	private static $srcDirectoryName = 'src';

	/** @var string */
	private static $vendorDirectoryName = 'vendor';

	/** @var Paths */
	private $paths;

	/** @var Filesystem */
	private $filesystem;

	/** @var  string|null */
	private $project;

	/** @var string|null */
	private $lens;

	/** @var string|null */
	private $coverage;

	/** @var string|null */
	private $tests;

	/** @var string|null */
	private $autoload;

	/** @var Settings */
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
		$this->findLensTests($paths);
		$this->findProject();
		$this->findSettings();
		$this->findSrc();
		$this->findAutoload();
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

		if ($this->lens === null) {
			throw LensException::unknownLensDirectory();
		}
	}

	private function findLensAndTestsByPaths(array $paths)
	{
		foreach ($paths as &$path) {
			$data = $this->paths->deserialize($path);
			$path = $data->getAtoms();
		}

		$parent = $this->getParent($paths);

		if ($this->getTarget($parent, $atoms)) {
			$data->setAtoms($atoms);
			$this->lens = $this->paths->serialize($data);

			$atoms[] = self::$testsDirectoryName;
			$data->setAtoms($atoms);
			$this->tests = $this->paths->serialize($data);
		} else {
			$atoms = $this->getDirectory($parent);
			$data->setAtoms($atoms);

			$this->tests = $this->paths->serialize($data);
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

	private function findLensAndTestsByCurrentDirectory()
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
				$this->lens = $path;
				$this->tests = $this->paths->join($this->lens, self::$testsDirectoryName);
				return;
			}

			unset($atoms[$i]);
		} while (0 <= $i--);
	}

	private function findProject()
	{
		$this->project = dirname($this->lens);
	}

	private function findSettings()
	{
		$path = $this->paths->join($this->lens, self::$settingsFileName);

		$this->settings = new Settings($this->paths, $this->filesystem, $path);
	}

	private function findSrc()
	{
		$this->findSrcFromSettings() ||
		$this->findSrcFromProject();

		if ($this->src === null) {
			throw LensException::unknownSrcDirectory();
		}

		$this->setSrc();
	}

	private function findSrcFromSettings()
	{
		$srcValue = $this->settings->getSrc();

		if ($srcValue === null) {
			return false;
		}

		$srcPath = $this->paths->join($this->project, $srcValue);
		$srcPath = $this->filesystem->getAbsolutePath($srcPath);
		return $this->isDirectory($srcPath, $this->src);
	}

	private function findSrcFromProject()
	{
		$srcPath = $this->paths->join($this->project, self::$srcDirectoryName);

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

	private function setSrc()
	{
		$srcValue = $this->paths->getRelativePath($this->project, $this->src);

		$this->settings->setSrc($srcValue);
	}

	private function findAutoload()
	{
		$this->findAutoloadFromSettings() ||
		$this->findAutoloadFromLens() ||
		$this->findAutoloadFromProject();

		if ($this->autoload === null) {
			throw LensException::unknownAutoloadFile();
		}

		$this->setAutoload();
	}

	private function findAutoloadFromSettings()
	{
		$autoloadValue = $this->settings->getAutoload();

		if ($autoloadValue === null) {
			return false;
		}

		$autoloadPath = $this->paths->join($this->project, $autoloadValue);
		$autoloadPath = $this->filesystem->getAbsolutePath($autoloadPath);
		return $this->isFile($autoloadPath, $this->autoload);
	}

	private function findAutoloadFromLens()
	{
		$autoloadPath = $this->paths->join($this->lens, self::$autoloadFileName);
		return $this->isFile($autoloadPath, $this->autoload);
	}

	private function findAutoloadFromProject()
	{
		$autoloadPath = $this->paths->join($this->project, self::$vendorDirectoryName, self::$autoloadFileName);
		return $this->isFile($autoloadPath, $this->autoload);
	}

	private function isFile($path, &$variable)
	{
		if (!$this->filesystem->isFile($path)) {
			return false;
		}

		$variable = $path;
		return true;
	}

	private function setAutoload()
	{
		$value = $this->paths->getRelativePath($this->project, $this->autoload);

		$this->settings->setAutoload($value);
	}

	private function findCoverage()
	{
		$this->coverage = $this->paths->join($this->lens, self::$coverageDirectoryName);
	}

	public function getProject()
	{
		return $this->project;
	}

	public function getLens()
	{
		return $this->lens;
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
