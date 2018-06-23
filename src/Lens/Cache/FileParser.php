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

namespace _Lens\Lens\Cache;

use _Lens\SpencerMortensen\Parser\Rule;
use _Lens\SpencerMortensen\Parser\String\Parser;
use _Lens\SpencerMortensen\Parser\String\Rules;

class FileParser extends Parser
{
	/** @var Rule */
	private $rule;

	/** @var string */
	private $input;

	public function __construct()
	{
		$grammar = <<<'EOS'
php: AND phpTag optionalNamespace optionalUses code
phpTag: AND phpTagLine optionalComments
phpTagLine: RE <\?php\s+
optionalComments: MANY comment 0
comment: RE /\*.*?\*/\s*
optionalNamespace: MANY namespace 0 1
namespace: AND namespaceLine optionalComments
namespaceLine: RE namespace\h+([a-zA-Z_0-9\\]+);\s*
optionalUses: MANY use 0
use: AND useLine optionalComments
useLine: RE use\h+(?<namespace>[a-zA-Z_0-9\\]+)(?:\h+as\h+(?<alias>[a-zA-Z_0-9]+))?;\s*
code: RE .*
EOS;

		$rules = new Rules($this, $grammar);
		$this->rule = $rules->getRule('php');
	}

	public function parse($input)
	{
		$this->input = $input;

		return $this->run($this->rule, $input);
	}

	public function getPhp(array $matches)
	{
		return [$matches[1], $matches[2]];
	}

	public function getOptionalNamespace(array $matches)
	{
		return array_shift($matches);
	}

	public function getNamespace(array $matches)
	{
		return $matches[0][1];
	}

	public function getOptionalUses(array $matches)
	{
		return self::merge($matches);
	}

	private static function merge(array $input)
	{
		$output = [];

		foreach ($input as $array) {
			$output += $array;
		}

		return $output;
	}

	public function getUse(array $matches)
	{
		return $matches[0];
	}

	public function getUseLine(array $match)
	{
		$namespace = $match['namespace'];
		$alias = &$match['alias'];

		if ($alias === null) {
			$alias = self::getAliasName($namespace);
		}

		return [
			$alias => $namespace
		];
	}

	private static function getAliasName($namespace)
	{
		$slash = strrpos($namespace, '\\');

		if (is_integer($slash)) {
			return substr($namespace, $slash + 1);
		}

		return $namespace;
	}
}
