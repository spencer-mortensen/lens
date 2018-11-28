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

class EffectsTokensParser
{
	/** @var TokenInput */
	private $input;

	/** @var mixed */
	private $output;

	/** @var array|null */
	private $expectation;

	/** @var mixed */
	private $position;

	public function parse(TokenInput $input)
	{
		$this->input = $input;
		$this->expectation = null;
		$this->position = null;

		if ($this->readStatements($output)) {
			$this->output = $output;
			$this->position = $this->input->getPosition();
			return true;
		}

		$this->output = null;
		return false;
	}

	public function getOutput()
	{
		return $this->output;
	}

	public function getExpectation()
	{
		return $this->expectation;
	}

	public function getPosition()
	{
		return $this->position;
	}

	private function readStatements(&$output)
	{
		$output = [];

		if (!$this->readStatement($output[])) {
			return false;
		}

		while ($this->readStatement($input)) {
			$output[] = $input;
		}

		return true;
	}

	private function readStatement(&$output)
	{
		if ($this->readStatementAssign($output) || $this->readStatementObjectValue($output) || $this->readStatementEcho($output) || $this->readStatementConstant($output) || $this->readStatementGlobal($output) || $this->readStatementReturn($output) || $this->readStatementThrow($output)) {
			return true;
		}

		$this->setExpectation('statement');
		return false;
	}

	private function readStatementAssign(&$output)
	{
		$position = $this->input->getPosition();

		if ($this->readVariable($variable) && $this->readEquals() && $this->readValue($value) && $this->readSemicolon()) {
			$output = [EffectsParser::TYPE_ASSIGN => [$variable, $value]];
			return true;
		}

		$this->input->setPosition($position);
		return false;
	}

	private function readVariable(&$output)
	{
		if ($this->input->read(Lexer::VARIABLE_, $token)) {
			$name = substr(current($token), 1);

			$output = [EffectsParser::TYPE_VARIABLE => $name];
			return true;
		}

		$this->setExpectation('variable');
		return false;
	}

	private function readEquals()
	{
		if ($this->input->read(Lexer::ASSIGN_)) {
			return true;
		}

		$this->setExpectation('equals');
		return false;
	}

	private function readValue(&$output)
	{
		if ($this->readNull($output) || $this->readValueBoolean($output) || $this->readValueInteger($output) || $this->readValueFloat($output) || $this->readValueString($output) || $this->readValueArray($output) || $this->readValueObject($output)) {
			return true;
		}

		$this->setExpectation('value');
		return false;
	}

	private function readNull(&$output)
	{
		if ($this->input->read(Lexer::KEYWORD_NULL_)) {
			$output = null;
			return true;
		}

		$this->setExpectation('null');
		return false;
	}

	private function readValueBoolean(&$output)
	{
		if ($this->readFalse($output) || $this->readTrue($output)) {
			return true;
		}

		$this->setExpectation('valueBoolean');
		return false;
	}

	private function readFalse(&$output)
	{
		if ($this->input->read(Lexer::KEYWORD_FALSE_)) {
			$output = false;
			return true;
		}

		$this->setExpectation('false');
		return false;
	}

	private function readTrue(&$output)
	{
		if ($this->input->read(Lexer::KEYWORD_TRUE_)) {
			$output = true;
			return true;
		}

		$this->setExpectation('true');
		return false;
	}

	private function readValueInteger(&$output)
	{
		if ($this->readValueIntegerNegative($output) || $this->readInteger($output)) {
			return true;
		}

		$this->setExpectation('valueInteger');
		return false;
	}

	private function readValueIntegerNegative(&$output)
	{
		$position = $this->input->getPosition();

		if ($this->readMinus() && $this->readInteger($value)) {
			$output = -$value;
			return true;
		}

		$this->input->setPosition($position);
		return false;
	}

	private function readMinus()
	{
		if ($this->input->read(Lexer::NUMBER_MINUS_)) {
			return true;
		}

		$this->setExpectation('minus');
		return false;
	}

	private function readInteger(&$output)
	{
		if ($this->input->read(Lexer::VALUE_INTEGER_, $token)) {
			$output = (int)current($token);
			return true;
		}

		$this->setExpectation('integer');
		return false;
	}

	private function readValueFloat(&$output)
	{
		if ($this->readValueFloatNegative($output) || $this->readFloat($output)) {
			return true;
		}

		$this->setExpectation('valueFloat');
		return false;
	}

	private function readValueFloatNegative(&$output)
	{
		$position = $this->input->getPosition();

		if ($this->readMinus() && $this->readFloat($value)) {
			$output = -$value;
			return true;
		}

		$this->input->setPosition($position);
		return false;
	}

