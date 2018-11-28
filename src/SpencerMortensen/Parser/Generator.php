<?php

namespace _Lens\SpencerMortensen\Parser;

use Exception;
use _Lens\SpencerMortensen\Parser\Generators\AndGenerator;
use _Lens\SpencerMortensen\Parser\Generators\AnyGenerator;
use _Lens\SpencerMortensen\Parser\Generators\GetGenerator;
use _Lens\SpencerMortensen\Parser\Generators\OrGenerator;
use _Lens\SpencerMortensen\Parser\Generators\NotGenerator;

class Generator
{
	/** @var StubAnalyzerInterface */
	private $stub;

	/** @var AndGenerator */
	private $and;

	/** @var AnyGenerator */
	private $any;

	/** @var GetGenerator */
	private $get;

	/** @var OrGenerator */
	private $or;

	public function __construct()
	{
		$this->and = new AndGenerator();
		$this->any = new AnyGenerator();
		$this->get = new GetGenerator();
		$this->or = new OrGenerator();
	}

	public function generate(StubAnalyzerInterface $stub)
	{
		$this->stub = $stub;

		$values = [
			'%head%' => $this->getHeaderPhp($stub),
			'%parser%' => $stub->getParserClass(),
			'%input%' => $stub->getInputClass(),
			'%startMethodName%' => $this->getMethodName($stub->getStartRule()),
			'%methods%' => $this->getRulesPhp()
		];

		return self::php(self::$classPhp, $values) . "\n";
	}

	private function getHeaderPhp(StubAnalyzerInterface $stub)
	{
		return $stub->getParserHeader();
	}

	private function getMethodName($ruleName)
	{
		return 'read' . ucfirst($ruleName);
	}

	private function getRulesPhp()
	{
		$methods = [];

		$rules = $this->stub->getRules();

		foreach ($rules as $rule => $definition) {
			$methodName = $this->getMethodName($rule);
			list($parametersPhp, $bodyPhp) = $this->getRulePhp($rule, $definition);
			$methods[] = $this->getMethodPhp($methodName, $parametersPhp, $bodyPhp);
		}

		return implode("\n\n", $methods);
	}

	private function getRulePhp($rule, array $definition)
	{
		list($type, $description, $output) = $definition;

		switch ($type) {
			case 'get':
				return $this->get->generate($rule, $this->stub, $description, $output);

			case 'and':
				$childRules = $this->getRules($description);
				return $this->and->generate($childRules, $output);

			case 'or':
				$childRules = $this->getRules($description);
				return $this->or->generate($rule, $childRules, $output);

			case 'any':
				list($childRuleName, $min, $max) = $description;
				$childMethodName = $this->getMethodName($childRuleName);

				// TODO: consider whether the child has output!
				return $this->any->generate($childMethodName, $output, $min, $max);

			default:
				throw new Exception();
		}
	}

	private function getRules(array $ruleNames)
	{
		$rules = $this->stub->getRules();

		$output = [];

		foreach ($ruleNames as $ruleName) {
			$methodName = $this->getMethodName($ruleName);
			$definition = $rules[$ruleName][2];
			$output[] = [$methodName, $definition];
		}

		return $output;
	}

	private function getMethodPhp($name, $argumentsPhp, $bodyPhp)
	{
		$values = [
			'%name%' => $name,
			'%arguments%' => $argumentsPhp,
			'%body%' => Paragraph::indent($bodyPhp, "\t\t")
		];

		return self::php(self::$methodPhp, $values);
	}

	private static function php($template, array $values)
	{
		return str_replace(array_keys($values), array_values($values), $template);
	}

	private static $classPhp = <<<'EOS'
%head%
class %parser%
{
	/** @var %input% */
	private $input;

	/** @var mixed */
	private $output;

	/** @var array|null */
	private $expectation;

	/** @var mixed */
	private $position;

	public function parse(%input% $input)
	{
		$this->input = $input;
		$this->expectation = null;
		$this->position = null;

		if ($this->%startMethodName%($output)) {
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

%methods%

	private function setExpectation($rule)
	{
		$this->expectation = $rule;
		$this->position = $this->input->getPosition();
	}
}
EOS;

	private static $methodPhp = <<<'EOS'
	private function %name%(%arguments%)
	{
%body%
	}
EOS;
}
