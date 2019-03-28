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

namespace _Lens\Lens\Phases\Code;

class Input
{
	/** @var array */
	private $tokens;

	/** @var integer */
	private $position;

	public function __construct(array $tokens)
	{
		$this->tokens = $tokens;
		$this->position = 0;
	}

	public function get($key, &$value = null)
	{
		if (!isset($this->tokens[$this->position])) {
			return false;
		}

		$token = $this->tokens[$this->position];

		if (key($token) !== $key) {
			return false;
		}

		$value = $token[$key];
		++$this->position;

		return true;
	}

	public function read(&$key = null, &$value = null)
	{
		if (!isset($this->tokens[$this->position])) {
			return false;
		}

		$token = $this->tokens[$this->position];

		$key = key($token);
		$value = $token[$key];

		return true;
	}

	// TODO: delete this:
	public function readTarget(...$targetTypes)
	{
		if (!isset($this->tokens[$this->position])) {
			return false;
		}

		$token = $this->tokens[$this->position];
		$type = key($token);

		return in_array($type, $targetTypes, true);
	}

	// TODO: delete this:
	public function readUnless(...$targetTypes)
	{
		if (!isset($this->tokens[$this->position])) {
			return false;
		}

		$token = $this->tokens[$this->position];
		$type = key($token);

		return !in_array($type, $targetTypes, true);
	}

	public function getTokens()
	{
		return $this->tokens;
	}

	public function getPosition()
	{
		return $this->position;
	}

	public function setPosition($position)
	{
		$this->position = $position;
	}

	public function move($offset)
	{
		$this->position += $offset;
	}
}
