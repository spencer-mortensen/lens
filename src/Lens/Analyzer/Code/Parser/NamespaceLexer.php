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

namespace _Lens\Lens\Analyzer\Code\Parser;

use _Lens\Lens\Php\Lexer as PhpLexer;

class NamespaceLexer
{
	public function lex(array $tokens)
	{
		$output = [];
		$iBegin = 0;

		foreach ($tokens as $iEnd => $token) {
			if (!$this->isNamespacetoken($token)) {
				continue;
			}

			$priorTokens = array_slice($tokens, $iBegin, $iEnd - $iBegin);
			$this->addNamespaceToken($priorTokens, $iBegin, $output);

			$iBegin = $iEnd;
		}

		$priorTokens = array_slice($tokens, $iBegin);
		$this->addNamespaceToken($priorTokens, $iBegin, $output);

		return $output;
	}

	private function isNamespaceToken(array $token)
	{
		$type = key($token);

		return $type === PhpLexer::NAMESPACE_;
	}

	private function addNamespaceToken(array $tokens, $iBegin, array &$output)
	{
		if (count($tokens) === 0) {
			return;
		}

		$output[] = [
			'position' => $iBegin,
			'tokens' => $tokens
		];
	}
}
