<?php

/**
 * Copyright (C) 2017 Spencer Mortensen
 *
 * This file is part of Exceptions.
 *
 * Exceptions is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Exceptions is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with Exceptions. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Spencer Mortensen <spencer@lens.guide>
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL-3.0
 * @copyright 2017 Spencer Mortensen
 */

namespace _Lens\SpencerMortensen\Exceptions;

use ErrorException;
use ReflectionClass;
use ReflectionProperty;

class ResultException extends ErrorException
{
	/** @var string */
	private $function;

	/** @var array */
	private $arguments;

	/** @var mixed */
	private $result;

	public function __construct($function, array $arguments, $result)
	{
		$message = $this->makeMessage($function, $arguments, $result);

		parent::__construct($message);

		$this->function = $function;
		$this->arguments = $arguments;
		$this->result = $result;
	}

	private function makeMessage($function, array $arguments, $result)
	{
		$argumentsText = $this->getListText($arguments);
		$resultText = $this->getValueText($result);

		return "Unexpected result: {$function}({$argumentsText}) returned {$resultText}";
	}

	private function getValueText($argument)
	{
		$type = gettype($argument);

		switch ($type) {
			case 'NULL':
				return 'null';

			case 'double':
				return json_encode($argument);

			case 'array':
				return $this->getArrayText($argument);

			case 'object':
				return $this->getObjectText($argument);

			case 'resource':
				return $this->getResourceText($argument);

			default:
				return var_export($argument, true);
		}
	}

	private function getArrayText(array $array)
	{
		if ($this->isList($array)) {
			$valuesText = $this->getListText($array);
		} else {
			$valuesText = $this->getMapText($array);
		}

		return "[{$valuesText}]";
	}

	private function isList(array $array)
	{
		$i = 0;

		foreach ($array as $key => $value) {
			if ($key !== $i) {
				return false;
			}

			++$i;
		}

		return true;
	}

	private function getListText(array $array)
	{
		$elements = [];

		foreach ($array as $value) {
			$elements[] = $this->getValueText($value);
		}

		return implode(', ', $elements);
	}

	private function getMapText(array $array)
	{
		$elements = [];

		foreach ($array as $key => $value) {
			$keyText = $this->getValueText($key);
			$valueText = $this->getValueText($value);

			$elements[] = "{$keyText} => {$valueText}";
		}

		return implode(', ', $elements);
	}

	private function getObjectText($object)
	{
		$class = get_class($object);
		$properties = $this->getProperties($object);
		$propertiesText = $this->getMapText($properties);

		return "new \\{$class}({$propertiesText})";
	}

	private function getProperties($object)
	{
		$output = [];

		$class = new ReflectionClass($object);

		do {
			$className = $class->getName();
			$properties = $class->getProperties();

			/** @var ReflectionProperty $property */
			foreach ($properties as $property) {
				$declaringClass = $property->getDeclaringClass();

				if ($declaringClass->getName() !== $className) {
					continue;
				}

				$property->setAccessible(true);
				$propertyName = $property->getName();
				$propertyValue = $property->getValue($object);

				$output[$className][$propertyName] = $propertyValue;
			}

			$class = $class->getParentClass();
		} while ($class !== false);

		return $output;
	}

	private function getResourceText($resource)
	{
		$id = (integer)$resource;
		$type = get_resource_type($resource);
		return "{$type}({$id})";
	}

	public function getFunction()
	{
		return $this->function;
	}

	public function getArguments()
	{
		return $this->arguments;
	}

	public function getResult()
	{
		return $this->result;
	}
}
