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

class Lexer
{
	const ABSTRACT_ = 1; // abstract
	const ARRAY_ = 2; // array
	const ASSIGN_ = 3; // =
	const ASSIGN_BIT_AND_ = 4; // &=
	const ASSIGN_BIT_OR_ = 5; // |=
	const ASSIGN_BIT_SHIFT_LEFT_ = 6; // <<=
	const ASSIGN_BIT_SHIFT_RIGHT_ = 7; // >>=
	const ASSIGN_BIT_XOR_ = 8; // ^=
	const ASSIGN_CONCATENATE_ = 9; // .=
	const ASSIGN_NUMBER_DECREMENT_ = 10; // --
	const ASSIGN_NUMBER_DIVIDE_ = 11; // /=
	const ASSIGN_NUMBER_INCREMENT_ = 12; // ++
	const ASSIGN_NUMBER_MINUS_ = 13; // -=
	const ASSIGN_NUMBER_MODULO_ = 14; // %=
	const ASSIGN_NUMBER_PLUS_ = 15; // +=
	const ASSIGN_NUMBER_POWER_ = 16; // **=
	const ASSIGN_NUMBER_TIMES_ = 17; // *=
	const AT_ = 18; // @
	const BAD_CHARACTER_ = 19; // \x07
	const BIT_AND_ = 20; // &
	const BIT_OR_ = 21; // |
	const BIT_SHIFT_LEFT_ = 22; // <<
	const BIT_SHIFT_RIGHT_ = 23; // >>
	const BIT_XOR_ = 24; // ^
	const BOOLEAN_AND_ = 25; // &&
	const BOOLEAN_NOT_ = 26; // !
	const BOOLEAN_OR_ = 27; // ||
	const BRACE_LEFT_ = 28; // {
	const BRACE_RIGHT_ = 29; // }
	const BRACKET_LEFT_ = 30; // [
	const BRACKET_RIGHT_ = 31; // ]
	const CALLABLE_ = 32;
	const CAST_ARRAY_ = 33; // (array)
	const CAST_BOOLEAN_ = 34; // (bool) or (boolean)
	const CAST_FLOAT_ = 35; // (float), (double), or (real)
	const CAST_INTEGER_ = 36; // (int) or (integer)
	const CAST_NULL_ = 37; // (unset)
	const CAST_OBJECT_ = 38; // (object)
	const CAST_STRING_ = 39; // (string)
	const CLASS_ = 40;
	const CLASS_C_ = 41;
	const CLONE_ = 42;
	const COALESCE_ = 43;
	const COLON_ = 44;
	const COMMA_ = 45; // ,
	const COMMENT_ = 46; // #, //, or /* */
	const CONCATENATE_ = 47; // .
	const CONST_ = 48;
	const CONTROL_BREAK_ = 49; // break
	const CONTROL_CASE_ = 50; // case
	const CONTROL_CATCH_ = 51; // catch
	const CONTROL_CONTINUE_ = 52; // continue
	const CONTROL_DECLARE_ = 53; // declare
	const CONTROL_DECLARE_END_ = 54;
	const CONTROL_DEFAULT_ = 55; // default
	const CONTROL_DO_ = 56; // do
	const CONTROL_ELSE_ = 57; // else
	const CONTROL_ELSE_IF_ = 58; // elseif
	const CONTROL_FINALLY_ = 59; // finally
	const CONTROL_FOREACH_ = 60; // foreach
	const CONTROL_FOREACH_END_ = 61; // endforeach
	const CONTROL_FOR_ = 62; // for
	const CONTROL_FOR_END_ = 63; // endfor
	const CONTROL_GOTO_ = 64; // goto
	const CONTROL_IF_ = 65; // if
	const CONTROL_IF_END_ = 66; // endif
	const CONTROL_INCLUDE_ = 67; // include
	const CONTROL_INCLUDE_ONCE_ = 68; // include_once
	const CONTROL_REQUIRE_ = 69; // require
	const CONTROL_REQUIRE_ONCE_ = 70; // require_once
	const CONTROL_RETURN_ = 71; // return
	const CONTROL_SWITCH_ = 72; // switch
	const CONTROL_SWITCH_END_ = 73; // endswitch
	const CONTROL_THROW_ = 74; // throw
	const CONTROL_TRY_ = 75; // try
	const CONTROL_WHILE_ = 76; // while
	const CONTROL_WHILE_END_ = 77; // endwhile
	const DIR_ = 79;
	const DOLLAR_ = 80;
	const DOLLAR_OPEN_CURLY_BRACES_ = 81;
	const DOUBLE_ARROW_ = 82; // =>
	const DOUBLE_COLON_ = 83; // ::
	const ECHO_ = 84;
	const ELLIPSIS_ = 85;
	const EMPTY_ = 86;
	const EVAL_ = 87;
	const EXIT_ = 88;
	const EXTENDS_ = 89; // extends
	const FILE_ = 90;
	const FINAL_ = 91;
	const FUNCTION_ = 92; // function
	const FUNC_C_ = 93;
	const GLOBAL_ = 94;
	const HALT_COMPILER_ = 95;
	const HEREDOC_BEGIN_ = 96;
	const HEREDOC_BODY_ = 97;
	const HEREDOC_END_ = 98;
	const HTML_ = 99;
	const IDENTIFIER_ = 100; // myWord
	const IMPLEMENTS_ = 101;
	const INSTANCEOF_ = 102;
	const INSTEADOF_ = 103;
	const INTERFACE_ = 104;
	const ISSET_ = 105;
	const IS_EQUAL_ = 106; // ==
	const IS_GREATER_ = 107; // >
	const IS_GREATER_OR_EQUAL_ = 108; // >=
	const IS_IDENTICAL_ = 109; // ===
	const IS_LESSER_ = 110; // <
	const IS_LESSER_OR_EQUAL_ = 111; // <=
	const IS_NOT_EQUAL_ = 112; // != or <>
	const IS_NOT_IDENTICAL_ = 113; // !==
	const KEYWORD_DEFINE_ = 114; // define
	const KEYWORD_FALSE_ = 115; // false
	const KEYWORD_NULL_ = 116; // null
	const KEYWORD_TRUE_ = 117; // true
	const LINE_ = 118;
	const LIST_ = 119;
	const LOGICAL_AND_ = 120; // and
	const LOGICAL_OR_ = 121; // or
	const LOGICAL_XOR_ = 122; // xor
	const METHOD_C_ = 123;
	const NAMESPACE_ = 124; // namespace
	const NAMESPACE_AS_ = 125; // as
	const NAMESPACE_SEPARATOR_ = 126; // \
	const NEW_ = 127; // new
	const NS_C_ = 128;
	const NUMBER_DIVIDE_ = 129; // /
	const NUMBER_MINUS_ = 130; // -
	const NUMBER_MODULO_ = 131; // %
	const NUMBER_PLUS_ = 132; // +
	const NUMBER_POWER_ = 133; // **
	const NUMBER_TIMES_ = 134; // *
	const NUM_STRING_ = 135;
	const OBJECT_OPERATOR_ = 136;
	const PARENTHESIS_LEFT_ = 137;
	const PARENTHESIS_RIGHT_ = 138;
	const PHP_BEGIN_ = 139; // <?php
	const PHP_BEGIN_ECHO_ = 140; // <?=
	const PHP_END_ = 141; // ? >
	const PRINT_ = 142;
	const PRIVATE_ = 143;
	const PROTECTED_ = 144;
	const PUBLIC_ = 145;
	const QUESTION_MARK_ = 146; // ?
	const DOUBLE_QUOTE_ = 147; // "
	const SEMICOLON_ = 148;
	const SPACESHIP_ = 149;
	const STATIC_ = 150; // static
	const STRING_VARNAME_ = 151;
	const TRAIT_ = 152;
	const TRAIT_C_ = 153;
	const UNSET_ = 154;
	const USE_ = 155;
	const VALUE_FLOAT_ = 156; // 3.14159
	const VALUE_INTEGER_ = 157; // 3
	const VALUE_STRING_ = 158; // 'x' or "x"
	const VAR_ = 159; // var
	const VARIABLE_ = 160; // $x
	const WHITESPACE_ = 161;
	const YIELD_ = 162;
	const YIELD_FROM_ = 163;

