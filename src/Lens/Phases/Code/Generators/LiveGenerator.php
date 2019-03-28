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

use _Lens\Lens\Phases\Code\Sanitizers\PathSanitizer;
use _Lens\Lens\Phases\Code\Sanitizers\WhitespaceStandardizer;
use _Lens\Lens\Php\Lexer;

class LiveGenerator
{
	/** @var PathSanitizer */
	private $pathSanitizer;

	/** @var WhitespaceStandardizer */
	private $whitespaceStandardizer;

	public function __construct(PathSanitizer $pathSanitizer, WhitespaceStandardizer $whitespaceStandardizer)
	{
		$this->pathSanitizer = $pathSanitizer;
		$this->whitespaceStandardizer = $whitespaceStandardizer;
	}

	public function generate(array $context, array $definition, array $deflatedTokens, array $inflatedTokens, array $map)
	{
		$references = [
			'classes' => $this->getReferences($definition['paths']['classes'], $deflatedTokens),
			'functions' => $this->getReferences($definition['paths']['functions'], $deflatedTokens)
		];

		list($context, $replacements) = $this->pathSanitizer->sanitize($context, $references);

		$definitionPhp = $this->getDefinitionPhp($definition, $references, $replacements, $inflatedTokens, $map);

		return [
			'context' => $context,
			'definition' => $definitionPhp
		];
	}

	private function getReferences(array $classPaths, array $deflatedTokens)
	{
		$references = [];

		foreach ($classPaths as $iBegin => $iEnd) {
			$pathTokens = array_slice($deflatedTokens, $iBegin, $iEnd - $iBegin + 1);
			$path = $this->getPhpFromTokens($pathTokens);

			$references[$path][$iBegin] = $iEnd;
		}

		return $references;
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

	private function getDefinitionPhp(array $definition, array $references, array $replacements, array $inflatedTokens, array $map)
	{
		$edits = $this->getEdits($replacements, $references, $map);
		$limits = $this->getLimits($definition['range'], $map);

		$inflatedTokens = $this->applyEdits($inflatedTokens, $limits, $edits);
		$inflatedTokens = $this->whitespaceStandardizer->standardize($inflatedTokens);

		return $this->getPhpFromTokens($inflatedTokens);
	}

	private function getEdits($replacements, $references, $map)
	{
		$edits = [];

		$this->addEdits($replacements['classes'], $references['classes'], $map, $edits);
		$this->addEdits($replacements['functions'], $references['functions'], $map, $edits);

		return $edits;
	}

	private function addEdits(array $replacements, array $references, array $map, array &$edits)
	{
		foreach ($replacements as $pathBefore => $pathAfter) {
			foreach ($references[$pathBefore] as $iBeginDeflated => $iEndDeflated) {
				$iBeginInflated = $map[$iBeginDeflated];
				$lengthInflated = $map[$iEndDeflated] - $iBeginInflated + 1;

				$edits[$iBeginInflated] = [$lengthInflated, [Lexer::IDENTIFIER_ => $pathAfter]];
			}
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
}
