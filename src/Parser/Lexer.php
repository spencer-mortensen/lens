<?php

/**
 * Copyright (C) 2017 Spencer Mortensen
 *
 * This file is part of parser.
 *
 * Parallel-processor is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Parallel-processor is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with parser. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Spencer Mortensen <spencer@testphp.org>
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL-3.0
 * @copyright 2017 Spencer Mortensen
 */

namespace SpencerMortensen\Parser;

class Lexer
{
	/** @var string */
	private $input;

	/** @var integer */
	private $position;

	public function __construct($input)
	{
		$this->input = $input;
		$this->position = 0;
	}

	public function getString($string)
	{
		$length = strlen($string);

		if (strncmp($this->input, $string, $length) !== 0) {
			return false;
		}

		$this->advance($length);
		return true;
	}

	public function getRe($expression, &$output = null)
	{
		$delimiter = "\x03";
		$flags = 'As';

		$pattern = "{$delimiter}{$expression}{$delimiter}{$flags}";

		if (preg_match($pattern, $this->input, $matches) !== 1) {
			return false;
		}

		$output = $matches;
		$length = strlen($matches[0]);

		$this->advance($length);
		return true;
	}

	public function getPosition()
	{
		return $this->position;
	}

	private function advance($length)
	{
		$this->input = substr($this->input, $length);
		$this->position += $length;
	}
}
