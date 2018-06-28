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

use ErrorException;
use _Lens\SpencerMortensen\Exceptions\Exceptions;
use _Lens\SpencerMortensen\Exceptions\ResultException;
use _Lens\SpencerMortensen\Filesystem\Paths\Path;
use _Lens\SpencerMortensen\Filesystem\Paths\PosixPath;
use _Lens\SpencerMortensen\Filesystem\Paths\WindowsPath;

class Filesystem
{
	/** @var bool */
	private $usePosix;

	public function __construct($usePosix = null)
	{
		if (!is_bool($usePosix)) {
			$usePosix = (DIRECTORY_SEPARATOR !== '\\');
		}

		$this->usePosix = $usePosix;
	}

	/**
	 * @param $string
	 * @return Path
	 */
	public function getPath($string)
	{
		if ($this->usePosix) {
			return PosixPath::fromString($string);
		}

		return WindowsPath::fromString($string);
	}

	/**
	 * @return Path
	 * @throws ResultException
	 */
	public function getCurrentDirectoryPath()
	{
		try {
			Exceptions::on();
			$cwd = getcwd();
		} finally {
			Exceptions::off();
		}

		if (!is_string($cwd)) {
			throw new ResultException('getcwd', [], $cwd);
		}

		return $this->getPath($cwd);
	}

	public function exists(Path $path)
	{
		$pathString = (string)$path;

		try {
			Exceptions::on();
			return file_exists($pathString);
		} finally {
			Exceptions::off();
		}
	}

	public function isDirectory(Path $path)
	{
		$pathString = (string)$path;

		try {
			Exceptions::on();
			return is_dir($pathString);
		} finally {
			Exceptions::off();
		}
	}

	public function isFile(Path $path)
	{
		$pathString = (string)$path;

		try {
			Exceptions::on();
			return is_file($pathString);
		} finally {
			Exceptions::off();
		}
	}

	/// FLUFF: ///

	public function getDirectory($string)
	{
		$path = $this->getPath($string);

		return new Directory($path);
	}

	public function getFile($string)
	{
		$path = $this->getPath($string);

		return new File($path);
	}

	public function getCurrentDirectory()
	{
		$path = $this->getCurrentDirectoryPath();

		return new Directory($path);
	}
}
