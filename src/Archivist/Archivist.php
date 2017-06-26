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

namespace TestPhp\Archivist;

use TestPhp\Archivist\Archives\ObjectArchive;
use TestPhp\Archivist\Archives\ResourceArchive;

class Archivist
{
	public static function archive($value)
	{
		$type = gettype($value);

		switch ($type) {
			default:
				return $value;

			case 'array':
				return self::archiveArray($value);

			case 'object':
				return new ObjectArchive($value);

			case 'resource':
				return new ResourceArchive($value);
		}
	}

	private static function archiveArray(array $array)
	{
		foreach ($array as &$value) {
			$value = self::archive($value);
		}

		return $array;
	}
}
