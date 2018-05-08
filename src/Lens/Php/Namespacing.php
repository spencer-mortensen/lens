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

namespace Lens_0_0_56\Lens\Php;

class Namespacing
{
	/** @var callable|null */
	private $isFunctionCallable;

	/** @var string */
	private $namespace;

	/** @var array */
	private $uses;

	public function __construct($isFunctionCallable, $namespace, array $uses)
	{
		$this->isFunctionCallable = $isFunctionCallable;
		$this->namespace = $namespace;
		$this->uses = $uses;
	}

	/**
	 * @param string $absoluteFunction
	 * The fully-qualified function name (without a leading backslash).
	 *
	 * For example:
	 *   "count" is the global built-in "count" function
	 *   "Example\\count" is a user-defined function
	 *
	 * @return string
	 * Relative function name (relative to the namespace or any applicable use statements).
	 */
	public function getRelativeFunction($absoluteFunction)
	{
		$slash = strrpos($absoluteFunction, '\\');

		if (is_int($slash)) {
			$namespace = substr($absoluteFunction, 0, $slash);
			$namespace = $this->getRelativeClass($namespace);
			$function = substr($absoluteFunction, $slash + 1);

			if (is_string($namespace)) {
				return "{$namespace}\\{$function}";
			}

			return $function;
		}

		if (is_string($this->namespace) && $this->isFunction("{$this->namespace}\\{$absoluteFunction}")) {
			return "\\{$absoluteFunction}";
		}

		return $absoluteFunction;
	}

	public function getRelativeClass($class)
	{
		foreach ($this->uses as $aliasName => $aliasPath) {
			if ($this->isChildNamespace($class, $aliasPath)) {
				return substr_replace($class, $aliasName, 0, strlen($aliasPath));
			}
		}

		if ($this->namespace === null) {
			return $class;
		}

		if (!$this->isChildNamespace($class, $this->namespace)) {
			return "\\{$class}";
		}

		$fileNamespaceLength = strlen($this->namespace);

		if (strlen($class) === $fileNamespaceLength) {
			return null;
		}

		return substr($class, $fileNamespaceLength + 1);
	}

	private function isChildNamespace($a, $b)
	{
		return strncmp("{$a}\\", "{$b}\\", strlen($b) + 1) === 0;
	}

	public function getAbsoluteFunction($function)
	{
		if (is_integer(strpos($function, '\\'))) {
			return $this->getAbsoluteClass($function);
		}

		if ($this->namespace === null) {
			return $function;
		}

		$namespacedFunction = "{$this->namespace}\\{$function}";

		if ($this->isFunction($namespacedFunction)) {
			return $namespacedFunction;
		}

		if ($this->isFunction($function)) {
			return $function;
		}

		return $namespacedFunction;
	}

	public function getAbsoluteClass($class)
	{
		if (substr($class, 0, 1) === '\\') {
			return substr($class, 1);
		}

		if (0 < count($this->uses)) {
			$slash = strpos($class, '\\');
			$head = substr($class, 0, $slash);

			if (isset($this->uses[$head])) {
				$head = $this->uses[$head];
				$tail = substr($class, $slash + 1);
				return "{$head}\\{$tail}";
			}
		}

		if ($this->namespace === null) {
			return $class;
		}

		return "{$this->namespace}\\{$class}";
	}

	private function isFunction($function)
	{
		return is_callable($this->isFunctionCallable) &&
			call_user_func($this->isFunctionCallable, $function);
	}
}
