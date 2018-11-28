<?php

namespace _Lens\SpencerMortensen\Parser\Generators;

use _Lens\SpencerMortensen\Parser\Paragraph;

class AndGenerator
{
	public function generate(array $childRules, $output)
	{
		if ($output === false) {
			$argumentsPhp = null;
		} else {
			$argumentsPhp = '&$output';
		}

		if ($output === false) {
			$parameters = array_fill(0, count($childRules), '$input');
			$outputPhp = null;
		} elseif ($output === true) {
			$parameters = array_fill(0, count($childRules), '$output');
			$outputPhp = null;
		} else {
			list($parameters, $outputPhp) = $output;
		}

		$inputPhp = $this->getInputPhp($childRules, $parameters);
		$bodyPhp = $this->getTransformationPhp($inputPhp, $outputPhp);

		return [$argumentsPhp, $bodyPhp];
	}

	private function getInputPhp(array $rules, array $parameters)
	{
		$rulePhps = [];

		$i = 0;

		foreach ($rules as $rule) {
			list($name, $definition) = $rule;

			if ($definition === false) {
				$parametersPhp = null;
			} else {
				$parametersPhp = $parameters[$i++];
			}

			$rulePhps[] = "\$this->{$name}({$parametersPhp})";
		}

		return implode(' && ', $rulePhps);
	}

	private function getTransformationPhp($inputPhp, $outputPhp)
	{
		if ($outputPhp !== null) {
			$outputPhp = Paragraph::indent($outputPhp, "\t") . "\n";
		}

		return "\$position = \$this->input->getPosition();\n\n" .
			"if ({$inputPhp}) {\n" .
			"{$outputPhp}\treturn true;\n" .
			"}\n\n" .
			"\$this->input->setPosition(\$position);\n" .
			"return false;";
	}
}
