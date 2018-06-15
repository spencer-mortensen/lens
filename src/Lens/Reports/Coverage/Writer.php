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

namespace Lens_0_0_56\Lens\Reports\Coverage;

use Lens_0_0_56\SpencerMortensen\Filesystem\Directory;
use Lens_0_0_56\SpencerMortensen\Filesystem\File;
use Lens_0_0_56\SpencerMortensen\Filesystem\Paths\Path;

class Writer
{
	public function write(Path $path, array $contents)
	{
		$directory = new Directory($path);
		$directory->delete();

		$this->writeDirectory($path, $contents);
	}

	// TODO: add this to the "Directory" class?
	private function writeDirectory(Path $directory, array $contents)
	{
		foreach ($contents as $childName => $childContents) {
			$childPath = $directory->add($directory, $childName);

			if (is_array($childContents)) {
				$this->writeDirectory($childPath, $childContents);
			} else {
				$this->writeFile($childPath, $childContents);
			}
		}
	}

	private function writeFile(Path $path, $contents)
	{
		$file = new File($path);
		$file->write($contents);
	}
}
