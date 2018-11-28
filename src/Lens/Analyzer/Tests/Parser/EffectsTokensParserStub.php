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

class EffectsTokensParserStub
{
	public $rules = <<<'EOS'
statements: any statement 1+
statement: or statementAssign statementObjectValue statementEcho statementConstant statementGlobal statementReturn statementThrow
statementAssign: and variable equals value semicolon
statementObjectValue: and valueObject semicolon
statementEcho: and echo valueString semicolon
statementConstant: and define parenthesisLeft valueString comma value isCaseInsensitiveMaybe parenthesisRight semicolon
	isCaseInsensitiveMaybe: any isCaseInsensitive 0 - 1
	isCaseInsensitive: and comma valueBoolean
statementGlobal: and global variable semicolon
statementReturn: and return value semicolon
statementThrow: and throw valueObject semicolon

name: and backslashOptional word nameLinksOptional
backslashOptional: any backslash 0 - 1
nameLinksOptional: any nameLink 0+
nameLink: and backslash word

function: and word arguments
arguments: and parenthesisLeft argumentsMaybe parenthesisRight
argumentsMaybe: any argumentsValues 0 - 1
argumentsValues: and value argumentsLinks
argumentsLinks: any argumentsLink 0+
argumentsLink: and comma value

value: or null valueBoolean valueInteger valueFloat valueString valueArray valueObject
valueBoolean: or false true
valueInteger: or valueIntegerNegative integer
valueIntegerNegative: and minus integer
valueFloat: or valueFloatNegative float
valueFloatNegative: and minus float
valueString: or stringQuoted valueStringDocument
valueStringDocument: and stringDocumentBegin stringDocumentBody stringDocumentEnd
valueArray: and bracketLeft elementsAny bracketRight
elementsAny: any elements 0 - 1
elements: and element elementsLinksAny commaMaybe
element: or mapExplicit mapImplicit
mapExplicit: and key doubleArrow value
key: or valueInteger valueString
mapImplicit: or value
elementsLinksAny: any elementsLink 0+
elementsLink: and comma element
commaMaybe: any comma 0 - 1
valueObject: or valueNew valueObjectMethod variable valueStaticMethod valueFunction
valueNew: and new name arguments
valueObjectMethod: and variable singleArrow word arguments
valueStaticMethod: and name doubleColon word arguments
valueFunction: and name arguments

backslash: get NAMESPACE_SEPARATOR
bracketLeft: get BRACKET_LEFT
bracketRight: get BRACKET_RIGHT
comma: get COMMA
define: get KEYWORD_DEFINE
doubleArrow: get DOUBLE_ARROW
doubleColon: get DOUBLE_COLON
echo: get ECHO
equals: get ASSIGN
false: get KEYWORD_FALSE
float: get VALUE_FLOAT
global: get GLOBAL
integer: get VALUE_INTEGER
minus: get NUMBER_MINUS
new: get NEW
null: get KEYWORD_NULL
parenthesisLeft: get PARENTHESIS_LEFT
parenthesisRight: get PARENTHESIS_RIGHT
return: get CONTROL_RETURN
semicolon: get SEMICOLON
singleArrow: get OBJECT_OPERATOR
stringDocumentBegin: get HEREDOC_BEGIN
stringDocumentBody: get HEREDOC_BODY
stringDocumentEnd: get HEREDOC_END
stringQuoted: get VALUE_STRING
throw: get CONTROL_THROW
true: get KEYWORD_TRUE
variable: get VARIABLE
word: get IDENTIFIER
EOS;

	public $startRule = 'statements';

	public function statements(array $statements)
	{
		return $statements;
	}

	public function statement($statement)
	{
		return $statement;
	}

	public function statementAssign(array $variable, $value)
	{
		return [EffectsParser::TYPE_ASSIGN => [$variable, $value]];
	}

	public function variable(array $token)
	{
		$name = substr(current($token), 1);

		return [EffectsParser::TYPE_VARIABLE => $name];
	}

	public function statementObjectValue($value)
	{
		return $value;
	}

	public function statementEcho($string)
	{
		return [EffectsParser::TYPE_ECHO => $string];
	}

	public function statementConstant($name, $value, $isCaseSensitive)
	{
		return [EffectsParser::TYPE_CONSTANT => [$name, $value, $isCaseSensitive]];
	}

	public function isCaseInsensitiveMaybe(array $values)
	{
		if (isset($values[0])) {
			$isCaseInsensitive = $values[0];
		} else {
			$isCaseInsensitive = false;
		}

		return $isCaseInsensitive;
	}

