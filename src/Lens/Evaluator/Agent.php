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

namespace Lens_0_0_56\Lens\Evaluator;

abstract class Agent
{
	/** @var null|string */
	private static $contextPhp;

	/** @var null|array */
	private static $script;

	/** @var null|array */
	private static $calls;

	public static function start($contextPhp, array $script)
	{
		self::$contextPhp = $contextPhp;
		self::$script = $script;
		self::$calls = array();
	}

	public static function call($context, $method, array $arguments)
	{
		self::record($context, $method, $arguments);

		return self::play();
	}

	private static function record($object, $method, array $arguments)
	{
		self::$calls[] = array($object, $method, $arguments);
	}

	private static function play()
	{
		$code = array_shift(self::$script);

		if ($code === null) {
			return null;
		}

		if (self::$contextPhp !== null) {
			$code = self::$contextPhp . "\n" . $code;
		}

		return $code;
	}

	public static function stop()
	{
		return self::$calls;
	}
}
