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

use Lens_0_0_56\Lens\Files\IniFile;

class Settings
{
	/** @var IniFile */
	private $file;

	/** @var array */
	private $initialValues;

	/** @var array */
	private $values;

	public function __construct(Filesystem $filesystem, $path)
	{
		$file = new IniFile($filesystem, $path);
		$file->read($input);
		$values = $this->getValues($input);

		$this->file = $file;
		$this->initialValues = $values;
		$this->values = $values;
	}

	private function getValues($input)
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
		return $this->values[$key];
	}

	public function set($key, $value)
	{
		$this->values[$key] = $value;
	}

	public function __destruct()
	{
		if ($this->values === $this->initialValues) {
			return;
		}

		$this->file->write($this->values);
	}
}
