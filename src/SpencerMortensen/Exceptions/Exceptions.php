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
use InvalidArgumentException;

class Exceptions
{
	/** @var integer|null */
	private static $depth;

	/** @var integer|null */
	private static $errorReportingLevel;

	/** @var array|null */
	private static $fatalErrorHandlers;

	public static function on($onFatalError = null, $onError = null)
	{
		$onFatalError = self::getOptionalHandler($onFatalError);
		$onError = self::getOptionalHandler($onError);

		if ($onError === null) {
			$onError = __CLASS__ . '::errorHandler';
		}

		self::setup();

		if (self::$depth === 0) {
			self::$errorReportingLevel = error_reporting();
			error_reporting(0);
		}

		set_error_handler($onError);
		self::$fatalErrorHandlers[] = $onFatalError;

		++self::$depth;
	}

	private static function getOptionalHandler($handler)
	{
		if ($handler === null) {
			return null;
		}

		if (is_callable($handler)) {
			return $handler;
		}

		throw self::invalidHandlerException($handler);
	}

	private static function invalidHandlerException($handler)
	{
		$handlerText = var_export($handler, true);

		return new InvalidArgumentException("The provided handler ({$handlerText}) is not a valid callable.");
	}

	public static function errorHandler($level, $message, $file, $line)
	{
		$message = trim($message);
		$code = null;

		throw new ErrorException($message, $code, $level, $file, $line);
	}

	private static function setup()
	{
		if (self::$depth === null) {
			self::$depth = 0;
			self::$fatalErrorHandlers = array();
			register_shutdown_function(__CLASS__ . '::fatalErrorHandler');
		}
	}

	public static function fatalErrorHandler()
	{
		$exception = self::getErrorException();

		if ($exception === null) {
			return;
		}

		for ($i = count(self::$fatalErrorHandlers) - 1; 0 <= $i; --$i) {
			$handler = self::$fatalErrorHandlers[$i];

			if ($handler == null) {
				continue;
			}

			call_user_func($handler, $exception);
		}
	}

	private static function getErrorException()
	{
		$data = error_get_last();

		if ($data === null) {
			return null;
		}

		if (function_exists('error_clear_last')) {
			error_clear_last();
		}

		$message = trim($data['message']);
		$code = null;
		$level = $data['type'];
		$file = $data['file'];
		$line = $data['line'];

		return new ErrorException($message, $code, $level, $file, $line);
	}

	public static function off()
	{
		--self::$depth;

		array_pop(self::$fatalErrorHandlers);
		restore_error_handler();

		if (self::$depth === 0) {
			error_reporting(self::$errorReportingLevel);
			self::$errorReportingLevel = null;
		}
	}
}
