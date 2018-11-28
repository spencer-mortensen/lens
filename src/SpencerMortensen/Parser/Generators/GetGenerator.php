<?php

namespace _Lens\SpencerMortensen\Parser\Generators;

use _Lens\SpencerMortensen\Parser\Paragraph;
use _Lens\SpencerMortensen\Parser\StubAnalyzerInterface;

class GetGenerator
{
	/** @var StubAnalyzerInterface */
	private $stub;

	public function generate($rule, StubAnalyzerInterface $stub, $expression, $output)
	{
		$rulePhp = var_export($rule, true);
		$this->stub = $stub;

		if ($output === false) {
			$argumentsPhp = null;
			$parameterPhp = null;
			$outputPhp = null;
		} elseif ($output === true) {
			$argumentsPhp = '&$output';
			$parameterPhp = '$output';
			$outputPhp = null;
		} else {
			$argumentsPhp = '&$output';
			list($parameters, $outputPhp) = $output;
			$parameterPhp = isset($parameters[0]) ? $parameters[0] : null;
		}

		$inputPhp = $this->getInputPhp($stub, $expression, $parameterPhp);
		$bodyPhp = $this->getTransformationPhp($rulePhp, $inputPhp, $outputPhp);

		return [$argumentsPhp, $bodyPhp];
	}

	private function getInputPhp(StubAnalyzerInterface $stub, $type, $parameterPhp)
	{
		$parametersPhp = $this->getInputParametersPhp($stub, $type, $parameterPhp);

		return "\$this->input->read({$parametersPhp})";
	}

	private function getInputParametersPhp(StubAnalyzerInterface $stub, $type, $outputVariablePhp = null)
	{
		$typePhp = $stub->getInputTypePhp($type);

		$parameters = [
			$typePhp
		];

		if ($outputVariablePhp !== null) {
			$parameters[] = $outputVariablePhp;
		}

		return implode(', ', $parameters);
	}

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
