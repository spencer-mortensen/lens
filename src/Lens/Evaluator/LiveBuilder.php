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

namespace Lens_0_0_56\Lens\Evaluator;

use Lens_0_0_56\Lens\Filesystem;
use Lens_0_0_56\Lens\Php\Code;
use Lens_0_0_56\Lens\Php\CallParser;
use Lens_0_0_56\Lens\Php\FileParser;
use Lens_0_0_56\Lens\Php\Semantics;
use Lens_0_0_56\SpencerMortensen\Parser\ParserException;
use Lens_0_0_56\SpencerMortensen\Paths\Paths;
use ReflectionClass;
use ReflectionFunction;

class LiveBuilder
{
	/** @var string */
	private $cache;

	/** @var Paths */
	private $paths;

	/** @var Filesystem */
	private $filesystem;

	/** @var CallParser */
	private $parser;

	public function __construct($cache)
	{
		// TODO: dependency injection
		$this->cache = $cache;
		$this->paths = Paths::getPlatformPaths();
		$this->filesystem = new Filesystem();
		$this->parser = new CallParser();
	}

	public function getClassPhp($class)
	{
		$reflection = new ReflectionClass($class);

		$inputFilePhp = $this->getInputFilePhp($reflection);
		$this->scanInputFile($reflection, $inputFilePhp, $namespace, $uses, $definitionPhp);
		$functions = $this->getClassFunctions($namespace, $uses, $definitionPhp);

		return $this->getOutputFilePhp($namespace, $uses, $functions, $definitionPhp);
	}

	/**
	 * @param ReflectionClass|ReflectionFunction $reflection
	 * @return string
	 */
	private function getInputFilePhp($reflection)
	{
		$file = $reflection->getFileName();
		return $this->filesystem->read($file);
	}

	/**
	 * @param ReflectionClass|ReflectionFunction $reflection
	 * @param string|null $filePhp
	 * @param string|null $namespace
	 * @param array $uses
	 * @param string|null $definitionPhp
	 */
	private function scanInputFile($reflection, $filePhp, &$namespace = null, array &$uses = null, &$definitionPhp = null)
	{
		$namespace = $this->getNamespace($reflection);
		$uses = $this->getUses($filePhp);
		$definitionPhp = $this->getDefinitionPhp($reflection, $filePhp);
	}

	/**
	 * @param ReflectionClass|ReflectionFunction $reflection
	 * @return string|null
	 */
	private function getNamespace($reflection)
	{
		$namespace = $reflection->getNamespaceName();

		if (strlen($namespace) === 0) {
			return null;
		}

		return $namespace;
	}

	private function getUses($filePhp)
	{
		$parser = new FileParser();
		return $parser->parse($filePhp);
	}

	/**
	 * @param ReflectionClass|ReflectionFunction $reflection
	 * @param string $code
	 * @return string
	 */
	private function getDefinitionPhp($reflection, $code)
	{
		$pattern = self::getPattern('\\r?\\n');
		$lines = preg_split($pattern, $code);

		$begin = $reflection->getStartLine() - 1;
		$length = $reflection->getEndLine() - $begin;

		$lines = array_slice($lines, $begin, $length);
		return implode("\n", $lines);
	}

	// TODO: use the regular expressions class:
	private static function getPattern($expression, $flags = '')
	{
		$delimiter = "\x03";

		return $delimiter . $expression . $delimiter . $flags . 'XDs';
	}

	private function getClassFunctions($namespace, array $uses, &$classPhp)
	{
		$tokens = $this->getTokens('class', $classPhp);

		return $this->getFunctions($namespace, $uses, $tokens, $classPhp);
	}

	private function getFunctions($namespace, array $uses, array $tokens, &$classPhp)
	{
		$functions = array();
		$edits = array();

		foreach ($tokens as $token) {
			$this->analyzeToken($namespace, $uses, $token, $functions, $edits);
		}

		$classPhp = $this->applyEdits($classPhp, $edits);

		return $functions;
	}

	private function getTokens($rule, $php)
	{
		try {
			return $this->parser->parse($rule, $php);
		} catch (ParserException $exception) {
			// TODO:
			return array();
		}
	}

