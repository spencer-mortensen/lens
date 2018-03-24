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

use ReflectionFunction;

class CachedFunction extends CachedResource
{
	/** @var string */
	private $cache;

	/** @var string */
	private $function;

	/**
	 * @param string $cache
	 * @param string $function
	 */
	public function __construct($cache, $function)
	{
		$this->cache = $cache;
		$this->function = $function;

		parent::__construct();
	}

	protected function getLockPath()
	{
		$relativePath = strtr($this->function, '\\', '.') . '.lock';
		return $this->paths->join($this->cache, 'locks', 'function', $relativePath);
	}

	protected function getLivePath()
	{
		$relativePath = $this->getRelativePath($this->function);
		return $this->paths->join($this->cache, 'functions', 'live', $relativePath) . '.php';
	}

	protected function getMockPath()
	{
		$relativePath = $this->getRelativePath($this->function);
		return $this->paths->join($this->cache, 'functions', 'mock', $relativePath) . '.php';
	}

	private function getRelativePath($namespacePath)
	{
		$parts = explode('\\', $namespacePath);
		return $this->paths->join($parts);
	}

	protected function getLiveCode()
	{
		$reflection = new ReflectionFunction($this->function);
		$namespace = $reflection->getNamespaceName();
		$namespacePhp = PhpCode::getNamespacePhp($namespace);

		// TODO: extract the use statements from the containing file

		$file = $reflection->getFileName();
		$contents = file_get_contents($file);
		$pattern = self::getPattern('\\r?\\n');
		$lines = preg_split($pattern, $contents);

		$begin = $reflection->getStartLine() - 1;
		$length = $reflection->getEndLine() - $begin;

		$lines = array_slice($lines, $begin, $length);
		$functionPhp = implode("\n", $lines);

		return self::getPhp($namespacePhp, $functionPhp);
	}

	private static function getPattern($expression, $flags = '')
	{
		$delimiter = "\x03";

		return $delimiter . $expression . $delimiter . $flags . 'XDs';
	}

	protected function getMockCode()
	{
		$reflection = new ReflectionFunction($this->function);
		$namespace = $reflection->getNamespaceName();

		$uses = array(
			'Agent' => __NAMESPACE__ . '\\Agent'
		);

		$contextPhp = PhpCode::getContextPhp($namespace, $uses);

		$mockBuilder = new MockBuilder();
		$functionPhp = $mockBuilder->getMockFunctionPhp($this->function);

		return self::getPhp($contextPhp, $functionPhp);
	}

	private static function getPhp()
	{
		$sections = func_get_args();
		$sections = array_filter($sections, 'is_string');
		array_unshift($sections, '<?php');

		return implode("\n\n", $sections) . "\n";
	}
}
