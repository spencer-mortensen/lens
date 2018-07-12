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

class Lexer
{
	/** @var string */
	private $php;

	/** @var array */
	private $nodes;

	public function getNodes($php)
	{
		$this->php = $php;
		$this->nodes = [];

		while ($this->getNode());

		if (0 < strlen($this->php)) {
			throw new InvalidArgumentException();
		}

		return $this->nodes;
	}

	private function getNode()
	{
		return $this->getWhitespace()
			|| $this->getIdentifier()
			|| $this->getValue()
			|| $this->getSymbol();
	}

	private function getWhitespace()
	{
		if (!self::match('\\s+', $value)) {
			return false;
		}

		$this->nodes[] = new Node($value, ['whitespace']);
		return true;
	}

	private function getIdentifier()
	{
		if (!self::match('[a-zA-Z_\\x7F-\\xFF][0-9a-zA-Z_\\x7F-\\xFF]*', $value)) {
			return false;
		}

		$this->nodes[] = new Node($value, ['identifier']);
		return true;
	}

	private function getValue()
	{
		return $this->getString()
			|| $this->getFloat()
			|| $this->getInteger();
	}

	private function getString()
	{
		return $this->getSingleQuotedString()
			|| $this->getDoubleQuotedString()
			|| $this->getDocumentString();
	}

	private function getSingleQuotedString()
	{
		// TODO: should this be more restrictive?
		if (!self::match("'(?:\\\\|\\'|[^'])*'", $value)) {
			return false;
		}

		$this->nodes[] = new Node($value, ['string', 'value']);
		return true;
	}

	private function getDoubleQuotedString()
	{
		// TODO: should this be more restrictive?
		if (!self::match('"(?:\\\\|\\"|[^"])*"', $value)) {
			return false;
		}

		$this->nodes[] = new Node($value, ['string', 'value']);
		return true;
	}

	private function getDocumentString()
	{
		// TODO: support the upcoming PHP 7.3 syntax
		if (!self::match('<<<([\'"]?)(\\w+)\\1\\n.*?\\n\\2;?(?:\\n|$)', $match)) {
			return false;
		}

		$value = $match[0];

		$this->nodes[] = new Node($value, ['string', 'value']);
		return true;
	}

	private function getFloat()
	{
		if (!self::match('(?:[0-9]*\\.[0-9]+|[0-9]+\\.[0-9]*)(?:[eE][+-]?[0-9]+)?', $value)) {
			return false;
		}

		$this->nodes[] = new Node($value, ['float', 'value']);
		return true;
	}

	private function getInteger()
	{
		if (!self::match('[1-9][0-9]*|0[xX][0-9a-fA-F]+|0[0-7]+|0[bB][01]+|0', $value)) {
			return false;
		}

		$this->nodes[] = new Node($value, ['integer', 'value']);
		return true;
	}

	private function getSymbol()
	{
		if (!self::match('<\\?php|===|!==|<=>|\\*\\*=|<<=|>>=|->|\\|\\||&&|\\?\\?|<=|==|!=|<>|>=|=>|\\+\\+|--|\\.=|\\+=|-=|\\*=|/=|%=|\\^=|\\|=|&=|\\*\\*|<<|>>|<\\?|\\?>|\\$|;|\\(|\\)|\\[|\\]|\\{|\\}|\\\\|=|,|\\.|\\+|-|\\*|/|%|\\^|\\||&|<|>|!|\\?|:|~|@', $value)) {
			return false;
		}

		$tag = $value;

		$this->nodes[] = new Node($value, [$tag]);
		return true;
	}

	private function match($expression, &$output = null)
	{
		$delimiter = "\x03";
		$flags = 'As';

		$pattern = $delimiter . $expression . $delimiter . $flags;

		if (preg_match($pattern, $this->php, $match) !== 1) {
			return false;
		}

		if (count($match) === 1) {
			$output = $match[0];
		} else {
			$output = $match;
		}

		$length = strlen($match[0]);
		$this->php = substr($this->php, $length);
		return true;
	}
}
