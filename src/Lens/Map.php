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

use Lens_0_0_56\Lens\Files\JsonFile;
use Lens_0_0_56\SpencerMortensen\Paths\Paths;

class Map
{
	/** @var Paths */
	private $paths;

	/** @var Filesystem */
	private $filesystem;

	/** @var string */
	private $baseDirectory;

	/** @var JsonFile */
	private $file;

	/** @var array */
	private $map;

	/** @var array|null */
	private $files;

	/** @var array|null */
	private $modified;

	/** @var array|null */
	private $classes;

	/** @var array|null */
	private $functions;

	public function __construct(Paths $paths, Filesystem $filesystem, $baseDirectory, $filePath)
	{
		$file = new JsonFile($filesystem, $filePath);
		$file->read($map);

		if (!is_array($map)) {
			$map = array();
		}

		$this->paths = $paths;
		$this->filesystem = $filesystem;
		$this->baseDirectory = $baseDirectory;
		$this->file = $file;

		$this->map = $map;
		$this->files = &$this->map['files'];
		$this->modified = &$this->map['modified'];
		$this->classes = &$this->map['classes'];
		$this->functions = &$this->map['functions'];
	}

	public function getModifiedTime($absolutePath)
	{
		$files = $this->getFiles($absolutePath);

		return $this->getModifiedTimeInternal($files);
	}

	public function setModifiedTime($absolutePath, $time)
	{
		$index = $this->getFiles($absolutePath);

		$this->modified[$index] = $time;

		echo "map: ", json_encode($this->map, JSON_PRETTY_PRINT), "\n";
	}

	private function getFiles($absolutePath)
	{
		$relativePath = $this->paths->getRelativePath($this->baseDirectory, $absolutePath);

		$data = $this->paths->deserialize($relativePath);
		$atoms = $data->getAtoms();

		$files = &$this->files;

		foreach ($atoms as $atom) {
			if (!is_array($files)) {
				// TODO: exception:
				return null;
			}

			$files = &$files[$atom];
		}

		return $files;
	}

	private function getModifiedTimeInternal($input)
	{
		if (is_array($input)) {
			return array_map(array($this, 'getModifiedTimeInternal'), $input);
		}

		if (is_integer($input) && isset($this->modified[$input])) {
			return $this->modified[$input];
		}

		return null;
	}
}
