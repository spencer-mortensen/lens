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

namespace Lens_0_0_56\Lens\Archivist;

use ReflectionClass;
use ReflectionProperty;
use Lens_0_0_56\Lens\Archivist\Archives\ObjectArchive;
use Lens_0_0_56\Lens\Archivist\Archives\ResourceArchive;

class Archivist
{
	/** @var array */
	private $archivedObjects;

	public function __construct()
	{
		$this->archivedObjects = array();
	}

	public function archive($value)
	{
		$type = gettype($value);

		switch ($type) {
			default:
				return $value;

			case 'array':
				return $this->archiveArray($value);

			case 'object':
				return $this->archiveObject($value);

			case 'resource':
				return $this->archiveResource($value);
		}
	}

	private function archiveArray(array $input)
	{
		$output = array();

		foreach ($input as $key => $value) {
			$output[$key] = $this->archive($value);
		}

		return $output;
	}

	private function archiveObject($object)
	{
		$id = spl_object_hash($object);

		$archive = &$this->archivedObjects[$id];

		if (isset($archive)) {
			return $archive;
		}

		$class = get_class($object);
		$properties = array();

		$archive = new ObjectArchive($id, $class, $properties);

		$properties = $this->archiveProperties($object);
		$archive->setProperties($properties);

		return $archive;
	}

	private function archiveProperties($object)
	{
		$output = array();

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

				if (self::isUnwantedProperty($className, $propertyName)) {
					continue;
				}

				$propertyValue = $property->getValue($object);

				$output[$className][$propertyName] = $this->archive($propertyValue);
			}

			$class = $class->getParentClass();
		} while ($class !== false);

		return $output;
	}

	private static function isUnwantedProperty($class, $property)
	{
		return (
			($property === 'trace') ||
			($property === 'xdebug_message')
		) && (
			($class === 'Error') ||
			($class === 'Exception')
		);
	}

	private function archiveResource($resource)
	{
		$id = (integer)$resource;
		$type = get_resource_type($resource);
		return new ResourceArchive($id, $type);
	}
}
