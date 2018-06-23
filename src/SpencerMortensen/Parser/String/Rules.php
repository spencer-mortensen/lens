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

namespace _Lens\SpencerMortensen\Parser\String;

use _Lens\SpencerMortensen\Parser\Core\Rules as CoreRules;
use _Lens\SpencerMortensen\Parser\String\Rules\ReRule;
use _Lens\SpencerMortensen\Parser\String\Rules\StringRule;

class Rules extends CoreRules
{
	protected function createRule($name, $type, $definition)
	{
		switch ($type) {
			case 're':
				return $this->createReRule($name, $definition);

			case 'string':
				return $this->createStringRule($name, $definition);

			default:
				return parent::createRule($name, $type, $definition);
		}
	}

	private function createReRule($name, $definition)
	{
		$expression = $definition;
		$callable = $this->getCallable($name);

		return new ReRule($name, $expression, $callable);
	}

	private function createStringRule($name, $definition)
	{
		$string = $definition;
		$callable = $this->getCallable($name);

		return new StringRule($name, $string, $callable);
	}
}
