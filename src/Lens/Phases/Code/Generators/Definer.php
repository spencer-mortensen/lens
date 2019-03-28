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

use _Lens\Lens\Phases\Code\Deflator;
use _Lens\Lens\Phases\Code\Input;
use _Lens\Lens\Phases\Code\Parsers\FileParser;
use _Lens\Lens\Phases\Code\Sanitizers\PathSanitizer;
use _Lens\Lens\Phases\Code\Sanitizers\WhitespaceStandardizer;
use _Lens\Lens\Php\Lexer;

class Definer
{
	/** @var Lexer */
	private $lexer;

	/** @var Deflator */
	private $deflator;

	/** @var FileParser */
	private $fileParser;

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
		// TODO: bubble these classes up to the surface?
		$this->lexer = new Lexer();
		$this->deflator = new Deflator();
		$this->fileParser = new FileParser();
		$pathSanitizer = new PathSanitizer();
		$whitespaceStandardizer = new WhitespaceStandardizer();
		$this->liveGenerator = new LiveGenerator($pathSanitizer, $whitespaceStandardizer);
		$this->mockClassGenerator = new MockClassGenerator();
		$this->mockFunctionGenerator = new MockFunctionGenerator();
		$this->mockTraitGenerator = new MockTraitGenerator();
	}

	public function getDefinitions($php)
	{
		$output = [
			'classes' => [],
			'functions' => [],
			'interfaces' => [],
			'traits' => []
		];

		if ($php === null) {
			return $output;
		}

		$inflatedTokens = $this->lexer->lex($php);
		$this->deflator->deflate($inflatedTokens, $deflatedTokens, $map);
		$input = new Input($deflatedTokens);

		if (!$this->fileParser->parse($input, $sections)) {
			return $output;
		}

		foreach ($sections as $section) {
			$context = $section['context'];
			$namespace = $context['namespace'];

			foreach ($section['definitions']['classes'] as $definition) {
				$name = $this->getFullName($namespace, $definition['name']);

				$output['classes'][$name] = [
					'live' => $this->liveGenerator->generate($context, $definition, $deflatedTokens, $inflatedTokens, $map),
					'mock' => $this->mockClassGenerator->generate($context, $definition, $map, $inflatedTokens)
				];
			}

			foreach ($section['definitions']['functions'] as $definition) {
				$name = $this->getFullName($namespace, $definition['name']);

				$output['functions'][$name] = [
					'live' => $this->liveGenerator->generate($context, $definition, $deflatedTokens, $inflatedTokens, $map),
					'mock' => $this->mockFunctionGenerator->generate($context, $definition, $map, $inflatedTokens)
				];
			}

			foreach ($section['definitions']['interfaces'] as $definition) {
				$name = $this->getFullName($namespace, $definition['name']);

				$output['interfaces'][$name] = [
					'live' => $this->liveGenerator->generate($context, $definition, $deflatedTokens, $inflatedTokens, $map)
				];
			}

			foreach ($section['definitions']['traits'] as $definition) {
				$name = $this->getFullName($namespace, $definition['name']);

				$output['traits'][$name] = [
					'live' => $this->liveGenerator->generate($context, $definition, $deflatedTokens, $inflatedTokens, $map),
					'mock' => $this->mockTraitGenerator->generate($context, $definition, $map, $inflatedTokens)
				];
			}
		}

		return $output;
	}

	private function getFullName($namespace, $name)
	{
		if ($namespace === null) {
			return $name;
		}

		return "{$namespace}\\{$name}";
	}
}
