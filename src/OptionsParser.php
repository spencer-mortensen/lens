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

class OptionsParser
{
	/** @var array */
	private $arguments;

	/** @var integer */
	private $i;

	public function __construct(array $arguments)
	{
		$this->arguments = $arguments;
		$this->i = 1;
	}

	public function getLongFlag(array &$options)
	{
		if (!$this->getExpression('^--([a-z_]+)$', $matches)) {
			return false;
		}

		$flag = $matches[1];

		$options[$flag] = $flag;

		return true;
	}

	public function getLongKeyValue(array &$options)
	{
		if (!$this->getExpression('^--([a-z_]+)=(.*)$', $matches)) {
			return false;
		}

		$key = $matches[1];
		$value = $matches[2];

		$options[$key] = $value;

		return true;
	}

	public function getValue(array &$options)
	{
		$argument = &$this->arguments[$this->i];

		if (!isset($argument)) {
			return false;
		}

		$options[] = $argument;
		++$this->i;

		return true;
	}

	private function getExpression($expression, &$output)
	{
		$argument = &$this->arguments[$this->i];

		if (!isset($argument)) {
			return false;
		}

		$pattern = "\x03^{$expression}$\x03s";

		if (preg_match($pattern, $argument, $output) !== 1) {
			return false;
		}

		++$this->i;
		return true;
	}
}
