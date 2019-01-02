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
use _Lens\Lens\Php\Semantics;

class InterfaceGenerator
{
	/** @var PathSanitizer */
	private $classSanitizer;

	/** @var WhitespaceStandardizer */
	private $whitespaceStandardizer;

	public function __construct()
	{
		$this->classSanitizer = new PathSanitizer(Semantics::getUnsafeClasses());
		$this->whitespaceStandardizer = new WhitespaceStandardizer();
	}

	public function generate(array $context, array $interface, array $deflatedTokens, array $inflatedTokens, array $map)
	{
		$contextPhp = $this->getContextPhp($context, $interface['classPaths'], $deflatedTokens);
		$definitionPhp = $this->getDefinitionPhp($interface['range'], $map, $inflatedTokens);
		$php = Code::combine($contextPhp, $definitionPhp);
		return Code::getFilePhp($php);
	}

	private function getContextPhp(array $context, array $classPaths, array $deflatedTokens)
	{
		$namespace = $context['namespace'];
		$classes = $this->getClasses($context, $classPaths, $deflatedTokens);
		$functions = [];

		return Code::getFullContextPhp($namespace, $classes, $functions);
	}

	private function getClasses(array $context, array $classPaths, array $deflatedTokens)
	{
		$this->classSanitizer->setContext($context['namespace'], $context['classes']);

		foreach ($classPaths as $iBegin => $iEnd) {
			$pathTokens = array_slice($deflatedTokens, $iBegin, $iEnd - $iBegin + 1);
			$path = $this->getPhpFromTokens($pathTokens);
			$this->classSanitizer->sanitize($path);
		}

		return $this->classSanitizer->getAliases();
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

	private function getDefinitionPhp(array $range, array $map, array $inflatedTokens)
	{
		$iBeginDeflated = key($range);
		$iEndDeflated = $range[$iBeginDeflated];

		$iBeginInflated = $map[$iBeginDeflated];
		$lengthInflated = $map[$iEndDeflated] - $iBeginInflated + 1;

		$inflatedTokens = array_slice($inflatedTokens, $iBeginInflated, $lengthInflated);
		$inflatedTokens = $this->whitespaceStandardizer->standardize($inflatedTokens);

		return $this->getPhpFromTokens($inflatedTokens);
	}
}
