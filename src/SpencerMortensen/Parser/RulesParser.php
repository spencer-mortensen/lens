<?php

namespace _Lens\SpencerMortensen\Parser;

use _Lens\SpencerMortensen\Parser\Input\StringInput;

class RulesParser
{
	/** @var StringInput */
	private $input;

	/** @var mixed */
	private $output;

	/** @var array|null */
	private $expectation;

	/** @var mixed */
	private $position;

	public function parse(StringInput $input)
	{
		$this->input = $input;
		$this->expectation = null;
		$this->position = null;

		if ($this->readRules($output)) {
			$this->output = $output;
			$this->position = $this->input->getPosition();
			return true;
		}

		$this->output = null;
		return false;
	}

	public function getOutput()
	{
		return $this->output;
	}

	public function getExpectation()
	{
		return $this->expectation;
	}

	public function getPosition()
	{
		return $this->position;
	}

	private function readRules(&$output)
	{
		$position = $this->input->getPosition();

		if ($this->readRule($rule) && $this->readRulesChain($rules)) {
			array_unshift($rules, $rule);
			$output = call_user_func_array('array_merge', $rules);
			return true;
		}

		$this->input->setPosition($position);
		return false;
	}

	private function readRule(&$output)
	{
		$position = $this->input->getPosition();

		if ($this->readRuleName($name) && $this->readRuleDefinition($definition)) {
			$output = [$name => $definition];
			return true;
		}

		$this->input->setPosition($position);
		return false;
	}

	private function readRuleName(&$output)
	{
		if ($this->input->read('\\h*([a-zA-Z_]+)\\h*:\\h*', $output)) {
			return true;
		}

		$this->setExpectation('ruleName');
		return false;
	}

	private function readRuleDefinition(&$output)
	{
		if ($this->readRuleGet($output) || $this->readRuleAnd($output) || $this->readRuleOr($output) || $this->readRuleAny($output)) {
			return true;
		}

		$this->setExpectation('ruleDefinition');
		return false;
	}

	private function readRuleGet(&$output)
	{
		if ($this->input->read('get\\h+([^\\v]+)', $expression)) {
			$output = ['get', rtrim($expression)];
			return true;
		}

		$this->setExpectation('ruleGet');
		return false;
	}

	private function readRuleAnd(&$output)
	{
		$position = $this->input->getPosition();

		if ($this->readRuleAndKeyword() && $this->readWords($words)) {
			$output = ['and', $words];
			return true;
		}

		$this->input->setPosition($position);
		return false;
	}

	private function readRuleAndKeyword()
	{
		if ($this->input->read('and\\h+')) {
			return true;
		}

		$this->setExpectation('ruleAndKeyword');
		return false;
	}

	private function readWords(&$output)
	{
		$output = [];

		if (!$this->readWord($output[])) {
			return false;
		}

		while ($this->readWord($input)) {
			$output[] = $input;
		}

		return true;
	}

	private function readWord(&$output)
	{
		if ($this->input->read('([a-zA-Z_]+)\\h*', $output)) {
			return true;
		}

		$this->setExpectation('word');
		return false;
	}

	private function readRuleOr(&$output)
	{
		$position = $this->input->getPosition();

		if ($this->readRuleOrKeyword() && $this->readWords($words)) {
			$output = ['or', $words];
			return true;
		}

		$this->input->setPosition($position);
		return false;
	}

	private function readRuleOrKeyword()
	{
		if ($this->input->read('or\\h+')) {
			return true;
		}

		$this->setExpectation('ruleOrKeyword');
		return false;
	}

	private function readRuleAny(&$output)
	{
		$position = $this->input->getPosition();

		if ($this->readRuleAnyKeyword() && $this->readWord($word) && $this->readRuleAnyBounds($bounds)) {
			$definition = $bounds;
			array_unshift($definition, $word);
			$output = ['any', $definition];
			return true;
		}

		$this->input->setPosition($position);
		return false;
	}

	private function readRuleAnyKeyword()
	{
		if ($this->input->read('any\\h+')) {
			return true;
		}

		$this->setExpectation('ruleAnyKeyword');
		return false;
	}

	private function readRuleAnyBounds(&$output)
	{
		if ($this->input->read('(?<min>0|[1-9][0-9]*)\\h*(?:[+]|[-]\\h*(?<max>0|[1-9][0-9]*))\\h*', $matches)) {
			$min = (int)$matches['min'];
			$max = isset($matches['max']) ? max($min, (int)$matches['max']) : null;

			$output = [$min, $max];
			return true;
		}

		$this->setExpectation('ruleAnyBounds');
		return false;
	}

	private function readRulesChain(&$output)
	{
		$output = [];

		while ($this->readRuleLink($input)) {
			$output[] = $input;
		}

		return true;
	}

	private function readRuleLink(&$output)
	{
		$position = $this->input->getPosition();

		if ($this->readRuleSeparator() && $this->readRule($output)) {
			return true;
		}

		$this->input->setPosition($position);
		return false;
	}

	private function readRuleSeparator()
	{
		if ($this->input->read('\\v\\s*')) {
			return true;
		}

		$this->setExpectation('ruleSeparator');
		return false;
	}

	private function setExpectation($rule)
	{
		$this->expectation = $rule;
		$this->position = $this->input->getPosition();
	}
}

