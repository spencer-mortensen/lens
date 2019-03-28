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

namespace _Lens\Lens\Phases\Tests\Parser;

use _Lens\Lens\Php\Lexer as PhpLexer;

class EffectsLexer
{
	const PHP = 1;
	const BODY = 2;

	/** @var PhpLexer */
	private $phpLexer;

	public function __construct(PhpLexer $phpLexer)
	{
		$this->phpLexer = $phpLexer;
	}

	public function lex(array $phpTokens, array $origin)
	{
		$effectTokens = [];

		$iBegin = 0;
		list($x, $y) = $origin;

		foreach ($phpTokens as $iEnd => $phpToken) {
			if (!$this->isBodyToken($phpToken)) {
				continue;
			}

			$childPhpTokens = array_slice($phpTokens, $iBegin, $iEnd - $iBegin);
			$effectTokens[] = $this->newToken(self::PHP, $childPhpTokens, $x, $y);

			foreach ($childPhpTokens as $childPhpToken) {
				$this->updateCoordinates($childPhpToken, $x, $y);
			}

			$effectTokens[] = $this->newBodyToken($phpToken, $x, $y);

			$this->updateCoordinates($phpToken, $x, $y);
			$iBegin = $iEnd + 1;
		}

		$childPhpTokens = array_slice($phpTokens, $iBegin);

		if (0 < count($childPhpTokens)) {
			$effectTokens[] = $this->newToken(self::PHP, $childPhpTokens, $x, $y);
		}

		return $effectTokens;
	}

	private function isBodyToken(array $phpToken)
	{
		if (key($phpToken) !== PhpLexer::COMMENT_) {
			return false;
		}

		$text = current($phpToken);

		return (substr($text, 0, 2) === '//') && is_int(strpos($text, ';'));
	}

	private function newToken($type, array $tokens, $x, $y)
	{
		$value = [
			'tokens' => $tokens,
			'origin' => [$x, $y]
		];

		return [$type => $value];
	}

	private function updateCoordinates(array $token, &$x, &$y)
	{
		$php = current($token);

		$dy = substr_count($php, "\n");
		$y += $dy;

		if ($dy === 0) {
			$x += strlen($php);
		} else {
			$x = strlen($php) - (strrpos($php, "\n") + 1);
		}
	}

	private function newBodyToken(array $phpToken, $x, $y)
	{
		$text = substr(current($phpToken), 2);
		$x += 2;

		// PHP generates a parse error when a HEREDOC statement appears at the
		// end of a file. We prevent this issue by adding whitespace:
		$php = "<?php\n{$text}\n";

		$phpTokens = $this->phpLexer->lex($php);
		array_pop($phpTokens); // Remove the trailing whitespace
		array_shift($phpTokens); // Remove the "<?php\n" token

		return $this->newToken(self::BODY, $phpTokens, $x, $y);
	}
}
