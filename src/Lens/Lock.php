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

class Lock
{
	/** @var resource */
	private $file;

	public function __construct($path)
	{
		$this->open($path);
	}

	public function __destruct()
	{
		$this->close();
	}

	public function unlock()
	{
		return $this->lock(LOCK_UN, true);
	}

	public function getShared($wait = true)
	{
		// TODO: throw an exception if no lock can be obtained while waiting
		return $this->lock(LOCK_SH, $wait);
	}

	public function getExclusive($wait = true)
	{
		// TODO: throw an exception if no lock can be obtained while waiting
		return $this->lock(LOCK_EX, $wait);
	}

	private function open($path)
	{
		set_error_handler(function () {});

		$parent = dirname($path);

		if (!file_exists($parent)) {
			mkdir($parent, 0775, true);
		}

		restore_error_handler();

		$this->file = fopen($path, 'c');
	}

	private function lock($mode, $wait)
	{
		if (!is_resource($this->file)) {
			return false;
		}

		if (!$wait) {
			$mode |= LOCK_NB;
		}

		// TODO: flock will always return false on a FAT filesystem
		// TODO: on some operating systems, flock only offers protection between processes;
		//       when using a multithreaded server API--e.g. ISAPI--the parallel threads might not be protected
		return flock($this->file, $mode);
	}

	private function close()
	{
		if (!is_resource($this->file)) {
			return;
		}

		flock($this->file, LOCK_UN);
		fclose($this->file);
	}
}
