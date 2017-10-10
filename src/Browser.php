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

class Browser
{
	/** @var Filesystem */
	private $filesystem;

	/** @var array */
	private $files;

	/** @var Base */
	private $base;

	public function __construct(Filesystem $filesystem)
	{
		$this->filesystem = $filesystem;
		$this->files = array();
	}

	public function browse($testsDirectory, array $paths)
	{
		$this->base = new Base($testsDirectory);

		foreach ($paths as $path) {
			if (!$this->base->isChildPath($path)) {
				// TODO: explain that this path is invalid because it lies outside the tests directory:
				throw Exception::invalidTestsPath($path);
			}

			$contents = $this->filesystem->read($path);

			if ($contents === null) {
				throw Exception::invalidTestsPath($path);
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
			$childPath = "{$path}/{$childName}";

			$this->get($childPath, $childContents);
		}
	}

	private function getFile($absolutePath, $contents)
	{
		if (!$this->isTestsFile($absolutePath)) {
			return;
		}

		$relativePath = $this->base->getRelativePath($absolutePath);

		$this->files[$relativePath] = $contents;
	}

	private function isTestsFile($path)
	{
		return substr($path, -4) === '.php';
	}
}