	public function statementGlobal(array $variable)
	{
		return [EffectsParser::TYPE_GLOBAL => $variable[1]];
	}

	public function statementReturn($value)
	{
		return [EffectsParser::TYPE_RETURN => $value];
	}

	public function statementThrow($value)
	{
		return [EffectsParser::TYPE_THROW => $value];
	}

	public function name($isAbsolute, $word, array $words)
	{
		array_unshift($words, $word);

		$path = implode('\\', $words);

		if ($isAbsolute) {
			$path = "\\{$path}";
		}

		return $path;
	}

	public function backslashOptional(array $backslashes)
	{
		return 0 < count($backslashes);
	}

	public function nameLinksOptional(array $words)
	{
		return $words;
	}

	public function nameLink($backslash, $word)
	{
		return $word;
	}

	public function backslash(array $token)
	{
		return current($token);
	}

	public function word(array $token)
	{
		return current($token);
	}

	public function function($word, array $arguments)
	{
		return [$word, $arguments];
	}

	public function arguments(array $arguments)
	{
		return $arguments;
	}

	public function argumentsMaybe(array $arguments)
	{
		if (count($arguments) === 1) {
			$arguments = current($arguments);
		}

		return $arguments;
	}

	public function argumentsValues($argument, array $arguments)
	{
		array_unshift($arguments, $argument);

		return $arguments;
	}

	public function argumentsLinks(array $arguments)
	{
		return $arguments;
	}

	public function argumentsLink($value)
	{
		return $value;
	}

	public function value($value)
	{
		return $value;
	}

	public function null()
	{
		return null;
	}

	public function valueBoolean($value)
	{
		return $value;
	}

	public function false()
	{
		return false;
	}

	public function true()
	{
		return true;
	}

	public function valueInteger($value)
	{
		return $value;
	}

	public function valueIntegerNegative($value)
	{
		return -$value;
	}

	public function integer(array $token)
	{
		return (int)current($token);
	}

	public function valueFloat($value)
	{
		return $value;
	}

	public function valueFloatNegative($value)
	{
		return -$value;
	}

	public function float(array $token)
	{
		return (float)current($token);
	}

	public function valueString($value)
	{
		return $value;
	}

	public function stringQuoted(array $token)
	{
		$value = current($token);

		return eval("return {$value};");
	}

	public function valueStringDocument($begin, $body, $end)
	{
		$value = $begin . $body . $end;

		return eval("return {$value};\n");
	}

	public function stringDocumentBegin(array $token)
	{
		return current($token);
	}

	public function stringDocumentBody(array $token)
	{
		return current($token);
	}

	public function stringDocumentEnd(array $token)
	{
		return current($token);
	}

	public function valueArray(array $elements)
	{
		return [EffectsParser::TYPE_ARRAY => $elements];
	}

	public function elementsAny(array $elements)
	{
		if (count($elements) === 1) {
			$elements = current($elements);
		}

		return $elements;
	}

	public function elements(array $mapA, array $mapB)
	{
		return array_merge($mapA, $mapB);
	}

	public function element($value)
	{
		return $value;
	}

	public function mapExplicit($key, $value)
	{
		return [$key => $value];
	}

	public function key($value)
	{
		return $value;
	}

	public function mapImplicit($value)
	{
		return [$value];
	}

	public function elementsLinksAny(array $elementsLink)
	{
		if (count($elementsLink) === 0) {
			$map = [];
		} else {
			$map = call_user_func_array('array_merge', $elementsLink);
		}

		return $map;
	}

	public function valueObjectMethod($variable, $word, array $arguments)
	{
		return [EffectsParser::TYPE_CALL => [$variable, $word, $arguments]];
	}

	public function valueStaticMethod($class, $word, array $arguments)
	{
		return [EffectsParser::TYPE_CALL => [$class, $word, $arguments]];
	}

	public function valueNew($name, array $arguments)
	{
		return [EffectsParser::TYPE_CALL => [$name, '__construct', $arguments]];
	}

	public function valueFunction($name, array $arguments)
	{
		return [EffectsParser::TYPE_CALL => [null, $name, $arguments]];
	}

	public function elementsLink($value)
	{
		return $value;
	}

	public function __invoke(TokenInput $input)
	{
	}

	public function __get($type)
	{
		return "Lexer::{$type}_";
	}
}
