<?php

/**
 * Copyright (C) 2017 Spencer Mortensen
 *
 * This file is part of testphp.
 *
 * Testphp is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Testphp is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with testphp. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Spencer Mortensen <spencer@testphp.org>
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL-3.0
 * @copyright 2017 Spencer Mortensen
 */

namespace TestPhp;

class Filesystem
{
	public function read($path)
	{
		if (is_dir($path)) {
			return $this->readDirectory($path);
		}

		return $this->readFile($path);
	}

	private function readDirectory($path)
	{
		$files = @scandir($path, SCANDIR_SORT_NONE);

		if ($files === false) {
			// TODO: throw exception
			return null;
		}

		$contents = array();

		foreach ($files as $file) {
			if (($file === '.') || ($file === '..')) {
				continue;
			}

			$childPath = "{$path}/{$file}";
			$contents[$file] = $this->read($childPath);
		}

		return $contents;
	}

	private function readFile($path)
	{
		$contents = @file_get_contents($path);

		if (!is_string($contents)) {
			$contents = null;
		}

		return $contents;
	}

	public function write($path, $contents)
	{
		return $this->writeFile($path, $contents);
	}

	private function writeFile($path, $contents)
	{
		$bytesWritten = @file_put_contents($path, $contents);

		if ($bytesWritten === false) {
			$parentDirectory = dirname($path);

			if (!@mkdir($parentDirectory, 0777, true)) {
				return false;
			}

			$bytesWritten = @file_put_contents($path, $contents);
		}

		return $bytesWritten === strlen($contents);
	}
}
