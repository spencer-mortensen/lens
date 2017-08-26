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

namespace Lens\Engine;

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
			$mockMethod = self::getMockMethod($name, $mockParameters);

			$mockMethods[$name] = $mockMethod;
		}
	}

	private function getMockMethod($name, $parameters)
	{
		$code = <<<'EOS'
	public function %s(%s)
	{
		return \Lens\Engine\Agent::call($this, __FUNCTION__, func_get_args());
	}
EOS;

		return sprintf($code, $name, $parameters);
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
