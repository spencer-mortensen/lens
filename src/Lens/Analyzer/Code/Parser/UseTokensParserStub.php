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

use _Lens\SpencerMortensen\Parser\Input\TokenInput;
use _Lens\Lens\Php\Lexer;

class UseTokensParserStub
{
	public $rules = <<<'EOS'
useStatement: and useKeyword useBody semicolon
useKeyword: get USE
useBody: or useFunction useClass
useFunction: and useFunctionKeyword useMaps
useFunctionKeyword: get FUNCTION
useClass: or useMaps
useMaps: or useMapList useMapGroup
useMapList: and useMap anyNamespaceMapLinks
useMap: and useName maybeAlias
useName: and anyNamespaceNameLinks identifier
anyNamespaceNameLinks: any useNameLink 0+
useNameLink: and identifier useNameSeparator
maybeAlias: any alias 0 - 1
alias: and aliasKeyword identifier
anyNamespaceMapLinks: any useMapLink 0+
useMapLink: and comma useMap
comma: get COMMA
useMapGroup: and someNamespaceNameLinks leftBrace useMap rightBrace
someNamespaceNameLinks: any useNameLink 1+
aliasKeyword: get NAMESPACE_AS
leftBrace: get BRACE_LEFT
useNameSeparator: get NAMESPACE_SEPARATOR
identifier: get IDENTIFIER
rightBrace: get BRACE_RIGHT
semicolon: get SEMICOLON
EOS;

	public $startRule = 'useStatement';

	public function useStatement(array $value)
	{
		return $value;
	}

	public function useFunction(array $useMap)
	{
		return ['function', $useMap];
	}

	public function useClass(array $useMap)
	{
		return ['class', $useMap];
	}

	public function useMapList(array $useMap, array $anyNamespaceMapLinks)
	{
		if (0 < count($anyNamespaceMapLinks)) {
			return array_merge($useMap, $anyNamespaceMapLinks);
		} else {
			return $useMap;
		}
	}

	public function useMap($name, $maybeAlias)
	{
		if (isset($maybeAlias)) {
			$alias = $maybeAlias;
		} else {
			$slash = strrpos($name, '\\');

			if (is_int($slash)) {
				$alias = substr($name, $slash + 1);
			} else {
				$alias = $name;
			}
		}

		return [
			$alias => $name
		];
	}

	public function useName(array $words, $word)
	{
		$words[] = $word;
		return implode('\\', $words);
	}

	public function anyNamespaceNameLinks(array $words)
	{
		return $words;
	}

	public function useNameLink($word)
	{
		return $word;
	}

	public function identifier(array $token)
	{
		return current($token);
	}

	public function maybeAlias(array $words)
	{
		return array_shift($words);
	}

	public function anyNamespaceMapLinks(array $useMapLinks)
	{
		if (0 < count($useMapLinks)) {
			return call_user_func_array('array_merge', $useMapLinks);
		} else {
			return [];
		}
	}

	public function useMapLink($useMap)
	{
		return $useMap;
	}

	public function useMapGroup(array $useNameLinks, array $useMapList)
	{
		$prefix = implode('\\', $useNameLinks);

		foreach ($useMapList as $alias => &$path) {
			$path = "{$prefix}\\{$path}";
		}

		return $useMapList;
	}

	public function someNamespaceNameLinks(array $useNameLinks)
	{
		return $useNameLinks;
	}

	public function __invoke(TokenInput $input)
	{
	}

	public function __get($type)
	{
		return "Lexer::{$type}_";
	}
}
