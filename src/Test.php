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

class Test
{
	/** @var string */
	private $code;

	/** @var boolean */
	private $isCoverageEnabled;

	/** @var array */
	private $errors;

	/** @var bool */
	private $complete;

	public function __construct($code, $isCoverageEnabled)
	{
		$this->code = $code;
		$this->isCoverageEnabled = $isCoverageEnabled && function_exists('xdebug_get_code_coverage');
		$this->errors = array();
	}

	public function run()
	{
		$this->registerShutdownFunction();
		$this->registerErrorHandler();
		self::unsetGlobals();
		ob_start();
		$this->startCodeCoverage();

		try {
			eval($this->code);
			$variables = get_defined_vars();
			$exception = null;
		} catch (\Exception $) {
			$variables = get_defined_vars();
			$exception = $;
			unset($, $variables['']);
		}

		$coverage = $this->getCodeCoverage();
		$output = ob_get_clean();
		$globals = self::getGlobals();
		$constants = self::getConstants();
		$calls = self::getMethodCalls();
		$errors = $this->errors;
		$fatalError = null;

		$this->send($variables, $globals, $constants, $output, $calls, $exception, $errors, $fatalError, $coverage);
	}

	private function send($variables, $globals, $constants, $output, $calls, $exception, $errors, $fatalError, $coverage)
	{
		$results = array(
			'variables' => self::getUnorderedListArchive($variables),
			'globals' => self::getUnorderedListArchive($globals),
			'constants' => self::getUnorderedListArchive($constants),
			'output' => Archivist::archive($output),
			'calls' => self::getOrderedListArchive($calls),
			'exception' => Archivist::archive($exception),
			'errors' => self::getOrderedListArchive($errors),
			'fatalError' => Archivist::archive($fatalError)
		);

		$output = array($results, $coverage);

		echo json_encode($output);
		$this->complete = true;
	}

	private static function getUnorderedListArchive(array $input)
	{
		ksort($input, SORT_NATURAL);

		return self::getOrderedListArchive($input);
	}

	private static function getOrderedListArchive(array $input)
	{
		$output = array();

		foreach ($input as $key => $value) {
			$output[$key] = Archivist::archive($value);
		}

		return $output;
	}

	private function registerShutdownFunction()
	{
		$callable = array($this, 'shutdownFunction');

		register_shutdown_function($callable);
		ini_set('display_errors', 'Off');
	}

	public function shutdownFunction()
	{
		if ($this->complete === true) {
			return;
		}

		$variables = array();
		$exception = null;
		$output = ob_get_clean();
		$globals = self::getGlobals();
		$constants = self::getConstants();
		$calls = self::getMethodCalls();
		$coverage = $this->getCodeCoverage();
		$errors = $this->errors;
		$fatalError = $this->getLastError();

		$this->send($variables, $globals, $constants, $output, $calls, $exception, $errors, $fatalError, $coverage);
	}

	private function registerErrorHandler()
	{
		$callable = array($this, 'errorHandler');

		set_error_handler($callable);
	}

	public function errorHandler($level, $message, $file, $line)
	{
		$this->errors[] = self::getErrorValue($level, $message, $file, $line);
	}

	private static function unsetGlobals()
	{
		foreach ($GLOBALS as $key => $value) {
			if ($key !== 'GLOBALS') {
				unset($GLOBALS[$key]);
			}
		}
	}

	private function startCodeCoverage()
	{
		if ($this->isCoverageEnabled) {
			xdebug_start_code_coverage(XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE);
		}
	}

	private static function getGlobals()
	{
		$globals = $GLOBALS;

		foreach ($globals as $key => $value) {
			if (self::isSuperGlobal($key)) {
				unset($globals[$key]);
			}
		}

		return $globals;
	}

	private static function isSuperGlobal($name)
	{
		$superglobals = array(
			'GLOBALS' => true,
			'_SERVER' => true,
			'_GET' => true,
			'_POST' => true,
			'_FILES' => true,
			'_COOKIE' => true,
			'_SESSION' => true,
			'_REQUEST' => true,
			'_ENV' => true
		);

		return isset($superglobals[$name]);
	}

	private static function getConstants()
	{
		$constants = get_defined_constants(true);
		$userConstants = &$constants['user'];

		if (!is_array($userConstants)) {
			return array();
		}

		return $userConstants;
	}

	private static function getMethodCalls()
	{
		return Agent::getCalls();
	}

	private function getCodeCoverage()
	{
		if (!$this->isCoverageEnabled) {
			return null;
		}

		xdebug_stop_code_coverage(false);
		return xdebug_get_code_coverage();
	}

	private static function getLastError()
	{
		$error = error_get_last();

		if (!is_array($error)) {
			return null;
		}

		return self::getErrorValue(
			$error['type'],
			$error['message'],
			$error['file'],
			$error['line']
		);
	}

	private static function getErrorValue($level, $message, $file, $line)
	{
		list($file, $line) = self::getSource($file, $line);

		return array($level, $message, $file, $line);
	}

	private static function getSource($errorFile, $errorLine)
	{
		if (!self::isEvaluatedCode($errorFile, $file, $line)) {
			$file = $errorFile;
			$line = $errorLine;
		} elseif ($file === __FILE__) {
			$file = null;
			$line = $errorLine;
		}

		return array($file, $line);
	}

	private static function isEvaluatedCode($input, &$file, &$line)
	{
		$pattern = '~^((?:[a-z]+://)?(?:/[^/]+)+)\(([0-9]+)\) : eval\(\)\'d code$~';

		if (preg_match($pattern, $input, $match) !== 1) {
			return false;
		}

		list( , $file, $line) = $match;
		return true;
	}
}
