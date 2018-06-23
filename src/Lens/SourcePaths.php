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

namespace Lens_0_0_57\Lens;

use Lens_0_0_57\SpencerMortensen\Filesystem\Filesystem;
use Lens_0_0_57\SpencerMortensen\Filesystem\Paths\Path;

class SourcePaths
{
	/** @var Filesystem */
	private $filesystem;

	/** @var Path */
	private $core;

	/** @var Path */
	private $cache;

	public function __construct(Filesystem $filesystem, Path $core, Path $cache)
	{
		$this->filesystem = $filesystem;
		$this->core = $core;
		$this->cache = $cache;
	}

	public function getCoreClassPath($class)
	{
		$relativePath = $this->getRelativeFilePath($class);
		return $this->core->add('classes', 'mock', $relativePath);
	}

	public function getLiveClassPath($class)
	{
		$relativePath = $this->getRelativeFilePath($class);
		return $this->cache->add('classes', 'live', $relativePath);
	}

	public function getMockClassPath($class)
	{
		$relativePath = $this->getRelativeFilePath($class);
		return $this->cache->add('classes', 'mock', $relativePath);
	}

	public function getLiveFunctionPath($function)
	{
		$relativePath = $this->getRelativeFilePath($function);
		return $this->cache->add('functions', 'live', $relativePath);
	}

	public function getMockFunctionPath($function)
	{
		$relativePath = $this->getRelativeFilePath($function);
		return $this->cache->add('functions', 'mock', $relativePath);
	}

	public function getInterfacePath($interface)
	{
		$relativePath = $this->getRelativeFilePath($interface);
		return $this->cache->add('interfaces', $relativePath);
	}

	public function getLiveTraitPath($trait)
	{
		$relativePath = $this->getRelativeFilePath($trait);
		return $this->cache->add('traits', 'live', $relativePath);
	}

	public function getMockTraitPath($trait)
	{
		$relativePath = $this->getRelativeFilePath($trait);
		return $this->cache->add('traits', 'mock', $relativePath);
	}

	private function getRelativeFilePath($namespacePath)
	{
		$path = $this->filesystem->getPath('.');
		$atoms = explode('\\', "{$namespacePath}.php");
		return $path->setAtoms($atoms);
	}
}
