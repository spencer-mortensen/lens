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

namespace _Lens\Lens\Cache;

use _Lens\Lens\JsonFile;
use _Lens\SpencerMortensen\Filesystem\Directory;
use _Lens\SpencerMortensen\Filesystem\File;
use _Lens\SpencerMortensen\Filesystem\Filesystem;
use _Lens\SpencerMortensen\Filesystem\Paths\Path;

class Watcher
{
	/** @var Filesystem */
	private $filesystem;

	/** @var JsonFile */
	private $cacheFile;

	/** @var Path */
	private $projectPath;

	/** @var array */
	private $cacheData;

	public function __construct(Filesystem $filesystem, Path $cachePath, Path $projectPath)
	{
		$this->filesystem = $filesystem;

		$cacheFile = new JsonFile($cachePath);
		$cacheData = $cacheFile->read();

		if (!is_array($cacheData)) {
			$cacheData = [];
		}

		$this->filesystem = $filesystem;
		$this->cacheFile = $cacheFile;
		$this->projectPath = $projectPath;
		$this->cacheData = $cacheData;
	}

	public function __destruct()
	{
		// TODO: write only if changes were made
		$this->cacheFile->write($this->cacheData);
	}

	public function getDirectoryChanges(Path $directoryPath)
	{
		$timesCached = $this->getCachedDirectoryModifiedTimes($directoryPath);
		$timesLive = $this->getLiveDirectoryModifiedTimes($directoryPath);

		$this->setCachedDirectoryModifiedTimes($directoryPath, $timesLive);
		return $this->getChangedFileList($directoryPath, $timesCached, $timesLive);
	}

	private function getCachedDirectoryModifiedTimes(Path $directoryPath)
	{
		$path = $this->projectPath->getRelativePath($directoryPath);
		$atoms = $path->getAtoms();

		$cache = &$this->cacheData;

		foreach ($atoms as $atom) {
			$cache = &$cache[$atom];
		}

		if (!is_array($cache)) {
			$cache = [];
		}

		return $cache;
	}

	private function getLiveDirectoryModifiedTimes(Path $directoryPath)
	{
		$times = [];

		$directory = new Directory($directoryPath);
		$childNames = $directory->read(false);

		foreach ($childNames as $childName) {
			$childPath = $directoryPath->add($childName);

			if ($this->filesystem->isDirectory($childPath)) {
				$value = $this->getLiveDirectoryModifiedTimes($childPath);
			} else {
				$value = $this->getLiveFileModifiedTime($childPath);
			}

			$times[$childName] = $value;
		}

		return $times;
	}

	private function getLiveFileModifiedTime(Path $filePath)
	{
		$file = new File($filePath);

		return $file->getModifiedTime();
	}

	private function setCachedDirectoryModifiedTimes(Path $directoryPath, array $times)
	{
		$path = $this->projectPath->getRelativePath($directoryPath);
		$atoms = $path->getAtoms();

		$cache = &$this->cacheData;

		foreach ($atoms as $atom) {
			$cache = &$cache[$atom];
		}

		$cache = $times;
	}

	private function getChangedFileList(Path $path, array $timesCached, array $timesLive)
	{
		$differences = [];

		$this->compare($path, $timesCached, $timesLive, $differences);

		return $differences;
	}

	private function compare(Path $path, &$a, &$b, array &$differences)
	{
		if (is_array($a) && is_array($b)) {
			$keys = array_keys(array_merge($a, $b));

			foreach ($keys as $key) {
				$this->compare($path->add($key), $a[$key], $b[$key], $differences);
			}
		} elseif ($a !== $b) {
			$this->recordDifferences($path, $a, false, $differences);
			$this->recordDifferences($path, $b, true, $differences);
		}
	}

	private function recordDifferences(Path $path, $value, $flag, array &$differences)
	{
		if (is_array($value)) {
			foreach ($value as $key => $childValue) {
				$this->recordDifferences($path->add($key), $childValue, $flag, $differences);
			}
		} elseif ($value !== null) {
			$differences[(string)$path] = $flag;
		}
	}

	public function isModifiedFilePath(Path $filePath)
	{
		$timeCached = $this->getCachedFileModifiedTime($filePath);
		$timeLive = $this->getLiveFileModifiedTime($filePath);

		$this->setCachedFileModifiedTime($filePath, $timeLive);
		return $timeCached !== $timeLive;
	}

	private function getCachedFileModifiedTime(Path $filePath)
	{
		$path = $this->projectPath->getRelativePath($filePath);
		$atoms = $path->getAtoms();

		$cache = &$this->cacheData;

		foreach ($atoms as $atom) {
			$cache = &$cache[$atom];
		}

		return $cache;
	}

	private function setCachedFileModifiedTime(Path $filePath, $time)
	{
		$path = $this->projectPath->getRelativePath($filePath);
		$atoms = $path->getAtoms();

		$cache = &$this->cacheData;

		foreach ($atoms as $atom) {
			$cache = &$cache[$atom];
		}

		$cache = $time;
	}
}