	private function readFloat(&$output)
	{
		if ($this->input->read(Lexer::VALUE_FLOAT_, $token)) {
			$output = (float)current($token);
			return true;
		}

		$this->setExpectation('float');
		return false;
	}

	private function readValueString(&$output)
	{
		if ($this->readStringQuoted($output) || $this->readValueStringDocument($output)) {
			return true;
		}

		$this->setExpectation('valueString');
		return false;
	}

	private function readStringQuoted(&$output)
	{
		if ($this->input->read(Lexer::VALUE_STRING_, $token)) {
			$value = current($token);

			$output = eval("return {$value};");
			return true;
		}

		$this->setExpectation('stringQuoted');
		return false;
	}

	private function readValueStringDocument(&$output)
	{
		$position = $this->input->getPosition();

		if ($this->readStringDocumentBegin($begin) && $this->readStringDocumentBody($body) && $this->readStringDocumentEnd($end)) {
			$value = $begin . $body . $end;

			$output = eval("return {$value};\n");
			return true;
		}

		$this->input->setPosition($position);
		return false;
	}

	private function readStringDocumentBegin(&$output)
	{
		if ($this->input->read(Lexer::HEREDOC_BEGIN_, $token)) {
			$output = current($token);
			return true;
		}

		$this->setExpectation('stringDocumentBegin');
		return false;
	}

	private function readStringDocumentBody(&$output)
	{
		if ($this->input->read(Lexer::HEREDOC_BODY_, $token)) {
			$output = current($token);
			return true;
		}

		$this->setExpectation('stringDocumentBody');
		return false;
	}

	private function readStringDocumentEnd(&$output)
	{
		if ($this->input->read(Lexer::HEREDOC_END_, $token)) {
			$output = current($token);
			return true;
		}

		$this->setExpectation('stringDocumentEnd');
		return false;
	}

	private function readValueArray(&$output)
	{
		$position = $this->input->getPosition();

		if ($this->readBracketLeft() && $this->readElementsAny($elements) && $this->readBracketRight()) {
			$output = [EffectsParser::TYPE_ARRAY => $elements];
			return true;
		}

		$this->input->setPosition($position);
		return false;
	}

	private function readBracketLeft()
	{
		if ($this->input->read(Lexer::BRACKET_LEFT_)) {
			return true;
		}

		$this->setExpectation('bracketLeft');
		return false;
	}

	private function readElementsAny(&$output)
	{
		$elements = [];

		if ($this->readElements($input)) {
			$elements[] = $input;
		}

		if (count($elements) === 1) {
			$elements = current($elements);
		}

		$output = $elements;

		return true;
	}

	private function readElements(&$output)
	{
		$position = $this->input->getPosition();

		if ($this->readElement($mapA) && $this->readElementsLinksAny($mapB) && $this->readCommaMaybe()) {
			$output = array_merge($mapA, $mapB);
			return true;
		}

		$this->input->setPosition($position);
		return false;
	}

	private function readElement(&$output)
	{
		if ($this->readMapExplicit($output) || $this->readMapImplicit($output)) {
			return true;
		}

		$this->setExpectation('element');
		return false;
	}

	private function readMapExplicit(&$output)
	{
		$position = $this->input->getPosition();

		if ($this->readKey($key) && $this->readDoubleArrow() && $this->readValue($value)) {
			$output = [$key => $value];
			return true;
		}

		$this->input->setPosition($position);
		return false;
	}

	private function readKey(&$output)
	{
		if ($this->readValueInteger($output) || $this->readValueString($output)) {
			return true;
		}

		$this->setExpectation('key');
		return false;
	}

	private function readDoubleArrow()
	{
		if ($this->input->read(Lexer::DOUBLE_ARROW_)) {
			return true;
		}

		$this->setExpectation('doubleArrow');
		return false;
	}

	private function readMapImplicit(&$output)
	{
		if ($this->readValue($value)) {
			$output = [$value];
			return true;
		}

		$this->setExpectation('mapImplicit');
		return false;
	}

	private function readElementsLinksAny(&$output)
	{
		$elementsLink = [];

		while ($this->readElementsLink($input)) {
			$elementsLink[] = $input;
		}

		if (count($elementsLink) === 0) {
			$map = [];
		} else {
			$map = call_user_func_array('array_merge', $elementsLink);
		}

		$output = $map;

		return true;
	}

	private function readElementsLink(&$output)
	{
		$position = $this->input->getPosition();

		if ($this->readComma() && $this->readElement($output)) {
			return true;
		}

		$this->input->setPosition($position);
		return false;
	}

	private function readComma()
	{
		if ($this->input->read(Lexer::COMMA_)) {
			return true;
		}

		$this->setExpectation('comma');
		return false;
	}

	private function readCommaMaybe()
	{
		$this->readComma();

		return true;
	}

