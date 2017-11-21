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

namespace Lens;

use SpencerMortensen\Paths\Paths;

class Browser
{
	/** @var Filesystem */
	private $filesystem;

	/** @var Paths */
	private $paths;

	/** @var string */
	private $testsDirectory;

	/** @var array */
	private $files;

	public function __construct(Filesystem $filesystem, Paths $paths)
	{
		$this->filesystem = $filesystem;
		$this->paths = $paths;
		$this->files = array();
	}

	public function browse($testsDirectory, array $paths)
	{
		$this->testsDirectory = $testsDirectory;

		foreach ($paths as $path) {
			if (($this->testsDirectory !== null) && !$this->paths->isChildPath($this->testsDirectory, $path)) {
				// TODO: explain that this path is invalid because it lies outside the tests directory:
				throw LensException::invalidTestsPath($path);
			}

			$contents = $this->filesystem->read($path);

			if ($contents === null) {
				throw LensException::invalidTestsPath($path);
			}

			$this->get($path, $contents);
		}

		return $this->files;
	}

	private function get($path, $contents)
	{
		if (is_array($contents)) {
			$this->getDirectory($path, $contents);
		} else {
			$this->getFile($path, $contents);
		}
	}

	private function getDirectory($path, array $contents)
	{
		foreach ($contents as $childName => $childContents) {
			$childPath = $this->paths->join($path, $childName);

			$this->get($childPath, $childContents);
		}
	}

	private function getFile($absolutePath, $contents)
	{
		if (!$this->isTestsFile($absolutePath)) {
			return;
		}

		if ($this->testsDirectory === null) {
			$relativePath = basename($absolutePath);
		} else {
			$relativePath = $this->paths->getRelativePath($this->testsDirectory, $absolutePath);
		}

		$this->files[$relativePath] = $contents;
	}

	private function isTestsFile($path)
	{
		return substr($path, -4) === '.php';
	}
}

