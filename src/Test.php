<?php

/**
 * Copyright (C) 2017 Spencer Mortensen
 *
 * This file is part of TestPHP.
 *
 * TestPHP is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * TestPHP is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with TestPHP. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Spencer Mortensen <spencer@spencermortensen.com>
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL-3.0
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
		$this->setFatalErrorHandler();
		$this->setNonFatalErrorHandler();
		self::unsetGlobals();
		ob_start();
		$this->startCodeCoverage();

		try {
			eval($this->code);
			$variables = get_defined_vars();
			$exception = null;
		} catch (\Exception $exception) {
			$variables = array();
		}

		$coverage = $this->getCodeCoverage();
		$output = ob_get_clean();
		$globals = self::getGlobals();
		$constants = self::getConstants();
		$calls = self::getMethodCalls();
		$errors = $this->getErrors();

		$this->send($variables, $globals, $constants, $output, $calls, $exception, $errors, $coverage);
	}

	private function send($variables, $globals, $constants, $output, $calls, $exception, $errors, $coverage)
	{
		$results = array(
			'variables' => self::getUnorderedListArchive($variables),
			'globals' => self::getUnorderedListArchive($globals),
			'constants' => self::getUnorderedListArchive($constants),
			'output' => Archivist::archive($output),
			'calls' => self::getOrderedListArchive($calls),
			'exception' => Archivist::archive($exception),
			'errors' => self::getOrderedListArchive($errors),
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

	private function setFatalErrorHandler()
	{
		$handler = array($this, 'fatalErrorHandler');

		register_shutdown_function($handler);
		ini_set('display_errors', 'Off');
	}

	private function setNonFatalErrorHandler()
	{
		$handler = array($this, 'nonFatalErrorHandler');

		set_error_handler($handler);
	}

	private static function unsetGlobals()
	{
		foreach ($GLOBALS as $key => $value) {
			if ($key !== 'GLOBALS') {
				unset($GLOBALS[$key]);
			}
		}
	}

	public function fatalErrorHandler()
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
		$errors = $this->getErrors();

		$this->send($variables, $globals, $constants, $output, $calls, $exception, $errors, $coverage);
	}

	public function nonFatalErrorHandler($level, $message, $file, $line)
	{
		list($file, $line) = self::getSource($file, $line);

		$this->errors[] = array($level, $message, $file, $line);
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

	private function getErrors()
	{
		$error = $this->getLastError();

		if ($error !== null) {
			$this->errors[] = $error;
		}

		return $this->errors;
	}

	private static function getLastError()
	{
		$error = error_get_last();

		if (!is_array($error)) {
			return null;
		}

		$level = $error['type'];
		$message = $error['message'];

		list($file, $line) = self::getSource($error['file'], $error['line']);

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
		$pattern = '~^((?:/[^/]+)+)\(([0-9]+)\) : eval\(\)\'d code$~';

		if (preg_match($pattern, $input, $match) !== 1) {
			return false;
		}

		list( , $file, $line) = $match;
		return true;
	}
}