	private function analyzeToken($namespace, array $uses, array $token, array &$functions, array &$edits)
	{
		list($type, $position, $value) = $token;

		switch ($type) {
			default: // ClassParser::TYPE_CLASS
				$this->analyzeClass($namespace, $uses, $position, $value, $edits);
				break;

			case CallParser::TYPE_FUNCTION:
				$this->analyzeFunction($namespace, $uses, $position, $value, $functions, $edits);
				break;
		}
	}

	private function analyzeClass($namespace, array $uses, $position, $value, array &$edits)
	{
		if (Semantics::isClassIdentifier($value)) {
			return;
		}

		$class = $this->getAbsolutePath($namespace, $uses, $value);

		if (Semantics::isUnsafeClass($class)) {
			$edits[$position] = array(strlen($value), "\\Lens\\{$class}");
		}
	}

	private function analyzeFunction($namespace, array $uses, $position, $value, array &$functions, array &$edits)
	{
		$function = $this->getFunction($namespace, $uses, $value);

		if (Semantics::isUnsafeFunction($function)) {
			$edits[$position] = array(strlen($value), "\\Lens\\{$function}");
		} elseif (!Semantics::isInternalFunction($function)) {
			$functions[$function] = $function;
		}
	}

	// $namespace: null, 'A', 'A\\B', ...
	private function getFunction($namespace, array $uses, $call)
	{
		$delimiter = strrpos($call, '\\');

		if ($delimiter === false) {
			$prefix = null;
			$name = $call;
		} else {
			$prefix = substr($call, 0, max($delimiter, 1));
			$name = substr($call, $delimiter + 1);
		}

		$path = $this->getAbsolutePath($namespace, $uses, $prefix);
		$function = $this->getAbsoluteFunction($path, $name);

		if (($prefix === null) && !function_exists($function)) {
			$function = $name;
		}

		return $function;
	}

	/*
	 * Absolute paths: null, 'A', A\\B', ...
	 */
	private function getAbsolutePath($namespace, array $uses, $prefix)
	{
		if ($prefix === null) {
			return $namespace;
		}

		if (substr($prefix, 0, 1) === '\\') {
			$path = substr($prefix, 1);

			if (strlen($path) === 0) {
				return null;
			}

			return $path;
		}

		$names = explode('\\', $prefix);
		$baseName = $names[0];

		if (isset($uses[$baseName])) {
			$names[0] = $uses[$baseName];
			return implode('\\', $names);
		}

		if ($namespace === null) {
			return $prefix;
		}

		array_unshift($names, $namespace);
		return implode('\\', $names);
	}

	private function getAbsoluteFunction($path, $name)
	{
		if ($path === null) {
			return $name;
		}

		return "{$path}\\{$name}";
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

	private function getOutputFilePhp($namespace, array $uses, $functions, $definitionPhp)
	{
		$contextPhp = Code::getContextPhp($namespace, $uses);
		$requirePhp = $this->getRequirePhp($functions);
		return Code::getPhp($contextPhp, $requirePhp, $definitionPhp);
	}

	private function getRequirePhp(array $functions)
	{
		if (count($functions) === 0) {
			return null;
		}

		$lines = array();

		foreach ($functions as $function) {
			// TODO: this is undoubtedly duplicated elsewhere:
			$names = explode('\\', $function);
			$relativePath = $this->paths->join($names) . '.php';
			$relativePath = DIRECTORY_SEPARATOR . $this->paths->join('functions', 'live', $relativePath);
			$pathPhp = 'LENS_CACHE_DIRECTORY . ' . Code::getValuePhp($relativePath);

			$lines[] = Code::getRequireOncePhp($pathPhp);
		}

		return implode("\n", $lines);
	}

	// TODO: get this to work:
	public function getFunctionPhp($function)
	{
		$reflection = new ReflectionFunction($function);

		$inputFilePhp = $this->getInputFilePhp($reflection);
		$this->scanInputFile($reflection, $inputFilePhp, $namespace, $uses, $definitionPhp);
		$functions = $this->getFunctionFunctions($namespace, $uses, $definitionPhp);

		return $this->getOutputFilePhp($namespace, $uses, $functions, $definitionPhp);
	}

	private function getFunctionFunctions($namespace, array $uses, &$functionPhp)
	{
		$tokens = $this->getTokens('function', $functionPhp);

		return $this->getFunctions($namespace, $uses, $tokens, $functionPhp);
	}
}