	// See: http://php.net/manual/en/tokens.php
	private static $map = [
		T_ABSTRACT => self::ABSTRACT_,
		T_AND_EQUAL => self::ASSIGN_BIT_AND_,
		T_ARRAY => self::ARRAY_,
		T_ARRAY_CAST => self::CAST_ARRAY_,
		T_AS => self::NAMESPACE_AS_,
		T_BOOLEAN_AND => self::BOOLEAN_AND_,
		T_BOOLEAN_OR => self::BOOLEAN_OR_,
		T_BOOL_CAST => self::CAST_BOOLEAN_,
		T_BREAK => self::CONTROL_BREAK_,
		T_CALLABLE => self::CALLABLE_,
		T_CASE => self::CONTROL_CASE_,
		T_CATCH => self::CONTROL_CATCH_,
		T_CLASS => self::CLASS_,
		T_CLASS_C => self::CLASS_C_,
		T_CLONE => self::CLONE_,
		T_CLOSE_TAG => self::PHP_END_,
		T_COMMENT => self::COMMENT_,
		T_CONCAT_EQUAL => self::ASSIGN_CONCATENATE_,
		T_CONST => self::CONST_,
		T_CONSTANT_ENCAPSED_STRING => self::VALUE_STRING_,
		T_CONTINUE => self::CONTROL_CONTINUE_,
		T_CURLY_OPEN => self::BRACE_LEFT_,
		T_DEC => self::ASSIGN_NUMBER_DECREMENT_,
		T_DECLARE => self::CONTROL_DECLARE_,
		T_DEFAULT => self::CONTROL_DEFAULT_,
		T_DIR => self::DIR_,
		T_DIV_EQUAL => self::ASSIGN_NUMBER_DIVIDE_,
		T_DNUMBER => self::VALUE_FLOAT_,
		T_DOC_COMMENT => self::COMMENT_, // /** */
		T_DO => self::CONTROL_DO_,
		T_DOLLAR_OPEN_CURLY_BRACES => self::DOLLAR_OPEN_CURLY_BRACES_,
		T_DOUBLE_ARROW => self::DOUBLE_ARROW_,
		T_DOUBLE_CAST => self::CAST_FLOAT_,
		T_DOUBLE_COLON => self::DOUBLE_COLON_,
		T_ECHO => self::ECHO_,
		T_ELLIPSIS => self::ELLIPSIS_,
		T_ELSE => self::CONTROL_ELSE_,
		T_ELSEIF => self::CONTROL_ELSE_IF_,
		T_EMPTY => self::EMPTY_,
		T_ENCAPSED_AND_WHITESPACE => self::HEREDOC_BODY_,
		T_ENDDECLARE => self::CONTROL_DECLARE_END_,
		T_ENDFOR => self::CONTROL_FOR_END_,
		T_ENDFOREACH => self::CONTROL_FOREACH_END_,
		T_ENDIF => self::CONTROL_IF_END_,
		T_ENDSWITCH => self::CONTROL_SWITCH_END_,
		T_ENDWHILE => self::CONTROL_WHILE_END_,
		T_END_HEREDOC => self::HEREDOC_END_,
		T_EVAL => self::EVAL_,
		T_EXIT => self::EXIT_,
		T_EXTENDS => self::EXTENDS_,
		T_FILE => self::FILE_,
		T_FINAL => self::FINAL_,
		T_FINALLY => self::CONTROL_FINALLY_,
		T_FOR => self::CONTROL_FOR_,
		T_FOREACH => self::CONTROL_FOREACH_,
		T_FUNCTION => self::FUNCTION_,
		T_FUNC_C => self::FUNC_C_,
		T_GLOBAL => self::GLOBAL_,
		T_GOTO => self::CONTROL_GOTO_,
		T_HALT_COMPILER => self::HALT_COMPILER_,
		T_IF => self::CONTROL_IF_,
		T_IMPLEMENTS => self::IMPLEMENTS_,
		T_INC => self::ASSIGN_NUMBER_INCREMENT_,
		T_INCLUDE => self::CONTROL_INCLUDE_,
		T_INCLUDE_ONCE => self::CONTROL_INCLUDE_ONCE_,
		T_INLINE_HTML => self::HTML_,
		T_INSTANCEOF => self::INSTANCEOF_,
		T_INSTEADOF => self::INSTEADOF_,
		T_INT_CAST => self::CAST_INTEGER_,
		T_INTERFACE => self::INTERFACE_,
		T_ISSET => self::ISSET_,
		T_IS_EQUAL => self::IS_EQUAL_,
		T_IS_GREATER_OR_EQUAL => self::IS_GREATER_OR_EQUAL_,
		T_IS_IDENTICAL => self::IS_IDENTICAL_,
		T_IS_NOT_EQUAL => self::IS_NOT_EQUAL_,
		T_IS_NOT_IDENTICAL => self::IS_NOT_IDENTICAL_,
		T_IS_SMALLER_OR_EQUAL => self::IS_LESSER_OR_EQUAL_,
		T_LINE => self::LINE_,
		T_LIST => self::LIST_,
		T_LNUMBER => self::VALUE_INTEGER_,
		T_LOGICAL_AND => self::LOGICAL_AND_,
		T_LOGICAL_OR => self::LOGICAL_OR_,
		T_LOGICAL_XOR => self::LOGICAL_XOR_,
		T_METHOD_C => self::METHOD_C_,
		T_MINUS_EQUAL => self::ASSIGN_NUMBER_MINUS_,
		T_MOD_EQUAL => self::ASSIGN_NUMBER_MODULO_,
		T_MUL_EQUAL => self::ASSIGN_NUMBER_TIMES_,
		T_NAMESPACE => self::NAMESPACE_,
		T_NS_C => self::NS_C_,
		T_NS_SEPARATOR => self::NAMESPACE_SEPARATOR_,
		T_NEW => self::NEW_,
		T_NUM_STRING => self::NUM_STRING_,
		T_OBJECT_CAST => self::CAST_OBJECT_,
		T_OBJECT_OPERATOR => self::OBJECT_OPERATOR_,
		T_OPEN_TAG => self::PHP_BEGIN_,
		T_OPEN_TAG_WITH_ECHO => self::PHP_BEGIN_ECHO_,
		T_OR_EQUAL => self::ASSIGN_BIT_OR_,
		T_PAAMAYIM_NEKUDOTAYIM => self::DOUBLE_COLON_,
		T_PLUS_EQUAL => self::ASSIGN_NUMBER_PLUS_,
		T_POW => self::NUMBER_POWER_,
		T_POW_EQUAL => self::ASSIGN_NUMBER_POWER_,
		T_PRINT => self::PRINT_,
		T_PRIVATE => self::PRIVATE_,
		T_PUBLIC => self::PUBLIC_,
		T_PROTECTED => self::PROTECTED_,
		T_REQUIRE => self::CONTROL_REQUIRE_,
		T_REQUIRE_ONCE => self::CONTROL_REQUIRE_ONCE_,
		T_RETURN => self::CONTROL_RETURN_,
		T_SL => self::BIT_SHIFT_LEFT_,
		T_SL_EQUAL => self::ASSIGN_BIT_SHIFT_LEFT_,
		T_SR => self::BIT_SHIFT_RIGHT_,
		T_SR_EQUAL => self::ASSIGN_BIT_SHIFT_RIGHT_,
		T_START_HEREDOC => self::HEREDOC_BEGIN_,
		T_STATIC => self::STATIC_,
		T_STRING => self::IDENTIFIER_,
		T_STRING_CAST => self::CAST_STRING_,
		T_STRING_VARNAME => self::STRING_VARNAME_,
		T_SWITCH => self::CONTROL_SWITCH_,
		T_THROW => self::CONTROL_THROW_,
		T_TRAIT => self::TRAIT_,
		T_TRAIT_C => self::TRAIT_C_,
		T_TRY => self::CONTROL_TRY_,
		T_UNSET => self::UNSET_,
		T_UNSET_CAST => self::CAST_NULL_,
		T_USE => self::USE_,
		T_VAR => self::VAR_,
		T_VARIABLE => self::VARIABLE_,
		T_WHILE => self::CONTROL_WHILE_,
		T_WHITESPACE => self::WHITESPACE_,
		T_XOR_EQUAL => self::ASSIGN_BIT_XOR_,
		T_YIELD => self::YIELD_,
		'(' => self::PARENTHESIS_LEFT_,
		')' => self::PARENTHESIS_RIGHT_,
		'[' => self::BRACKET_LEFT_,
		']' => self::BRACKET_RIGHT_,
		'{' => self::BRACE_LEFT_,
		'}' => self::BRACE_RIGHT_,
		',' => self::COMMA_,
		'=' => self::ASSIGN_,
		';' => self::SEMICOLON_,
		':' => self::COLON_,
		'"' => self::DOUBLE_QUOTE_,
		'.' => self::CONCATENATE_,
		'$' => self::DOLLAR_,
		'&' => self::BIT_AND_,
		'|' => self::BIT_OR_,
		'^' => self::BIT_XOR_,
		'+' => self::NUMBER_PLUS_,
		'-' => self::NUMBER_MINUS_,
		'*' => self::NUMBER_TIMES_,
		'/' => self::NUMBER_DIVIDE_,
		'%' => self::NUMBER_MODULO_,
		'?' => self::QUESTION_MARK_,
		'!' => self::BOOLEAN_NOT_,
		'<' => self::IS_LESSER_,
		'>' => self::IS_GREATER_,
		'@' => self::AT_
	];

