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

namespace _Lens\Lens\Php;

use InvalidArgumentException;

class Parser
{
	/** @var Lexer */
	private $lexer;

	/** @var int */
	private $position;

	/** @var array */
	private $input;

	public function __construct()
	{
		$this->lexer = new Lexer();
	}

	public function parse($php)
	{
		$this->position = 0;
		$this->input = $this->lexer->getNodes($php);
		$output = [];

		if (!$this->getName($output)) {
			throw new InvalidArgumentException();
		}

		echo "input: ", var_export(array_slice($this->input, $this->position)), "\n";
		echo "output: ", var_export($output), "\n";

		if ($this->position < count($this->input)) {
			throw new InvalidArgumentException();
		}

		return $output;
	}

	private function getName(array &$output)
	{
		$children = [];

		if (!$this->getNamePath($children)) {
			return false;
		}

		$output[] = new Node($children, ['name']);
		return true;
	}

	private function getNamePath(array &$output)
	{
		$position = $this->position;

		if (
			$this->getIdentifier($output) &&
			$this->getNameSegments($output)
		) {
			return true;
		}

		$this->revert($position, $output);
		return false;
	}

	private function getNameSegments(array &$output)
	{
		while ($this->getNameSegment($output));

		return true;
	}

	private function getNameSegment(array &$output)
	{
		$position = $this->position;

		if (
			$this->getBackslash($output) &&
			$this->getIdentifier($output)
		) {
			return true;
		}

		$this->revert($position, $output);
		return false;
	}

	private function getBackslash(array &$output)
	{
		return $this->get('\\', $output);
	}

	private function getIdentifier(array &$output)
	{
		return $this->get('identifier', $output);
	}

	private function get($tag, array &$output)
	{
		if (count($this->input) <= $this->position) {
			return false;
		}

		$node = $this->input[$this->position];

		if (!$node->hasTag($tag)) {
			return false;
		}

		++$this->position;
		$output[] = $node;

		return true;
	}

	private function revert($position, array &$output)
	{
		while ($position < $this->position) {
			array_pop($output);
			--$this->position;
		}
	}
}
