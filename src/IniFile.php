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

use ErrorException;

class IniFile
{
	/** @var Filesystem */
	private $filesystem;

	/** @var string */
	private $path;

	public function __construct(Filesystem $filesystem, $path)
	{
		$this->filesystem = $filesystem;
		$this->path = $path;
	}

	public function getPath()
	{
		return $this->path;
	}

	public function read()
	{
		$contents = $this->filesystem->read($this->path);

		if ($contents === null) {
			return null;
		}

		// TODO: use the "Exceptions" class:
		set_error_handler(array($this, 'errorHandler'));
		$settings = parse_ini_string($contents, false);
		restore_error_handler();

		foreach ($settings as &$setting) {
			if ($setting === '') {
				$setting = null;
			}
		}

		return $settings;
	}


	public function errorHandler($level, $message, $file, $line)
	{
		$code = 0;

		throw new ErrorException($message, $code, $level, $file, $line);
	}

	public function write(array $settings)
	{
		$contents = self::getIniText($settings);

		$this->filesystem->write($this->path, $contents);
	}

	private static function getIniText(array $values)
	{
		$lines = array();

		foreach ($values as $key => $value) {
			$valueText = self::getValueText($value);
			$lines[] = "{$key} = {$valueText}";
		}

		return implode(PHP_EOL, $lines) . PHP_EOL;
	}

	private static function getValueText($value)
	{
		if ($value === null) {
			return 'null';
		}

		return var_export($value, true);
	}
}
