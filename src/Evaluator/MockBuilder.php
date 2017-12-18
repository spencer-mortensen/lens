<?php

/**
 * Copyright (C) 2017 Spencer Mortensen
 *
 * This file is part of Lens.
 *
 * Lens is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Lens is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Lens. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Spencer Mortensen <spencer@lens.guide>
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL-3.0
 * @copyright 2017 Spencer Mortensen
 */

namespace Lens\Evaluator;

use ReflectionClass;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionParameter;
use SpencerMortensen\RegularExpressions\Re;

class MockBuilder
{
	public function getMockClassPhp($namespacedClass)
	{
		// TODO: check that the $namespacedClass exists
		$class = new ReflectionClass($namespacedClass);

		$namePhp = $class->getShortName();
		$methodsPhp = $this->getMethodsListPhp($class);

		return "class {$namePhp} extends \\{$namespacedClass}\n{\n{$methodsPhp}\n}";
	}

	private function getMethodsListPhp(ReflectionClass $class)
	{
		$methodsPhp = array(
			'__construct' => $this->getMethodPhp('__construct', '')
		);

		$methods = $class->getMethods(ReflectionMethod::IS_PUBLIC);

		foreach ($methods as $method) { /** @var ReflectionMethod $method */
			if ($method->isStatic() || $method->isFinal()) {
				continue;
			}

			$namePhp = $method->getShortName();
			$methodPhp = &$methodsPhp[$namePhp];

			if (isset($methodPhp)) {
				continue;
			}

			$parametersPhp = $this->getParametersListPhp($method);
			$bodyPhp = $this->getBodyPhp('$this', "\t\t");

			$methodPhp = "\tpublic function {$namePhp}({$parametersPhp})\n\t{\n{$bodyPhp}\n\t}";
		}

		return implode("\n\n", $methodsPhp);
	}

	private function getMethodPhp($namePhp, $parametersPhp)
	{
		$bodyPhp = $this->getBodyPhp('$this', "\t\t");

		return "\tpublic function {$namePhp}({$parametersPhp})\n\t{\n{$bodyPhp}\n\t}";
	}

	public function getMockFunctionPhp($namespacedFunction)
	{
		$function = new ReflectionFunction($namespacedFunction);

		$namePhp = $function->getShortName();
		$parametersPhp = $this->getParametersListPhp($function);
		$bodyPhp = $this->getBodyPhp('null', "\t");

		return "function {$namePhp}({$parametersPhp})\n{\n{$bodyPhp}\n}";
	}

	private function getParametersListPhp(ReflectionFunctionAbstract $function)
	{
		$mockParametersPhp = array();

		$parameters = $function->getParameters();

		foreach ($parameters as $parameter) {
			$mockParametersPhp[] = $this->getParameterPhp($parameter);
		}

		return implode(', ', $mockParametersPhp);
	}

	private function getParameterPhp(ReflectionParameter $parameter)
	{
		$name = $parameter->getName();

		$definition = '$' . $name;

		if ($parameter->isPassedByReference()) {
			$definition = '&' . $definition;
		}

		if ($this->getHintPhp($parameter, $hint)) {
			$definition = "{$hint} {$definition}";
		}

		if ($this->getDefaultValuePhp($parameter, $defaultValue)) {
			$definition .= " = {$defaultValue}";
		}

		return $definition;
	}

	private function getHintPhp(ReflectionParameter $parameter, &$php)
	{
		if (method_exists($parameter, 'hasType')) {
			return $this->getHintPhp7($parameter, $php);
		}

		return $this->getHintPhp5($parameter, $php);
	}

	private function getHintPhp7(ReflectionParameter $parameter, &$php)
	{
		if (!$parameter->hasType()) {
			return false;
		}

		$php = $parameter->getType();

		if ($parameter->getClass() !== null) {
			$php = '\\' . $php;
		}

		return true;
	}

	private function getHintPhp5(ReflectionParameter $parameter, &$php)
	{
		$parameterExpression = '<(?<status>required|optional)> (?<type>[a-zA-Z_0-9\\\\]+)?';

		Re::match($parameterExpression, (string)$parameter, $match);

		$type = &$match['type'];

		if ($type === null) {
			return false;
		}

		$firstLetter = substr($type, 0, 1);

		if ($firstLetter === strtoupper($firstLetter)) {
			$type = '\\' . $type;
		}

		$php = $type;
		return true;
	}

	private function getDefaultValuePhp(ReflectionParameter $parameter, &$php)
	{
		if ($parameter->isDefaultValueAvailable()) {
			$value = $parameter->getDefaultValue();
			$php = var_export($value, true);
			return true;
		}

		if ($parameter->isOptional()) {
			$php = 'null';
			return true;
		}

		return false;
	}

	private function getBodyPhp($contextPhp, $padding)
	{
		return "{$padding}\\Lens\\Evaluator\\Agent::record({$contextPhp}, __FUNCTION__, func_get_args());\n" .
			"{$padding}return eval(\\Lens\\Evaluator\\Agent::play());";
	}
}
