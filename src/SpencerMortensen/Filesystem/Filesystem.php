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

namespace Lens_0_0_56\SpencerMortensen\Filesystem;

use ErrorException;
use Lens_0_0_56\SpencerMortensen\Filesystem\Exceptions\ResultException;
use Lens_0_0_56\SpencerMortensen\Filesystem\Paths\Path;
use Lens_0_0_56\SpencerMortensen\Filesystem\Paths\PosixPath;
use Lens_0_0_56\SpencerMortensen\Filesystem\Paths\WindowsPath;

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
		set_error_handler(__CLASS__ . '::onError');
		$cwd = getcwd();
		restore_error_handler();

		if (!is_string($cwd)) {
			throw new ResultException('getcwd', [], $cwd);
		}

		return $this->getPath($cwd);
	}

	public function exists(Path $path)
	{
		$pathString = (string)$path;

		set_error_handler(__CLASS__ . '::onError');
		$exists = file_exists($pathString);
		restore_error_handler();

		return $exists;
	}

	public function isDirectory(Path $path)
	{
		$pathString = (string)$path;

		set_error_handler(__CLASS__ . '::onError');
		$isDirectory = is_dir($pathString);
		restore_error_handler();

		return $isDirectory;
	}

	public function isFile(Path $path)
	{
		$pathString = (string)$path;

		set_error_handler(__CLASS__ . '::onError');
		$isFile = is_file($pathString);
		restore_error_handler();

		return $isFile;
	}

	protected static function onError($level, $message, $file, $line)
	{
		$message = trim($message);
		$code = null;

		throw new ErrorException($message, $code, $level, $file, $line);
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
