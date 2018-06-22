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

namespace Lens_0_0_57\Lens\Tests;

use Error;
use ErrorException;
use Exception;
use Lens_0_0_57\SpencerMortensen\Exceptions\Exceptions;

class Examiner
{
	/** @var boolean */
	private $isUsable;

	/** @var string */
	private $code;

	/** @var array */
	private $state;

	public function __construct()
	{
		self::unsetGlobals();

		$this->isUsable = true;
		$this->state = [
			'output' => null,
			'variables' => [],
			'globals' => null,
			'constants' => null,
			'exception' => null,
			'errors' => null
		];
	}

	public function run($code)
	{
		if (!$this->isUsable) {
			return;
		}

		$this->code = $code;

		$onFatalError =  [$this, 'onFatalError'];
		$onError = [$this, 'onError'];

		Exceptions::on($onFatalError, $onError);

		$this->evaluateCode();

		Exceptions::off();
	}

	private function evaluateCode()
	{
		$this->state['exception'] = null;
		$this->state['errors'] = [];

		extract($this->state['variables']);
		ob_start();

		try {
			eval($this->code);
		} catch (Exception $LENS_EXCEPTION) {
			$this->isUsable = false;
			$this->state['exception'] = $LENS_EXCEPTION;
			unset($LENS_EXCEPTION);
		} catch (Error $LENS_EXCEPTION) {
			$this->isUsable = false;
			$this->state['exception'] = $LENS_EXCEPTION;
			unset($LENS_EXCEPTION);
		}

		$this->state['variables'] = get_defined_vars();
		ksort($this->state['variables'], SORT_NATURAL);
		$this->setGlobalState();
	}

	private function setGlobalState()
	{
		$this->state['output'] = self::getOutput();
		$this->state['globals'] = self::getGlobals();
		$this->state['constants'] = self::getConstants();
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
		$globals = [];

		foreach ($GLOBALS as $key => $value) {
			if (!self::isSuperGlobal($key)) {
				$globals[$key] = $value;
			}
		}

		ksort($globals, SORT_NATURAL);
		return $globals;
	}

	private static function isSuperGlobal($name)
	{
		$superglobals = [
			'GLOBALS' => true,
			'_SERVER' => true,
			'_GET' => true,
			'_POST' => true,
			'_FILES' => true,
			'_COOKIE' => true,
			'_SESSION' => true,
			'_REQUEST' => true,
			'_ENV' => true
		];

		return isset($superglobals[$name]);
	}

	private static function getConstants()
	{
		$constants = get_defined_constants(true);
		$userConstants = &$constants['user'];

		if (!is_array($userConstants)) {
			return [];
		}

		ksort($userConstants, SORT_NATURAL);
		return $userConstants;
	}

	public function onFatalError(ErrorException $exception)
	{
		$this->isUsable = false;
		$this->state['variables'] = [];
		$this->onError($exception->getSeverity(), $exception->getMessage(), $exception->getFile(), $exception->getLine());
		$this->setGlobalState();
	}

	public function onError($level, $message, $file, $line)
	{
		$this->state['errors'][] = self::getErrorValue($level, $message, $file, $line);
	}

	private static function getErrorValue($level, $message, $file, $line)
	{
		list($file, $line) = self::getSource($file, $line);

		return [$level, $message, $file, $line];
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

		return [$file, $line];
	}

	private static function isEvaluatedCode($input, &$file, &$line)
	{
		$pattern = '~^((?:[a-z]+://)?(?:/[^/]+)+)\\(([0-9]+)\\) : eval\\(\\)\'d code$~';

		if (preg_match($pattern, $input, $match) !== 1) {
			return false;
		}

		list( , $file, $line) = $match;
		return true;
	}

	public function isUsable()
	{
		return $this->isUsable;
	}

	public function getState()
	{
		return $this->state;
	}
}
