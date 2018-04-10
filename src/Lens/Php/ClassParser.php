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

namespace Lens_0_0_56\Lens\Php;

use Lens_0_0_56\SpencerMortensen\Parser\String\Parser;
use Lens_0_0_56\SpencerMortensen\Parser\String\Rules;

class ClassParser extends Parser
{
	/** @var string */
	private $input;

	public function __construct()
	{
		$grammar = <<<'EOS'
class: AND classDeclaration openingBrace classBody closingBrace
classDeclaration: RE [^{]+
openingBrace: STRING {
classBody: MANY classBodyUnit 0
classBodyUnit: OR fluff functionDefinition comment string
fluff: RE (?:(?![#'"{}]|/\*|//|<<<).)+
functionDefinition: AND openingBrace functionBody closingBrace
functionBody: MANY functionBodyUnit 0
functionBodyUnit: OR safeSymbols call comment string group character
safeSymbols: RE [^\w\\/#'"<{}$-]+
call: OR functionCall excludedCall constructorCall staticMethodCall
functionCall: RE ([\w\\]+)\s*\(
excludedCall: RE (?:->\s*|\$)[\w\\]+\s*\(
constructorCall: RE \bnew\s+([\w\\]+)\s*\(
staticMethodCall: RE ([\w\\]+)::(\w+)\s*\(
comment: OR multiLineComment singleLineComment
multiLineComment: RE /\*.*?\*/
singleLineComment: RE (?://|#).*?(?:\n|$)
string: OR singleQuotedString doubleQuotedString docString
singleQuotedString: RE '(?:\\\\|\\\'|[^'])*'
doubleQuotedString: RE "(?:\\\\|\\\"|[^"])*"
docString: RE <<<(['"]?)(\w+)\1\r?\n.*?\n\2;?(?:\r?\n|$)
group: AND openingBrace functionBody closingBrace
character: RE [^}]
closingBrace: STRING }
EOS;

		$rules = new Rules($this, $grammar);
		$rule = $rules->getRule('class');

		parent::__construct($rule);
	}

	public function parse($input)
	{
		$this->input = $input;

		return parent::parse($input);
	}

	public function getClass(array $matches)
	{
		return $matches[2];
	}

	public function getClassBody(array $matches)
	{
		return self::merge($matches);
	}

	public function getFluff()
	{
		return null;
	}

	public function getFunctionDefinition(array $matches)
	{
		return $matches[1];
	}

	public function getFunctionBody(array $matches)
	{
		return self::merge($matches);
	}

	private static function merge(array $array)
	{
		$array = array_filter($array, array('self', 'isNonNull'));

		if (0 < count($array)) {
			$array = call_user_func_array('array_merge', $array);
		}

		return $array;
	}

	private static function isNonNull($value)
	{
		return $value !== null;
	}

	public function getSafeSymbols()
	{
		return null;
	}

	public function getFunctionCall(array $match)
	{
		$namespace = null;
		$function = $match[1];

		if (Semantics::isKeyword($function)) {
			return null;
		}

		return array(array($namespace, $function));
	}

	public function getExcludedCall()
	{
		return null;
	}

	public function getConstructorCall(array $match)
	{
		$namespace = $match[1];
		$function = '__construct';

		return array(array($namespace, $function));
	}

	public function getStaticMethodCall(array $match)
	{
		$namespace = $match[1];
		$function = $match[2];

		return array(array($namespace, $function));
	}

	public function getComment()
	{
		return null;
	}

	public function getString()
	{
		return null;
	}

	public function getGroup(array $matches)
	{
		return $matches[1];
	}

	public function getCharacter()
	{
		return null;
	}
}
