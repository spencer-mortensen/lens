<?php

/**
 * Copyright (C) 2017 Spencer Mortensen
 *
 * This file is part of TestPHP.
 *
 * TestPHP is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * TestPHP is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with TestPHP. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Spencer Mortensen <smortensen@datto.com>
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL-3.0
 * @copyright 2017 Spencer Mortensen
 */

namespace TestPhp;

use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;

class Mock
{
	public static function define($mock, $class)
	{
		$reflection = new ReflectionClass($class);
		$methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

		$mockMethods = array();

		foreach ($methods as $method) {
			if ($method->isConstructor() || $method->isStatic() || $method->isFinal()) {
				continue;
			}

			$mockMethods[] = self::getMockMethodDefinition($method);
		}

		$mockMethodList = implode("\n\n", $mockMethods);

		list($mockClassNamespace, $mockClassName) = self::getParts($mock);

		$definition = <<<EOS
namespace {$mockClassNamespace};

class {$mockClassName} extends \\{$class}
{
	/** @var Test */
	private \$test;

	/** @var array */
	private \$output;

	public function __construct(\TestPhp\Test \$test)
	{
		\$this->test = \$test;
		\$this->output = array_slice(func_get_args(), 1);
	}

{$mockMethodList}

	private function getOutput()
	{
		list(, \$value) = each(\$this->output);
		return \$value;
	}
}
EOS;

		eval($definition);
	}

	private static function getParts($class)
	{
		$parts = explode('\\', $class);

		$name = array_pop($parts);
		$namespace = implode('\\', $parts);

		return array($namespace, $name);
	}

	private static function getMockMethodDefinition(ReflectionMethod $method)
	{
		$name = $method->getName();

		$mockParameterList = self::getParameterList($method);

		return <<<EOS
	public function {$name}({$mockParameterList})
	{
		\$this->test->call(array(\$this, '{$name}'), func_get_args());
		return \$this->getOutput();
	}
EOS;
	}

	private static function getParameterList(ReflectionMethod $method)
	{
		$parameters = $method->getParameters();

		$definitions = array();

		foreach ($parameters as $parameter) {
			$definitions[] = self::getParameterDefinition($parameter);
		}

		return implode(', ', $definitions);
	}

	private static function getParameterDefinition(ReflectionParameter $parameter)
	{
		$name = $parameter->getName();
		$hint = self::getParameterHint($parameter);

		$definition = '$' . $name;

		if ($hint !== null) {
			$definition = "{$hint} {$definition}";
		}

		if ($parameter->isOptional()) {
			$definition = "{$definition} = null";
		}

		return $definition;
	}

	private static function getParameterHint(ReflectionParameter $parameter)
	{
		if ($parameter->isArray()) {
			return 'array';
		}

		if ($parameter->isCallable()) {
			return 'callable';
		}

		$class = $parameter->getClass();

		if ($class !== null) {
			$className = $class->getName();
			return '\\' . $className;
		}

		return null;
	}
}
