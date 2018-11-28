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

namespace _Lens\Lens\Analyzer\Tests\Parser;

use _Lens\Lens\Exceptions\ParsingException;
use _Lens\SpencerMortensen\Parser\Input\TokenInput;

class FileParser
{
	/** @var FileTokensParser */
	private $fileTokensParser;

	public function __construct(FileTokensParser $fileTokensParser)
	{
		$this->fileTokensParser = $fileTokensParser;
	}

	public function parse(array $tokens)
	{
		$input = new TokenInput($tokens);

		if (
			$this->fileTokensParser->parse($input) &&
			($this->fileTokensParser->getPosition() === count($tokens))
		) {
			return $this->fileTokensParser->getOutput();
		}

		throw $this->newException($tokens);
	}

	private function newException(array $tokens)
	{
		$expectation = $this->fileTokensParser->getExpectation();
		$position = $this->fileTokensParser->getPosition();

		$errorValue = current($tokens[$position]);
		$coordinates = $errorValue['origin'];

		$expected = $this->getExpected($expectation);
		$actual = $this->getActual($errorValue['tokens'], 0);

		return new ParsingException($coordinates, $expected, $actual);
	}

	private function getExpected($expectation)
	{
		switch ($expectation)
		{
			case 'mocks':
				return '// Mocks';

			case 'subject':
				return '// Test';

			case 'cause':
				return '// Cause';

			case 'effect':
				return '// Effect';

			default:
				// TODO: throw exception
				// throw new ErrorException("Undefined expectation ({$expectation})", null, E_USER_ERROR, __FILE__, __LINE__);
				return null;
		}
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
