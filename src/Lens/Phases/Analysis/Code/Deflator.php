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

namespace _Lens\Lens\Phases\Analysis\Code;

use _Lens\Lens\Php\Lexer;

class Deflator
{
	private static $metaTokens = [
		Lexer::COMMENT_ => Lexer::COMMENT_,
		Lexer::WHITESPACE_ => Lexer::WHITESPACE_,
	];

	public function deflate(array $inflatedTokens, array &$deflatedTokens = null, array &$map = null)
	{
		$deflatedTokens = [];
		$map = [];

		$iDeflated = 0;

		foreach ($inflatedTokens as $iInflated => $token) {
			if ($this->isMetaToken($token)) {
				continue;
			}

			$deflatedTokens[$iDeflated] = $token;
			$map[$iDeflated] = $iInflated;
			++$iDeflated;
		}

		return [$deflatedTokens, $map];
	}

	private function isMetaToken(array $node)
	{
		$type = key($node);

		return isset(self::$metaTokens[$type]);
	}
}