	public function __construct()
	{
		if (defined('T_BAD_CHARACTER')) {
			self::$map[T_BAD_CHARACTER] = self::BAD_CHARACTER_;
		}

		if (defined('T_COALESCE')) {
			self::$map[T_COALESCE] = self::COALESCE_;
		}

		if (defined('T_SPACESHIP')) {
			self::$map[T_SPACESHIP] = self::SPACESHIP_;
		}

		if (defined('T_YIELD_FROM')) {
			self::$map[T_YIELD_FROM] = self::YIELD_FROM_;
		}
	}

	public function lex($php)
	{
		$dirtyTokens = $this->getDirtyTokens($php);
		return $this->getCleanTokens($dirtyTokens);
	}

	// See: http://php.net/manual/en/tokens.php
	// $name = token_name($type);
	private function getDirtyTokens($php)
	{
		if (defined('TOKEN_PARSE')) {
			// PHP 7.0.0+ allows reserved words in some contexts. When that
			// happens, this TOKEN_PARSE argument is required.
			return @token_get_all($php, TOKEN_PARSE);
		}

		// The error suppression ("@") is necessary because this function
		// can generate an uncatchable E_COMPILE_WARNING. (For example,
		// "<?php\n\x07;" triggers an uncatchable warning.)
		return @token_get_all($php);
	}

