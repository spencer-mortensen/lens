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

namespace _Lens\Lens\Phases\Analysis\Code\Sanitizers;

class PathSanitizer
{
	/** @var array */
	private $unsafeNames;

	/** @var string|null */
	private $namespace;

	/** @var array */
	private $aliases;

	public function __construct($unsafeNames)
	{
		$this->unsafeNames = $unsafeNames;
	}

	public function setContext($namespace, array $aliases)
	{
		$this->namespace = $namespace;
		$this->aliases = $aliases;
	}

	private function isUnsafeName($name)
	{
		return isset($this->unsafeNames[$name]);
	}

	public function sanitize($path)
	{
		if (substr($path, 0, 1) === '\\') {
			$name = substr($path, 1);

			if ($this->isUnsafeName($name)) {
				$path = $this->getAlias($name, $name);
			}
		} elseif (($this->namespace === null) && !isset($this->aliases[$path]) && $this->isUnsafeName($path)) {
			$this->aliases[$path] = $path;
		}

		return $path;
	}

	private function getAlias($alias, $name)
	{
		$this->getExistingAlias($alias, $name) ||
		$this->getNewAlias($alias, $name);

		return $alias;
	}

	private function getExistingAlias(&$alias, $name)
	{
		$key = array_search($name, $this->aliases, true);

		if (!is_string($key)) {
			return false;
		}

		$alias = $key;
		return true;
	}

	private function getNewAlias(&$alias, $name)
	{
		while (isset($this->aliases[$alias])) {
			$alias .= '_';
		}

		$this->aliases[$alias] = $name;
		return true;
	}

	// TODO: eliminate unused aliases
	public function getAliases()
	{
		return $this->aliases;
	}
}
