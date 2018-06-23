<?php

/**
 * Copyright (C) 2017 Spencer Mortensen
 *
 * This file is part of parser.
 *
 * Parser is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Parser is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with parser. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Spencer Mortensen <spencer@lens.guide>
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL-3.0
 * @copyright 2017 Spencer Mortensen
 */

namespace _Lens\SpencerMortensen\Parser;

use Exception;

class ParserException extends Exception
{
	/** @var mixed */
	private $rule;

	/** @var mixed */
	private $state;

	public function __construct($rule, $state)
	{
		parent::__construct();

		$this->rule = $rule;
		$this->state = $state;
	}

	public function getRule()
	{
		return $this->rule;
	}

	public function getState()
	{
		return $this->state;
	}
}
