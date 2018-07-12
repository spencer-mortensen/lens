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

namespace _Lens\Lens;

use _Lens\Lens\Php\CallParser;
use _Lens\Lens\Php\Code;
use _Lens\Lens\Php\Namespacing;
use _Lens\Lens\Php\Semantics;
use _Lens\SpencerMortensen\Filesystem\Path;
use _Lens\SpencerMortensen\Parser\ParserException;

class Sanitizer
{
	/** @var Namespacing */
	private $namespacing;

	/** @var CallParser */
	private $parser;

	/** @var array */
	private $mockFunctions;

	public function __construct(Namespacing $namespacing, array $mockFunctions)
	{
		$this->namespacing = $namespacing;
		// TODO: dependency injection:
		$this->parser = new CallParser();
		$this->mockFunctions = $mockFunctions;
	}

	public function sanitize($type, $namespace, array $uses, $definitionPhp)
	{
		$this->namespacing->setContext($namespace, $uses);

		$tokens = $this->getTokens($type, $definitionPhp);

		$functions = [];
		$edits = [];

		foreach ($tokens as $token) {
			$this->analyzeToken($token, $functions, $edits);
		}

		$requirePhp = $this->getRequirePhp($functions);
		$definitionPhp = $this->applyEdits($definitionPhp, $edits);

		return Code::combine($requirePhp, $definitionPhp);
	}

	private function getTokens($type, $php)
	{
		switch ($type) {
			case 'class':
				$rule = 'class';
				break;

			case 'function':
				$rule = 'function';
				break;

			case 'trait':
				$rule = 'trait';
				break;

			default:
				$rule = 'functionBody';
				break;
		}

		try {
			return $this->parser->parse($rule, $php);
		} catch (ParserException $exception) {
			// TODO:
			return [];
		}
	}

	private function analyzeToken(array $token, array &$functions, array &$edits)
	{
		list($type, $position, $value) = $token;

		switch ($type) {
			default: // CallParser::TYPE_CLASS
				$this->analyzeClass($position, $value, $edits);
				break;

			case CallParser::TYPE_FUNCTION:
				$this->analyzeFunction($position, $value, $functions, $edits);
				break;
		}
	}

	private function analyzeClass($position, $relativeClass, array &$edits)
	{
		if (Semantics::isClassIdentifier($relativeClass)) {
			return;
		}

		$absoluteClass = $this->namespacing->getAbsoluteClass($relativeClass);

		if (Semantics::isUnsafeClass($absoluteClass)) {
			$absoluteClass = "Lens\\{$absoluteClass}";
			$edits[$position] = [strlen($relativeClass), "\\{$absoluteClass}"];
		}
	}

	private function analyzeFunction($position, $relativeFunction, array &$functions, array &$edits)
	{
		$absoluteFunction = $this->namespacing->getAbsoluteFunction($relativeFunction);

		if (Semantics::isUnsafeFunction($absoluteFunction)) {
			$absoluteFunction = "Lens\\{$absoluteFunction}";
			$edits[$position] = [strlen($relativeFunction), "\\{$absoluteFunction}"];
			$functions[$absoluteFunction] = $absoluteFunction;
		} elseif (!Semantics::isPhpFunction($absoluteFunction)) {
			$functions[$absoluteFunction] = $absoluteFunction;
		}
	}

	private function applyEdits($subject, array $edits)
	{
		krsort($edits, SORT_NUMERIC);

		foreach ($edits as $start => $edit) {
			list($length, $replacement) = $edit;

			$subject = substr_replace($subject, $replacement, $start, $length);
		}

		return $subject;
	}

	private function getRequirePhp(array $functions)
	{
		if (count($functions) === 0) {
			return null;
		}

		$lines = [];

		foreach ($functions as $function) {
			$this->getCoreMockPath($function, $pathPhp) ||
			$this->getUserPath($function, $pathPhp);

			$lines[] = Code::getConditionalRequireOnce($pathPhp);
		}

		return implode("\n", $lines);
	}

	private function getCoreMockPath($function, &$pathPhp)
	{
		if (strncmp($function, 'Lens\\', 5) !== 0) {
			return false;
		}

		// TODO: move this to the "SourcePaths" somehow:
		$currentPath = Path::fromString('.');
		$function = substr($function, 5);
		$relativePath = $this->getRelativeFilePath($function);
		$pathValue = DIRECTORY_SEPARATOR . (string)$currentPath->add('functions', 'mock', $relativePath);
		$pathPhp = 'LENS_CORE_DIRECTORY . ' . Code::getValuePhp($pathValue);

		return true;
	}

	private function getRelativeFilePath($function)
	{
		$path = Path::fromString('.');
		$components = explode('\\', "{$function}.php");
		return $path->setComponents($components);
	}

	private function getUserPath($function, &$pathPhp)
	{
		if (isset($this->mockFunctions[$function])) {
			$directory = 'mock';
		} else {
			$directory = 'live';
		}

		// TODO: move this to the "SourcePaths" somehow:
		$currentPath = Path::fromString('.');
		$relativePath = $this->getRelativeFilePath($function);
		$pathValue = DIRECTORY_SEPARATOR . (string)$currentPath->add('functions', $directory, $relativePath);
		$pathPhp = 'LENS_CACHE_DIRECTORY . ' . Code::getValuePhp($pathValue);

		return true;
	}
}
