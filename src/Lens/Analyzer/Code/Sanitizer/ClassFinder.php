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

class ClassFinder implements Finder
{
	/** @var array */
	private $tokens;

	/** @var int */
	private $position;

	public function find(array $deflatedTokens)
	{
		$functions = [];

		$this->tokens = $deflatedTokens;

		for ($this->position = 0, $n = count($this->tokens); $this->position < $n; ++$this->position) {
			if (
				$this->readClassPath($iBegin, $iEnd) &&
				!$this->isReservedWord($iBegin, $iEnd)
			) {
				$functions[$iBegin] = $iEnd;
			}
		}

		return $functions;
	}

	private function readClassPath(&$iBegin, &$iEnd)
	{
		return $this->readInstantiation($iBegin, $iEnd) ||
			$this->readStaticCall($iBegin, $iEnd) ||
			$this->readExtension($iBegin, $iEnd) ||
			$this->readTypeHint($iBegin, $iEnd);

	}

	private function readInstantiation(&$iBegin, &$iEnd)
	{
		$position = $this->position;

		if (
			$this->readNew() &&
			$this->readPath($iBegin, $iEnd) &&
			$this->readLeftParenthesis()
		) {
			return true;
		}

		$this->position = $position;
		return false;
	}

	private function readNew()
	{
		if ($this->is(Lexer::NEW_)) {
			++$this->position;
			return true;
		}

		return false;
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

	private function readPath(&$iBegin, &$iEnd)
	{
		$position = $this->position;

		if (
			$this->readMaybeSeparator() &&
			$this->readIdentifier() &&
			$this->readAnyLinks()
		) {
			$iBegin = $position;
			$iEnd = $this->position - 1;
			return true;
		}

		$this->position = $position;
		return false;
	}

	private function readMaybeSeparator()
	{
		$this->readSeparator();

		return true;
	}

	private function readSeparator()
	{
		if ($this->is(Lexer::NAMESPACE_SEPARATOR_)) {
			++$this->position;
			return true;
		}

		return false;
	}

	private function readIdentifier()
	{
		if ($this->is(Lexer::IDENTIFIER_)) {
			++$this->position;
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

	private function readLeftParenthesis()
	{
		if ($this->is(Lexer::PARENTHESIS_LEFT_)) {
			++$this->position;
			return true;
		}

		return false;
	}

	private function readStaticCall(&$iBegin, &$iEnd)
	{
		$position = $this->position;

		if (
			$this->readPath($iBegin, $iEnd) &&
			$this->readDoubleColon()
		) {
			return true;
		}

		$this->position = $position;
		return false;
	}

	private function readDoubleColon()
	{
		if ($this->is(Lexer::DOUBLE_COLON_)) {
			++$this->position;
			return true;
		}

		return false;
	}

	private function readExtension(&$iBegin, &$iEnd)
	{
		$position = $this->position;

		if (
			$this->readExtends() &&
			$this->readPath($iBegin, $iEnd)
		) {
			return true;
		}

		$this->position = $position;
		return false;
	}

	private function readExtends()
	{
		if ($this->is(Lexer::EXTENDS_)) {
			++$this->position;
			return true;
		}

		return false;
	}

	private function readTypeHint(&$iBegin, &$iEnd)
	{
		// TODO: support classname type hints
		return false;
	}

	private function isReservedWord($iBegin, $iEnd)
	{
		$length = $iEnd - $iBegin + 1;
		$tokens = array_slice($this->tokens, $iBegin, $length);

		$self = [
			[Lexer::IDENTIFIER_ => 'self'],
		];

		$parent = [
			[Lexer::IDENTIFIER_ => 'parent']
		];

		return ($tokens === $self) || ($tokens === $parent);
	}
}
