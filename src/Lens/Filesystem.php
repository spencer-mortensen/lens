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

use Closure;
use ErrorException;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

// TODO: move this to an external library
class Filesystem
{
	const TYPE_DIRECTORY = 1;
	const TYPE_FILE = 2;

	/** @var integer */
	private $directoryPermissions = 0775;

	/** @var integer */
	private $filePermissions = 0664;

	/** @var Closure */
	private $errorHandler;

	public function __construct()
	{
		// Suppress built-in PHP warnings that are inappropriately generated
		// under normal operating conditions
		$this->errorHandler = function () {};
	}

	public function read($path)
	{
		set_error_handler($this->errorHandler);

		$contents = self::getString(file_get_contents($path));

		restore_error_handler();
		return $contents;
	}

	public function write($path, $contents)
	{
		$this->prepareFileDestination($path);
		$this->atomicWriteFile($path, $contents);

		return true;
	}

	public function scan($directoryPath)
	{
		$contents = array();

		// TODO: error handling
		$directory = opendir($directoryPath);

		for ($childName = readdir($directory); $childName !== false; $childName = readdir($directory)) {
			if (($childName === '.') || ($childName === '..')) {
				continue;
			}

			$contents[] = $childName;
		}

		closedir($directory);

		return $contents;
	}

	private function prepareFileDestination($path)
	{
		if (file_exists($path)) {
			if (!is_file($path)) {
				// TODO: improve this exception:
				$pathName = var_export($path, true);
				throw new ErrorException("{$pathName} must be a file");
			}
		} else {
			$parent = dirname($path);

			if (file_exists($parent)) {
				if (!is_dir($parent)) {
					// TODO: improve this exception:
					$parentName = var_export($parent, true);
					throw new ErrorException("{$parentName} must be a directory");
				}
			} else {
				mkdir($parent, $this->directoryPermissions, true);
			}
		}
	}

	// TODO: If the temporary directory and the final destination are in different filesystems, then this won't be atomic at all:
	//       Should we choose a temporary file that is in the same directory as the original file?
	private function atomicWriteFile($path, $contents)
	{
		$temporaryPath = tempnam(null, null);

		$totalBytes = strlen($contents);
		$writtenBytes = file_put_contents($temporaryPath, $contents);

		if ($writtenBytes !== $totalBytes) {
			unlink($temporaryPath);

			// TODO: improve this exception:
			throw new ErrorException("Unable to write the entire file contents");
		}

		rename($temporaryPath, $path);
		chmod($path, $this->filePermissions);
	}

	public function delete($path)
	{
		if (!is_string($path) || (strlen($path) === 0)) {
			return false;
		}

		if (!file_exists($path)) {
			return true;
		}

		if (is_dir($path)) {
			return $this->deleteDirectory($path);
		}

		return $this->deleteFile($path);
	}

	public function createEmptyDirectory($path)
	{
		set_error_handler($this->errorHandler);
		$result = mkdir($path, $this->directoryPermissions, true);
		restore_error_handler();

		return $result;
	}

	public function deleteEmptyDirectory($path)
	{
		set_error_handler($this->errorHandler);
		$result = rmdir($path);
		restore_error_handler();

		return $result;
	}

	private function deleteFile($path)
	{
		return unlink($path);
	}

	private function deleteDirectory($directoryPath)
	{
		$files = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($directoryPath, RecursiveDirectoryIterator::SKIP_DOTS),
			RecursiveIteratorIterator::CHILD_FIRST
		);

		foreach ($files as $file) {
			$isDirectory = $file->isDir();
			$childPath = $file->getRealPath();

			if ($isDirectory) {
				$isDeleted = rmdir($childPath);
			} else {
				$isDeleted = unlink($childPath);
			}

			if (!$isDeleted) {
				return false;
			}
		}

		return rmdir($directoryPath);
	}

	public function rename($oldPath, $newPath)
	{
		// TODO:
		// $oldPath might not exist:
		// If renaming a file and newname exists, it will be overwritten:
		// If renaming a directory and newname exists, this function will emit a warning:
		return rename($oldPath, $newPath);
	}

	public function search($expression)
	{
		return glob($expression);
	}

	public function isDirectory($path)
	{
		return is_dir($path);
	}

	public function isFile($path)
	{
		return is_file($path);
	}

	public function getCurrentDirectory()
	{
		return self::getString(getcwd());
	}

	public function getModifiedTime($path)
	{
		return filemtime($path);
	}

	public function getAbsolutePath($path)
	{
		return self::getString(realpath($path));
	}

	private static function getString($value)
	{
		if (is_string($value)) {
			return $value;
		}

		return null;
	}
}
