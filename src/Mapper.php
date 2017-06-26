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

use TestPhp\Packager\Packages\Package;
use TestPhp\Packager\Packages\ObjectPackage;
use TestPhp\Packager\Packages\ResourcePackage;

class Mapper
{
	/** @var array */
	private $objects;

	/** @var array */
	private $resources;

	public function __construct()
	{
		$this->objects = array();
		$this->resources = array();
	}

	public function associate($packedValue, $unpackedValue)
	{
		$type = gettype($unpackedValue);

		switch ($type) {
			case 'array':
				$this->associateArray($packedValue, $unpackedValue);
				break;

			case 'object':
				$this->associateObject($packedValue, $unpackedValue);
				break;

			case 'resource':
				$this->associateResource($packedValue, $unpackedValue);
				break;
		}
	}

	private function associateArray($packedValue, array $unpackedArray)
	{
		if (!is_array($packedValue)) {
			return;
		}

		$keys = array_keys($unpackedArray);

		if ($keys !== array_keys($packedValue)) {
			return;
		}

		foreach ($keys as $key) {
			$this->associate($packedValue[$key], $unpackedArray[$key]);
		}
	}

	private function associateObject($packedValue, $unpackedObject)
	{
		if (!is_object($packedValue)) {
			return;
		}

		/** @var Package $packedValue */
		$packageType = $packedValue->getPackageType();

		if ($packageType !== Package::TYPE_OBJECT) {
			return;
		}

		/** @var ObjectPackage $packedValue */
		$id = $packedValue->getId();

		if (isset($this->objects[$id])) {
			return;
		}

		$this->objects[$id] = $unpackedObject;
	}

	private function associateResource($packedValue, $unpackedResource)
	{
		if (!is_object($packedValue)) {
			return;
		}

		/** @var Package $packedValue */
		$packageType = $packedValue->getPackageType();

		if ($packageType !== Package::TYPE_RESOURCE) {
			return;
		}

		/** @var ResourcePackage $packedValue */
		$id = $packedValue->getId();

		if (isset($this->resources[$id])) {
			return;
		}

		$this->resources[$id] = $unpackedResource;
	}

	public function unpack($packedValue)
	{
		$type = gettype($packedValue);

		if ($type === 'array') {
			return $this->unpackArray($packedValue);
		}

		if ($type === 'object') {
			/** @var Package $packedValue */
			$packageType = $packedValue->getPackageType();

			if ($packageType === Package::TYPE_OBJECT) {
				/** @var ObjectPackage $packedValue */
				return $this->unpackObject($packedValue);
			} else {
				/** @var ResourcePackage $packedValue */
				return $this->unpackResource($packedValue);
			}
		}

		return $packedValue;
	}

	private function unpackArray(array $packedArray)
	{
		$unpackedArray = array();

		foreach ($packedArray as $key => $packedValue) {
			$unpackedArray[$key] = $this->unpack($packedValue);
		}

		return $unpackedArray;
	}

	private function unpackObject(ObjectPackage $packedObject)
	{
		$id = $packedObject->getId();
		$object = &$this->objects[$id];

		if (!isset($object)) {
			$object = $packedObject->getObject();
		}

		return $object;
	}

	private function unpackResource(ResourcePackage $packedResource)
	{
		$id = $packedResource->getId();
		$resource = &$this->resources[$id];

		return $resource;
	}
}
