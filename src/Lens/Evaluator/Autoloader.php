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

namespace Lens_0_0_56\Lens\Evaluator;

use Lens_0_0_56\Lens\Filesystem;
use Lens_0_0_56\SpencerMortensen\Paths\Paths;

class Autoloader
{
	/** @var string */
	private $core;

	/** @var string|null */
	private $cache;

	/** @var array */
	private $mockClasses;

	/** @var Paths */
	private $paths;

	/** @var Filesystem */
	private $filesystem;

	/** @var integer */
	private $depth;

	/** @var integer|null */
	private $mockDepth;

	public function __construct($core, $cache, array $mockClasses)
	{
		$this->core = $core;
		$this->cache = $cache;
		$this->mockClasses = $mockClasses;

		// TODO: dependency injection:
		$this->paths = Paths::getPlatformPaths();
		$this->filesystem = new Filesystem();
		$this->depth = 0;
		$this->mockDepth = null;
	}

	public function enable()
	{
		define('LENS_CORE_DIRECTORY', $this->core);
		define('LENS_CACHE_DIRECTORY', $this->cache);

		spl_autoload_register(array($this, 'autoload'));
	}

	public function enableLiveMode()
	{
		$this->mockDepth = $this->depth;
	}

	public function autoload($name)
	{
		if ($this->mockDepth !== null) {
			unset($this->mockClasses[$name]);
		}

		++$this->depth;

		$this->autoloadClass($name) ||
		$this->autoloadInterface($name) ||
		$this->autoloadTrait($name);

		--$this->depth;

		if ($this->mockDepth == $this->depth) {
			$this->mockDepth = null;
		}
	}

	private function autoloadClass($class)
	{
		$this->getCoreMockClassPath($class, $path) ||
		$this->getUserMockClassPath($class, $path) ||
		$this->getUserLiveClassPath($class, $path);

		if (!$this->filesystem->isFile($path)) {
			return false;
		}

		include $path;
		return true;
	}

	private function getCoreMockClassPath($class, &$absoluteFilePath)
	{
		if (strncmp($class, 'Lens\\', 5) !== 0) {
			return false;
		}

		$class = substr($class, 5);
		$relativeFilePath = $this->getRelativeFilePath($class);
		$absoluteFilePath = $this->paths->join($this->core, 'files', 'mocks', 'classes', $relativeFilePath);
		return true;
	}

	private function getUserMockClassPath($class, &$absoluteFilePath)
	{
		if (!isset($this->mockClasses[$class])) {
			return false;
		}

		$relativeFilePath = $this->getRelativeFilePath($class);
		$absoluteFilePath = $this->paths->join($this->cache, 'classes', 'mock', $relativeFilePath);
		return true;
	}

	private function getUserLiveClassPath($class, &$absoluteFilePath)
	{
		$relativeFilePath = $this->getRelativeFilePath($class);
		$absoluteFilePath = $this->paths->join($this->cache, 'classes', 'live', $relativeFilePath);
		return true;
	}

	private function autoloadInterface($interface)
	{
		$this->getUserLiveInterfacePath($interface, $path);

		if (!$this->filesystem->isFile($path)) {
			return false;
		}

		include $path;
		return true;
	}

	// TODO: this is duplicated in the "CacheBuilder":
	private function getUserLiveInterfacePath($class, &$absoluteFilePath)
	{
		$relativeFilePath = $this->getRelativeFilePath($class);
		$absoluteFilePath = $this->paths->join($this->cache, 'interfaces', $relativeFilePath);
		return true;
	}

	private function autoloadTrait($class)
	{
		$this->getUserMockTraitPath($class, $path) ||
		$this->getUserLiveTraitPath($class, $path);

		if (!$this->filesystem->isFile($path)) {
			return false;
		}

		include $path;
		return true;
	}

	private function getUserMockTraitPath($class, &$absoluteFilePath)
	{
		if (!isset($this->mockClasses[$class])) {
			return false;
		}

		$relativeFilePath = $this->getRelativeFilePath($class);
		$absoluteFilePath = $this->paths->join($this->cache, 'traits', 'mock', $relativeFilePath);
		return true;
	}

	private function getUserLiveTraitPath($class, &$absoluteFilePath)
	{
		$relativeFilePath = $this->getRelativeFilePath($class);
		$absoluteFilePath = $this->paths->join($this->cache, 'traits', 'live', $relativeFilePath);
		return true;
	}

	// TODO: this is duplicated elsewhere
	private function getRelativeFilePath($class)
	{
		$parts = explode('\\', $class);
		return $this->paths->join($parts) . '.php';
	}
}
