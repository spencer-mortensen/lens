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

class FunctionParser
{
	/** @var ClassCallParser */
	private $classCallParser;

	/** @var FunctionCallParser */
	private $functionCallParser;

	/** @var Input */
	private $input;

	public function __construct(ClassCallParser $classCallParser, FunctionCallParser $functionCallParser)
	{
		$this->classCallParser = $classCallParser;
		$this->functionCallParser = $functionCallParser;
	}

	public function parse(Input $input, &$function, array &$paths)
	{
		$this->input = $input;
		$iBegin = $input->getPosition();

		if (
			$input->get(Lexer::FUNCTION_) &&
			$this->getOptionalAmpersand() &&
			$input->get(Lexer::IDENTIFIER_, $name) &&
			// TODO: extract any class paths from PHP 7 type hints
			$this->getEnd($iSignatureEnd, $isDefinition, $paths)
		) {
			$iEnd = $this->input->getPosition() - 1;

			$function = [
				'name' => $name,
				'range' => [$iBegin => $iEnd],
				'signatureRange' => [$iBegin => $iSignatureEnd],
				'isDefinition' => $isDefinition
			];

			return true;
		}

		$input->setPosition($iBegin);
		return false;
	}

	private function getOptionalAmpersand()
	{
		if ($this->input->read($type) && ($type === Lexer::BIT_AND_)) {
			$this->input->move(1);
		}

		return true;
	}

	private function getEnd(&$iSignatureEnd, &$isDefinition, array &$paths)
	{
		$position = $this->input->getPosition();

		while ($this->input->read($type)) {
			$this->input->move(1);

			if ($type === Lexer::SEMICOLON_) {
				$iSignatureEnd = $this->input->getPosition() - 2;
				$isDefinition = false;
				return true;
			}

			if ($type === Lexer::BRACE_LEFT_) {
				$iSignatureEnd = $this->input->getPosition() - 2;

				if ($this->getBody($paths)) {
					$isDefinition = true;
					return true;
				}

				break;
			}
		}

		$this->input->setPosition($position);
		return false;
	}

	private function getBody(array &$paths)
	{
		$position = $this->input->getPosition();
		$depth = 1;

		while ($this->input->read($type)) {
			if ($type === Lexer::BRACE_LEFT_) {
				$this->input->move(1);

				++$depth;
			} elseif ($type === Lexer::BRACE_RIGHT_) {
				$this->input->move(1);

				if (--$depth === 0) {
					return true;
				}
			} elseif ($this->classCallParser->parse($this->input, $classPath)) {
				$paths['classes'] += $classPath;
			} elseif ($this->functionCallParser->parse($this->input, $functionPath)) {
				$paths['functions'] += $functionPath;
			} else {
				$this->input->move(1);
			}
		}

		$this->input->setPosition($position);
		return false;
	}
}
