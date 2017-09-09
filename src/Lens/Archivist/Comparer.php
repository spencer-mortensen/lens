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

namespace Lens\Archivist;

use Lens\Archivist\Archives\Archive;
use Lens\Archivist\Archives\ObjectArchive;
use Lens\Archivist\Archives\ResourceArchive;

class Comparer
{
	public function isSame($a, $b)
	{
		if (gettype($a) !== gettype($b)) {
			return false;
		}

		if (is_object($a)) {
			/** @var Archive $a */
			if ($a->isResourceArchive()) {
				/** @var ResourceArchive $a */
				return $this->isSameResource($a, $b);
			}

			/** @var ObjectArchive $a */
			return $this->isSameObject($a, $b);
		}

		if (is_array($a)) {
			return $this->isSameArray($a, $b);
		}

		return $a === $b;
	}

	// TODO: handle recursive arrays
	private function isSameArray(array $a, array $b)
	{
		$keys = array_keys($a);

		if ($keys !== array_keys($b)) {
			return false;
		}

		foreach ($keys as $key) {
			if (!$this->isSame($a[$key], $b[$key])) {
				return false;
			}
		}

		return true;
	}

	// TODO: handle recursive objects
	private function isSameObject(ObjectArchive $a, ObjectArchive $b)
	{
		// TODO: check object IDs (but handle the case where both the Expected and Actual code create matching objects)
		return ($a->getClass() === $b->getClass()) &&
			$this->isSame($a->getProperties(), $b->getProperties());
	}

	private function isSameResource(ResourceArchive $a, ResourceArchive $b)
	{
		// TODO: check resource IDs (but handle the case where both the Expected and Actual code create matching objects)
		return ($a->getType() === $b->getType());
	}
}
