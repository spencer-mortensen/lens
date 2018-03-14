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

namespace Lens_0_0_56\Lens;

use Lens_0_0_56\SpencerMortensen\RegularExpressions\Re;

class Arguments
{
	/** @var string */
	private $executable;

	/** @var array */
	private $options;

	/** @var array */
	private $values;

	public function __construct()
	{
		self::read($GLOBALS['argv'], $this->executable, $this->options, $this->values);
	}

	public function getExecutable()
	{
		return $this->executable;
	}

	public function getOptions()
	{
		return $this->options;
	}

	public function getValues()
	{
		return $this->values;
	}

	private static function read(array $arguments, &$executable, &$options, &$values)
	{
		$options = array();
		$values = array();

		$executable = array_shift($arguments);

		foreach ($arguments as $argument) {
			if (self::getKeyValue($argument, $key, $value)) {
				$options[$key] = $value;
			} else {
				$values[] = $argument;
			}
		}
	}

	private static function getKeyValue($argument, &$key, &$value)
	{
		$expression = '^--(?<key>[a-z-]+)(?:=(?<value>.*))?$';

		if (!Re::match($expression, $argument, $match)) {
			return false;
		}

		$key = $match['key'];

		if (isset($match['value'])) {
			$value = $match['value'];
		} else {
			$value = true;
		}

		return true;
	}
}
