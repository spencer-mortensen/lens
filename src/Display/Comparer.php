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

namespace TestPhp\Display;

use TestPhp\Archivist;

class Comparer
{
	/** @var array */
	private $map;

	public function __construct()
	{
		$this->map = array();
	}

	/**
	 * Compare any two archived values.
	 *
	 * @param mixed $aToken
	 *
	 * @param mixed $bToken
	 *
	 * @return bool
	 * Returns true iff the archived values are the same (after translating
	 * object and resource IDs).
	 */
	public function isSame($aToken, $bToken)
	{
		if (!is_array($aToken) || !is_array($bToken)) {
			return $aToken === $bToken;
		}

		list($aType, $aValue) = each($aToken);
		list($bType, $bValue) = each($bToken);

		if ($aType !== $bType) {
			return false;
		}

		switch ($aType) {
			case Archivist::TYPE_ARRAY:
				return $this->isSameArray($aValue, $bValue);

			case Archivist::TYPE_OBJECT:
				return $this->isSameObject($aValue, $bValue);

			case Archivist::TYPE_RESOURCE:
				return $this->isSameResource($aValue, $bValue);
		}

		return false;
	}

	public function isSameArray(array $a, array $b)
	{
		$keys = array_keys($a);

		if (array_keys($b) !== $keys) {
			return false;
		}

		foreach ($keys as $key) {
			if (!$this->isSame($a[$key], $b[$key])) {
				return false;
			}
		}

		return true;
	}

	private function isSameObject($aObject, $bObject)
	{
		list($aId, $aClass, $aProperties) = $aObject;
		list($bId, $bClass, $bProperties) = $bObject;

		$aMappedId = &$this->map[$aId];

		if (isset($aMappedId)) {
			return $aMappedId === $bId;
		}

		if ($aClass !== $bClass) {
			return false;
		}

		if (!self::isSameArray($aProperties, $bProperties)) {
			return false;
		}

		$aMappedId = $bId;
		return true;
	}

	private function isSameResource($a, $b)
	{
		// TODO:
		return false;
	}
}
