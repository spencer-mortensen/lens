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

use _Lens\Lens\Phases\Analysis\Code\Sanitizers\PathSanitizer;
use _Lens\Lens\Phases\Analysis\Code\Sanitizers\WhitespaceStandardizer;
use _Lens\Lens\Php\Code;
use _Lens\Lens\Php\Lexer;
use _Lens\Lens\Php\Semantics;

class LiveGenerator
{
	/** @var PathSanitizer */
	private $classSanitizer;

	/** @var PathSanitizer */
	private $functionSanitizer;

	/** @var WhitespaceStandardizer */
	private $whitespaceStandardizer;

	public function __construct()
	{
		$this->classSanitizer = new PathSanitizer(Semantics::getUnsafeClasses());
		$this->functionSanitizer = new PathSanitizer(Semantics::getUnsafeFunctions());
		$this->whitespaceStandardizer = new WhitespaceStandardizer();
	}

	public function generate(array $context, array $class, array $deflatedTokens, array $inflatedTokens, array $map)
	{
		$namespace = $context['namespace'];

		$this->classSanitizer->setContext($namespace, $context['classes']);
		$this->functionSanitizer->setContext($namespace, $context['functions']);

		$edits = [];

		$this->addEdits($class['classPaths'], $this->classSanitizer, $deflatedTokens, $map, $edits);
		$this->addEdits($class['functionPaths'], $this->functionSanitizer, $deflatedTokens, $map, $edits);

		$limits = $this->getLimits($class['range'], $map);
		$inflatedTokens = $this->applyEdits($inflatedTokens, $limits, $edits);

		$classes = $this->getClassAliases($this->classSanitizer->getAliases());
		$functions = $this->getFunctionAliases($this->functionSanitizer->getAliases());

		return $this->getFilePhp($namespace, $classes, $functions, $inflatedTokens);
	}

	private function addEdits(array $paths, PathSanitizer $sanitizer, array $deflatedTokens, array $map, array &$edits)
	{
		foreach ($paths as $iBeginDeflated => $iEndDeflated) {
			$pathTokens = array_slice($deflatedTokens, $iBeginDeflated, $iEndDeflated - $iBeginDeflated + 1);
			$path = $this->getPhpFromTokens($pathTokens);
			$safePath = $sanitizer->sanitize($path);

			if ($safePath === $path) {
				continue;
			}

			$iBeginInflated = $map[$iBeginDeflated];
			$lengthInflated = $map[$iEndDeflated] - $iBeginInflated + 1;
			$edits[$iBeginInflated] = [$lengthInflated, [Lexer::IDENTIFIER_ => $safePath]];
		}
	}

	private function getLimits(array $range, array $map)
	{
		$iBeginDeflated = key($range);
		$iEndDeflated = $range[$iBeginDeflated];

		$iBeginInflated = $map[$iBeginDeflated];
		$lengthInflated = $map[$iEndDeflated] - $iBeginInflated + 1;

		return [$iBeginInflated => $lengthInflated];
	}

	private function applyEdits(array $input, array $limits, array $edits)
	{
		$output = [];

		ksort($edits, SORT_NUMERIC);
		$iEdit = key($edits);

		$i = key($limits);
		$n = $i + $limits[$i];

		while ($i < $n) {
			if ($i === $iEdit) {
				list($length, $token) = $edits[$i];

				$i += $length;
				$output[] = $token;

				next($edits);
				$iEdit = key($edits);
			} else {
				$output[] = $input[$i];
				++$i;
			}
		}

		return $output;
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

	private function getFilePhp($namespace, array $classes, array $functions, array $tokens)
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
}
