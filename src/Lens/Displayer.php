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

namespace Lens_0_0_56\Lens;

use Lens_0_0_56\Lens\Archivist\Archives\Archive;
use Lens_0_0_56\Lens\Archivist\Archives\ObjectArchive;
use Lens_0_0_56\Lens\Archivist\Archives\ResourceArchive;

class Displayer
{
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
		if ($value->isResourceArchive()) {
			/** @var ResourceArchive $value */
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
		$decidingCharacters = "'\n\r\t\v\e\f\\";

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
		$rows = [];

		foreach ($array as $key => $value) {
			$rows[] = $this->display($value);
		}

		return $rows;
	}

	private function getAssociativeArrayRows(array $array)
	{
		$rows = [];

		foreach ($array as $key => $value) {
			$rows[] = $this->display($key) . ' => ' . $this->display($value);
		}

		return $rows;
	}

	private function getArray($rows)
	{
		$body = implode(', ', $rows);

		return "[{$body}]";
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

		$classValue = $this->showString($class);

		if (count($properties) === 0) {
			$innerText = $classValue;
		} else {
			$innerText = $this->getClassPropertiesText($properties);
		}

		return "object({$innerText})";
	}

	private function getClassPropertiesText(array $input)
	{
		$output = [];

		foreach ($input as $class => $properties) {
			$classText = $this->showString($class);
			$propertiesText = $this->showArray($properties);

			$output[] = "{$classText}: {$propertiesText}";
		}

		return implode(", ", $output);
	}
}
