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

class FunctionCallParser
{
	/** @var PathParser */
	private $pathParser;

	/** @var Input */
	private $input;

	public function __construct(PathParser $pathParser)
	{
		$this->pathParser = $pathParser;
	}

	public function parse(Input $input, &$pathRange = null)
	{
		$this->input = $input;
		$position = $this->input->getPosition();

		if (
			$this->pathParser->parse($this->input, $pathRange) &&
			$this->input->get(Lexer::PARENTHESIS_LEFT_) &&
			$this->isSafeContext($position - 1)
		) {
			return true;
		}

		$this->input->setPosition($position);
		return false;
	}

	private function isSafeContext($targetPosition)
	{
		$position = $this->input->getPosition();

		$this->input->setPosition($targetPosition);

		$isUnsafe = $this->input->read($type) && (
			($type === Lexer::FUNCTION_) ||
			($type === Lexer::OBJECT_OPERATOR_)
		);

		$this->input->setPosition($position);

		return !$isUnsafe;
	}
}
