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

use _Lens\Lens\Php\Lexer;

class NamespaceTokensParserStub
{
	public $rules = <<<'EOS'
expression: any notNamespace 1+
notNamespace: get !NAMESPACE
EOS;

	public $startRule = 'expression';

	public function expression(array $tokens)
	{
		return $tokens;
	}

	public function notNamespace(array $token)
	{
		return $token;
	}

	public function __invoke(NotTokenInput $input)
	{
	}

	public function __get($type)
	{
		if (substr($type, 0, 1) === '!') {
			$type = substr($type, 1);
			return "-Lexer::{$type}_";
		}

		return "Lexer::{$type}_";
	}
}
