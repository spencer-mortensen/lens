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

namespace Lens\Engine;

use Exception;
use Lens\Archivist\Archivist;
use Throwable;

class Code implements Job
{
	const MODE_RECORD = 1;
	const MODE_PLAY = 2;

	/** @var Archivist */
	private $archivist;

	/** @var callable */
	private $callable;

	/** @var string */
	private $code;

	/** @var integer */
	private $mode;

	/** @var boolean */
	private $isCoverageEnabled;

	/** @var boolean */
	private $isBroken;

	/** @var boolean */
	private $isRunning;

	/** @var array */
	private $state;

	// Output written to STDOUT/STDERR (and the overall exit code) is not checked by Lens
	public function __construct()
	{
		$this->archivist = new Archivist();

		ini_set('display_errors', 'Off');
		set_error_handler(array($this, 'errorHandler'));
		register_shutdown_function(array($this, 'shutdownFunction'));
		self::unsetGlobals();

		$this->isBroken = false;
		$this->state = array(
			'output' => null,
			'variables' => array(),
			'globals' => null,
			'constants' => null,
			'exception' => null,
			'errors' => null,
			'calls' => null
		);
	}

	public function setCode($code)
	{
		$this->code = $code;
	}

	public function setMode($mode)
	{
		$this->mode = $mode;
		$this->isCoverageEnabled = $this->isCoverageEnabled($this->mode);
	}

	public function run($callable)
	{
		$this->callable = $callable;

		if ($this->isBroken) {
			$results = null;
		} else {
			$results = $this->evaluateCode();
		}

		$this->sendResults($results);
	}

	public function isBroken()
	{
		return $this->isBroken;
	}

	private function evaluateCode()
	{
		$this->state['exception'] = null;
		$this->state['errors'] = array();
		extract($this->state['variables']);
		ob_start();

		$this->isRunning = true;
		$this->startCoverage();

		try {
			eval($this->code);
		} catch (Throwable $LENS_EXCEPTION) {
			$this->isBroken = true;
			$this->state['exception'] = $LENS_EXCEPTION;
			unset($LENS_EXCEPTION);
		} catch (Exception $LENS_EXCEPTION) {
			$this->isBroken = true;
			$this->state['exception'] = $LENS_EXCEPTION;
			unset($LENS_EXCEPTION);
		}

		$this->stopCoverage();
		$this->isRunning = false;

		$this->state['variables'] = get_defined_vars();
		$this->getGlobalState();

		return $this->getResults();
	}

	public function shutdownFunction()
	{
		if (!$this->isRunning) {
			return;
		}

		$this->isBroken = true;

		$this->stopCoverage();
		$this->state['variables'] = array();
		$this->getLastError();
		$this->getGlobalState();

		$results = $this->getResults();

		$this->sendResults($results);
	}

	private function getGlobalState()
	{
		$this->state['output'] = self::getOutput();
		$this->state['globals'] = self::getGlobals();
		$this->state['constants'] = self::getConstants();
		$this->state['calls'] = self::getCalls();
	}

	private function getResults()
	{
		ksort($this->state['variables'], SORT_NATURAL);
		ksort($this->state['globals'], SORT_NATURAL);
		ksort($this->state['constants'], SORT_NATURAL);

		$archivedState = $this->archivist->archive($this->state);
		$script = $this->getScript();
		$coverage = $this->getCoverage();

		return array($archivedState, $script, $coverage);
	}

	private function sendResults($results)
	{
		call_user_func($this->callable, $results);
	}

	public function errorHandler($level, $message, $file, $line)
	{
		$this->state['errors'][] = self::getErrorValue($level, $message, $file, $line);
	}

	private static function getOutput()
	{
		$output = ob_get_clean();

		if (strlen($output) === 0) {
			return null;
		}

		return $output;
	}

	private static function unsetGlobals()
	{
		foreach ($GLOBALS as $key => $value) {
			if ($key !== 'GLOBALS') {
				unset($GLOBALS[$key]);
			}
		}
	}

	private static function getGlobals()
	{
		$globals = array();

		foreach ($GLOBALS as $key => $value) {
			if (!self::isSuperGlobal($key)) {
				$globals[$key] = $value;
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

	private static function getCalls()
	{
		return Agent::getCalls();
	}

	private function getScript()
	{
		if ($this->mode === self::MODE_RECORD) {
			return Agent::getScript();
		}

		return null;
	}

	private function isCoverageEnabled($mode)
	{
		return ($mode === self::MODE_PLAY) && function_exists('xdebug_start_code_coverage');
	}

	private function startCoverage()
	{
		if ($this->isCoverageEnabled) {
			xdebug_start_code_coverage(XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE);
		}
	}

	private function stopCoverage()
	{
		if ($this->isCoverageEnabled) {
			xdebug_stop_code_coverage(false);
		}
	}

	private function getCoverage()
	{
		if (!$this->isCoverageEnabled) {
			return null;
		}

		$prefix = __DIR__ . '/';
		$prefixLength = strlen($prefix);

		$coverage = xdebug_get_code_coverage();

		foreach ($coverage as $path => $lines) {
			if (strncmp($path, $prefix, $prefixLength) === 0) {
				unset($coverage[$path]);
			}
		}

		return $coverage;
	}

	private function getLastError()
	{
		$error = error_get_last();
		error_clear_last();

		if (is_array($error)) {
			$this->state['errors'][] = self::getErrorValue($error['type'], $error['message'], $error['file'], $error['line']);
		}
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
