<?php

namespace _Lens\SpencerMortensen\Parser;

use Exception;
use ReflectionClass;
use ReflectionMethod;
use _Lens\SpencerMortensen\Filesystem\File;
use _Lens\SpencerMortensen\Filesystem\Path;
use _Lens\SpencerMortensen\Parser\Exceptions\ParseException;
use _Lens\SpencerMortensen\Parser\Input\StringInput;

class StubAnalyzer implements StubAnalyzerInterface
{
	private $stub;

	/** @var string */
	private $parserHeader;

	/** @var string */
	private $parserClass;

	/** @var string */
	private $inputClass;

	/** @var array */
	private $rules;

	/** @var string */
	private $startRule;

	public function __construct($stub)
	{
		$this->stub = $stub;

		$reflection = new ReflectionClass($stub);
		$fileLines = $this->getFileLines($reflection);

		$this->parserHeader = $this->getParserHeaderFromStub($reflection, $fileLines);
		$this->parserClass = $this->getParserClassFromStub($reflection);

		$inputPath = $this->getInputPathFromStub($reflection);
		$this->inputClass = $this->getClass($inputPath);

		$this->startRule = $stub->startRule;
		$allRules = $this->getRulesFromText($stub->rules);

		$usedRules = [];
		$this->getUsedRules($this->startRule, $allRules, $usedRules);
		$this->addOutput($reflection, $fileLines, $this->startRule, $usedRules);

		$this->rules = $usedRules;
	}

	public function getParserHeader()
	{
		return $this->parserHeader;
	}

	public function getParserClass()
	{
		return $this->parserClass;
	}

	public function getInputClass()
	{
		return $this->inputClass;
	}

	public function getRules()
	{
		return $this->rules;
	}

	public function getStartRule()
	{
		return $this->startRule;
	}

	public function getInputTypePhp($type)
	{
		return $this->stub->__get($type);
	}

	private function getFileLines(ReflectionClass $class)
	{
		$path = Path::fromString($class->getFileName());
		$file = new File($path);
		$php = $file->read();

		// TODO: support all line endings
		$lines = explode("\n", $php);

		return $lines;
	}

	private function getParserHeaderFromStub(ReflectionClass $reflection, array $lines)
	{
		$beginLine = $reflection->getStartLine();

		$headerLines = array_slice($lines, 0, $beginLine - 1);

		return implode("\n", $headerLines);
	}

	private function getParserClassFromStub(ReflectionClass $stub)
	{
		$path = $stub->getShortName();

		if (substr($path, -4) === 'Stub') {
			$path = substr($path, 0, -4);
		}

		return $path;
	}

	private function getInputPathFromStub(ReflectionClass $stub)
	{
		$method = $stub->getMethod('__invoke');
		$parameters = $method->getParameters();
		$parameter = $parameters[0];
		return (string)$parameter->getType();
	}

	private function getClass($path)
	{
		$slash = strrpos($path, '\\');

		if ($slash === false) {
			return $path;
		}

		return substr($path, $slash + 1);
	}

	private function getRulesFromText($inputText)
	{
		$parser = new RulesParser();
		$input = new StringInput($inputText);

		if ($parser->parse($input)) {
			$position = $parser->getPosition();

			if ($position === strlen($inputText)) {
				return $parser->getOutput();
			}
		}

		$expectation = $parser->getExpectation();
		$position = $parser->getPosition();

		throw new ParseException($position, $expectation);
	}

	private function getUsedRules($rule, array $allRules, array &$usedRules)
	{
		if (array_key_exists($rule, $usedRules)) {
			return;
		}

		list($type, $definition) = $allRules[$rule];

		$usedRules[$rule] = [$type, $definition];

		switch ($type) {
			case 'and':
			case 'or':
				foreach ($definition as $child) {
					$this->getUsedRules($child, $allRules, $usedRules);
				}
				break;

			case 'any':
				$child = $definition[0];
				$this->getUsedRules($child, $allRules, $usedRules);
				break;
		}
	}

	private function addOutput(ReflectionClass $class, array $lines, $ruleName, array &$usedRules)
	{
		$rule = &$usedRules[$ruleName];

		if (array_key_exists(2, $rule)) {
			return;
		}

		$output = &$rule[2];

		list($type, $definition) = $rule;

		switch ($type) {
			case 'get':
				$output = $this->getGetOutput($class, $lines, $ruleName);
				break;

			case 'and':
				$output = $this->getAndOutput($class, $lines, $ruleName, $definition, $usedRules);
				break;

			case 'or':
				$output = $this->getOrOutput($class, $lines, $ruleName, $definition, $usedRules);
				break;

			case 'any':
				list($childRuleName, $min, $max) = $definition;
				$output = $this->getAnyOutput($class, $lines, $ruleName, $childRuleName, $min, $max, $usedRules);
				break;
		}
	}

