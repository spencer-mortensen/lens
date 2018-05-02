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

class Resolver
{
	/** @var callable */
	private $isFunctionCallable;

	public function __construct($isFunctionCallable = 'function_exists')
	{
		$this->isFunctionCallable = $isFunctionCallable;
	}

	public function resolve($namespace, array $uses, $path, $isFunctionPath)
	{
		if (substr($path, 0, 1) === '\\') {
			return substr($path, 1);
		}

		if (!$isFunctionPath || is_integer(strpos($path, '\\'))) {
			return $this->resolveRelativePath($namespace, $uses, $path);
		}

		return $this->resolveBareFunction($namespace, $path);
	}

	private function resolveRelativePath($namespace, array $uses, $path)
	{
		if (0 < count($uses)) {
			$names = explode('\\', $path, 2);
			$baseName = $names[0];

			if (isset($uses[$baseName])) {
				$names[0] = $uses[$baseName];
				return implode('\\', $names);
			}
		}

		if ($namespace === null) {
			return $path;
		}

		return "{$namespace}\\{$path}";
	}

	private function resolveBareFunction($namespace, $function)
	{
		if ($namespace === null) {
			return $function;
		}

		$namespacedFunction = "{$namespace}\\{$function}";

		if ($this->isFunction($namespacedFunction)) {
			return $namespacedFunction;
		}

		if ($this->isFunction($function)) {
			return $function;
		}

		return $namespacedFunction;
	}

	private function isFunction($function)
	{
		return call_user_func($this->isFunctionCallable, $function);
	}
}
