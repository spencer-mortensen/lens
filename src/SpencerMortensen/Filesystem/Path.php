<?php

/**
 * Copyright (C) 2018 Spencer Mortensen
 *
 * This file is part of Filesystem.
 *
 * Filesystem is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Filesystem is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with Filesystem. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Spencer Mortensen <spencer@lens.guide>
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL-3.0
 * @copyright 2018 Spencer Mortensen
 */

namespace _Lens\SpencerMortensen\Filesystem;

use InvalidArgumentException;
use _Lens\SpencerMortensen\Filesystem\Paths\PosixPath;
use _Lens\SpencerMortensen\Filesystem\Paths\WindowsPath;

abstract class Path
{
	/**
	 * @param string $input
	 * @return Path
	 */
	public static function fromString($input)
	{
		if (!is_string($input)) {
			throw new InvalidArgumentException();
		}

		$usePosix = (DIRECTORY_SEPARATOR !== '\\');

		if ($usePosix) {
			return PosixPath::fromString($input);
		}

		return WindowsPath::fromString($input);
	}

	/**
	 * @return string
	 */
	abstract public function __toString();

	/**
	 * @param array ...$paths
	 * @return Path
	 */
	abstract public function add(...$paths);

	/**
	 * @param string|Path $path
	 * @return bool
	 */
	abstract public function contains($path);

	/**
	 * @return bool
	 */
	abstract public function isAbsolute();

	/**
	 * @param string|Path $path
	 * @return Path
	 */
	abstract public function getRelativePath($path);

	/**
	 * @return array
	 */
	abstract public function getComponents();

	/**
	 * @param array $components
	 * @return Path
	 */
	abstract public function setComponents(array $components);
}
