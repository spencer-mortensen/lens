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

namespace Lens_0_0_57\Lens\Tests;

use Lens_0_0_57\SpencerMortensen\Filesystem\Filesystem;
use Lens_0_0_57\SpencerMortensen\Filesystem\Paths\Path;
use Lens_0_0_57\Lens\SourcePaths;

class Autoloader
{
	/** @var Filesystem */
	private $filesystem;

	/** @var Path */
	private $core;

	/** @var Path */
	private $cache;

	/** @var string[] */
	private $mockClasses;

	/** @var SourcePaths */
	private $sourcePaths;

	/** @var integer */
	private $depth;

	/** @var integer|null */
	private $mockDepth;

	public function __construct(Filesystem $filesystem, Path $core, Path $cache, array $mockClasses)
	{
		$this->filesystem = $filesystem;
		$this->core = $core;
		$this->cache = $cache;
		$this->mockClasses = $mockClasses;
		$this->sourcePaths = new SourcePaths($filesystem, $core, $cache);
		$this->depth = 0;
		$this->mockDepth = null;
	}

	public function enable()
	{
		define('LENS_CORE_DIRECTORY', (string)$this->core);
		define('LENS_CACHE_DIRECTORY', (string)$this->cache);

		spl_autoload_register([$this, 'autoload']);
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
		$absoluteFilePath = $this->sourcePaths->getCoreClassPath($class);
		return true;
	}

	private function getUserMockClassPath($class, &$absoluteFilePath)
	{
		if (!isset($this->mockClasses[$class])) {
			return false;
		}

		$absoluteFilePath = $this->sourcePaths->getMockClassPath($class);
		return true;
	}

	private function getUserLiveClassPath($class, &$absoluteFilePath)
	{
		$absoluteFilePath = $this->sourcePaths->getLiveClassPath($class);
		return true;
	}

	private function autoloadInterface($interface)
	{
		$path = $this->sourcePaths->getInterfacePath($interface);

		if (!$this->filesystem->isFile($path)) {
			return false;
		}

		include $path;
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

	private function getUserMockTraitPath($trait, &$absoluteFilePath)
	{
		if (!isset($this->mockClasses[$trait])) {
			return false;
		}

		$absoluteFilePath = $this->sourcePaths->getMockTraitPath($trait);
		return true;
	}

	private function getUserLiveTraitPath($trait, &$absoluteFilePath)
	{
		$absoluteFilePath = $this->sourcePaths->getLiveTraitPath($trait);
		return true;
	}
}
