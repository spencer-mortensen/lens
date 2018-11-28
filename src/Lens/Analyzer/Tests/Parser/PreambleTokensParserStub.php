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

use _Lens\Lens\Php\Lexer;
use _Lens\SpencerMortensen\Parser\Input\TokenInput;

class PreambleTokensParserStub
{
	public $rules = <<<'EOS'
namespace: or namespaceDeclared namespaceImplied
namespaceDeclared: and namespaceKeyword namespaceName semicolon useStatements
namespaceKeyword: get NAMESPACE
namespaceName: and anyNamespaceNameLinks namespaceWord
anyNamespaceNameLinks: any namespaceNameLink 0+
namespaceNameLink: and namespaceWord namespaceNameSeparator
namespaceWord: get IDENTIFIER
namespaceNameSeparator: get NAMESPACE_SEPARATOR
semicolon: get SEMICOLON
useStatements: any useStatement 0+
useStatement: and useKeyword namespaceMaps semicolon
useKeyword: get USE
namespaceMaps: or namespaceMapList namespaceMapGroup
namespaceMapList: and namespaceMap anyNamespaceMapLinks
namespaceMap: and namespaceName maybeAlias
maybeAlias: any alias 0 - 1
alias: and aliasKeyword namespaceWord
aliasKeyword: get NAMESPACE_AS
anyNamespaceMapLinks: any namespaceMapLink 0+
namespaceMapLink: and comma namespaceMap
comma: get COMMA
namespaceMapGroup: and someNamespaceNameLinks leftBrace namespaceMapList rightBrace
someNamespaceNameLinks: any namespaceNameLink 1+
leftBrace: get BRACE_LEFT
rightBrace: get BRACE_RIGHT
namespaceImplied: and useStatements
EOS;

	public $startRule = 'namespace';

	public function namespaceDeclared($name, array $use)
	{
		return [
			'namespace' => $name,
			'uses' => $use
		];
	}

	public function namespaceName(array $words, $word)
	{
		$words[] = $word;
		return implode('\\', $words);
	}

	public function anyNamespaceNameLinks(array $words)
	{
		return $words;
	}

	public function namespaceNameLink($word)
	{
		return $word;
	}

	public function namespaceWord(array $token)
	{
		return current($token);
	}


	public function useStatements(array $maps)
	{
		if (0 < count($maps)) {
			return call_user_func_array('array_merge', $maps);
		} else {
			return [];
		}
	}

	public function useStatement(array $namespaceMapList)
	{
		return $namespaceMapList;
	}

	public function namespaceMapList(array $namespaceMap, array $anyNamespaceMapLinks)
	{
		if (0 < count($anyNamespaceMapLinks)) {
			return array_merge($namespaceMap, $anyNamespaceMapLinks);
		} else {
			return $namespaceMap;
		}
	}

	public function namespaceMap($name, $maybeAlias)
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

	public function maybeAlias(array $words)
	{
		return array_shift($words);
	}

	public function anyNamespaceMapLinks(array $namespaceMapLinks)
	{
		if (0 < count($namespaceMapLinks)) {
			return call_user_func_array('array_merge', $namespaceMapLinks);
		} else {
			return [];
		}
	}

	public function namespaceMapLink($namespaceMap)
	{
		return $namespaceMap;
	}

	public function namespaceMapGroup(array $namespaceNameLinks, array $namespaceMapList)
	{
		$prefix = implode('\\', $namespaceNameLinks);

		foreach ($namespaceMapList as $alias => &$path) {
			$path = "{$prefix}\\{$path}";
		}

		return $namespaceMapList;
	}

	public function someNamespaceNameLinks(array $namespaceNameLinks)
	{
		return $namespaceNameLinks;
	}

	public function namespaceImplied(array $use)
	{
		return [
			'name' => null,
			'use' => $use
		];
	}

	public function __invoke(TokenInput $input)
	{
	}

	public function __get($type)
	{
		return "Lexer::{$type}_";
	}
}
