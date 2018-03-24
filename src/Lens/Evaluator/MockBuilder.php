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

namespace Lens_0_0_56\Lens\Evaluator;

use ReflectionClass;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionParameter;
use Lens_0_0_56\SpencerMortensen\RegularExpressions\Re;

class MockBuilder
{
	public function getMockClassPhp($className)
	{
		// TODO: mock interfaces, abstract classes, and missing classes
		// TODO: check that the $namespacedClass exists
		$class = new ReflectionClass($className);

		$namePhp = $class->getShortName();
		$php = "class {$namePhp}";

		if ($class->isAbstract()) {
			$php = "abstract {$php}";
		}

		if ($class->isFinal()) {
			$php = "final {$php}";
		}

		$parent = $class->getParentClass();

		if ($parent !== false) {
			$parentName = $parent->getName();
			$php .= " extends \\{$parentName}";
		}

		$methodsPhp = $this->getMethodsListPhp($class);
		$php .= "\n{\n{$methodsPhp}\n}";

		return $php;
	}

	private function getMethodsListPhp(ReflectionClass $class)
	{
		$methodsPhp = array();

		$this->addMethod($methodsPhp, 'public', '__construct', '');
		$this->addMethod($methodsPhp, 'public', '__call', '$function, array $arguments', '$this', '$function', '$arguments');
		$this->addMethod($methodsPhp, 'public static', '__callStatic', '$function, array $arguments', '__CLASS__', '$function', '$arguments');
		$this->addMethod($methodsPhp, 'public', '__get', '$name');
		$this->addMethod($methodsPhp, 'public', '__set', '$name, $value');
		$this->addMethod($methodsPhp, 'public', '__isset', '$name');
		$this->addMethod($methodsPhp, 'public', '__unset', '$name');
		$this->addMethod($methodsPhp, 'public', '__toString', '');
		$this->addMethod($methodsPhp, 'public', '__invoke', '');
		$this->addMethod($methodsPhp, 'public static', '__setState', 'array $properties', '__CLASS__', '__FUNCTION__', 'func_get_args()');

		$methods = $class->getMethods(ReflectionMethod::IS_PUBLIC);

		foreach ($methods as $method) { /** @var ReflectionMethod $method */
			$namePhp = $method->getShortName();
			$methodPhp = &$methodsPhp[$namePhp];

			if (isset($methodPhp)) {
				continue;
			}

			$methodPhp = $this->getMethodPhp($method);
		}

		return implode("\n\n", $methodsPhp);
	}

	private function addMethod(array &$methods, $typePhp, $namePhp, $parametersPhp, $contextPhp = '$this', $functionPhp = '__FUNCTION__', $argumentsPhp = 'func_get_args()')
	{
		$typePhp .= ' function';
		$bodyPhp = $this->getAgentPhp("\t", $contextPhp, $functionPhp, $argumentsPhp);
		$methodPhp = $this->getFunctionPhp("\t", $typePhp, $namePhp, $parametersPhp, $bodyPhp);

		$methods[$namePhp] = $methodPhp;
	}

	private function getMethodPhp(ReflectionMethod $method)
	{
		$typePhp = $this->getMethodTypePhp($method);
		$namePhp = $method->getShortName();
		$parametersPhp = $this->getFunctionParametersPhp($method);
		$bodyPhp = $this->getMethodBodyPhp($method);

		return $this->getFunctionPhp("\t", $typePhp, $namePhp, $parametersPhp, $bodyPhp);
	}

	private function getMethodTypePhp(ReflectionMethod $method)
	{
		$attributes = array();

		if ($method->isFinal()) {
			$attributes[] = 'final';
		}

		if ($method->isAbstract()) {
			$attributes[] = 'abstract';
		}

		$attributes[] = $this->getVisibility($method);

		if ($method->isStatic()) {
			$attributes[] = 'static';
		}

		$attributes[] = 'function';

		return implode(' ', $attributes);
	}

	private function getVisibility(ReflectionMethod $method)
	{
		if ($method->isPrivate()) {
			return 'private';
		}

		if ($method->isProtected()) {
			return 'protected';
		}

		return 'public';
	}

	private function getFunctionParametersPhp(ReflectionFunctionAbstract $function)
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

	private function getMethodBodyPhp(ReflectionMethod $method)
	{
		if ($method->isAbstract()) {
			return ';';
		}

		$contextPhp = $this->getMethodContextPhp($method);

		return $this->getAgentPhp("\t", $contextPhp, '__FUNCTION__', 'func_get_args()');
	}

	private function getMethodContextPhp(ReflectionMethod $method)
	{
		if ($method->isStatic()) {
			return '__CLASS__';
		}

		return '$this';
	}

	public function getMockFunctionPhp($namespacedFunction)
	{
		$function = new ReflectionFunction($namespacedFunction);

		$namePhp = $function->getShortName();
		$parametersPhp = $this->getFunctionParametersPhp($function);
		$bodyPhp = $this->getAgentPhp('', 'null', '__FUNCTION__', 'func_get_args()');

		return $this->getFunctionPhp('', 'function', $namePhp, $parametersPhp, $bodyPhp);
	}

	private function getAgentPhp($padding, $contextPhp, $functionPhp, $argumentsPhp)
	{
		return "\n{$padding}{\n{$padding}\treturn eval(Agent::call({$contextPhp}, {$functionPhp}, {$argumentsPhp}));\n{$padding}}";
	}

	private function getFunctionPhp($padding, $typePhp, $namePhp, $parametersPhp, $bodyPhp)
	{
		return "{$padding}{$typePhp} {$namePhp}({$parametersPhp}){$bodyPhp}";
	}
}
