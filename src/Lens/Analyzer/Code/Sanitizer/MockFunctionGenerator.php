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

use _Lens\Lens\Php\Code;
use _Lens\Lens\Php\Lexer;

class MockFunctionGenerator
{
	/** @var WhitespaceStandardizer */
	private $whitespaceStandardizer;

	public function __construct()
	{
		$this->whitespaceStandardizer = new WhitespaceStandardizer();
	}

	public function generate($namespace, array $tokens)
	{
		$classes = [
			'Agent' => '_Lens\\Lens\\Tests\\Agent'
		];
		$functions = [];

		$iLeftBrace = $this->findLeftBrace($tokens);
		$signatureTokens = array_slice($tokens, 0, $iLeftBrace);
		$signatureTokens = $this->whitespaceStandardizer->standardize($signatureTokens);

		$signaturePhp = $this->getPhpFromTokens($signatureTokens);
		$definitionPhp = "{$signaturePhp}{\n\treturn eval(Agent::call(null, __FUNCTION__, func_get_args()));\n}";

		return $this->getPhp($namespace, $classes, $functions, $definitionPhp);
	}

	private function findLeftBrace(array $tokens)
	{
		foreach ($tokens as $i => $token) {
			if (key($token) === Lexer::BRACE_LEFT_) {
				return $i;
			}
		}

		return null;
	}

	// TODO: this is duplicated elsewhere
	private function getPhpFromTokens(array $tokens)
	{
		ob_start();

		foreach ($tokens as $token) {
			echo current($token);
		}

		return ob_get_clean();
	}

	private function getPhp($namespace, array $classes, array $functions, $definitionPhp)
	{
		$contextPhp = Code::getFullContextPhp($namespace, $classes, $functions);
		$php = Code::combine($contextPhp, $definitionPhp);
		return Code::getFilePhp($php);
	}
}
