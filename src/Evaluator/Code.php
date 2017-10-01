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

class Code
{
	public function getPhp($fixturePhp, $inputPhp, $outputPhp, $testPhp)
	{
		$namespace = self::extractNamespace($fixturePhp);
		$aliases = self::extractAliases($fixturePhp);

		$contextPhp = self::getContextPhp($namespace, $aliases);
		$fixturePhp = self::combine($fixturePhp, $inputPhp);

		$mocks = self::extractMockNames($namespace, $aliases, $fixturePhp);
		$script = self::extractScript($mocks, $outputPhp);

		return array($contextPhp, $fixturePhp, $outputPhp, $testPhp, $script);
	}

	private static function extractNamespace(&$php)
	{
		$pattern = self::getPattern('^namespace\h+([^;]+);');

		if (preg_match($pattern, $php, $match) !== 1) {
			return null;
		}

		$namespace = $match[1];
		$php = trim(substr($php, strlen($match[0])));

		return $namespace;
	}

	private static function extractAliases(&$php)
	{
		$aliases = array();

		$lines = self::getLines($php);

		foreach ($lines as $i => $line) {
			if (self::getAlias($line, $name, $path)) {
				$aliases[$name] = $path;
				unset($lines[$i]);
			}
		}

		$php = implode("\n", $lines);

		return $aliases;
	}

	private static function getLines($code)
	{
		$pattern = self::getPattern('\\r?\\n');

		return preg_split($pattern, $code, null, PREG_SPLIT_NO_EMPTY);
	}

	private static function getAlias($php, &$name, &$path)
	{
		$expression = '^use\\h+(?\'path\'[a-zA-Z_0-9\\\\]+)(?:\\h+as\\h+(?\'alias\'[a-zA-Z_0-9]+))?;$';
		$pattern = self::getPattern($expression, 'm');

		if (preg_match($pattern, $php, $match) !== 1) {
			return false;
		}

		$path = $match['path'];
		$alias = &$match['alias'];

		if (!is_string($alias) || (strlen($alias) === 0)) {
			$alias = self::getAliasName($path);
		}

		$name = $alias;

		return true;
	}

	private static function getAliasName($path)
	{
		$slash = strrpos($path, '\\');

		if (is_integer($slash)) {
			return substr($path, $slash + 1);
		}

		return $path;
	}

	private static function getContextPhp($namespace, array $aliases)
	{
		$namespacePhp = self::getNamespacePhp($namespace);
		$aliasesPhp = self::getAliasesPhp($aliases);

		return self::combine($namespacePhp, $aliasesPhp);
	}

	private static function getNamespacePhp($namespace)
	{
		if ($namespace === null) {
			return null;
		}

		return "namespace {$namespace};";
	}

	private static function getAliasesPhp(array $aliases)
	{
		if (count($aliases) === 0) {
			return null;
		}

		$aliasesPhp = array();

		foreach ($aliases as $name => $path) {
			$aliasesPhp[] = self::getAliasPhp($name, $path);
		}

		return implode("\n", $aliasesPhp);
	}

	private static function getAliasPhp($name, $path)
	{
		$aliasPhp = "use {$path}";

		if ($name !== self::getAliasName($path)) {
			$aliasPhp .= " as {$name}";
		}

		$aliasPhp .= ';';

		return $aliasPhp;
	}

	private static function combine()
	{
		return implode("\n\n", array_filter(func_get_args(), 'is_string'));
	}

	private static function extractMockNames($namespace, array $aliases, &$php)
	{
		$names = array();

		$lines = self::getLines($php);

		foreach ($lines as &$line) {
			if (self::getMock($line, $name, $class, $arguments)) {
				$absoluteClass = self::getAbsoluteClass($namespace, $aliases, $class);
				$mockClass = "\\Lens\\Mock{$absoluteClass}";
				$line = self::getInstantiationPhp($name, $mockClass, $arguments);

				$names[$name] = $name;
			}
		}

		$php = implode("\n", $lines);

		return $names;
	}

	private static function getMock(&$php, &$name, &$class, &$arguments)
	{
		$expression = '^\$(?\'name\'[a-zA-Z_0-9]+)\\h*=\\h*new\\h+(?\'class\'[a-zA-Z_0-9\\\\]+)\\((?\'arguments\'.*?)\\);\\h*// Mock$';
		$pattern = self::getPattern($expression);

		if (preg_match($pattern, $php, $match) !== 1) {
			return false;
		}

		$name = $match['name'];
		$class = $match['class'];
		$arguments = $match['arguments'];

		return true;
	}

	private static function getAbsoluteClass($namespace, array $aliases, $class)
	{
		if (substr($class, 0, 1) === '\\') {
			return $class;
		}

		if (self::resolveAliases($aliases, $class)) {
			return $class;
		}

		if ($namespace === null) {
			return "\\{$class}";
		}

		return "\\{$namespace}\\{$class}";
	}

	private static function resolveAliases(array $aliases, &$class)
	{
		$names = explode('\\', $class, 2);
		$baseName = $names[0];
		$basePath = &$aliases[$baseName];

		if ($basePath === null) {
			return false;
		}

		$names[0] = $basePath;
		$class = '\\' . implode('\\', $names);

		return true;
	}

	private static function getInstantiationPhp($name, $class, $arguments)
	{
		return "\${$name} = new {$class}({$arguments});";
	}

	private static function extractScript(array $mocks, &$outputPhp)
	{
		$script = array();

		$pattern = self::getScriptPattern($mocks);
		$lines = self::getLines($outputPhp);

		foreach ($lines as &$line) {
			if (preg_match($pattern, $line, $match) === 1) {
				$line = $match[1];
				$script[] = &$match[2];
			}
		}

		$outputPhp = implode("\n", $lines);

		return $script;
	}

	private static function getScriptPattern(array $mocks)
	{
		$literals = array();

		foreach ($mocks as $name) {
			$literals[] = self::getLiteral($name);
		}

		$literalsExpression = implode('|', $literals);
		$expression = "^(\\\$(?:{$literalsExpression})->.*?)(?:\\s*//\\s+(.*))?$";
		return self::getPattern($expression);
	}

	private static function getLiteral($string)
	{
		$delimiter = "\x03";

		return preg_quote($string, $delimiter);
	}

	private static function getPattern($expression, $flags = '')
	{
		$delimiter = "\x03";

		return "{$delimiter}{$expression}{$delimiter}{$flags}";
	}
}
