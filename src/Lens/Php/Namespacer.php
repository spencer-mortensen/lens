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

namespace _Lens\Lens\Php;

class Namespacer
{
	/** @var string|null */
	private $namespace;

	/** @var array */
	private $uses;

	public function setNamespace($namespace)
	{
		$this->namespace = $namespace;
	}

	public function setUses(array $uses)
	{
		$this->uses = $uses;
	}

	/**
	 * @param string $path
	 * Namespaced class path (with or without a leading backslash)
	 *
	 * @return string
	 * Fully-qualified name (without a leading backslash)
	 */
	public function getClassName($path)
	{
		$this->getNameFromAbsolutePath($path, $name)
			|| $this->getNameFromAliases($path, $this->uses['classes'], $name)
			|| $this->getNameFromNamespace($path, $name);

		return $name;
	}

	/**
	 * @param string $path
	 * Namespaced function path (with or without a leading backslash)
	 *
	 * @return string
	 * Fully-qualified name (without a leading backslash)
	 */
	public function getFunctionName($path)
	{
		$this->getNameFromAbsolutePath($path, $name)
			|| $this->getNameFromAliases($path, $this->uses['functions'], $name)
			|| $this->getNameFromNamespace($path, $name);

		return $name;
	}

	private function getNameFromAbsolutePath($path, &$name)
	{
		if (substr($path, 0, 1) === '\\') {
			$name = substr($path, 1);
			return true;
		}

		return false;
	}

	private function getNameFromAliases($path, array $aliases, &$name)
	{
		$slash = strpos($path, '\\');

		if ($slash === false) {
			if (isset($aliases[$path])) {
				$name = $aliases[$path];
				return true;
			}
		} else {
			$head = substr($path, 0, $slash);
			$tail = substr($path, $slash + 1);

			if (isset($this->uses['classes'][$head])) {
				$head = $this->uses['classes'][$head];
				$name = "{$head}\\{$tail}";
				return true;
			}
		}

		return false;
	}

	private function getNameFromNamespace($path, &$name)
	{
		if ($this->namespace === null) {
			$name = $path;
		} else {
			$name = "{$this->namespace}\\{$path}";
		}

		return true;
	}

	/**
	 * @param string $name
	 * Fully-qualified name (without a leading backslash)
	 *
	 * @return string
	 * Namespaced class path (with or without a leading backslash)
	 */
	public function getClassPath($name)
	{
		$this->getPathFromAlias($name, $this->uses['classes'], $path)
			|| $this->getPathFromPrefixes($name, $path)
			|| $this->getPathFromNamespace($name, $path);

		return $path;
	}

	/**
	 * @param string $name
	 * Fully-qualified name (without a leading backslash)
	 *
	 * @return string
	 * Namespaced class path (with or without a leading backslash)
	 */
	public function getFunctionPath($name)
	{
		$this->getPathFromAlias($name, $this->uses['functions'], $path)
			|| $this->getPathFromPrefixes($name, $path)
			|| $this->getPathFromNamespace($name, $path);

		return $path;
	}

	private function getPathFromAlias($name, array $aliases, &$path)
	{
		$path = array_search($name, $aliases, true);
		return is_string($path);
	}

	private function getPathFromPrefixes($name, &$path)
	{
		$longestPrefixAlias = null;
		$longestPrefixLength = 0;

		foreach ($this->uses['classes'] as $alias => $prefix) {
			$prefixLength = strlen($prefix);

			if ($prefixLength <= $longestPrefixLength) {
				continue;
			}

			if (strncmp($name, "{$prefix}\\", $prefixLength + 1) !== 0) {
				continue;
			}

			$longestPrefixAlias = $alias;
			$longestPrefixLength = $prefixLength;
		}

		if ($longestPrefixLength === 0) {
			return false;
		}

		$tail = substr($name, $longestPrefixLength + 1);
		$path = "{$longestPrefixAlias}\\{$tail}";
		return true;
	}

	private function getPathFromNamespace($name, &$path)
	{
		if ($this->namespace === null) {
			$path = $name;
		} elseif (strncmp($name, "{$this->namespace}\\", strlen($this->namespace) + 1) === 0) {
			$path = substr($name, strlen($this->namespace) + 1);
		} else {
			$path = "\\{$name}";
		}

		return true;
	}
}
