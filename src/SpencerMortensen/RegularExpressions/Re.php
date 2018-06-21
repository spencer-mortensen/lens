<?php

/**
 * Copyright (C) 2017 Spencer Mortensen
 *
 * This file is part of regular-expressions.
 *
 * Regular-expressions is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Regular-expressions is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with regular-expressions. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Spencer Mortensen <spencer@lens.guide>
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL-3.0
 * @copyright 2017 Spencer Mortensen
 */

namespace Lens_0_0_56\SpencerMortensen\RegularExpressions;

class Re
{
	/** @var string */
	private static $delimiter = "\x03";

	public static function match($expression, $input, &$match = null, $flags = '')
	{
		$pattern = self::getPattern($expression, $flags);

		if (preg_match($pattern, $input, $parts) !== 1) {
			return false;
		}

		$match = self::getMatch($parts);
		return true;
	}

	private static function getPattern($expression, $flags)
	{
		return self::$delimiter . $expression . self::$delimiter . $flags . 'XDs';
	}

	private static function getMatch(array $parts)
	{
		if (count($parts) === 1) {
			return $parts[0];
		}

		return $parts;
	}

	public static function matches($expression, $input, &$matches, $flags = '')
	{
		$pattern = self::getPattern($expression, $flags);

		$count = preg_match_all($pattern, $input, $parts, PREG_SET_ORDER);

		if (!is_integer($count) || ($count < 1)) {
			return false;
		}

		foreach ($parts as $part) {
			$matches[] = self::getMatch($part);
		}

		return true;
	}

	public static function quote($literal)
	{
		return preg_quote($literal, self::$delimiter);
	}

	public static function replace($expression, $replacement, $input, $flags = '')
	{
		$pattern = self::getPattern($expression, $flags);

		return preg_replace($pattern, $replacement, $input);
	}

	public static function split($expression, $input, $flags = '')
	{
		$pattern = self::getPattern($expression, $flags);

		$matches = preg_split($pattern, $input);

		return self::getChunks($matches);
	}

	private static function getChunks(array $matches)
	{
		$chunks = [];

		foreach ($matches as $match) {
			if (0 < strlen($match)) {
				$chunks[] = $match;
			}
		}

		return $chunks;
	}
}
