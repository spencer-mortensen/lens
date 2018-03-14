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

namespace Lens_0_0_56\Lens\Exceptions;

use Error;
use Exception;
use ReflectionClass;
use ReflectionProperty;

class DataExtractor
{
	/**
	 * @param Exception|Error $exception
	 * @return array
	 */
	public function getData($exception)
	{
		return array(
			'class' => get_class($exception),
			'code' => $exception->getCode(),
			'message' => $exception->getMessage(),
			'file' => $exception->getFile(),
			'line' => $exception->getLine(),
			'properties' => $this->getExceptionProperties($exception),
			'exception' => $this->getPreviousExceptionData($exception)
		);
	}

	/**
	 * @param Exception|Error $exception
	 * @return array
	 */
	private function getExceptionProperties($exception)
	{
		$properties = $this->getObjectProperties($exception);

		unset($properties['Exception'], $properties['Error']);

		return $properties;
	}

	// TODO: use this in the Archivist
	private function getObjectProperties($object)
	{
		$class = new ReflectionClass($object);
		$properties = $class->getProperties();

		$output = array();

		/** @var ReflectionProperty $property */
		foreach ($properties as $property) {
			$class = $property->getDeclaringClass();
			$className = $class->getName();

			$property->setAccessible(true);
			$propertyName = $property->getName();
			$propertyValue = $property->getValue($object);

			$output[$className][$propertyName] = $propertyValue;
		}

		return $output;
	}

	/**
	 * @param Exception|Error $exception
	 * @return array|null
	 */
	private function getPreviousExceptionData($exception)
	{
		$previous = $exception->getPrevious();

		if ($previous === null) {
			return null;
		}

		return $this->getData($previous);
	}
}
