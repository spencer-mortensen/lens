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

use _Lens\Lens\Php\Lexer as PhpLexer;

class Deflator
{
	private static $metaTokens = [
		PhpLexer::COMMENT_ => PhpLexer::COMMENT_,
		PhpLexer::PHP_BEGIN_ => PhpLexer::PHP_BEGIN_,
		PhpLexer::PHP_END_ => PhpLexer::PHP_END_,
		PhpLexer::WHITESPACE_ => PhpLexer::WHITESPACE_,
	];

	public function deflate(array $inflatedTokens)
	{
		$deflatedTokens = [];
		$metaTokens = [];

		foreach ($inflatedTokens as $i => $token) {
			if ($this->isMetaToken($token)) {
				$metaTokens[$i] = $token;
			} else {
				$deflatedTokens[] = $token;
			}
		}

		return [$deflatedTokens, $metaTokens];
	}

	private function isMetaToken(array $node)
	{
		$type = key($node);

		return isset(self::$metaTokens[$type]);
	}

	public function getInflatedPosition(array $inflatedTokens, array $metaTokens, $iDeflatedTarget)
	{
		$iDeflated = 0;

		for ($iInflated = 0, $n = count($inflatedTokens); $iInflated < $n; ++$iInflated) {
			if (isset($metaTokens[$iInflated])) {
				continue;
			}

			if ($iDeflated === $iDeflatedTarget) {
				return $iInflated;
			}

			++$iDeflated;
		}

		return null;
	}

	public function inflate(array $deflatedTokens, array $metaTokens, $iBegin, $iEnd)
	{
		$inflatedTokens = [];

		$iDeflated = 0;
		$iInflated = 0;
		reset($metaTokens);
		$iMeta = key($metaTokens);

		while ($iDeflated < $iBegin) {
			while ($iInflated === $iMeta) {
				++$iInflated;
				next($metaTokens);
				$iMeta = key($metaTokens);
			}

			++$iDeflated;
			++$iInflated;
		}

		while ($iDeflated <= $iEnd) {
			while ($iInflated === $iMeta) {
				$inflatedTokens[] = $metaTokens[$iMeta];

				++$iInflated;
				next($metaTokens);
				$iMeta = key($metaTokens);
			}

			$inflatedTokens[] = $deflatedTokens[$iDeflated];
			++$iDeflated;
			++$iInflated;
		}

		return $inflatedTokens;
	}
}
