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
use _Lens\SpencerMortensen\Filesystem\CorePath;
use _Lens\SpencerMortensen\Filesystem\Path;

class WindowsPath extends Path
{
	/** @var string */
	private static $delimiter = '\\';

	/** @var string|null */
	private $scheme;

	/** @var string|null */
	private $prefix;

	/** @var CorePath */
	private $path;

	public function __construct($scheme, $prefix, CorePath $path)
	{
		$this->scheme = $scheme;
		$this->prefix = $prefix;
		$this->path = $path;
	}

	public static function fromString($input)
	{
		if (!self::parse($input, $scheme, $prefix, $path)) {
			throw new InvalidArgumentException();
		}

		return new self($scheme, $prefix, $path);
	}

	/**
	 * @link https://doc.rust-lang.org/std/path/enum.Prefix.html
	 * @param mixed $input
	 * @param string|null $scheme
	 * @param string|null $prefix
	 * @param CorePath $corePath
	 * @return bool
	 */
	private static function parse($input, &$scheme, &$prefix, &$corePath)
	{
		if (!is_string($input)) {
			return false;
		}

		$expression = '^(?:(?<scheme>[a-z]+)://)?(?:(?<prefix>[a-zA-Z]):)?(?<path>.*)$';

		self::match($expression, $input, $match);

		$scheme = self::getNonEmptyString($match['scheme']);
		$prefix = self::getNonEmptyString($match['prefix']);

		$path = str_replace('/', self::$delimiter, $match['path']);
		$corePath = CorePath::fromString($path, self::$delimiter);

		return true;
	}

	private static function match($expression, $input, array &$match = null)
	{
		$delimiter = "\x03";
		$flags = 'XDs';
		$pattern = $delimiter . $expression . $delimiter . $flags;

		return preg_match($pattern, $input, $match) === 1;
	}

	private static function getNonEmptyString($string)
	{
		if (strlen($string) === 0) {
			return null;
		}

		return $string;
	}

	public function __toString()
	{
		$path = (string)$this->path;

		if ($this->prefix !== null) {
			$path = "{$this->prefix}:{$path}";
		}

		if ($this->scheme !== null) {
			$path = "{$this->scheme}://{$path}";
		}

		return $path;
	}

	public function getScheme()
	{
		return $this->scheme;
	}

	public function getPrefix()
	{
		return $this->prefix;
	}

	public function isAbsolute()
	{
		return $this->path->isAbsolute();
	}

	public function getComponents()
	{
		return $this->path->getComponents();
	}

	public function setComponents(array $components)
	{
		$isAbsolute = $this->path->isAbsolute();
		$path = new CorePath($isAbsolute, $components, self::$delimiter);
		return new self($this->scheme, $this->prefix, $path);
	}

	public function add(...$arguments)
	{
		$objects = [];

		foreach ($arguments as $argument) {
			// TODO: check the prefix
			$objects[] = $this->getPathObject($argument);
		}

		$path = $this->path->add($objects);

		return new self($this->scheme, $this->prefix, $path);
	}

	private function getPathObject($path)
	{
		if ($path instanceof self) {
			$isAbsolute = $path->isAbsolute();
			$components = $path->getComponents();

			return new CorePath($isAbsolute, $components, self::$delimiter);
		}

		return CorePath::fromString($path, self::$delimiter);
	}

	// TODO: check the prefix
	public function contains($path)
	{
		$object = $this->getPathObject($path);

		return $this->path->contains($object);
	}

	public function getRelativePath($input)
	{
		$absolutePath = $this->getPathObject($input);
		$relativePath = $this->path->getRelativePath($absolutePath);

		return new self($this->scheme, $this->prefix, $relativePath);
	}
}
