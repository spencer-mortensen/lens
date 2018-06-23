<?php

/**
 * Copyright (C) 2017 Spencer Mortensen
 *
 * This file is part of Autoloader.
 *
 * Autoloader is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Autoloader is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with Autoloader. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Spencer Mortensen <spencer@lens.guide>
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL-3.0
 * @copyright 2018 Spencer Mortensen
 */

namespace _Lens\SpencerMortensen\Autoloader;

class Autoloader
{
	private $projectDirectory;

	public function __construct($projectDirectory, array $namespaces)
	{
		$this->projectDirectory = $projectDirectory;

		foreach ($namespaces as $namespace => $relativePath) {
			$this->map($namespace, $relativePath);
		}
	}

	private function map($namespace, $path)
	{
		$namespacePrefix = "{$namespace}\\";
		$namespacePrefixLength = strlen($namespacePrefix);
		$absolutePath = $this->getAbsolutePath($path);

		$autoloader = function ($class) use ($namespacePrefix, $namespacePrefixLength, $absolutePath) {
			if (strncmp($class, $namespacePrefix, $namespacePrefixLength) !== 0) {
				return;
			}

			$relativeClassName = substr($class, $namespacePrefixLength);
			$relativeFilePath = strtr($relativeClassName, '\\', DIRECTORY_SEPARATOR) . '.php';
			$absoluteFilePath = $absolutePath . DIRECTORY_SEPARATOR . $relativeFilePath;

			if (is_file($absoluteFilePath)) {
				include $absoluteFilePath;
			}
		};

		spl_autoload_register($autoloader);
	}

	private function getAbsolutePath($path)
	{
		if ($this->isAbsolutePath($path)) {
			return $path;
		}

		return $this->projectDirectory . DIRECTORY_SEPARATOR . $path;
	}

	private function isAbsolutePath($path)
	{
		return substr($path, 0, 1) === '/';
	}
}
