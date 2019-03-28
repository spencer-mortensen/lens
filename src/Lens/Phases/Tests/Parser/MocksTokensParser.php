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

namespace _Lens\Lens\Phases\Tests\Parser;

use _Lens\Lens\Php\Lexer;
use _Lens\SpencerMortensen\Parser\Input\TokenInput;

class MocksTokensParser
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

		if ($this->readMocks($output)) {
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

	private function readMocks(&$output)
	{
		$maps = [];

		while ($this->readMock($input)) {
			$maps[] = $input;
		}

		if (count($maps) === 0) {
			$mocks = [];
		} else {
			$mocks = call_user_func_array('array_merge', $maps);
		}

		$output = $mocks;

		return true;
	}

	private function readMock(&$output)
	{
		$position = $this->input->getPosition();

		if ($this->readVariable($variable) && $this->readAssign() && $this->readNewKeyword() && $this->readPath($path) && $this->readParenthesisLeft() && $this->readParenthesisRight() && $this->readSemicolon()) {
			$output = [$variable => $path];
			return true;
		}

		$this->input->setPosition($position);
		return false;
	}

	private function readVariable(&$output)
	{
		if ($this->input->read(Lexer::VARIABLE_, $token)) {
			$output = substr(current($token), 1);
			return true;
		}

		$this->setExpectation('variable');
		return false;
	}

	private function readAssign()
	{
		if ($this->input->read(Lexer::ASSIGN_)) {
			return true;
		}

		$this->setExpectation('assign');
		return false;
	}

	private function readNewKeyword()
	{
		if ($this->input->read(Lexer::NEW_)) {
			return true;
		}

		$this->setExpectation('newKeyword');
		return false;
	}

	private function readPath(&$output)
	{
		$position = $this->input->getPosition();

		if ($this->readMaybeWordSeparator($isAbsolute) && $this->readMaybeWordLinks($links) && $this->readWord($word)) {
			$links[] = $word;
			$path = implode('\\', $links);

			if ($isAbsolute) {
				$path = "\\{$path}";
			}

			$output = $path;
			return true;
		}

		$this->input->setPosition($position);
		return false;
	}

	private function readMaybeWordSeparator(&$output)
	{
		$separators = [];

		if ($this->readWordSeparator($input)) {
			$separators[] = $input;
		}

		$output = array_shift($separators);

		return true;
	}

	private function readWordSeparator(&$output)
	{
		if ($this->input->read(Lexer::NAMESPACE_SEPARATOR_, $token)) {
			$output = 0 < count($token);
			return true;
		}

		$this->setExpectation('wordSeparator');
		return false;
	}

	private function readMaybeWordLinks(&$output)
	{
		$output = [];

		while ($this->readWordLink($input)) {
			$output[] = $input;
		}

		return true;
	}

	private function readWordLink(&$output)
	{
		$position = $this->input->getPosition();

		if ($this->readWord($word) && $this->readWordSeparator($separator)) {
			$output = $word;
			return true;
		}

		$this->input->setPosition($position);
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

	private function readParenthesisLeft()
	{
		if ($this->input->read(Lexer::PARENTHESIS_LEFT_)) {
			return true;
		}

		$this->setExpectation('parenthesisLeft');
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

	private function readSemicolon()
	{
		if ($this->input->read(Lexer::SEMICOLON_)) {
			return true;
		}

		$this->setExpectation('semicolon');
		return false;
	}

	private function setExpectation($rule)
	{
		$this->expectation = $rule;
		$this->position = $this->input->getPosition();
	}
}
