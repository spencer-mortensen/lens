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

	public function display($token)
	{
		if (!is_array($token)) {
			$type = gettype($token);
			return $this->showPrimitive($type, $token);
		}

		list($type, $value) = each($token);
		return $this->showComplex($type, $value);
	}

	private function showPrimitive($type, $value)
	{
		switch ($type) {
			default: return $this->showNull();
			case 'boolean': return $this->showBoolean($value);
			case 'integer': return $this->showInteger($value);
			case 'double': return $this->showFloat($value);
			case 'string': return $this->showString($value);
		}
	}

	private function showComplex($type, $value)
	{
		switch ($type) {
			default: return $this->showArray($value);
			case Archivist::TYPE_OBJECT: return $this->showObject($value);
			case Archivist::TYPE_RESOURCE: return $this->showResource($value);
		}
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

	private function showObject(array $object)
	{
		list($id, $class, $properties) = $object;

		$number = self::getNumber($this->objects, $id);
		$propertiesList = $this->getPropertiesList($properties);

		return "object#{$number}('{$class}', {$propertiesList})";
	}

	private function getPropertiesList(array $properties)
	{
		$rows = array();

		foreach ($properties as $name => $value) {
			$rows[] = $this->display($name) . ': ' . $this->display($value);
		}

		return '{' . implode(', ', $rows) . '}';
	}

	private function showResource(array $resource)
	{
		list($id, $type) = $resource;

		$number = self::getNumber($this->resources, $id);

		return "resource#{$number}({$type})";
	}

	private static function getNumber(&$numbers, $id)
	{
		$number = &$numbers[$id];

		if (!isset($number)) {
			$number = count($numbers);
		}

		return $number;
	}
}
