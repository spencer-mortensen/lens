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

namespace _Lens\Lens\Phases\Analysis\Tests\Parser;

use _Lens\Lens\Exceptions\ParsingException;
use _Lens\Lens\Php\Deflator;
use _Lens\SpencerMortensen\Parser\Input\TokenInput;

class PreambleParser
{
	/** @var Deflator */
	private $deflator;

	/** @var PreambleTokensParser */
	private $parser;

	public function __construct(Deflator $deflator, PreambleTokensParser $parser)
	{
		$this->deflator = $deflator;
		$this->parser = $parser;
	}

	public function parse(array $inflatedTokens)
	{
		list($deflatedTokens, $metaTokens) = $this->deflator->deflate($inflatedTokens);

		$input = new TokenInput($deflatedTokens);

		if (
			$this->parser->parse($input) &&
			($this->parser->getPosition() === count($deflatedTokens))
		) {
			return $this->parser->getOutput();
		}

		throw $this->newException($inflatedTokens, $metaTokens);
	}

	// TODO: this is duplicated elsewhere:
	private function newException(array $inflatedTokens, array $metaTokens)
	{
		$deflatedPosition = $this->parser->getPosition();
		$inflatedPosition = $this->deflator->getInflatedPosition($inflatedTokens, $metaTokens, $deflatedPosition);
		$expectation = $this->parser->getExpectation();

		$coordinates = $this->getCoordinates($inflatedTokens, $inflatedPosition);
		$expected = $this->getExpected($expectation);
		$actual = $this->getActual($inflatedTokens, $inflatedPosition);

		return new ParsingException($coordinates, $expected, $actual);
	}

	// TODO: this is duplicated elsewhere:
	private function getCoordinates(array $tokens, $position)
	{
		$x = 0;
		$y = 0;

		for ($i = 0; $i < $position; ++$i) {
			$token = $tokens[$i];
			$value = current($token);

			$this->updatePosition($value, $x, $y);
		}

		return [$x, $y];
	}

	private function updatePosition($text, &$x, &$y)
	{
		$dy = substr_count($text, "\n");
		$y += $dy;

		if ($dy === 0) {
			$x += strlen($text);
		} else {
			$x = strlen($text) - (strrpos($text, "\n") + 1);
		}
	}

	private function getExpected($expectation)
	{
		// TODO:
		// throw new ErrorException("Undefined expectation ({$expectation})", null, E_USER_ERROR, __FILE__, __LINE__);
		return $expectation;
	}

	private function getActual(array $tokens, $position)
	{
		$tail = '';

		for ($i = $position, $n = count($tokens); $i < $n; ++$i) {
			$token = $tokens[$i];
			$value = current($token);

			$iNewline = strpos($value, "\n");

			if (is_int($iNewline)) {
				$tail .= substr($value, 0, $iNewline);
				return $tail;
			} else {
				$tail .= $value;
			}
		}

		return $tail;
	}
}
