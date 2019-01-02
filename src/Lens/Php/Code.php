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

namespace _Lens\Lens\Php;

class Code
{
	public static function getTagPhp()
	{
		return '<?php';
	}

	public static function getFilePhp($php)
	{
		return "<?php\n\n{$php}\n";
	}

	public static function getFullContextPhp($namespace, array $classes, array $functions)
	{
		$namespacePhp = self::getNamespacePhp($namespace);

		$aliasSections = [
			self::getClassAliasPhp($classes),
			self::getFunctionAliasPhp($functions)
		];

		$aliasPhp = self::merge($aliasSections, "\n");

		$contextSections = [
			$namespacePhp,
			$aliasPhp
		];

		return self::merge($contextSections, "\n\n");
	}

	private static function getClassAliasPhp(array $aliases)
	{
		if (count($aliases) === 0) {
			return null;
		}

		$lines = [];

		foreach ($aliases as $alias => $name) {
			$lines[] = self::getUseClassPhp($alias, $name);
		}

		return implode("\n", $lines);
	}

	private static function getUseClassPhp($alias, $name)
	{
		$usePhp = "use {$name}";

		if ($alias !== self::getTail($name, '\\')) {
			$usePhp .= " as {$alias}";
		}

		$usePhp .= ';';

		return $usePhp;
	}

	private static function getFunctionAliasPhp(array $aliases)
	{
		if (count($aliases) === 0) {
			return null;
		}

		$lines = [];

		foreach ($aliases as $alias => $name) {
			$lines[] = self::getUseFunctionPhp($alias, $name);
		}

		return implode("\n", $lines);
	}

	private static function getUseFunctionPhp($alias, $name)
	{
		$usePhp = "use function {$name}";

		if ($alias !== self::getTail($name, '\\')) {
			$usePhp .= " as {$alias}";
		}

		$usePhp .= ';';

		return $usePhp;
	}

	public static function getValuePhp($value)
	{
		return var_export($value, true);
	}

	public static function getRequirePhp($filePhp)
	{
		return self::getIncludeStatement('require', $filePhp);
	}

	public static function getConditionalRequireOnce($filePhp)
	{
		return "if (file_exists({$filePhp})) { require_once {$filePhp}; };";
	}

	private static function getIncludeStatement($keyword, $filePhp)
	{
		return "{$keyword} {$filePhp};";
	}

	public static function getContextPhp($namespace, array $uses)
	{
		$namespacePhp = self::getNamespacePhp($namespace);
		$usesPhp = self::getUsesPhp($uses);

		return self::combine($namespacePhp, $usesPhp);
	}

	public static function getNamespacePhp($namespace)
	{
		if ($namespace === null) {
			return null;
		}

		return "namespace {$namespace};";
	}

	private static function getUsesPhp(array $uses)
	{
		if (count($uses) === 0) {
			return null;
		}

		$usesPhp = [];

		foreach ($uses as $name => $path) {
			$usesPhp[] = self::getUsePhp($name, $path);
		}

		return implode("\n", $usesPhp);
	}

	private static function getUsePhp($name, $path)
	{
		$usePhp = "use {$path}";

		if ($name !== self::getTail($path, '\\')) {
			$usePhp .= " as {$name}";
		}

		$usePhp .= ';';

		return $usePhp;
	}

	private static function getTail($haystack, $needle)
	{
		$position = strrpos($haystack, $needle);

		if (is_integer($position)) {
			return substr($haystack, $position + 1);
		}

		return $haystack;
	}

	public static function combine()
	{
		$sections = func_get_args();

		return self::merge($sections, "\n\n");
	}

	private static function merge(array $sections, $separator)
	{
		$sections = array_filter($sections, 'is_string');

		if (count($sections) === 0) {
			return null;
		}

		return implode($separator, $sections);
	}
}
