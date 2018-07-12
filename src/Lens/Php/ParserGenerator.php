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

namespace _Lens\Lens\Php;

class ParserGenerator
{
	/*
Name: MAKE NamePath 'name'
NamePath: AND Identifier NameSegments
NameSegments: ALL NameSegment
NameSegment: AND Backslash Identifier
Backslash: READ '\\'
Identifier: READ 'identifier'
	*/
	public function generate($input)
	{
		$lines = explode("\n", $input);

		$functions = [];

		foreach ($lines as $line) {
			$functions[] = $this->getFunctionPhp($line);
		}

		$php = implode("\n\n", $functions);

		return $php;
	}

	private function getFunctionPhp($line)
	{
		list($name, $definition) = explode(': ', $line, 2);
		list($type, $details) = explode(' ', $definition, 2);

		switch ($type) {
			case 'AND':
				$conditions = explode(' ', $details);
				return $this->getAndPhp($name, $conditions);

			case 'READ':
				return $this->getReadPhp($name, $details);

			case 'ALL':
				return $this->getAllPhp($name, $details);

			case 'MAKE':
				list($function, $tag) = explode(' ', $details);
				return $this->getMakePhp($name, $function, $tag);
		}
		echo "name: $name\n";
		echo "type: $type\n";
		echo "details: $details\n\n";

		return null;
	}

	private function getAndPhp($name, array $conditions)
	{
		$values = ['%condition%' => $this->getAndConditionsPhp($conditions)];
		return self::makeFunctionPhp(self::$andPhp, $name, $values);
	}

	private function getReadPhp($name, $tag)
	{
		$values = ['%tag%' => var_export($tag, true)];
		return self::makeFunctionPhp(self::$readPhp, $name, $values);
	}

	private function getAllPhp($name, $condition)
	{
		$values = ['%condition%' => $this->getConditionPhp($condition)];
		return self::makeFunctionPhp(self::$allPhp, $name, $values);
	}

	private function getMakePhp($name, $function, $tag)
	{
		$values = [
			'%function%' => self::getFunctionName($function),
			'%tag%' => var_export($tag, true)
		];

		return self::makeFunctionPhp(self::$makePhp, $name, $values);
	}

	private static function makeFunctionPhp($template, $name, array $values)
	{
		$values['%name%'] = self::getFunctionName($name);
		return str_replace(array_keys($values), array_values($values), $template);
	}

	private static function getFunctionName($name)
	{
		return 'get' . ucfirst($name);
	}

	private function getConditionPhp($name)
	{
		$functionName = self::getFunctionName($name);
		return "\$this->{$functionName}(\$output)";
	}

	private function getAndConditionsPhp(array $names)
	{
		$conditions = [];

		foreach ($names as $name) {
			$conditionPhp = $this->getConditionPhp($name);
			$conditions[] = "\t\t\t{$conditionPhp}";
		}

		return implode(" &&\n", $conditions);
	}

	private static $andPhp = <<<'EOS'
	private function %name%(array &$output)
	{
		$position = $this->position;

		if (
%condition%
		) {
			return true;
		}

		$this->revert($position, $output);
		return false;
	}
EOS;

	private static $readPhp = <<<'EOS'
	private function %name%(array &$output)
	{
		return $this->get(%tag%, $output);
	}
EOS;

	private static $allPhp = <<<'EOS'
	private function %name%(array &$output)
	{
		while (%condition%);

		return true;
	}
EOS;

	private static $makePhp = <<<'EOS'
	private function %name%(array &$output)
	{
		$children = [];

		if (!$this->%function%($children)) {
			return false;
		}

		$output[] = new Node($children, [%tag%]);
		return true;
	}
EOS;

	private static $classPhp = <<<'EOS'
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

namespace _Lens\Lens\Php;

use InvalidArgumentException;

class Parser
{
	/** @var Lexer */
	private $lexer;

	/** @var int */
	private $position;

	/** @var array */
	private $input;

	public function __construct()
	{
		$this->lexer = new Lexer();
	}

	public function parse($php)
	{
		$this->position = 0;
		$this->input = $this->lexer->getNodes($php);
		$output = [];

		if (!$this->getName($output)) {
			throw new InvalidArgumentException();
		}

		echo "input: ", var_export(array_slice($this->input, $this->position)), "\n";
		echo "output: ", var_export($output), "\n";

		if ($this->position < count($this->input)) {
			throw new InvalidArgumentException();
		}

		return $output;
	}

%methods%

	private function get($tag, array &$output)
	{
		if (count($this->input) <= $this->position) {
			return false;
		}

		$node = $this->input[$this->position];

		if (!$node->hasTag($tag)) {
			return false;
		}

		++$this->position;
		$output[] = $node;

		return true;
	}

	private function revert($position, array &$output)
	{
		while ($position < $this->position) {
			array_pop($output);
			--$this->position;
		}
	}
}
EOS;
}