	private function readBracketRight()
	{
		if ($this->input->read(Lexer::BRACKET_RIGHT_)) {
			return true;
		}

		$this->setExpectation('bracketRight');
		return false;
	}

	private function readValueObject(&$output)
	{
		if ($this->readValueNew($output) || $this->readValueObjectMethod($output) || $this->readVariable($output) || $this->readValueStaticMethod($output) || $this->readValueFunction($output)) {
			return true;
		}

		$this->setExpectation('valueObject');
		return false;
	}

	private function readValueNew(&$output)
	{
		$position = $this->input->getPosition();

		if ($this->readNew() && $this->readName($name) && $this->readArguments($arguments)) {
			$output = [EffectsParser::TYPE_CALL => [$name, '__construct', $arguments]];
			return true;
		}

		$this->input->setPosition($position);
		return false;
	}

	private function readNew()
	{
		if ($this->input->read(Lexer::NEW_)) {
			return true;
		}

		$this->setExpectation('new');
		return false;
	}

	private function readName(&$output)
	{
		$position = $this->input->getPosition();

		if ($this->readBackslashOptional($isAbsolute) && $this->readWord($word) && $this->readNameLinksOptional($words)) {
			array_unshift($words, $word);

			$path = implode('\\', $words);

			if ($isAbsolute) {
				$path = "\\{$path}";
			}

			$output = $path;
			return true;
		}

		$this->input->setPosition($position);
		return false;
	}

	private function readBackslashOptional(&$output)
	{
		$backslashes = [];

		if ($this->readBackslash($input)) {
			$backslashes[] = $input;
		}

		$output = 0 < count($backslashes);

		return true;
	}

	private function readBackslash(&$output)
	{
		if ($this->input->read(Lexer::NAMESPACE_SEPARATOR_, $token)) {
			$output = current($token);
			return true;
		}

		$this->setExpectation('backslash');
		return false;
	}

	private function readWord(&$output)
	{
		if ($this->input->read(Lexer::IDENTIFIER_, $token)) {
			$output = current($token);
			return true;
		}

		$this->setExpectation('word');
		return false;
	}

	private function readNameLinksOptional(&$output)
	{
		$output = [];

		while ($this->readNameLink($input)) {
			$output[] = $input;
		}

		return true;
	}

	private function readNameLink(&$output)
	{
		$position = $this->input->getPosition();

		if ($this->readBackslash($backslash) && $this->readWord($word)) {
			$output = $word;
			return true;
		}

		$this->input->setPosition($position);
		return false;
	}

	private function readArguments(&$output)
	{
		$position = $this->input->getPosition();

		if ($this->readParenthesisLeft() && $this->readArgumentsMaybe($output) && $this->readParenthesisRight()) {
			return true;
		}

		$this->input->setPosition($position);
		return false;
	}

	private function readParenthesisLeft()
	{
		if ($this->input->read(Lexer::PARENTHESIS_LEFT_)) {
			return true;
		}

		$this->setExpectation('parenthesisLeft');
		return false;
	}

	private function readArgumentsMaybe(&$output)
	{
		$arguments = [];

		if ($this->readArgumentsValues($input)) {
			$arguments[] = $input;
		}

		if (count($arguments) === 1) {
			$arguments = current($arguments);
		}

		$output = $arguments;

		return true;
	}

	private function readArgumentsValues(&$output)
	{
		$position = $this->input->getPosition();

		if ($this->readValue($argument) && $this->readArgumentsLinks($arguments)) {
			array_unshift($arguments, $argument);

			$output = $arguments;
			return true;
		}

		$this->input->setPosition($position);
		return false;
	}

	private function readArgumentsLinks(&$output)
	{
		$output = [];

		while ($this->readArgumentsLink($input)) {
			$output[] = $input;
		}

		return true;
	}

	private function readArgumentsLink(&$output)
	{
		$position = $this->input->getPosition();

		if ($this->readComma() && $this->readValue($output)) {
			return true;
		}

		$this->input->setPosition($position);
		return false;
	}

	private function readParenthesisRight()
	{
		if ($this->input->read(Lexer::PARENTHESIS_RIGHT_)) {
			return true;
		}

		$this->setExpectation('parenthesisRight');
		return false;
	}

	private function readValueObjectMethod(&$output)
	{
		$position = $this->input->getPosition();

		if ($this->readVariable($variable) && $this->readSingleArrow() && $this->readWord($word) && $this->readArguments($arguments)) {
			$output = [EffectsParser::TYPE_CALL => [$variable, $word, $arguments]];
			return true;
		}

		$this->input->setPosition($position);
		return false;
	}

	private function readSingleArrow()
	{
		if ($this->input->read(Lexer::OBJECT_OPERATOR_)) {
			return true;
		}

		$this->setExpectation('singleArrow');
		return false;
	}

