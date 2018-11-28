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

class UseTokensParser
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

		if ($this->readUseStatement($output)) {
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

	private function readUseStatement(&$output)
	{
		$position = $this->input->getPosition();

		if ($this->readUseKeyword() && $this->readUseBody($output) && $this->readSemicolon()) {
			return true;
		}

		$this->input->setPosition($position);
		return false;
	}

	private function readUseKeyword()
	{
		if ($this->input->read(Lexer::USE_)) {
			return true;
		}

		$this->setExpectation('useKeyword');
		return false;
	}

	private function readUseBody(&$output)
	{
		if ($this->readUseFunction($output) || $this->readUseClass($output)) {
			return true;
		}

		$this->setExpectation('useBody');
		return false;
	}

	private function readUseFunction(&$output)
	{
		$position = $this->input->getPosition();

		if ($this->readUseFunctionKeyword() && $this->readUseMaps($useMap)) {
			$output = ['function', $useMap];
			return true;
		}

		$this->input->setPosition($position);
		return false;
	}

	private function readUseFunctionKeyword()
	{
		if ($this->input->read(Lexer::FUNCTION_)) {
			return true;
		}

		$this->setExpectation('useFunctionKeyword');
		return false;
	}

	private function readUseMaps(&$output)
	{
		if ($this->readUseMapList($output) || $this->readUseMapGroup($output)) {
			return true;
		}

		$this->setExpectation('useMaps');
		return false;
	}

	private function readUseMapList(&$output)
	{
		$position = $this->input->getPosition();

		if ($this->readUseMap($useMap) && $this->readAnyNamespaceMapLinks($anyNamespaceMapLinks)) {
			if (0 < count($anyNamespaceMapLinks)) {
				$output = array_merge($useMap, $anyNamespaceMapLinks);
			} else {
				$output = $useMap;
			}
			return true;
		}

		$this->input->setPosition($position);
		return false;
	}

	private function readUseMap(&$output)
	{
		$position = $this->input->getPosition();

		if ($this->readUseName($name) && $this->readMaybeAlias($maybeAlias)) {
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

			$output = [
				$alias => $name
			];
			return true;
		}

		$this->input->setPosition($position);
		return false;
	}

	private function readUseName(&$output)
	{
		$position = $this->input->getPosition();

		if ($this->readAnyNamespaceNameLinks($words) && $this->readIdentifier($word)) {
			$words[] = $word;
			$output = implode('\\', $words);
			return true;
		}

		$this->input->setPosition($position);
		return false;
	}

	private function readAnyNamespaceNameLinks(&$output)
	{
		$output = [];

		while ($this->readUseNameLink($input)) {
			$output[] = $input;
		}

		return true;
	}

	private function readUseNameLink(&$output)
	{
		$position = $this->input->getPosition();

		if ($this->readIdentifier($output) && $this->readUseNameSeparator()) {
			return true;
		}

		$this->input->setPosition($position);
		return false;
	}

	private function readIdentifier(&$output)
	{
		if ($this->input->read(Lexer::IDENTIFIER_, $token)) {
			$output = current($token);
			return true;
		}

		$this->setExpectation('identifier');
		return false;
	}

	private function readUseNameSeparator()
	{
		if ($this->input->read(Lexer::NAMESPACE_SEPARATOR_)) {
			return true;
		}

		$this->setExpectation('useNameSeparator');
		return false;
	}

	private function readMaybeAlias(&$output)
	{
		$words = [];

		if ($this->readAlias($input)) {
			$words[] = $input;
		}

		$output = array_shift($words);

		return true;
	}

	private function readAlias(&$output)
	{
		$position = $this->input->getPosition();

		if ($this->readAliasKeyword() && $this->readIdentifier($output)) {
			return true;
		}

		$this->input->setPosition($position);
		return false;
	}

	private function readAliasKeyword()
	{
		if ($this->input->read(Lexer::NAMESPACE_AS_)) {
			return true;
		}

		$this->setExpectation('aliasKeyword');
		return false;
	}

	private function readAnyNamespaceMapLinks(&$output)
	{
		$useMapLinks = [];

		while ($this->readUseMapLink($input)) {
			$useMapLinks[] = $input;
		}

		if (0 < count($useMapLinks)) {
			$output = call_user_func_array('array_merge', $useMapLinks);
		} else {
			$output = [];
		}

		return true;
	}

	private function readUseMapLink(&$output)
	{
		$position = $this->input->getPosition();

		if ($this->readComma() && $this->readUseMap($output)) {
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

	private function readUseMapGroup(&$output)
	{
		$position = $this->input->getPosition();

		if ($this->readSomeNamespaceNameLinks($useNameLinks) && $this->readLeftBrace() && $this->readUseMap($useMapList) && $this->readRightBrace()) {
			$prefix = implode('\\', $useNameLinks);

			foreach ($useMapList as $alias => &$path) {
				$path = "{$prefix}\\{$path}";
			}

			$output = $useMapList;
			return true;
		}

		$this->input->setPosition($position);
		return false;
	}

	private function readSomeNamespaceNameLinks(&$output)
	{
		$output = [];

		if (!$this->readUseNameLink($output[])) {
			return false;
		}

		while ($this->readUseNameLink($input)) {
			$output[] = $input;
		}

		return true;
	}

	private function readLeftBrace()
	{
		if ($this->input->read(Lexer::BRACE_LEFT_)) {
			return true;
		}

		$this->setExpectation('leftBrace');
		return false;
	}

	private function readRightBrace()
	{
		if ($this->input->read(Lexer::BRACE_RIGHT_)) {
			return true;
		}

		$this->setExpectation('rightBrace');
		return false;
	}

	private function readUseClass(&$output)
	{
		if ($this->readUseMaps($useMap)) {
			$output = ['class', $useMap];
			return true;
		}

		$this->setExpectation('useClass');
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
