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
 * @author Spencer Mortensen <spencer@spencermortensen.com>
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL-3.0
 * @copyright 2017 Spencer Mortensen
 */

namespace TestPhp;

class MockBuilder
{
	/** @var string */
	private $absoluteParentClass;

	/** @var string */
	private $childNamespace;

	/** @var string */
	private $childClass;

	public function __construct($mockPrefix, $absoluteParentClass)
	{
		$absoluteChildClass = "{$mockPrefix}{$absoluteParentClass}";
		$slash = strrpos($absoluteChildClass, '\\');

		$this->absoluteParentClass = $absoluteParentClass;
		$this->childNamespace = substr($absoluteChildClass, 0, $slash);
		$this->childClass = substr($absoluteChildClass, $slash + 1);
	}

	public function getMock()
	{
		$mockMethods = $this->getMockMethods();

		return <<<EOS
namespace {$this->childNamespace};

class {$this->childClass} extends \\{$this->absoluteParentClass}
{
{$mockMethods}
}
EOS;
	}

	private function getMockMethods()
	{
		$mockMethods = array(
			'__construct' => self::getMockMethod('__construct', '')
		);

		$this->addMockMethods($mockMethods);

		$mockMethods['__call'] = self::getMockCallMethod();

		return implode("\n\n", $mockMethods);
	}

	private function addMockMethods(array &$mockMethods)
	{
		$parentClass = new \ReflectionClass($this->absoluteParentClass);
		$methods = $parentClass->getMethods(\ReflectionMethod::IS_PUBLIC);

		/** @var \ReflectionMethod $method */
		foreach ($methods as $method) {
			if ($method->isStatic() || $method->isFinal()) {
				continue;
			}

			$name = $method->getName();
			$mockParameters = self::getMockParameters($method);

			$mockMethods[$name] = self::getMockMethod($name, $mockParameters);
		}
	}

	private static function getMockCallMethod()
	{
		return <<<EOS
	public function __call(\$name, \$arguments)
	{
		\$callable = array(\$this, \$name);

		return \TestPhp\Agent::recall(\$callable, \$arguments);
	}
EOS;
	}

	private static function getMockMethod($name, $parameters)
	{
		return <<<EOS
	public function {$name}({$parameters})
	{
		\$callable = array(\$this, __FUNCTION__);
		\$arguments = func_get_args();

		return \TestPhp\Agent::recall(\$callable, \$arguments);
	}
EOS;
	}

	private static function getMockParameters(\ReflectionMethod $method)
	{
		$mockParameters = array();

		$parameters = $method->getParameters();

		foreach ($parameters as $parameter) {
			$mockParameters[] = self::getMockParameter($parameter);
		}

		return implode(', ', $mockParameters);
	}

	private static function getMockParameter(\ReflectionParameter $parameter)
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

	private static function getParameterHint(\ReflectionParameter $parameter)
	{
		if ($parameter->isArray()) {
			return 'array';
		}

		if ($parameter->isCallable()) {
			return 'callable';
		}

		// TODO: support PHP-7 type hinting...

		$class = $parameter->getClass();

		if ($class === null) {
			return null;
		}

		$className = $class->getName();
		return '\\' . $className;
	}
}