	private function getGetOutput(ReflectionClass $class, array $lines, $ruleName)
	{
		if (!$class->hasMethod($ruleName)) {
			return false;
		}

		$method = $class->getMethod($ruleName);
		$parameters = $this->getParameterNames($method);

		if (1 < count($parameters)) {
			throw new Exception();
		}

		$outputPhp = $this->getOutputPhp($method, $lines);

		if ($this->isIdentity($parameters, $outputPhp)) {
			return true;
		}

		return [$parameters, $outputPhp];
	}

	private function getParameterNames(ReflectionMethod $method)
	{
		$names = [];

		$parameters = $method->getParameters();

		foreach ($parameters as $parameter) {
			$names[] = '$' . $parameter->getName();
		}

		return $names;
	}

	private function getOutputPhp(ReflectionMethod $method, array $lines)
	{
		$methodBeginLine = $method->getStartLine();
		$methodEndLine = $method->getEndLine();
		$methodLength = $methodEndLine - $methodBeginLine;
		$methodLines = array_slice($lines, $methodBeginLine, $methodLength);

		// TODO: parse this (to avoid complications with string values that contain the characters "return"):
		$php = implode("\n", $methodLines);
		$php = self::replace('^\\s*{\\v*(.*?)\\s*}\\s*$', '$1', $php);
		$php = self::trimIndentation($php);
		$php = self::replace('return\\s+\\$output;', '', $php);
		$php = self::replace('(?<![\'"])return ', '$output = ', $php);

		return rtrim($php);
	}

	private static function replace($expression, $replacement, $input)
	{
		$delimiter = "\x03";
		$flags = 'XDs';

		$pattern = $delimiter . $expression . $delimiter . $flags;

		return preg_replace($pattern, $replacement, $input);
	}

	private static function trimIndentation($php)
	{
		if (!self::match('^\\h+', $php, $padding)) {
			return $php;
		}

		$expression = "(^|\n){$padding}";
		return self::replace($expression, '$1', $php);
	}

	private static function match($expression, $input, array &$output = null)
	{
		$delimiter = "\x03";
		$flags = 'XDs';

		$pattern = $delimiter . $expression . $delimiter . $flags;

		if (preg_match($pattern, $input, $matches) !== 1) {
			return false;
		}

		if (count($matches) === 1) {
			$output = $matches[0];
		} else {
			$output = $matches;
		}

		return true;
	}

	private function isIdentity(array $parameters, $php)
	{
		if (count($parameters) !== 1) {
			return false;
		}

		$parameterName = $parameters[0];

		$minimalPhp = self::replace('\\s+', '', $php);
		$identityPhp = "\$output={$parameterName};";

		return $minimalPhp === $identityPhp;
	}

	private function getAndOutput(ReflectionClass $class, array $lines, $ruleName, array $childRuleNames, array &$rules)
	{
		foreach ($childRuleNames as $childRuleName) {
			$this->addOutput($class, $lines, $childRuleName, $rules);
		}

		if ($class->hasMethod($ruleName)) {
			return $this->getTransformationPhp($class, $lines, $ruleName);
		}

		$childrenWithOutput = $this->countRulesWithOutput($childRuleNames, $rules);

		if (1 < $childrenWithOutput) {
			throw new Exception();
		}

		return ($childrenWithOutput === 1);
	}

	private function getTransformationPhp(ReflectionClass $class, array $lines, $ruleName)
	{
		$method = $class->getMethod($ruleName);
		$parameters = $this->getParameterNames($method);
		$outputPhp = $this->getOutputPhp($method, $lines);

		if ($this->isIdentity($parameters, $outputPhp)) {
			return true;
		}

		return [$parameters, $outputPhp];
	}

	private function countRulesWithOutput(array $ruleNames, array $rules)
	{
		$count = 0;

		foreach ($ruleNames as $ruleName) {
			if ($this->hasOutput($ruleName, $rules)) {
				++$count;
			}
		}

		return $count;
	}

	private function hasOutput($ruleName, array $rules)
	{
		return isset($rules[$ruleName][2]) && ($rules[$ruleName][2] !== false);
	}

	private function getOrOutput(ReflectionClass $class, array $lines, $ruleName, array $childRuleNames, array &$rules)
	{
		foreach ($childRuleNames as $childRuleName) {
			$this->addOutput($class, $lines, $childRuleName, $rules);
		}

		if ($class->hasMethod($ruleName)) {
			return $this->getTransformationPhp($class, $lines, $ruleName);
		}

		$childrenWithOutput = $this->countRulesWithOutput($childRuleNames, $rules);

		if ($childrenWithOutput === 0) {
			return false;
		}

		if ($childrenWithOutput = count($childRuleNames)) {
			return true;
		}

		throw new Exception();
	}

	private function getAnyOutput(ReflectionClass $class, array $lines, $ruleName, $childRuleName, $min, $max, array &$rules)
	{
		$this->addOutput($class, $lines, $childRuleName, $rules);

		if ($class->hasMethod($ruleName)) {
			return $this->getTransformationPhp($class, $lines, $ruleName);
		}

		return $this->hasOutput($childRuleName, $rules);
	}
}
