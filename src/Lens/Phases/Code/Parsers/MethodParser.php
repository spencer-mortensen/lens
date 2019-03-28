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

class MethodParser
{
	/** @var FunctionParser */
	private $functionParser;

	/** @var Input */
	private $input;

	public function __construct(FunctionParser $functionParser)
	{
		$this->functionParser = $functionParser;
	}

	public function parse(Input $input, &$method, &$paths)
	{
		$this->input = $input;
		$iBegin = $this->input->getPosition();

		if (
			$this->getOptionalMethodTypes($isStatic, $isPrivate) &&
			$this->functionParser->parse($this->input, $function, $paths)
		) {
			$iSignatureEnd = current($function['signatureRange']);

			$method = [
				'name' => $function['name'],
				'signatureRange' => [$iBegin => $iSignatureEnd],
				'isStatic' => $isStatic,
				'isPrivate' => $isPrivate,
				'isDefinition' => $function['isDefinition']
			];

			return true;
		}

		$this->input->setPosition($iBegin);
		return false;
	}

	private function getOptionalMethodTypes(&$isStatic, &$isPrivate)
	{
		$isStatic = false;
		$isPrivate = false;

		while ($this->input->read($type)) {
			if ($this->isMethodType($type, $isStatic, $isPrivate)) {
				$this->input->move(1);
			} else {
				return true;
			}
		}

		return true;
	}

	private function isMethodType($type, &$isStatic, &$isPrivate)
	{
		switch ($type) {
			case Lexer::PUBLIC_:
				return true;

			case Lexer::PROTECTED_:
				return true;

			case Lexer::PRIVATE_:
				$isPrivate = true;
				return true;

			case Lexer::ABSTRACT_:
				return true;

			case Lexer::STATIC_:
				$isStatic = true;
				return true;

			case Lexer::FINAL_:
				return true;
		}

		return false;
	}
}
