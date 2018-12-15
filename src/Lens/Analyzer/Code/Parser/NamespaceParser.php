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

use _Lens\Lens\Php\Lexer as PhpLexer;
use _Lens\SpencerMortensen\Parser\Input\TokenInput;

class NamespaceParser
{
	/** @var TokenInput */
	private $input;

	/** @var UseTokensParser */
	private $useParser;

	/** @var array */
	private $context;

	/** @var array */
	private $definitions;

	public function __construct()
	{
		$this->useParser = new UseTokensParser();
	}

	public function parse(array $tokens)
	{
		$this->input = new TokenInput($tokens);

		$this->context = [
			'namespace' => null,
			'functions' => [],
			'classes' => []
		];

		$this->definitions = [
			'functions' => [],
			'classes' => [],
			'interfaces' => [],
			'traits' => []
		];

		$this->getNamespace($this->context['namespace']);

		while ($this->input->getPosition() < count($tokens)) {
			if (!(
				$this->getUseStatement()
				|| $this->getFunctionDefinition()
				|| $this->getClassDefinition()
				|| $this->getInterfaceDefinition()
				|| $this->getTraitDefinition()
			)) {
				$position = $this->input->getPosition();
				$this->input->setPosition(++$position);
			}
		}

		return [
			'context' => $this->context,
			'definitions' => $this->definitions
		];
	}

	private function getNamespace(&$output)
	{
		$this->getNamespaceKeyword()
		&& $this->getNamespaceName($output);

		return true;
	}

	private function getNamespaceKeyword()
	{
		return $this->input->read(PhpLexer::NAMESPACE_);
	}

	private function getNamespaceName(&$output)
	{
		$position = $this->input->getPosition();

		if (
			$this->getNamespaceWord($word)
			&& $this->getNamespaceWords($words)
		) {
			array_unshift($words, $word);
			$output = implode('\\', $words);
			return true;
		}

		$this->input->setPosition($position);
		return false;
	}

	private function getNamespaceWord(&$output)
	{
		if ($this->input->read(PhpLexer::IDENTIFIER_, $token)) {
			$output = current($token);
			return true;
		}

		return false;
	}

	private function getNamespaceWords(&$output)
	{
		$output = [];

		while ($this->getNamespaceLink($word)) {
			$output[] = $word;
		}

		return true;
	}

	private function getNamespaceLink(&$output)
	{
		$position = $this->input->getPosition();

		if (
			$this->input->read(PhpLexer::NAMESPACE_SEPARATOR_)
			&& $this->getNamespaceWord($output)
		) {
			return true;
		}

		$this->input->setPosition($position);
		return false;
	}

	private function getUseStatement()
	{
		if (!$this->useParser->parse($this->input)) {
			return false;
		}

		list($type, $map) = $this->useParser->getOutput();

		if ($type === 'class') {
			$this->context['classes'] = array_merge($this->context['classes'], $map);
		} else {
			$this->context['functions'] = array_merge($this->context['functions'], $map);
		}

		return true;
	}

	private function getFunctionDefinition()
	{
		$iBegin = $this->input->getPosition();

		if (
			$this->input->read(PhpLexer::FUNCTION_)
			&& $this->getWord($name)
			&& $this->getUntilRightBrace($iEnd)
		) {
			$this->definitions['functions'][$name] = [$iBegin, $iEnd];
			return true;
		}

		$this->input->setPosition($iBegin);
		return false;
	}

	private function getWord(&$output)
	{
		if ($this->input->read(PhpLexer::IDENTIFIER_, $token)) {
			$output = current($token);
			return true;
		}

		return false;
	}

	private function getClassDefinition()
	{
		$iBegin = $this->input->getPosition();

		if (
			$this->getOptionalClassType()
			&& $this->input->read(PhpLexer::CLASS_)
			&& $this->getWord($name)
			&& $this->getUntilRightBrace($iEnd)
		) {
			$this->definitions['classes'][$name] = [$iBegin, $iEnd];
			return true;
		}

		$this->input->setPosition($iBegin);
		return false;
	}

	private function getOptionalClassType()
	{
		$this->input->read(PhpLexer::ABSTRACT_) ||
		$this->input->read(PhpLexer::FINAL_);

		return true;
	}

	private function getInterfaceDefinition()
	{
		$iBegin = $this->input->getPosition();

		if (
			$this->input->read(PhpLexer::INTERFACE_)
			&& $this->getWord($name)
			&& $this->getUntilRightBrace($iEnd)
		) {
			$this->definitions['interfaces'][$name] = [$iBegin, $iEnd];
			return true;
		}

		$this->input->setPosition($iBegin);
		return false;
	}

	private function getTraitDefinition()
	{
		$iBegin = $this->input->getPosition();

		if (
			$this->input->read(PhpLexer::TRAIT_)
			&& $this->getWord($name)
			&& $this->getUntilRightBrace($iEnd)
		) {
			$this->definitions['traits'][$name] = [$iBegin, $iEnd];
			return true;
		}

		$this->input->setPosition($iBegin);
		return false;
	}

	private function getUntilRightBrace(&$output)
	{
		$tokens = $this->input->getTokens();
		$position = $this->input->getPosition();
		$depth = 0;

		for ($n = count($tokens); $position < $n; ++$position) {
			$type = key($tokens[$position]);

			if ($type === PhpLexer::BRACE_LEFT_) {
				++$depth;
			} elseif ($type === PhpLexer::BRACE_RIGHT_) {
				--$depth;

				if ($depth === 0) {
					$this->input->setPosition($position);
					$output = $position;
					return true;
				}
			}
		}

		return false;
	}
}
