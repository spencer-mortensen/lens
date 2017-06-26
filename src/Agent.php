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

use TestPhp\Packager\Packager;

abstract class Agent
{
	private static $isRecording = true;

	private static $calls = array();

	private static $script = array();

	/** @var Mapper */
	private static $mapper;

	public static function call(array $callable, array $arguments, array $result = null)
	{
		self::$calls[] = array($callable, $arguments);

		if (self::$isRecording) {
			return self::record($callable, $arguments, $result);
		}

		return self::play($callable, $arguments);
	}

	private static function record(array $callable, array $arguments, array $result = null)
	{
		if ($result === null) {
			$result = array(0, null);
		}

		self::$script[] = Packager::package(array($callable, $arguments, $result));

		return null;
	}

	private static function play(array $unpackedCallable, array $unpackedArguments)
	{
		$call = array_shift(self::$script);

		if ($call === null) {
			return null;
		}

		list($packedCallable, $packedArguments, $packedResult) = $call;

		self::$mapper->associate($packedCallable, $unpackedCallable);
		self::$mapper->associate($packedArguments, $unpackedArguments);
		$unpackedResult = self::$mapper->unpack($packedResult);

		return self::perform($unpackedResult);
	}

	private static function perform($result)
	{
		list($action, $value) = $result;

		if ($action === 1) {
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
		return self::$script;
	}

	public static function setScript($scriptSerialized)
	{
		self::$script = unserialize($scriptSerialized);
		self::$isRecording = false;
		self::$mapper = new Mapper();
	}
}
