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

namespace _Lens\Lens\Phases\Code\Sanitizers;

use _Lens\Lens\Php\Semantics;

class PathSanitizer
{
	public function sanitize(array $context, array $paths)
	{
		$context = $this->getUsedContext($context, $paths);
		$this->rewriteUnsafePaths($context, $paths);

		return [$context, $paths];
	}

	private function getUsedContext(array $context, array $paths)
	{
		$namespace = $context['namespace'];
		$usedClasses = [];
		$usedFunctions = [];

		foreach ($paths['classes'] as $path => $null) {
			$slash = strpos($path, '\\');

			if ($slash === false) {
				$usedClasses[$path] = $this->getClassName($context, $path);
			} elseif (0 < $slash) {
				$alias = substr($path, 0, $slash);
				$usedClasses[$alias] = $this->getClassName($context, $alias);
			}
		}

		foreach ($paths['functions'] as $path => $null) {
			$slash = strpos($path, '\\');

			if ($slash === false) {
				$usedFunctions[$path] = $this->getFunctionName($context, $path);
			} elseif (0 < $slash) {
				$alias = substr($path, 0, $slash);
				$usedClasses[$alias] = $this->getClassName($context, $alias);
			}
		}

		return [
			'namespace' => $namespace,
			'classes' => $usedClasses,
			'functions' => $usedFunctions
		];
	}

	private function getClassName(array $context, $alias)
	{
		$this->getAliasName($context['classes'], $alias, $name) ||
		$this->getClassFullName($context['namespace'], $alias, $name);

		return $name;
	}

	private function getAliasName(array $aliases, $alias, &$name)
	{
		if (isset($aliases[$alias])) {
			$name = $aliases[$alias];
			return true;
		}

		return false;
	}

	private function getClassFullName($namespace, $alias, &$name)
	{
		if ($namespace === null) {
			$name = $alias;
		} else {
			$name = "{$namespace}\\{$alias}";
		}

		return true;
	}

	private function getFunctionName(array $context, $alias)
	{
		$this->getAliasName($context['functions'], $alias, $name) ||
		$this->getFunctionFullName($context['namespace'], $alias, $name);

		return $name;
	}

	private function getFunctionFullName($namespace, $alias, &$name)
	{
		if ($namespace === null) {
			$name = $alias;
		} else {
			$name = ["{$namespace}\\{$alias}", $alias];
		}

		return true;
	}

	private function rewriteUnsafePaths(array &$context, array &$paths)
	{
		$this->rewrite(Semantics::getUnsafeClasses(), $context['classes'], $paths['classes']);
		$this->rewrite(Semantics::getUnsafeFunctions(), $context['functions'], $paths['functions']);
	}

	private function rewrite(array $unsafeNames, array &$aliases, array &$paths)
	{
		$map = [];

		foreach ($paths as $path => $null) {
			if (substr($path, 0, 1) !== '\\') {
				continue;
			}

			$name = substr($path, 1);

			if (isset($unsafeNames[$name])) {
				$map[$path] = $this->getAlias($aliases, $name);
			}
		}

		$paths = $map;
	}

	private function getAlias(array &$aliases, $name)
	{
		$this->getExistingAlias($aliases, $name, $alias) ||
		$this->getNewAlias($aliases, $name, $alias);

		return $alias;
	}

	private function getExistingAlias(array $aliases, $name, &$alias)
	{
		$alias = array_search($name, $aliases, true);

		return $alias !== false;
	}

	private function getNewAlias(array &$aliases, $name, &$alias)
	{
		$alias = $name;

		while (isset($aliases[$alias])) {
			$alias .= '_';
		}

		$aliases[$alias] = $name;
		return true;
	}
}
