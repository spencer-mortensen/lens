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

namespace _Lens\Lens\Analyzer\Code\Sanitizer;

use _Lens\Lens\Php\Lexer;

class FunctionFinder implements Finder
{
	/** @var array */
	private $tokens;

	/** @var int */
	private $position;

	public function find(array $deflatedTokens)
	{
		$functions = [];

		$this->tokens = $deflatedTokens;

		for ($this->position = count($this->tokens) - 1; -1 < $this->position; --$this->position) {
			if ($this->readFunctionCall($iBegin, $iEnd)) {
				$functions[$iBegin] = $iEnd;
			}
		}

		return array_reverse($functions, true);
	}

	private function readFunctionCall(&$iBegin, &$iEnd)
	{
		$position = $this->position;

		if (
			$this->readLeftParenthesis() &&
			$this->readPath($iBegin, $iEnd) &&
			!$this->isNew() &&
			!$this->isFunction() &&
			!$this->isDoubleColon() &&
			!$this->isRightArrow()
		) {
			return true;
		}

		$this->position = $position;
		return false;
	}

	private function readLeftParenthesis()
	{
		if ($this->is(Lexer::PARENTHESIS_LEFT_)) {
			--$this->position;
			return true;
		}

		return false;
	}

	private function readPath(&$iBegin, &$iEnd)
	{
		$position = $this->position;

		if (
			$this->readIdentifier() &&
			$this->readAnyLinks() &&
			$this->readMaybeSeparator()
		) {
			$iEnd = $position;
			$iBegin = $this->position + 1;
			return true;
		}

		$this->position = $position;
		return false;
	}

	private function readIdentifier()
	{
		if ($this->is(Lexer::IDENTIFIER_)) {
			--$this->position;
			return true;
		}

		return false;
	}

	private function readAnyLinks()
	{
		while ($this->readLink());

		return true;
	}

	private function readLink()
	{
		$position = $this->position;

		if (
			$this->readSeparator() &&
			$this->readIdentifier()
		) {
			return true;
		}

		$this->position = $position;
		return false;
	}

	private function readSeparator()
	{
		if ($this->is(Lexer::NAMESPACE_SEPARATOR_)) {
			--$this->position;
			return true;
		}

		return false;
	}

	private function readMaybeSeparator()
	{
		$this->readSeparator();

		return true;
	}

	private function isNew()
	{
		return $this->is(Lexer::NEW_);
	}

	private function isFunction()
	{
		return $this->is(Lexer::FUNCTION_);
	}

	private function isDoubleColon()
	{
		return $this->is(Lexer::DOUBLE_COLON_);
	}

	private function isRightArrow()
	{
		return $this->is(Lexer::OBJECT_OPERATOR_);
	}

	private function is($targetType)
	{
		if (!isset($this->tokens[$this->position])) {
			return false;
		}

		$token = $this->tokens[$this->position];
		$type = key($token);

		return $type === $targetType;
	}
}
