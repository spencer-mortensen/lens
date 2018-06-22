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

namespace Lens_0_0_57\SpencerMortensen\Parser\Core;

use ErrorException;
use Lens_0_0_57\SpencerMortensen\Parser\Core\Rules\AndRule;
use Lens_0_0_57\SpencerMortensen\Parser\Core\Rules\ManyRule;
use Lens_0_0_57\SpencerMortensen\Parser\Core\Rules\OrRule;
use Lens_0_0_57\SpencerMortensen\Parser\Rule;

abstract class Parser
{
	protected function runRule(Rule $rule, &$output = null)
	{
		if ($rule instanceof AndRule) {
			/** @var AndRule $rule */
			return $this->runAndRule($rule, $output);
		} elseif ($rule instanceof ManyRule) {
			/** @var ManyRule $rule */
			return $this->runManyRule($rule, $output);
		} elseif ($rule instanceof OrRule) {
			/** @var OrRule $rule */
			return $this->runOrRule($rule, $output);
		} else {
			throw $this->unknownRule($rule);
		}
	}

	private function runAndRule(AndRule $rule, &$output)
	{
		$state = $this->getState();

		$childRules = $rule->getRules();
		$input = array();

		foreach ($childRules as $childRule) {
			$this->setExpectation($childRule);

			if (!$this->runRule($childRule, $input[])) {
				$this->setState($state);
				return false;
			}
		}

		$output = $this->formatOutput($rule, $input);
		$this->setExpectation(null);
		return true;
	}

	private function runManyRule(ManyRule $rule, &$output)
	{
		$state = $this->getState();

		$childRule = $rule->getRule();
		$min = $rule->getMin();
		$max = $rule->getMax();

		$input = array();

		for ($i = 0; (($max === null) || ($i < $max)); ++$i) {
			$this->setExpectation($childRule);

			if (!$this->runRule($childRule, $inputValue)) {
				break;
			}

			$input[] = $inputValue;
		}

		if ((($min !== null) && ($i < $min))) {
			$this->setState($state);
			return false;
		}

		$output = $this->formatOutput($rule, $input);
		$this->setExpectation(null);
		return true;
	}

	private function runOrRule(OrRule $rule, &$output)
	{
		$childRules = $rule->getRules();

		foreach ($childRules as $childRule) {
			if ($this->runRule($childRule, $input)) {
				$output = $this->formatOutput($rule, $input);
				$this->setExpectation(null);
				return true;
			}
		}

		$this->setExpectation($rule);
		return false;
	}

	private function formatOutput(Rule $rule, $input)
	{
		$callable = $rule->getCallable();

		if ($callable !== null) {
			return call_user_func($callable, $input);
		}

		return $input;
	}

	private function unknownRule(Rule $rule)
	{
		$class = get_class($rule);
		$classText = json_encode($class);

		return new ErrorException("Unknown rule {$classText}");
	}

	abstract protected function getState();

	abstract protected function setState($state);

	abstract protected function setExpectation(Rule $rule = null);
}
