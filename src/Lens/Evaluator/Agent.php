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

namespace Lens\Evaluator;

abstract class Agent
{
	const ACTION_RETURN = 0;
	const ACTION_THROW = 1;

	private static $isRecording = null;

	private static $calls = array();

	private static $script = array();

	public static function call($object, $method, array $arguments, array $action = null)
	{
		if (self::$isRecording === null) {
			return null;
		}

		self::$calls[] = array($object, $method, $arguments);

		if (self::$isRecording) {
			return self::record($action);
		}

		return self::play();
	}

	private static function record(array $action = null)
	{
		self::$script[] = $action;

		return null;
	}

	private static function play()
	{
		$action = array_shift(self::$script);

		if ($action === null) {
			return null;
		}

		return self::perform($action);
	}

	private static function perform($action)
	{
		list($type, $value) = $action;

		if ($type === self::ACTION_THROW) {
			// TODO: only a throwable can be thrown
			throw $value;
		}

		return $value;
	}

	public static function getCalls()
	{
		return self::$calls;
	}

	public static function getScript()
	{
		return serialize(self::$script);
	}

	public static function startRecording()
	{
		self::$isRecording = true;
	}

	public static function startPlaying($serializedScript)
	{
		self::$isRecording = false;
		self::$script = unserialize($serializedScript);
	}
}
