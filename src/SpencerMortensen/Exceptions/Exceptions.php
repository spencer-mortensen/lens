<?php

/**
 * Copyright (C) 2017 Spencer Mortensen
 *
 * This file is part of Exceptions.
 *
 * Exceptions is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Exceptions is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with Exceptions. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Spencer Mortensen <spencer@lens.guide>
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL-3.0
 * @copyright 2017 Spencer Mortensen
 */

namespace _Lens\SpencerMortensen\Exceptions;

use ErrorException;

class Exceptions
{
	private static $handlers;

	private static $errorReportingLevel;

	public static function setHandler($handler)
	{
		if (self::$handlers === null) {
			$onShutdown = [__CLASS__, 'onShutdown'];
			register_shutdown_function($onShutdown);

			$onException = [__CLASS__, 'onException'];
			set_exception_handler($onException);

			self::$handlers = [];
		}

		if (count(self::$handlers) === 0) {
			self::$errorReportingLevel = error_reporting();
			error_reporting(0);
		}

		self::$handlers[] = $handler;
	}

	public static function unsetHandler()
	{
		if (count(self::$handlers) === 0) {
			return;
		}

		array_pop(self::$handlers);

		if ((count(self::$handlers) === 0) && (self::$errorReportingLevel !== null)) {
			error_reporting(self::$errorReportingLevel);
			self::$errorReportingLevel = null;
		}
	}

	public static function on($handler = null)
	{
		if ($handler === null) {
			$handler = [__CLASS__, 'onError'];
		}

		set_error_handler($handler);
	}

	public static function off()
	{
		restore_error_handler();
	}

	public static function onError($level, $message, $file, $line)
	{
		$message = trim($message);
		$code = null;

		throw new ErrorException($message, $code, $level, $file, $line);
	}

	public static function onException($exception)
	{
		if (count(self::$handlers) === 0) {
			return;
		}

		for ($i = count(self::$handlers) - 1; 0 <= $i; --$i) {
			call_user_func(self::$handlers[$i], $exception);
		}
	}

	public static function onShutdown()
	{
		$error = error_get_last();

		if ($error === null) {
			return;
		}

		$message = trim($error['message']);
		$code = null;
		$level = $error['type'];
		$file = $error['file'];
		$line = $error['line'];

		$exception = new ErrorException($message, $code, $level, $file, $line);

		self::onException($exception);
	}
}
