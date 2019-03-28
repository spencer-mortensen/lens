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

namespace _Lens\Lens\Phases\Execution;

use _Lens\SpencerMortensen\Filesystem\Filesystem;
use _Lens\SpencerMortensen\Filesystem\Path;
use _Lens\Lens\Phases\Finder;

class Autoloader
{
	/** @var Filesystem */
	private $filesystem;

	/** @var Finder */
	private $finder;

	public function __construct(Filesystem $filesystem, Finder $finder)
	{
		$this->filesystem = $filesystem;
		$this->finder = $finder;
	}

	public function enable()
	{
		spl_autoload_register([$this, 'autoloadClass']);
	}

	public function autoloadClass($name)
	{
		$livePath = $this->finder->getLivePath();
		$path = $this->finder->getClassPath($livePath, $name);
		$this->includeFile($path);
	}

	/*
	public function autoloadFunction($name)
	{
		$livePath = $this->finder->getLivePath();
		$path = $this->finder->getFunctionPath($livePath, $name);
		$this->includeFile($path);
	}
	*/

	private function includeFile(Path $path)
	{
		if (!$this->filesystem->isFile($path)) {
			return false;
		}

		// TODO: autoload any function dependencies...
		include (string)$path;
		return true;
	}
}
