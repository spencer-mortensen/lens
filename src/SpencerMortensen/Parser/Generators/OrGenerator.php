<?php

namespace _Lens\SpencerMortensen\Parser\Generators;

use _Lens\SpencerMortensen\Parser\Paragraph;

class OrGenerator
{
	public function generate($rule, array $childRules, $output)
	{
		$rulePhp = var_export($rule, true);

		if ($output === false) {
			$argumentsPhp = null;
			$parameterPhp = '$output';
			$outputPhp = null;
		} elseif ($output === true) {
			$argumentsPhp = '&$output';
			$parameterPhp = '$output';
			$outputPhp = null;
		} else {
			$argumentsPhp = '&$output';
			list($parameters, $outputPhp) = $output;
			$parameterPhp = $parameters[0];
		}

		$inputPhp = $this->getInputPhp($childRules, $parameterPhp);
		$bodyPhp = $this->getTransformationPhp($rulePhp, $inputPhp, $outputPhp);

		return [$argumentsPhp, $bodyPhp];
	}

	private function getInputPhp(array $rules, $parameterPhp)
	{
		$rulePhps = [];

		foreach ($rules as $rule) {
			list($name, $definition) = $rule;

			if ($definition === false) {
				$parametersPhp = null;
			} else {
				$parametersPhp = $parameterPhp;
			}

			$rulePhps[] = "\$this->{$name}({$parametersPhp})";
		}

		return implode(' || ', $rulePhps);
	}

	// SAME AS GET:
	private function getTransformationPhp($rulePhp, $inputPhp, $outputPhp)
	{
		if ($outputPhp !== null) {
			$outputPhp = Paragraph::indent($outputPhp, "\t") . "\n";
		}

		return "if ({$inputPhp}) {\n" .
			"{$outputPhp}\treturn true;\n" .
			"}\n\n" .
			"\$this->setExpectation({$rulePhp});\n" .
			"return false;";
	}
}
