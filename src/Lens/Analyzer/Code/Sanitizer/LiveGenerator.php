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

namespace _Lens\Lens\Analyzer\Code\Sanitizer;

use _Lens\Lens\Analyzer\Code\Deflator;
use _Lens\Lens\Php\Code;
use _Lens\Lens\Php\Lexer as PhpLexer;
use _Lens\Lens\Php\Semantics;

class LiveGenerator
{
	/** @var Deflator */
	private $deflator;

	/** @var ClassFinder */
	private $classFinder;

	/** @var PathSanitizer */
	private $classSanitizer;

	/** @var FunctionFinder */
	private $functionFinder;

	/** @var PathSanitizer */
	private $functionSanitizer;

	/** @var WhitespaceStandardizer */
	private $whitespaceStandardizer;

	public function __construct()
	{
		// TODO: share dependencies
		$this->deflator = new Deflator();
		$this->classFinder = new ClassFinder();
		$this->classSanitizer = new PathSanitizer(Semantics::getUnsafeClasses());
		$this->functionFinder = new FunctionFinder();
		$this->functionSanitizer = new PathSanitizer(Semantics::getUnsafeFunctions());
		$this->whitespaceStandardizer = new WhitespaceStandardizer();
	}

	public function generate(array $context, array $inflatedTokens)
	{
		$namespace = $context['namespace'];
		$functions = $context['functions'];

		$this->deflator->deflate($inflatedTokens, $deflatedTokens, $map);

		$this->functionSanitizer->setNamespace($namespace);
		$this->functionSanitizer->setAliases($functions);
		$this->classSanitizer->setNamespace($context['namespace']);
		$this->classSanitizer->setAliases($context['classes']);

		$edits = $this->getEdits($deflatedTokens);
		$tokens = $this->applyEdits($edits, $inflatedTokens, $map);
		$classes = $this->getClassAliases($this->classSanitizer->getAliases());
		$functions = $this->getFunctionAliases($this->functionSanitizer->getAliases());

		return $this->getPhp($namespace, $classes, $functions, $tokens);
	}

	private function getEdits(array $deflatedTokens)
	{
		$edits = [];

		$this->addEdits($deflatedTokens, $this->functionFinder, $this->functionSanitizer, $edits);
		$this->addEdits($deflatedTokens, $this->classFinder, $this->classSanitizer, $edits);

		return $edits;
	}

	private function addEdits(array $tokens, Finder $finder, PathSanitizer $sanitizer, array &$edits)
	{
		$positions = $finder->find($tokens);

		foreach ($positions as $begin => $end) {
			$length = $end - $begin + 1;
			$pathTokens = array_slice($tokens, $begin, $length);
			$path = $this->getPhpFromTokens($pathTokens);

			// TODO: reuse these translations (if they crop up again):
			$safePath = $sanitizer->sanitize($path);

			if ($safePath !== $path) {
				$safePathToken = [PhpLexer::IDENTIFIER_ => $safePath];
				$edits[$begin] = [$end, [$safePathToken]];
			}
		}
	}

	private function applyEdits(array $edits, array $tokensInflated, array $map)
	{
		krsort($edits, SORT_NUMERIC);

		foreach ($edits as $iBeginDeflated => $edit) {
			list($iEndDeflated, $replacementTokens) = $edit;
			$iBeginInflated = $map[$iBeginDeflated];
			$iEndInflated = $map[$iEndDeflated];
			$lengthInflated = $iEndInflated - $iBeginInflated + 1;
			array_splice($tokensInflated, $iBeginInflated, $lengthInflated, $replacementTokens);
		}

		return $tokensInflated;
	}

	private function getPhp($namespace, array $classes, array $functions, array $tokens)
	{
		$contextPhp = Code::getFullContextPhp($namespace, $classes, $functions);
		$tokens = $this->whitespaceStandardizer->standardize($tokens);
		$definitionPhp = $this->getPhpFromTokens($tokens);
		$php = Code::combine($contextPhp, $definitionPhp);
		return Code::getFilePhp($php);
	}

	// TODO: this is duplicated elsewhere
	private function getPhpFromTokens(array $tokens)
	{
		ob_start();

		foreach ($tokens as $token) {
			echo current($token);
		}

		return ob_get_clean();
	}

	private function getClassAliases(array $aliases)
	{
		$safe = [];

		foreach ($aliases as $alias => $name) {
			if (Semantics::isUnsafeClass($name)) {
				$name = "Lens\\{$name}";
			}

			$safe[$alias] = $name;
		}

		return $safe;
	}

	private function getFunctionAliases(array $aliases)
	{
		$safe = [];

		foreach ($aliases as $alias => $name) {
			if (Semantics::isUnsafeFunction($name)) {
				$name = "Lens\\{$name}";
			} elseif ($alias === $name) {
				continue;
			}

			$safe[$alias] = $name;
		}

		return $safe;
	}
}
