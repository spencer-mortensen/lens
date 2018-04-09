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

use Lens_0_0_56\Lens\Files\File;
use Lens_0_0_56\SpencerMortensen\Paths\Paths;

class Watcher
{
	/** @var Paths  */
	private $paths;

	/** @var Filesystem */
	private $filesystem;

	/** @var File */
	private $cacheFile;

	/** @var string */
	private $projectDirectory;

	/** @var array */
	private $cache;

	public function __construct(File $cacheFile, $projectDirectory)
	{
		$cacheFile->read($cache);

		if (!is_array($cache)) {
			$cache = array();
		}

		$this->paths = Paths::getPlatformPaths();
		$this->filesystem = new Filesystem();
		$this->cacheFile = $cacheFile;
		$this->projectDirectory = $projectDirectory;
		$this->cache = $cache;
	}

	public function __destruct()
	{
		$this->cacheFile->write($this->cache);
	}

	public function isModifiedFile($filePath)
	{
		$timeCached = $this->getCachedFileModifiedTime($filePath);
		$timeLive = $this->getLiveFileModifiedTime($filePath);

		$this->setCachedFileModifiedTime($filePath, $timeLive);
		return $timeCached !== $timeLive;
	}

	private function getCachedFileModifiedTime($filePath)
	{
		$path = $this->paths->getRelativePath($this->projectDirectory, $filePath);
		$data = $this->paths->deserialize($path);
		$atoms = $data->getAtoms();

		$cache = &$this->cache;

		foreach ($atoms as $atom) {
			$cache = &$cache[$atom];
		}

		return $cache;
	}

	private function setCachedFileModifiedTime($filePath, $time)
	{
		$path = $this->paths->getRelativePath($this->projectDirectory, $filePath);
		$data = $this->paths->deserialize($path);
		$atoms = $data->getAtoms();

		$cache = &$this->cache;

		foreach ($atoms as $atom) {
			$cache = &$cache[$atom];
		}

		$cache = $time;
	}

	private function getLiveFileModifiedTime($filePath)
	{
		// TODO: this can generate a warning, and return a false value, if the file doesn't exist
		return filemtime($filePath);
	}

	public function getDirectoryStatus($directoryPath)
	{
		$timesCached = $this->getCachedDirectoryModifiedTimes($directoryPath);
		$timesLive = $this->getLiveDirectoryModifiedTimes($directoryPath);

		$this->setCachedDirectoryModifiedTimes($directoryPath, $timesLive);
		return $this->getChangedFileList($directoryPath, $timesCached, $timesLive);
	}

	private function getChangedFileList($path, array $timesCached, array $timesLive)
	{
		$differences = array();

		$this->compare($path, $timesCached, $timesLive, $differences);

		return $differences;
	}

	private function compare($path, &$a, &$b, array &$differences)
	{
		if (is_array($a) && is_array($b)) {
			$keys = array_keys(array_merge($a, $b));

			foreach ($keys as $key) {
				$childPath = $path . DIRECTORY_SEPARATOR . $key;
				$this->compare($childPath, $a[$key], $b[$key], $differences);
			}
		} elseif ($a !== $b) {
			$this->recordDifferences($path, $a, false, $differences);
			$this->recordDifferences($path, $b, true, $differences);
		}
	}

	private function recordDifferences($path, $value, $flag, array &$differences)
	{
		if (is_array($value)) {
			foreach ($value as $key => $childValue) {
				$childPath = $path . DIRECTORY_SEPARATOR . $key;
				$this->recordDifferences($childPath, $childValue, $flag, $differences);
			}
		} elseif ($value !== null) {
			$differences[$path] = $flag;
		}
	}

	private function getCachedDirectoryModifiedTimes($directoryPath)
	{
		$path = $this->paths->getRelativePath($this->projectDirectory, $directoryPath);
		$data = $this->paths->deserialize($path);
		$atoms = $data->getAtoms();

		$cache = &$this->cache;

		foreach ($atoms as $atom) {
			$cache = &$cache[$atom];
		}

		if (!is_array($cache)) {
			$cache = array();
		}

		return $cache;
	}

	private function setCachedDirectoryModifiedTimes($directoryPath, $times)
	{
		$path = $this->paths->getRelativePath($this->projectDirectory, $directoryPath);
		$data = $this->paths->deserialize($path);
		$atoms = $data->getAtoms();

		$cache = &$this->cache;

		foreach ($atoms as $atom) {
			$cache = &$cache[$atom];
		}

		$cache = $times;
	}

	private function getLiveDirectoryModifiedTimes($directoryPath)
	{
		$times = array();

		// TODO: error handling
		$directory = opendir($directoryPath);

		for ($childName = readdir($directory); $childName !== false; $childName = readdir($directory)) {
			if (($childName === '.') || ($childName === '..')) {
				continue;
			}

			$childPath = $directoryPath . DIRECTORY_SEPARATOR . $childName;

			if (is_dir($childPath)) {
				$time = $this->getLiveDirectoryModifiedTimes($childPath);
			} else {
				$time = filemtime($childPath);
			}

			$times[$childName] = $time;
		}

		closedir($directory);

		return $times;
	}
}
