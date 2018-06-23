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

namespace _Lens\Lens\Tests;

use _Lens\SpencerMortensen\Filesystem\Directory;
use _Lens\SpencerMortensen\Filesystem\File;
use _Lens\SpencerMortensen\Filesystem\Filesystem;
use _Lens\SpencerMortensen\Filesystem\Paths\Path;

class Browser
{
	/** @var Filesystem */
	private $filesystem;

	/** @var Path[] */
	private $files;

	public function __construct(Filesystem $filesystem)
	{
		$this->filesystem = $filesystem;
		$this->files = [];
	}

	public function browse(array $paths)
	{
		$output = [];

		$children = $this->getChildren($paths);

		$this->readChildren($children, $output);

		return $output;
	}

	private function getChildren(array $paths)
	{
		$children = [];

		foreach ($paths as $path) {
			$children[] = $this->getChild($path);
		}

		return $children;
	}

	private function getChild(Path $path)
	{
		if ($this->filesystem->isDirectory($path)) {
			return new Directory($path);
		}

		return new File($path);
	}

	private function readChildren(array $children, array &$files)
	{
		foreach ($children as $child) {
			if ($child instanceof Directory) {
				$this->readChildren($child->read(), $files);
			} else {
				$this->readFile($child, $files);
			}
		}
	}

	private function readFile(File $file, array &$files)
	{
		$path = $file->getPath();

		if ($this->isTestsFile($path)) {
			$files[(string)$path] = $file->read();
		}
	}

	// TODO: this is duplicated elsewhere
	private function isTestsFile($path)
	{
		return substr($path, -4) === '.php';
	}
}
