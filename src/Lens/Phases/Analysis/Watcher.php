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

namespace _Lens\Lens\Phases\Analysis;

use _Lens\SpencerMortensen\Filesystem\Directory;
use _Lens\SpencerMortensen\Filesystem\File;

class Watcher
{
	const TYPE_ADDED = 1;
	const TYPE_MODIFIED = 2;
	const TYPE_REMOVED = 3;

	public function watch(Directory $watchedDirectory, File $cacheFile)
	{
		$timesCached = $this->getCachedModifiedTimes($cacheFile);
		$timesLive = $this->getLiveModifiedTimes($watchedDirectory);

		$this->setCachedModifiedTimes($cacheFile, $timesLive);

		return $this->compareArrays($timesCached, $timesLive);
	}

	private function getCachedModifiedTimes(File $file)
	{
		$times = $file->read();

		if (!is_array($times)) {
			$times = [];
		}

		return $times;
	}

	private function getLiveModifiedTimes(Directory $directory)
	{
		$times = [];

		/** @var Directory $child */
		// TODO: add an interface for the "Directory" and "File" classes
		foreach ($directory->read() as $child) {
			$path = $child->getPath();
			$components = $path->getComponents();
			$key = end($components);

			if ($child instanceof Directory) {
				$value = $this->getLiveModifiedTimes($child);
			} else {
				$value = $child->getModifiedTime();
			}

			$times[$key] = $value;
		}

		return $times;
	}

	private function setCachedModifiedTimes(File $file, array $times)
	{
		$file->write($times);
	}

	private function compare($old, $new)
	{
		if (!is_array($old) && !is_array($new)) {
			return $this->comparePrimitives($old, $new);
		}

		if (!is_array($old)) {
			$differences = $this->mark($new, self::TYPE_ADDED);
			$differences[''] = self::TYPE_REMOVED;

			return $differences;
		}

		if (!is_array($new)) {
			$differences = $this->mark($old, self::TYPE_REMOVED);
			$differences[''] = self::TYPE_ADDED;

			return $differences;
		}

		return $this->compareArrays($old, $new);
	}

	private function comparePrimitives($old, $new)
	{
		if ($old === $new) {
			return [];
		}

		return ['' => self::TYPE_MODIFIED];
	}

	private function mark($value, $type)
	{
		if (is_array($value)) {
			return $this->markArray($value, $type);
		}

		return $this->markPrimitive($type);
	}

	private function markArray(array $array, $type)
	{
		$differences = [];

		foreach ($array as $key => $value) {
			$differences[$key] = $this->mark($value, $type);
		}

		return $differences;
	}

	private function markPrimitive($type)
	{
		return ['' => $type];
	}

	private function compareArrays(array $old, array $new)
	{
		$differences = [];

		$keys = array_keys(array_merge($old, $new));

		foreach ($keys as $key) {
			if (!isset($new[$key])) {
				$value = $this->mark($old[$key], self::TYPE_REMOVED);
			} elseif (!isset($old[$key])) {
				$value = $this->mark($new[$key], self::TYPE_ADDED);
			} else {
				$value = $this->compare($old[$key], $new[$key]);
			}

			if (0 < count($value)) {
				$differences[$key] = $value;
			}
		}

		ksort($differences, SORT_STRING);
		return $differences;
	}
}
