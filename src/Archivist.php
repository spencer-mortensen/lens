<?php

/**
 * Copyright (C) 2017 Spencer Mortensen
 *
 * This file is part of testphp.
 *
 * Testphp is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Testphp is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with testphp. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Spencer Mortensen <spencer@testphp.org>
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL-3.0
 * @copyright 2017 Spencer Mortensen
 */

namespace TestPhp;

use ReflectionObject;
use ReflectionProperty;

class Archivist
{
	const TYPE_ARRAY = 1;
	const TYPE_OBJECT = 2;
	const TYPE_RESOURCE = 3;

	/** @var array */
	private static $objects;

	public static function archive($input)
	{
		$type = gettype($input);

		switch ($type) {
			default: return $input;
			case 'array': return self::getArray(self::TYPE_ARRAY, $input);
			case 'object': return self::getObject(self::TYPE_OBJECT, $input);
			case 'resource': return self::getResource(self::TYPE_RESOURCE, $input);
		}
	}

	private static function getArray($type, array $input)
	{
		$output = array();

		foreach ($input as $key => $value) {
			$output[$key] = self::archive($value);
		}

		return array($type => $output);
	}

	private static function getObject($type, $input)
	{
		$output = array(
			self::getObjectId($input),
			get_class($input),
			self::getObjectProperties($input)
		);

		return array($type => $output);
	}

	private static function getObjectId($object)
	{
		// This hash is unique as long as the object remains in use;
		// if the object is ever destroyed, then its hash may be reused for a
		// completely different object.
		$id = spl_object_hash($object);

		// Ensure that the hash remains unique by keeping a reference to the object:
		$reference = &self::$objects[$id];

		if (!isset($reference)) {
			$reference = $object;
		}

		return $id;
	}

	private static function getObjectProperties($object)
	{
		$properties = array();

		$reflectionObject = new ReflectionObject($object);
		$reflectionProperties = $reflectionObject->getProperties();

		/** @var ReflectionProperty $reflectionProperty */
		foreach ($reflectionProperties as $reflectionProperty) {
			$reflectionProperty->setAccessible(true);
			$name = $reflectionProperty->getName();
			$value = $reflectionProperty->getValue($object);

			$properties[$name] = self::archive($value);
		}

		return $properties;
	}

	private static function getResource($type, $input)
	{
		$output = array(
			(integer)$input,
			get_resource_type($input)
		);

		return array($type => $output);
	}
}
