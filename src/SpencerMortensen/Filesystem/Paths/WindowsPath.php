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

use InvalidArgumentException;
use Lens_0_0_56\SpencerMortensen\Filesystem\AtomicPath;

class WindowsPath implements Path
{
	/** @var string */
	private static $delimiter = '\\';

	/** @var string|null */
	private $drive;

	/** @var AtomicPath */
	private $path;

	public function __construct($drive, AtomicPath $path)
	{
		$this->drive = $drive;
		$this->path = $path;
	}

	public static function fromString($input)
	{
		if (!self::getDrivePath($input, $drive, $path)) {
			throw new InvalidArgumentException();
		}

		$path = AtomicPath::fromString($path, self::$delimiter);

		return new self($drive, $path);
	}

	private static function getDrivePath($input, &$drive, &$path)
	{
		if (!is_string($input)) {
			return false;
		}

		$expression = '^(?:(?<drive>[a-zA-Z]):)?(?<path>.*)$';

		if (!self::match($expression, $input, $match)) {
			return false;
		}

		$drive = $match['drive'];

		if (strlen($drive) === 0) {
			$drive = null;
		}

		$path = str_replace('/', self::$delimiter, $match['path']);
		return true;
	}

	private static function match($expression, $input, array &$match = null)
	{
		$delimiter = "\x03";
		$flags = 'XDs';
		$pattern = $delimiter . $expression . $delimiter . $flags;

		return preg_match($pattern, $input, $match) === 1;
	}

	public function __toString()
	{
		$path = (string)$this->path;

		if ($this->drive !== null) {
			$path = "{$this->drive}:{$path}";
		}

		return $path;
	}

	public function getDrive()
	{
		return $this->drive;
	}

	public function isAbsolute()
	{
		return $this->path->isAbsolute();
	}

	public function getAtoms()
	{
		return $this->path->getAtoms();
	}

	public function setAtoms(array $atoms)
	{
		$isAbsolute = $this->path->isAbsolute();
		$path = new AtomicPath($isAbsolute, $atoms, self::$delimiter);
		return new self($this->drive, $path);
	}

	public function add(...$arguments)
	{
		$drive = $this->drive;

		$objects = [];

		foreach ($arguments as $argument) {
			// TODO: check the drive components
			$objects[] = $this->getPathObject($argument);
		}

		$path = $this->path->add($objects);

		return new self($drive, $path);
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

	// TODO: check the drive
	public function contains($path)
	{
		$object = $this->getPathObject($path);

		return $this->path->contains($object);
	}

	public function getRelativePath($input)
	{
		$drive = $this->drive;

		$absolutePath = $this->getPathObject($input);
		$relativePath = $this->path->getRelativePath($absolutePath);

		return new self($drive, $relativePath);
	}
}
