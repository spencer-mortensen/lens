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

namespace _Lens\Lens\Phases\Analysis\Code\Generators;

class FileGenerator
{
	/** @var InterfaceGenerator */
	private $interfaceGenerator;

	/** @var LiveGenerator */
	private $liveGenerator;

	/** @var MockClassGenerator */
	private $mockClassGenerator;

	/** @var MockFunctionGenerator */
	private $mockFunctionGenerator;

	/** @var MockTraitGenerator */
	private $mockTraitGenerator;

	public function __construct()
	{
		$this->interfaceGenerator = new InterfaceGenerator();
		$this->liveGenerator = new LiveGenerator();
		$this->mockClassGenerator = new MockClassGenerator();
		$this->mockFunctionGenerator = new MockFunctionGenerator();
		$this->mockTraitGenerator = new MockTraitGenerator();
	}

	public function generate(array $sections, array $deflatedTokens, array $inflatedTokens, array $map)
	{
		$liveClasses = [];
		$liveFunctions = [];
		$mockClasses = [];
		$mockFunctions = [];

		foreach ($sections as $section) {
			$context = $section['context'];
			$namespace = $context['namespace'];

			foreach ($section['definitions']['classes'] as $class) {
				$name = $this->getFullName($namespace, $class['name']);

				$livePhp = $this->liveGenerator->generate($context, $class, $deflatedTokens, $inflatedTokens, $map);
				$mockPhp = $this->mockClassGenerator->generate($context, $class, $map, $inflatedTokens);

				$liveClasses[$name] = $livePhp;
				$mockClasses[$name] = $mockPhp;
			}

			foreach ($section['definitions']['functions'] as $function) {
				$name = $this->getFullName($namespace, $function['name']);

				$livePhp = $this->liveGenerator->generate($context, $function, $deflatedTokens, $inflatedTokens, $map);
				$mockPhp = $this->mockFunctionGenerator->generate($context, $function, $map, $inflatedTokens);

				$liveFunctions[$name] = $livePhp;
				$mockFunctions[$name] = $mockPhp;
			}

			foreach ($section['definitions']['interfaces'] as $interface) {
				$name = $this->getFullName($namespace, $interface['name']);

				$livePhp = $this->interfaceGenerator->generate($context, $interface, $deflatedTokens, $inflatedTokens, $map);

				$liveClasses[$name] = $livePhp;
				$mockClasses[$name] = $livePhp;
			}

			foreach ($section['definitions']['traits'] as $trait) {
				$name = $this->getFullName($namespace, $trait['name']);

				$livePhp = $this->liveGenerator->generate($context, $trait, $deflatedTokens, $inflatedTokens, $map);
				$mockPhp = $this->mockTraitGenerator->generate($context, $trait, $map, $inflatedTokens);

				$liveClasses[$name] = $livePhp;
				$mockClasses[$name] = $mockPhp;
			}
		}

		return [
			'live' => [
				'classes' => $liveClasses,
				'functions' => $liveFunctions
			],
			'mock' => [
				'classes' => $mockClasses,
				'functions' => $mockFunctions
			]
		];
	}

	private function getFullName($namespace, $name)
	{
		if ($namespace === null) {
			return $name;
		}

		return "{$namespace}\\{$name}";
	}
}
