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

namespace _Lens\SpencerMortensen\Parser\Core;

use ErrorException;
use _Lens\SpencerMortensen\Parser\Core\Rules\AndRule;
use _Lens\SpencerMortensen\Parser\Core\Rules\ManyRule;
use _Lens\SpencerMortensen\Parser\Core\Rules\OrRule;

class Rules
{
	/** @var Object */
	private $object;

	/** @var array */
	protected $rules;

	public function __construct($object, $grammar)
	{
		$this->object = $object;
		$this->setRules($grammar);
	}

	public function getRule($name)
	{
		$rule = &$this->rules[$name];

		if ($rule === null) {
			throw $this->unknownRuleName($name);
		}

		return $rule;
	}

	private function setRules($grammar)
	{
		$this->rules = array();

		$grammar = trim($grammar);
		$lines = explode("\n", $grammar);

		foreach ($lines as $line) {
			$line = trim($line);

			if (strlen($line) === 0) {
				continue;
			}

			list($name, $text) = explode(':', $line, 2);

			if (isset($this->rules[$name])) {
				throw $this->redefinedRule($name);
			}

			$parts = explode(' ', ltrim($text), 2);
			$type = strtolower($parts[0]);
			$definition = &$parts[1];

			$this->rules[$name] = $this->createRule($name, $type, $definition);
		}

		foreach ($this->rules as $name => $rule) {
			if ($rule === null) {
				throw $this->undefinedRule($name);
			}
		}
	}

	protected function createRule($name, $type, $definition)
	{
		switch ($type) {
			case 'and':
				return $this->createAndRule($name, $definition);

			case 'many':
				return $this->createManyRule($name, $definition);

			case 'or':
				return $this->createOrRule($name, $definition);

			default:
				throw $this->unknownRuleType($type);
		}
	}

	private function createAndRule($name, $definition)
	{
		$ruleNames = explode(' ', $definition);
		$rules = $this->getRules($ruleNames);
		$callable = $this->getCallable($name);

		return new AndRule($name, $rules, $callable);
	}

	private function createManyRule($name, $definition)
	{
		$arguments = explode(' ', $definition);
		$childRuleName = array_shift($arguments);
		$childRule = &$this->rules[$childRuleName];

		$arguments = array_map('intval', $arguments);
		$min = &$arguments[0];
		$max = &$arguments[1];

		$callable = $this->getCallable($name);

		return new ManyRule($name, $childRule, $min, $max, $callable);
	}

	private function createOrRule($name, $definition)
	{
		$ruleNames = explode(' ', $definition);
		$rules = $this->getRules($ruleNames);
		$callable = $this->getCallable($name);

		return new OrRule($name, $rules, $callable);
	}

	protected function getRules(array $names)
	{
		$rules = array();

		foreach ($names as $name) {
			$rules[] = &$this->rules[$name];
		}

		return $rules;
	}

	protected function getCallable($name)
	{
		$method = 'get' . ucfirst($name);
		$callable = array($this->object, $method);

		if (is_callable($callable)) {
			return $callable;
		}

		return null;
	}

	private function undefinedRule($name)
	{
		$nameText = json_encode($name);

		return new ErrorException("The rule {$nameText} has no definition.");
	}

	private function redefinedRule($name)
	{
		$nameText = json_encode($name);

		return new ErrorException("The rule {$nameText} has multiple definitions.");
	}

	private function unknownRuleName($name)
	{
		$nameText = json_encode($name);

		return new ErrorException("The rule {$nameText} is unknown.");
	}

	private function unknownRuleType($type)
	{
		$typeText = json_encode($type);

		return new ErrorException("The rule type {$typeText} is unknown.");
	}
}
