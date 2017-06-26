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

use TestPhp\Archivist\Archives\Archive;
use TestPhp\Archivist\Archives\ObjectArchive;
use TestPhp\Archivist\Archives\ResourceArchive;

class Displayer
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

	public function display($archive)
	{
		if (is_object($archive)) {
			return $this->showComplex($archive);
		}

		return $this->showPrimitive($archive);
	}

	private function showPrimitive($value)
	{
		$type = gettype($value);

		switch ($type) {
			default: return $this->showNull();
			case 'boolean': return $this->showBoolean($value);
			case 'integer': return $this->showInteger($value);
			case 'double': return $this->showFloat($value);
			case 'string': return $this->showString($value);
			case 'array': return $this->showArray($value);
		}
	}

	private function showComplex(Archive $value)
	{
		$type = $value->getArchiveType();

		/** @var ResourceArchive $value */
		if ($type === Archive::TYPE_RESOURCE) {
			return $this->showResource($value);
		}

		/** @var ObjectArchive $value */
		return $this->showObject($value);
	}

	private function showNull()
	{
		return 'null';
	}

	private function showBoolean($boolean)
	{
		return $boolean ? 'true' : 'false';
	}

	private function showInteger($integer)
	{
		return (string)$integer;
	}

	private function showFloat($float)
	{
		return (string)$float;
	}

	/**
	 * Convert the text input to a PHP string expression. Use single quotes
	 * by default (e.g. 'single-quoted text'), but switch to double-quotes when
	 * the input contains special characters (e.g. "double-quoted text\n")
	 * that would be more readable as escape sequences.
	 *
	 * @param string $string
	 * @return string
	 */
	private function showString($string)
	{
		$decidingCharacters = "'\n\r\t\v\e\f\\\$";

		if (strcspn($string, $decidingCharacters) === strlen($string)) {
			return var_export($string, true);
		}

		return '"' . addcslashes($string, "\n\r\t\v\e\f\\\$\"") . '"';
	}

	private function showArray(array $array)
	{
		if (self::isZeroIndexedArray($array)) {
			$rows = $this->getZeroIndexedArrayRows($array);
		} else {
			$rows = $this->getAssociativeArrayRows($array);
		}

		return $this->getArray($rows);
	}

	private static function isZeroIndexedArray(array $array)
	{
		$i = 0;

		foreach ($array as $key => $value) {
			if ($key !== $i++) {
				return false;
			}
		}

		return true;
	}

	private function getZeroIndexedArrayRows(array $array)
	{
		$rows = array();

		foreach ($array as $key => $value) {
			$rows[] = $this->display($value);
		}

		return $rows;
	}

	private function getAssociativeArrayRows(array $array)
	{
		$rows = array();

		foreach ($array as $key => $value) {
			$rows[] = $this->display($key) . ' => ' . $this->display($value);
		}

		return $rows;
	}

	private function getArray($rows)
	{
		$body = implode(', ', $rows);

		return "array({$body})";
	}

	private function showResource(ResourceArchive $resource)
	{
		$type = $resource->getType();

		return "resource({$type})";
	}

	private function showObject(ObjectArchive $object)
	{
		$class = $object->getClass();
		$properties = $object->getProperties();

		if (count($properties) === 0) {
			return "object('{$class}')";
		}

		$propertiesList = $this->getPropertiesList($properties);

		return "object('{$class}', {$propertiesList})";
	}

	private function getPropertiesList(array $properties)
	{
		$rows = array();

		foreach ($properties as $name => $value) {
			$rows[] = $this->display($name) . ': ' . $this->display($value);
		}

		return '{' . implode(', ', $rows) . '}';
	}
}
