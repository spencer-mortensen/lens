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

namespace _Lens\Lens\Phases\Code\Generators;

class MockClassGenerator extends MockGenerator
{
	public function generate(array $context, array $class, array $map, array $inflatedTokens)
	{
		$context = $this->getContext($context['namespace']);
		$definitionPhp = $this->getClassPhp($class, $map, $inflatedTokens);

		return [$context, $definitionPhp];
	}

	private function getClassPhp(array $class, array $map, array $inflatedTokens)
	{
		$signaturePhp = $this->getRangePhp($class['signatureRange'], $map, $inflatedTokens);
		$methodsPhp = $this->getMethodsPhp($class['methods'], $map, $inflatedTokens);
		return "{$signaturePhp}\n{\n{$methodsPhp}\n}";
	}

	private function getMethodsPhp(array $methods, array $map, array $inflatedTokens)
	{
		$methodsPhp = [];

		foreach ($methods as $method) {
			$name = $method['name'];
			$signaturePhp = $this->getRangePhp($method['signatureRange'], $map, $inflatedTokens);
			$methodsPhp[$name] = $this->getMethodPhp(!$method['isDefinition'], $method['isStatic'], $method['isPrivate'], $signaturePhp);
		}

		$methodsPhp = $this->addMagicMethods($methodsPhp);

		return implode("\n\n", $methodsPhp);
	}

	private function addMagicMethods(array $methodsPhp)
	{
		$magicMethods = [
			'__construct' => ['', ['$this', '__FUNCTION__', 'func_get_args()']],
			'__call' => ['$function, array $arguments', ['$this', '$function', '$arguments']],
			'__callStatic' => ['$function, array $arguments', ['__CLASS__', '$function', '$arguments']],
			'__get' => ['$name', ['$this', '__FUNCTION__', 'func_get_args()']],
			'__set' => ['$name, $value', ['$this', '__FUNCTION__', 'func_get_args()']],
			'__isset' => ['$name', ['$this', '__FUNCTION__', 'func_get_args()']],
			'__unset' => ['$name', ['$this', '__FUNCTION__', 'func_get_args()']],
			'__toString' => ['', ['$this', '__FUNCTION__', 'func_get_args()']],
			'__invoke' => ['', ['$this', '__FUNCTION__', 'func_get_args()']],
			'__setState' => ['array $properties', ['__CLASS__', '__FUNCTION__', 'func_get_args()']],
			'__destruct' => ['', ['$this', '__FUNCTION__', 'func_get_args()']]
		];

		foreach ($magicMethods as $name => $value) {
			if (isset($methodsPhp[$name])) {
				continue;
			}

			list($argumentsPhp, $call) = $value;

			if ($call[0] === '__CLASS__') {
				$typePhp = "public static function";
			} else {
				$typePhp = "public function";
			}

			$signaturePhp = "{$typePhp} {$name}({$argumentsPhp})";

			$bodyPhp = $this->getMethodBodyPhp($call[0], $call[1], $call[2]);
			$methodPhp = $this->getConcreteMethodPhp($signaturePhp, $bodyPhp);

			$methodsPhp[$name] = $methodPhp;
		}

		return $methodsPhp;
	}
}