	private function getCleanTokens(array $dirtyTokens)
	{
		$cleanTokens = [];

		foreach ($dirtyTokens as $dirtyToken) {
			$this->addCleanTokens($cleanTokens, $dirtyToken);
		}

		return $cleanTokens;
	}

	private function addCleanTokens(array &$cleanTokens, $dirtyToken)
	{
		if (is_array($dirtyToken)) {
			$dirtyType = $dirtyToken[0];
			$value = $dirtyToken[1];
		} else {
			$dirtyType = $dirtyToken;
			$value = $dirtyToken;
		}

		$cleanType = $this->getCleanType($dirtyType, $value);
		$cleanTokens[] = [$cleanType => $value];
	}

	private function getCleanType($dirtyType, $value)
	{
		if ($dirtyType === T_STRING) {
			switch (strtolower($value)) {
				case 'null':
					return self::KEYWORD_NULL_;

				case 'false':
					return self::KEYWORD_FALSE_;

				case 'true':
					return self::KEYWORD_TRUE_;

				case 'define':
					return self::KEYWORD_DEFINE_;
			}
		}

		// TODO: throw exception:
		if (!array_key_exists($dirtyType, self::$map)) {
			if (is_string($dirtyType)) {
				$dirtyTypeName = $dirtyType;
			} else {
				$dirtyTypeName = token_name($dirtyType);
			}

			echo "missing: ", var_export($dirtyTypeName, true), "\n";
			exit;
		}

		return self::$map[$dirtyType];
	}
}
