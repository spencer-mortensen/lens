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

use _Lens\Lens\Php\Deflator;
use _Lens\Lens\Php\Lexer as PhpLexer;

class FileLexer
{
	const PREAMBLE = 'PREAMBLE';
	const SUBJECT = 'SUBJECT';
	const MOCKS = 'MOCKS';
	const CAUSE = 'CAUSE';
	const EFFECT = 'EFFECT';

	/** @var Deflator */
	private $deflator;

	public function __construct(Deflator $deflator)
	{
		$this->deflator = $deflator;
	}

	// We analyze PHP tokens, rather than raw lines of code, because
	// a multiline comment or string might include a line that matches
	// a label pattern, but is NOT a label.
	public function lex(array $phpTokens)
	{
		$lensTokens = [];

		$typeBegin = self::PREAMBLE;
		$iBegin = 0;
		$x = 0;
		$y = 0;

		foreach ($phpTokens as $iEnd => $phpToken) {
			if (!$this->getLabelToken($phpToken, $typeEnd)) {
				continue;
			}

			$childPhpTokens = array_slice($phpTokens, $iBegin, $iEnd - $iBegin);
			$lensTokens[] = $this->newLensToken($typeBegin, $childPhpTokens, $x, $y);

			$this->updateCoordinates($childPhpTokens, $x, $y);
			$typeBegin = $typeEnd;
			$iBegin = $iEnd;
		}

		$childPhpTokens = array_slice($phpTokens, $iBegin);
		$lensTokens[] = $this->newLensToken($typeBegin, $childPhpTokens, $x, $y);

		return $lensTokens;
	}

	private function getLabelToken(array $phpToken, &$typeEnd)
	{
		if (key($phpToken) !== PhpLexer::COMMENT_) {
			return false;
		}

		switch (trim(current($phpToken))) {
			case '// Test':
				$typeEnd = self::SUBJECT;
				return true;

			case '// Mocks':
				$typeEnd = self::MOCKS;
				return true;

			case '// Cause':
				$typeEnd = self::CAUSE;
				return true;

			case '// Effect':
				$typeEnd = self::EFFECT;
				return true;

			default:
				return false;
		}
	}

	private function updateCoordinates(array $tokens, &$x, &$y)
	{
		foreach ($tokens as $token) {
			$php = current($token);

			$dy = substr_count($php, "\n");
			$y += $dy;

			if ($dy === 0) {
				$x += strlen($php);
			} else {
				$x = strlen($php) - (strrpos($php, "\n") + 1);
			}
		}
	}

	private function newLensToken($type, array $tokens, $x, $y)
	{
		$value = [
			'tokens' => $tokens,
			'origin' => [$x, $y]
		];

		return [$type => $value];
	}
}
