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

namespace Lens;

use SpencerMortensen\Paths\Paths;

class Settings
{
	/** @var Paths */
	private $paths;

	/** @var Filesystem */
	private $filesystem;

	/** @var IniFile */
	private $file;

	/** @var array|null */
	private $input;

	public function __construct(Paths $paths, Filesystem $filesystem, $path)
	{
		$this->paths = $paths;
		$this->filesystem = $filesystem;
		$this->file = new IniFile($this->filesystem, $path);
		$this->input = $this->file->read();
		$this->data = self::getValidData($this->input);
	}

	public function __destruct()
	{
		if ($this->data !== $this->input) {
			$this->file->write($this->data);
		}
	}

	private static function getValidData(array $input = null)
	{
		$src = self::getNonEmptyString($input['src']);
		$autoload = self::getNonEmptyString($input['autoload']);
		$cache = self::getNonEmptyString($input['cache']);
		$checkForUpdates = self::getBoolean($input['checkForUpdates']);

		if ($checkForUpdates === null) {
			$checkForUpdates = true;
		}

		return array(
			'src' => $src,
			'autoload' => $autoload,
			'cache' => $cache,
			'checkForUpdates' => $checkForUpdates
		);
	}

	private static function getNonEmptyString(&$value)
	{
		if (!is_string($value) || (strlen($value) === 0)) {
			return null;
		}

		return $value;
	}

	private static function getBoolean(&$value)
	{
		if (!is_bool($value)) {
			return null;
		}

		return $value;
	}

	public function get($key)
	{
		return $this->data[$key];
	}

	public function set($key, $value)
	{
		$this->data[$key] = $value;
	}
}
