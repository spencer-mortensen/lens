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

class Ini
{
	public function serialize(array $data)
	{
		$lines = array();

		foreach ($data as $key => $value) {
			if (is_array($value)) {
				$this->getArray($key, $value, $lines);
			} else {
				$this->getScalar($key, $value, $lines);
			}
		}

		return implode(PHP_EOL, $lines) . PHP_EOL;
	}

	private function getArray($name, array $values, array &$lines)
	{
		foreach ($values as $key => $value) {
			$serializedValue = $this->serializeScalarValue($value);

			$lines[] = "{$name}[{$key}] = {$serializedValue}";
		}
	}

	private function getScalar($key, $value, array &$lines)
	{
		$serializedValue = $this->serializeScalarValue($value);

		$lines[] = "{$key} = {$serializedValue}";
	}

	private function serializeScalarValue($value)
	{
		if (is_null($value)) {
			return 'null';
		}

		if (is_bool($value) || is_int($value) || is_float($value)) {
			return json_encode($value);
		}

		if (is_string($value)) {
			return var_export($value, true);
		}

		// TODO: throw exception
		return null;
	}

	public function deserialize($text)
	{
		// TODO: throw exception
		set_error_handler(function () {});
		$data = parse_ini_string($text, false, INI_SCANNER_TYPED);
		restore_error_handler();

		if (!is_array($data)) {
			$data = array();
		}

		return $data;
	}
}
