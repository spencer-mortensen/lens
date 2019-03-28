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

namespace _Lens\Lens\Phases\Code\Parsers;

use _Lens\Lens\Phases\Code\Input;
use _Lens\Lens\Php\Lexer;

class ClassParser
{
	/** @var PathParser */
	private $pathParser;

	/** @var MethodParser */
	private $methodParser;

	/** @var Input */
	private $input;

	public function __construct(PathParser $pathParser, MethodParser $methodParser)
	{
		$this->pathParser = $pathParser;
		$this->methodParser = $methodParser;
	}

	public function parse(Input $input, &$class)
	{
		$this->input = $input;
		$iSignatureBegin = $this->input->getPosition();

		$paths = [
			'classes' => [],
			'functions' => []
		];

		if (
			$this->getOptionalType() &&
			$this->input->get(Lexer::CLASS_) &&
			$this->input->get(Lexer::IDENTIFIER_, $name) &&
			$this->getOptionalExtends($paths) &&
			$this->getOptionalImplements($paths) &&
			$this->input->get(Lexer::BRACE_LEFT_) &&
			$this->getOptionalMethods($iSignatureEnd, $methods, $paths) &&
			$this->input->get(Lexer::BRACE_RIGHT_)
		) {
			$iEnd = $this->input->getPosition() - 1;

			$class = [
				'name' => $name,
				'range' => [$iSignatureBegin => $iEnd],
				'signatureRange' => [$iSignatureBegin => $iSignatureEnd],
				'methods' => $methods,
				'paths' => $paths
			];

			return true;
		}

		$this->input->setPosition($iSignatureBegin);
		return false;
	}

	private function getOptionalType()
	{
		if ($this->input->read($type) && (($type === Lexer::ABSTRACT_) || ($type === Lexer::FINAL_))) {
			$this->input->move(1);
		}

		return true;
	}

	private function getOptionalExtends(array &$paths)
	{
		if (
			$this->input->get(Lexer::EXTENDS_) &&
			$this->pathParser->parse($this->input, $classPath)
		) {
			$paths['classes'] += $classPath;
		}

		return true;
	}

	private function getOptionalImplements(array &$paths)
	{
		if (!$this->input->get(Lexer::IMPLEMENTS_)) {
			return true;
		}

		if (!$this->pathParser->parse($this->input, $classPath)) {
			return false;
		}

		$paths['classes'] += $classPath;

		while ($this->input->get(Lexer::COMMA_)) {
			if (!$this->pathParser->parse($this->input, $classPath)) {
				return false;
			}

			$paths['classes'] += $classPath;
		}

		return true;
	}

	private function getOptionalMethods(&$iSignatureEnd, &$methods, array &$paths)
	{
		$iSignatureEnd = $this->input->getPosition() - 2;

		$methods = [];

		while (true) {
			if ($this->methodParser->parse($this->input, $method, $paths)) {
				$methods[] = $method;
				continue;
			}

			if ($this->input->read($type) && ($type !== Lexer::BRACE_RIGHT_)) {
				$this->input->move(1);
				continue;
			}

			break;
		}

		return true;
	}
}
