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

namespace Lens_0_0_56\Lens\Evaluator;

use Lens_0_0_56\Lens\Filesystem;
use Lens_0_0_56\SpencerMortensen\Paths\Paths;

class Autoloader
{
	/** @var Paths */
	private $paths;

	/** @var Filesystem */
	private $filesystem;

	public function __construct()
	{
		// TODO: dependency injection
		$this->paths = Paths::getPlatformPaths();
		$this->filesystem = new Filesystem();
	}

	public function enable()
	{
		spl_autoload_register(array($this, 'autoload'));
	}

	public function autoload($class)
	{
		$this->getCoreMockPath($class, $filePath) ||
		$this->getUserMockPath($class, $filePath);

		if ($this->filesystem->isFile($filePath)) {
			include $filePath;
		}
	}

	private function getCoreMockPath($class, &$absoluteFilePath)
	{
		if (strncmp($class, 'Lens\\', 5) !== 0) {
			return false;
		}

		// TODO: this is duplicated elsewhere
		$lensCoreDirectory = dirname(dirname(dirname(__DIR__)));

		$class = substr($class, 5);
		$relativeFilePath = $this->getRelativeFilePath($class);

		$absoluteFilePath = $this->paths->join($lensCoreDirectory, 'files', 'mocks', 'classes', $relativeFilePath);
		return true;
	}

	private function getUserMockPath($class, &$absoluteFilePath)
	{
		$relativeFilePath = $this->getRelativeFilePath($class);

		// TODO: autoload mock classes when necessary:
		$absoluteFilePath = $this->paths->join(LENS_CACHE_DIRECTORY, 'classes', 'live', $relativeFilePath);
		return true;
	}

	// TODO: this is duplicated elsewhere
	private function getRelativeFilePath($class)
	{
		$parts = explode('\\', $class);
		return $this->paths->join($parts) . '.php';
	}
}
