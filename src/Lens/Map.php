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
	/** @var JsonFile */
	private $file;

	/** @var Lock */
	private $lock;

	public function __construct(Paths $paths, Filesystem $filesystem, $filePath)
	{
		$file = new JsonFile($filesystem, $filePath);

		$lockPath = $this->getLockPath($paths, $filePath);
		$lock = new Lock($lockPath);

		$this->file = $file;
		$this->lock = $lock;
	}

	private function getLockPath(Paths $paths, $filePath)
	{
		$lockDirectory = $this->getLockDirectory($paths);
		$relativePath = $this->getRelativeLockPath($paths, $filePath);
		return $paths->join($lockDirectory, 'file', $relativePath);
	}

	private function getLockDirectory(Paths $paths)
	{
		$temporaryDirectory = sys_get_temp_dir();

		return $paths->join($temporaryDirectory, 'locks');
	}

	private function getRelativeLockPath(Paths $paths, $filePath)
	{
		$filePath = str_replace('_', '__', $filePath);

		$data = $paths->deserialize($filePath);
		$atoms = $data->getAtoms();

		return implode('_', $atoms) . '.lock';
	}

	public function get($key, &$value)
	{
		$this->lock->getShared();

		$success = $this->read($key, $value);

		$this->lock->unlock();

		return $success;
	}

	private function read($key, &$value)
	{
		if (!$this->file->read($values)) {
			return false;
		}

		if (!is_array($values)) {
			return false;
		}

		if (!array_key_exists($key, $values)) {
			return false;
		}

		$value = $values[$key];
		return true;
	}

	public function set($key, $value)
	{
		$this->lock->getExclusive();

		$success = $this->write($key, $value);

		$this->lock->unlock();

		return $success;
	}

	private function write($key, $value)
	{
		if (!$this->file->read($values)) {
			$values = array();
		}

		if (!is_array($values)) {
			// TODO: throw an exception instead
			return false;
		}

		$values[$key] = $value;

		return $this->file->write($values);
	}
}