	private function readValueStaticMethod(&$output)
	{
		$position = $this->input->getPosition();

		if ($this->readName($class) && $this->readDoubleColon() && $this->readWord($word) && $this->readArguments($arguments)) {
			$output = [EffectsParser::TYPE_CALL => [$class, $word, $arguments]];
			return true;
		}

		$this->input->setPosition($position);
		return false;
	}

	private function readDoubleColon()
	{
		if ($this->input->read(Lexer::DOUBLE_COLON_)) {
			return true;
		}

		$this->setExpectation('doubleColon');
		return false;
	}

	private function readValueFunction(&$output)
	{
		$position = $this->input->getPosition();

		if ($this->readName($name) && $this->readArguments($arguments)) {
			$output = [EffectsParser::TYPE_CALL => [null, $name, $arguments]];
			return true;
		}

		$this->input->setPosition($position);
		return false;
	}

	private function readSemicolon()
	{
		if ($this->input->read(Lexer::SEMICOLON_)) {
			return true;
		}

		$this->setExpectation('semicolon');
		return false;
	}

	private function readStatementObjectValue(&$output)
	{
		$position = $this->input->getPosition();

		if ($this->readValueObject($output) && $this->readSemicolon()) {
			return true;
		}

		$this->input->setPosition($position);
		return false;
	}

	private function readStatementEcho(&$output)
	{
		$position = $this->input->getPosition();

		if ($this->readEcho() && $this->readValueString($string) && $this->readSemicolon()) {
			$output = [EffectsParser::TYPE_ECHO => $string];
			return true;
		}

		$this->input->setPosition($position);
		return false;
	}

	private function readEcho()
	{
		if ($this->input->read(Lexer::ECHO_)) {
			return true;
		}

		$this->setExpectation('echo');
		return false;
	}

	private function readStatementConstant(&$output)
	{
		$position = $this->input->getPosition();

		if ($this->readDefine() && $this->readParenthesisLeft() && $this->readValueString($name) && $this->readComma() && $this->readValue($value) && $this->readIsCaseInsensitiveMaybe($isCaseSensitive) && $this->readParenthesisRight() && $this->readSemicolon()) {
			$output = [EffectsParser::TYPE_CONSTANT => [$name, $value, $isCaseSensitive]];
			return true;
		}

		$this->input->setPosition($position);
		return false;
	}

	private function readDefine()
	{
		if ($this->input->read(Lexer::KEYWORD_DEFINE_)) {
			return true;
		}

		$this->setExpectation('define');
		return false;
	}

	private function readIsCaseInsensitiveMaybe(&$output)
	{
		$values = [];

		if ($this->readIsCaseInsensitive($input)) {
			$values[] = $input;
		}

		if (isset($values[0])) {
			$isCaseInsensitive = $values[0];
		} else {
			$isCaseInsensitive = false;
		}

		$output = $isCaseInsensitive;

		return true;
	}

	private function readIsCaseInsensitive(&$output)
	{
		$position = $this->input->getPosition();

		if ($this->readComma() && $this->readValueBoolean($output)) {
			return true;
		}

		$this->input->setPosition($position);
		return false;
	}

	private function readStatementGlobal(&$output)
	{
		$position = $this->input->getPosition();

		if ($this->readGlobal() && $this->readVariable($variable) && $this->readSemicolon()) {
			$output = [EffectsParser::TYPE_GLOBAL => $variable[1]];
			return true;
		}

		$this->input->setPosition($position);
		return false;
	}

	private function readGlobal()
	{
		if ($this->input->read(Lexer::GLOBAL_)) {
			return true;
		}

		$this->setExpectation('global');
		return false;
	}

	private function readStatementReturn(&$output)
	{
		$position = $this->input->getPosition();

		if ($this->readReturn() && $this->readValue($value) && $this->readSemicolon()) {
			$output = [EffectsParser::TYPE_RETURN => $value];
			return true;
		}

		$this->input->setPosition($position);
		return false;
	}

	private function readReturn()
	{
		if ($this->input->read(Lexer::CONTROL_RETURN_)) {
			return true;
		}

		$this->setExpectation('return');
		return false;
	}

	private function readStatementThrow(&$output)
	{
		$position = $this->input->getPosition();

		if ($this->readThrow() && $this->readValueObject($value) && $this->readSemicolon()) {
			$output = [EffectsParser::TYPE_THROW => $value];
			return true;
		}

		$this->input->setPosition($position);
		return false;
	}

	private function readThrow()
	{
		if ($this->input->read(Lexer::CONTROL_THROW_)) {
			return true;
		}

		$this->setExpectation('throw');
		return false;
	}

	private function setExpectation($rule)
	{
		$this->expectation = $rule;
		$this->position = $this->input->getPosition();
	}
}
