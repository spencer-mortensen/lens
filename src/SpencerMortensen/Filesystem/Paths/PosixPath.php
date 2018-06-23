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

namespace _Lens\SpencerMortensen\Filesystem\Paths;

use InvalidArgumentException;
use _Lens\SpencerMortensen\Filesystem\AtomicPath;

class PosixPath implements Path
{
	/** @var string */
	private static $delimiter = '/';

	/** @var string|null */
	private $scheme;

	/** @var AtomicPath */
	private $path;

	public function __construct($scheme, AtomicPath $path)
	{
		$this->scheme = $scheme;
		$this->path = $path;
	}

	public static function fromString($input)
	{
		self::getSchemePath($input, $scheme, $path);

		return new self($scheme, $path);
	}

	private static function getSchemePath($input, &$scheme, &$path)
	{
		if (!is_string($input)) {
			throw new InvalidArgumentException();
		}

		$expression = '^(?:(?<scheme>[a-z]+)://)?(?<path>.*)$';

		if (!self::match($expression, $input, $match)) {
			throw new InvalidArgumentException();
		}

		$scheme = $match['scheme'];

		if (strlen($scheme) === 0) {
			$scheme = null;
		}

		$path = AtomicPath::fromString($match['path'], self::$delimiter);
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
		$output = '';

		if ($this->scheme !== null) {
			$output .= "{$this->scheme}://";
		}

		$output .= (string)$this->path;

		return $output;
	}

	public function getScheme()
	{
		return $this->scheme;
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
		return new self($this->scheme, $path);
	}

	public function add(...$arguments)
	{
		$objects = [];

		foreach ($arguments as $argument) {
			$objects[] = $this->getPathObject($argument);
		}

		$path = $this->path->add($objects);

		return new self($this->scheme, $path);
	}

	private function getPathObject($path)
	{
		// TODO: consider the scheme (what if it's a different scheme?):
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

		return new self($this->scheme, $relativePath);
	}
}
