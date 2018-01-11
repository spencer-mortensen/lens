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

namespace Lens\Evaluator;

class Autoloader
{
	/** @var string */
	private $mockPrefix;

	/** @var integer */
	private $mockPrefixLength;

	/** @var MockBuilder */
	private $mockBuilder;

	public function __construct()
	{
		$this->mockPrefix = 'Lens\\Mock\\';
		$this->mockPrefixLength = strlen($this->mockPrefix);
		$this->mockBuilder = new MockBuilder();
	}

	public function register()
	{
		$autoloader = array($this, 'autoloader');

		spl_autoload_register($autoloader);
	}

	public function autoloader($class)
	{
		eval($this->getMockPhp($class));
	}

	private function getMockPhp($class)
	{
		$namespace = $this->getNamespace($class);

		if (strncmp($class, $this->mockPrefix, $this->mockPrefixLength) === 0) {
			$parentClass = substr($class, $this->mockPrefixLength);

			return $this->getMockClassPhp($namespace, $parentClass);
		}

		return $this->getMockFunctionsPhp($namespace);
	}

	private function getNamespace($class)
	{
		$slash = strrpos($class, '\\');

		if (!is_integer($slash)) {
			return null;
		}

		return substr($class, 0, $slash);
	}

	private function getMockClassPhp($childNamespace, $parentClass)
	{
		$namespacePhp = self::getNamespacePhp($childNamespace);
		$childClassPhp = $this->mockBuilder->getMockClassPhp($parentClass);

		return "{$namespacePhp}\n\n{$childClassPhp}";
	}

	private static function getNamespacePhp($namespace)
	{
		return "namespace {$namespace};";
	}

	public function getMockFunctionsPhp($namespace)
	{
		if ($namespace === null) {
			return null;
		}

		$sections = array(
			self::getNamespacePhp($namespace)
		);

		$functions = PhpCore::getExternalFunctions();

		foreach ($functions as $function) {
			$mock = "{$namespace}\\{$function}";

			if (function_exists($function) && !function_exists($mock)) {
				$sections[] = $this->getMockFunctionPhp($function);
			}
		}

		return implode("\n\n", $sections);
	}

	private function getMockFunctionPhp($function)
	{
		$php = $this->mockBuilder->getMockFunctionPhp($function);

		return $php;
	}
}
