<?php

namespace _Lens\SpencerMortensen\Parser\Generators;

class AnyGenerator
{
	/**
	 * @param string $childName
	 * The name of the child rule.
	 *
	 * @param array|true|false $output
	 * [$arguments, $php] iff the default output will be transformed
	 * true iff the default output will be returned without modifications
	 * false iff there will be no output
	 *
	 * @param $min
	 * @param $max
	 * @return array
	 */
	public function generate($childName, $output, $min, $max)
	{
		if (($min === 0) && ($max === 0)) {
			$argumentsPhp = null;
			$bodyPhp = $this->getNegationPhp($childName, $output);
		} elseif ($output === false) {
			$argumentsPhp = null;
			$bodyPhp = $this->getDeletionPhp($childName, $min, $max);
		} elseif ($output === true) {
			$argumentsPhp = '&$output';
			$bodyPhp = $this->getIdentityPhp($childName, $min, $max);
		} else {
			$argumentsPhp = '&$output';
			$bodyPhp = $this->getTransformationPhp($childName, $min, $max, $output);
		}

		return [$argumentsPhp, $bodyPhp];
	}

	private function getNegationPhp($childName, $output)
	{
		$parameterPhp = ($output === false) ? null : '$input';
		$callPhp = $this->getCallPhp($childName, $parameterPhp);

		return "\$position = \$this->input->getPosition();\n\n" .
			"if (!{$callPhp}) {\n" .
			"\treturn true;\n" .
			"}\n\n" .
			"\$this->input->setPosition(\$position);\n" .
			"return false;";
	}

	private function getCallPhp($methodName, $parameterPhp = null)
	{
		return "\$this->{$methodName}({$parameterPhp})";
	}

	private function getDeletionPhp($childName, $min, $max)
	{
		$callPhp = $this->getCallPhp($childName);

		if (($min === 1) && ($max === 1)) {
			return $this->getDeletionDegeneratePhp($callPhp);
		}

		if (is_int($max)) {
			$max -= $min;
		}

		$output = [
			$this->getRequiredPhp($callPhp, $min),
			$this->getDeletionOptionalPhp($callPhp, $max),
			'return true;'
		];

		$output = array_filter($output, 'is_string');

		return implode("\n\n", $output);
	}

	private function getDeletionDegeneratePhp($callPhp)
	{
		return "return {$callPhp};";
	}

	private function getDeletionOptionalPhp($callPhp, $repetitions)
	{
		if ($repetitions === 0) {
			return null;
		}

		if ($repetitions === null) {
			return "while ({$callPhp});";
		}

		return $callPhp . str_repeat(" && {$callPhp}", $repetitions - 1) . ';';
	}

	private function getIdentityPhp($childName, $min, $max)
	{
		if (($min === 1) && ($max === 1)) {
			return $this->getIdentityDegeneratePhp($childName);
		}

		if (is_int($max)) {
			$max -= $min;
		}

		$callPhp = $this->getCallPhp($childName, '$output[]');

		$output = [
			'$output = [];',
			$this->getRequiredPhp($callPhp, $min),
			$this->getOptionalPhp($childName, $max, '$output[]'),
			'return true;'
		];

		$output = array_filter($output, 'is_string');

		return implode("\n\n", $output);
	}

	private function getIdentityDegeneratePhp($childName)
	{
		$callPhp = $this->getCallPhp($childName, '$output[]');

		return "\$output = [];\n\n" .
			"return {$callPhp};";
	}

	private function getRequiredPhp($callPhp, $repetitions)
	{
		if ($repetitions === 0) {
			return null;
		}

		if ($repetitions === 1) {
			return "if (!{$callPhp}) {\n" .
				"\treturn false;\n" .
				"}";
		}

		// 1 < $repetitions
		$conditionPhp = $callPhp . str_repeat(" && {$callPhp}", $repetitions - 1);

		return "\$position = \$this->input->getPosition();\n\n" .
			"if (!({$conditionPhp})) {\n" .
			"\t\$this->input->setPosition(\$position);\n" .
			"\treturn false;\n" .
			"}";
	}

	private function getOptionalPhp($childName, $repetitions, $outputVariablePhp)
	{
		if ($repetitions === 0) {
			return null;
		}

		$callPhp = $this->getCallPhp($childName, '$input');
		$controlPhp = $this->getOptionalCallsControlPhp($callPhp, $repetitions);

		return "{$controlPhp} {\n" .
			"\t{$outputVariablePhp} = \$input;\n" .
			"}";
	}

	private function getOptionalCallsControlPhp($callPhp, $repetitions)
	{
		if ($repetitions === 1) {
			return "if ({$callPhp})";
		}

		if ($repetitions === null) {
			return "while ({$callPhp})";
		}

		return "for (\$i = 0; (\$i < {$repetitions}) && {$callPhp}; ++\$i)";
	}

	private function getTransformationPhp($childName, $min, $max, array $transformation)
	{
		if (is_int($max)) {
			$max -= $min;
		}

		list($transformationArguments, $transformationPhp) = $transformation;
		$transformationArgumentPhp = $transformationArguments[0];
		$callPhp = $this->getCallPhp($childName, "{$transformationArgumentPhp}[]");

		$output = [
			"{$transformationArgumentPhp} = [];",
			$this->getRequiredPhp($callPhp, $min),
			$this->getOptionalPhp($childName, $max, "{$transformationArgumentPhp}[]"),
			$transformationPhp,
			'return true;'
		];

		$output = array_filter($output, 'is_string');

		return implode("\n\n", $output);
	}
}
