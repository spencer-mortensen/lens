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

class PreambleTokensParser
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

		if ($this->readNamespace($output)) {
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

	private function readNamespace(&$output)
	{
		if ($this->readNamespaceDeclared($output) || $this->readNamespaceImplied($output)) {
			return true;
		}

		$this->setExpectation('namespace');
		return false;
	}

	private function readNamespaceDeclared(&$output)
	{
		$position = $this->input->getPosition();

		if ($this->readNamespaceKeyword() && $this->readNamespaceName($name) && $this->readSemicolon() && $this->readUseStatements($use)) {
			$output = [
				'namespace' => $name,
				'uses' => $use
			];
			return true;
		}

		$this->input->setPosition($position);
		return false;
	}

	private function readNamespaceKeyword()
	{
		if ($this->input->read(Lexer::NAMESPACE_)) {
			return true;
		}

		$this->setExpectation('namespaceKeyword');
		return false;
	}

	private function readNamespaceName(&$output)
	{
		$position = $this->input->getPosition();

		if ($this->readAnyNamespaceNameLinks($words) && $this->readNamespaceWord($word)) {
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

		while ($this->readNamespaceNameLink($input)) {
			$output[] = $input;
		}

		return true;
	}

	private function readNamespaceNameLink(&$output)
	{
		$position = $this->input->getPosition();

		if ($this->readNamespaceWord($output) && $this->readNamespaceNameSeparator()) {
			return true;
		}

		$this->input->setPosition($position);
		return false;
	}

	private function readNamespaceWord(&$output)
	{
		if ($this->input->read(Lexer::IDENTIFIER_, $token)) {
			$output = current($token);
			return true;
		}

		$this->setExpectation('namespaceWord');
		return false;
	}

	private function readNamespaceNameSeparator()
	{
		if ($this->input->read(Lexer::NAMESPACE_SEPARATOR_)) {
			return true;
		}

		$this->setExpectation('namespaceNameSeparator');
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

	private function readUseStatements(&$output)
	{
		$maps = [];

		while ($this->readUseStatement($input)) {
			$maps[] = $input;
		}

		if (0 < count($maps)) {
			$output = call_user_func_array('array_merge', $maps);
		} else {
			$output = [];
		}

		return true;
	}

	private function readUseStatement(&$output)
	{
		$position = $this->input->getPosition();

		if ($this->readUseKeyword() && $this->readNamespaceMaps($output) && $this->readSemicolon()) {
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

	private function readNamespaceMaps(&$output)
	{
		if ($this->readNamespaceMapList($output) || $this->readNamespaceMapGroup($output)) {
			return true;
		}

		$this->setExpectation('namespaceMaps');
		return false;
	}

	private function readNamespaceMapList(&$output)
	{
		$position = $this->input->getPosition();

		if ($this->readNamespaceMap($namespaceMap) && $this->readAnyNamespaceMapLinks($anyNamespaceMapLinks)) {
			if (0 < count($anyNamespaceMapLinks)) {
				$output = array_merge($namespaceMap, $anyNamespaceMapLinks);
			} else {
				$output = $namespaceMap;
			}
			return true;
		}

		$this->input->setPosition($position);
		return false;
	}

	private function readNamespaceMap(&$output)
	{
		$position = $this->input->getPosition();

		if ($this->readNamespaceName($name) && $this->readMaybeAlias($maybeAlias)) {
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

		if ($this->readAliasKeyword() && $this->readNamespaceWord($output)) {
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
		$namespaceMapLinks = [];

		while ($this->readNamespaceMapLink($input)) {
			$namespaceMapLinks[] = $input;
		}

		if (0 < count($namespaceMapLinks)) {
			$output = call_user_func_array('array_merge', $namespaceMapLinks);
		} else {
			$output = [];
		}

		return true;
	}

	private function readNamespaceMapLink(&$output)
	{
		$position = $this->input->getPosition();

		if ($this->readComma() && $this->readNamespaceMap($output)) {
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

	private function readNamespaceMapGroup(&$output)
	{
		$position = $this->input->getPosition();

		if ($this->readSomeNamespaceNameLinks($namespaceNameLinks) && $this->readLeftBrace() && $this->readNamespaceMapList($namespaceMapList) && $this->readRightBrace()) {
			$prefix = implode('\\', $namespaceNameLinks);

			foreach ($namespaceMapList as $alias => &$path) {
				$path = "{$prefix}\\{$path}";
			}

			$output = $namespaceMapList;
			return true;
		}

		$this->input->setPosition($position);
		return false;
	}

	private function readSomeNamespaceNameLinks(&$output)
	{
		$output = [];

		if (!$this->readNamespaceNameLink($output[])) {
			return false;
		}

		while ($this->readNamespaceNameLink($input)) {
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

	private function readNamespaceImplied(&$output)
	{
		$position = $this->input->getPosition();

		if ($this->readUseStatements($use)) {
			$output = [
				'name' => null,
				'use' => $use
			];
			return true;
		}

		$this->input->setPosition($position);
		return false;
	}

	private function setExpectation($rule)
	{
		$this->expectation = $rule;
		$this->position = $this->input->getPosition();
	}
}
