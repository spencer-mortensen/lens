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

namespace Lens_0_0_56\SpencerMortensen\Filesystem\Paths;

use Lens_0_0_56\SpencerMortensen\Filesystem\AtomicPath;

class PosixPath implements Path
{
	/** @var string */
	private static $delimiter = '/';

	/** @var AtomicPath */
	private $path;

	public function __construct(AtomicPath $path)
	{
		$this->path = $path;
	}

	public static function fromString($input)
	{
		$path = AtomicPath::fromString($input, self::$delimiter);

		return new self($path);
	}

	public function __toString()
	{
		return (string)$this->path;
	}

	public function isAbsolute()
	{
		return $this->path->isAbsolute();
	}

	public function getAtoms()
	{
		return $this->path->getAtoms();
	}

	public function append(...$arguments)
	{
		$objects = array();

		foreach ($arguments as $argument) {
			$objects[] = $this->getPathObject($argument);
		}

		$path = $this->path->append($objects);

		return new self($path);
	}

	private function getPathObject($path)
	{
		if ($path instanceof self) {
			$isAbsolute = $path->isAbsolute();
			$atoms = $path->getAtoms();

			return new AtomicPath($isAbsolute, $atoms, self::$delimiter);
		}

		return AtomicPath::fromString($path, self::$delimiter);
	}

	public function contains($path)
	{
		$object = $this->getPathObject($path);

		return $this->path->contains($object);
	}

	public function getRelativePath($input)
	{
		$absolutePath = $this->getPathObject($input);
		$relativePath = $this->path->getRelativePath($absolutePath);

		return new self($relativePath);
	}
}
