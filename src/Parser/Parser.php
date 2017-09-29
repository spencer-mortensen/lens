<?php

/**
 * Copyright (C) 2017 Spencer Mortensen
 *
 * This file is part of parser.
 *
 * Parallel-processor is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Parallel-processor is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with parser. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Spencer Mortensen <spencer@testphp.org>
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL-3.0
 * @copyright 2017 Spencer Mortensen
 */

namespace SpencerMortensen\Parser;

abstract class Parser
{
	private static $TYPE_STRING = 1;
	private static $TYPE_RE = 2;
	private static $TYPE_AND = 3;
	private static $TYPE_REPEAT = 4;

	/** @var array */
	private $rules;

	/** @var Lexer */
	private $lexer;

	/** @var null|integer */
	private $furthestPosition;

	/** @var null|string */
	private $furthestExpectation;

	public function __construct($syntax)
	{
		$this->setRules($syntax);
	}

	protected function evaluate($input, $rule)
	{
		$this->lexer = new Lexer($input);

		$this->furthestPosition = null;
		$this->furthestExpectation = null;

		if (!$this->run($rule, $output)) {
			throw new Exception($this->furthestPosition, $this->furthestExpectation);
		}

		if ($this->lexer->getPosition() < strlen($input)) {
			throw new Exception($this->furthestPosition, null);
		}

		return $output;
	}

	private function setRules($syntax)
	{
		$lines = explode("\n", $syntax);

		foreach ($lines as $line) {
			if (strlen($line) === 0) {
				continue;
			}

			list($ruleName, $ruleText) = explode(':', $line, 2);
			list($ruleType, $ruleBody) = explode(' ', trim($ruleText), 2);

			$this->rules[$ruleName] = $this->getRule($ruleName, $ruleType, $ruleBody);
		}
	}

	private function getRule($name, $type, $body)
	{
		switch ($type) {
			default: // 'STRING'
				return $this->getStringRule($body);

			case 'RE':
				return $this->getReRule($name, $body);

			case 'AND':
				return $this->getAndRule($name, $body);

			case 'REPEAT':
				return $this->getRepeatRule($name, $body);
		}
	}

	private function getStringRule($body)
	{
		return array(self::$TYPE_STRING, $body, null);
	}

	private function getReRule($name, $body)
	{
		$callable = $this->getCallable($name);

		return array(self::$TYPE_RE, $body, $callable);
	}

	private function getAndRule($name, $body)
	{
		$rules = explode(' ', $body);

		$callable = $this->getCallable($name);

		return array(self::$TYPE_AND, $rules, $callable);
	}

	private function getRepeatRule($name, $body)
	{
		$options = explode(' ', $body);

		$rule = array_shift($options);
		$options = array_map('intval', $options);

		$min = &$options[0];
		$max = &$options[1];

		$callable = $this->getCallable($name);

		return array(self::$TYPE_REPEAT, $rule, $min, $max, $callable);
	}

	private function getCallable($name)
	{
		$method = 'format' . ucfirst($name);
		$callable = array($this, $method);

		if (!is_callable($callable)) {
			return null;
		}

		return $callable;
	}

	protected function run($rule, &$output = null)
	{
		$this->updateMarkers($rule);

		$definition = $this->rules[$rule];

		$type = array_shift($definition);

		switch ($type) {
			default: // self::TYPE_STRING
				list($string) = $definition;
				return $this->runString($string);

			case self::$TYPE_RE:
				list($expression, $formatter) = $definition;
				return $this->runRe($expression, $formatter, $output);

			case self::$TYPE_AND:
				list($ruleNames, $formatter) = $definition;
				return $this->runAnd($ruleNames, $formatter, $output);

			case self::$TYPE_REPEAT:
				list($rule, $min, $max, $formatter) = $definition;
				return $this->runRepeat($rule, $min, $max, $formatter, $output);
		}
	}

	private function updateMarkers($rule)
	{
		$position = $this->lexer->getPosition();

		if (($this->furthestPosition === null) || ($this->furthestPosition <= $position)) {
			$this->furthestPosition = $position;
			$this->furthestExpectation = $rule;
		}
	}

	private function runString($string)
	{
		return $this->lexer->getString($string);
	}

	private function runRe($expression, $formatter, &$output)
	{
		if (!$this->lexer->getRe($expression, $input)) {
			return false;
		}

		if ($formatter === null) {
			$output = $input[0];
		} else {
			$output = call_user_func($formatter, $input);
		}

		return true;
	}

	private function runAnd(array $rules, $formatter, &$output)
	{
		$lexer = clone $this->lexer;

		$input = array();

		foreach ($rules as $rule) {
			if (!$this->run($rule, $input[])) {
				$this->lexer = $lexer;
				return false;
			}
		}

		if ($formatter === null) {
			$output = $input;
		} else {
			$output = call_user_func($formatter, $input);
		}

		return true;
	}

	private function runRepeat($rule, $min, $max, $formatter, &$output)
	{
		$lexer = clone $this->lexer;

		$input = array();

		for ($i = 0; (($max === null) || ($i < $max)) && $this->run($rule, $inputValue); ++$i) {
			$input[] = $inputValue;
		}

		if (($min !== null) && ($i < $min)) {
			$this->lexer = $lexer;
			return false;
		}

		if ($formatter === null) {
			$output = $input;
		} else {
			$output = call_user_func($formatter, $input);
		}

		return true;
	}
}
