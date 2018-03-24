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

namespace Lens_0_0_56\Lens\Files;

use Lens_0_0_56\Lens\Filesystem;

class IniFile implements File
{
	/** @var TextFile */
	private $file;

	public function __construct(Filesystem $filesystem, $path)
	{
		$this->file = new TextFile($filesystem, $path);
	}

	public function read(&$value)
	{
		if (!$this->file->read($contents)) {
			return false;
		}

		set_error_handler(function () {});
		$settings = parse_ini_string($contents, false, INI_SCANNER_TYPED);
		restore_error_handler();

		if (!is_array($settings)) {
			return false;
		}

		foreach ($settings as &$setting) {
			if ($setting === '') {
				$setting = null;
			}
		}

		$value = $settings;
		return true;
	}

	public function write($value)
	{
		if (!is_array($value)) {
			return false;
		}

		$contents = self::getIniText($value);

		return $this->file->write($contents);
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

	public function getPath()
	{
		return $this->file->getPath();
	}
}
