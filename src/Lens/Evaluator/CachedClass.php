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

use ReflectionClass;

class CachedClass extends CachedResource
{
	/** @var string */
	private $cache;

	/** @var string */
	private $class;

	/**
	 * @param string $cache
	 * @param string $class
	 */
	public function __construct($cache, $class)
	{
		$this->cache = $cache;
		$this->class = $class;

		parent::__construct();
	}

	protected function getLockPath()
	{
		$relativePath = strtr($this->class, '\\', '.') . '.lock';
		return $this->paths->join($this->cache, 'locks', 'class', $relativePath);
	}

	protected function getLivePath()
	{
		$relativePath = $this->getRelativePath($this->class);
		return $this->paths->join($this->cache, 'classes', 'live', $relativePath) . '.php';
	}

	protected function getMockPath()
	{
		$relativePath = $this->getRelativePath($this->class);
		return $this->paths->join($this->cache, 'classes', 'mock', $relativePath) . '.php';
	}

	private function getRelativePath($namespacePath)
	{
		$parts = explode('\\', $namespacePath);
		return $this->paths->join($parts);
	}

	protected function getLiveCode()
	{
		// TODO: extract the use statements
		// TODO: extract the class source code
		$reflection = new ReflectionClass($this->class);
		$file = $reflection->getFileName();

		return $this->filesystem->read($file);
	}

	protected function getMockCode()
	{
		$reflection = new ReflectionClass($this->class);
		$namespace = $reflection->getNamespaceName();

		$uses = array(
			'Agent' => __NAMESPACE__ . '\\Agent'
		);

		$contextPhp = PhpCode::getContextPhp($namespace, $uses);

		$mockBuilder = new MockBuilder();
		$classPhp = $mockBuilder->getMockClassPhp($this->class);

		return self::getPhp($contextPhp, $classPhp);
	}


	private static function getPhp()
	{
		$sections = func_get_args();
		$sections = array_filter($sections, 'is_string');
		array_unshift($sections, '<?php');

		return implode("\n\n", $sections) . "\n";
	}
}
