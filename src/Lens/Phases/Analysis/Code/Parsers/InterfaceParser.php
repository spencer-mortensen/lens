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

namespace _Lens\Lens\Phases\Analysis\Code\Parsers;

use _Lens\Lens\Phases\Analysis\Code\Input;
use _Lens\Lens\Php\Lexer;

class InterfaceParser
{
	/** @var PathParser */
	private $pathParser;

	/** @var BlockParser */
	private $blockParser;

	/** @var Input */
	private $input;

	public function __construct(PathParser $pathParser, BlockParser $blockParser)
	{
		$this->pathParser = $pathParser;
		$this->blockParser = $blockParser;
	}

	public function parse(Input $input, &$output)
	{
		$this->input = $input;
		$iBegin = $this->input->getPosition();

		$classPaths = [];

		if (
			$this->input->get(Lexer::INTERFACE_) &&
			$this->input->get(Lexer::IDENTIFIER_, $name) &&
			$this->getOptionalExtends($classPaths) &&
			$this->blockParser->parse($this->input)
		) {
			$iEnd = $this->input->getPosition() - 1;

			$output = [
				'name' => $name,
				'range' => [$iBegin => $iEnd],
				'classPaths' => $classPaths
			];

			return true;
		}

		$this->input->setPosition($iBegin);
		return false;
	}

	private function getOptionalExtends(array &$classPaths)
	{
		if (!$this->input->get(Lexer::EXTENDS_)) {
			return true;
		}

		if (!$this->pathParser->parse($this->input, $classPath)) {
			return false;
		}

		$classPaths += $classPath;

		while ($this->input->get(Lexer::COMMA_)) {
			if (!$this->pathParser->parse($this->input, $classPath)) {
				return false;
			}

			$classPaths += $classPath;
		}

		return true;
	}
}
