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

use _Lens\Lens\Php\Lexer;
use _Lens\SpencerMortensen\Parser\Input\TokenInput;

class MocksTokensParserStub
{
	public $rules = <<<'EOS'
mocks: any mock 0+
mock: and variable assign newKeyword path parenthesisLeft parenthesisRight semicolon
path: and maybeWordSeparator maybeWordLinks word
maybeWordSeparator: any wordSeparator 0 - 1
maybeWordLinks: any wordLink 0+
wordLink: and word wordSeparator

variable: get VARIABLE
assign: get ASSIGN
newKeyword: get NEW
wordSeparator: get NAMESPACE_SEPARATOR
word: get IDENTIFIER
parenthesisLeft: get PARENTHESIS_LEFT
parenthesisRight: get PARENTHESIS_RIGHT
semicolon: get SEMICOLON
EOS;

	public $startRule = 'mocks';

	public function mocks(array $maps)
	{
		if (count($maps) === 0) {
			$mocks = [];
		} else {
			$mocks = call_user_func_array('array_merge', $maps);
		}

		return $mocks;
	}

	public function mock($variable, $path)
	{
		return [$variable => $path];
	}

	public function variable(array $token)
	{
		return substr(current($token), 1);
	}

	public function path($isAbsolute, array $links, $word)
	{
		$links[] = $word;
		$path = implode('\\', $links);

		if ($isAbsolute) {
			$path = "\\{$path}";
		}

		return $path;
	}

	public function maybeWordSeparator(array $separators)
	{
		return array_shift($separators);
	}

	public function wordSeparator(array $token)
	{
		return 0 < count($token);
	}

	public function maybeWordLinks($links)
	{
		return $links;
	}

	public function wordLink($word, $separator)
	{
		return $word;
	}

	public function word(array $token)
	{
		return current($token);
	}

	public function __invoke(TokenInput $input)
	{
	}

	public function __get($type)
	{
		return "Lexer::{$type}_";
	}
}
