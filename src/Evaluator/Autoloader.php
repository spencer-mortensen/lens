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

use Lens\Filesystem;
use Lens\Finder;
use ReflectionClass;
use SpencerMortensen\Paths\Paths;

class Autoloader
{
	/** @var Paths */
	private $paths;

	/** @var Filesystem */
	private $filesystem;

	/** @var string */
	private $autoload;

	/** @var string */
	private $cache;

	/** @var array */
	private $liveClasses;

	/** @var array */
	private $userAutoloaders;

	/** @var array */
	private $map;

	/** @var array */
	private $externalClasses;

	/** @var boolean */
	private static $mustReboot;

	public function __construct(Paths $paths, Filesystem $filesystem, $autoload, $cache, array $liveClasses)
	{
		$this->paths = $paths;
		$this->filesystem = $filesystem;
		$this->autoload = $autoload;
		$this->cache = $cache;
		$this->liveClasses = $liveClasses;

		$this->userAutoloaders = null;

		$this->map = $this->getMap();

		$this->externalClasses = PhpExternal::getClasses();
		self::$mustReboot = false;

		spl_autoload_register(array($this, 'autoload'));
	}

	public function __destruct()
	{
		$mapPath = $this->getMapPath();
		$json = json_encode($this->map);

		$this->filesystem->write($mapPath, $json);
	}

	public static function mustReboot()
	{
		return self::$mustReboot;
	}

	private function getMap()
	{
		$mapPath = $this->getMapPath();

		$json = $this->filesystem->read($mapPath);
		$map = json_decode($json, true);

		if (!is_array($map)) {
			$map = array();
		}

		return $map;
	}

	private function getMapPath()
	{
		return $this->paths->join($this->cache, 'map.json');
	}

	public function autoload($class)
	{
		if ($this->isLiveClass($class)) {
			return $this->autoloadLiveClass($class);
		}

		// TODO: What if $class is *already* a global class?
		return $this->autoloadMockClass($class) ||
			$this->autoloadMockDangerousGlobalClass($class);
	}

	private function isLiveClass($class)
	{
		return isset($this->liveClasses[$class]);
	}

	private function autoloadLiveClass($class)
	{
		return $this->autoloadLiveClassFromMap($class) ||
			$this->autoloadLiveClassFromUser($class);
	}

	private function autoloadLiveClassFromMap($class)
	{
		if (!isset($this->map[$class])) {
			return false;
		}

		$files = $this->map[$class];

		foreach ($files as $file) {
			include $file;
		}

		return true;
	}

	private function autoloadLiveClassFromUser($class)
	{
		$this->getUserAutoloaders();

		$countClasses = count(get_declared_classes());
		$countFiles = count(get_included_files());

		if (!$this->callUserAutoloaders($class)) {
			return false;
		}

		$classes = array_slice(get_declared_classes(), $countClasses);
		$files = array_slice(get_included_files(), $countFiles);

		foreach ($classes as $class) {
			foreach ($files as $file) {
				$this->map[$class][$file] = $file;
			}
		}

		// Add "class => test" to the map

		return true;
	}

	private function getUserAutoloaders()
	{
		if ($this->userAutoloaders !== null) {
			return;
		}

		$countAutoloaders = count(spl_autoload_functions());

		// TODO: add exception handling
		include $this->autoload;

		$autoloaders = array_slice(spl_autoload_functions(), $countAutoloaders);

		foreach ($autoloaders as $autoloader) {
			spl_autoload_unregister($autoloader);
		}

		$this->userAutoloaders = $autoloaders;
	}

	private function callUserAutoloaders($class)
	{
		foreach ($this->userAutoloaders as $autoloader) {
			call_user_func($autoloader, $class);

			if (class_exists($class, false)) {
				return true;
			}
		}

		return false;
	}

	private function autoloadMockClass($class)
	{
		return $this->autoloadMockClassFromCache($class)
			|| $this->autoloadMockClassFromUser($class);
	}

	private function autoloadMockClassFromCache($class)
	{
		$path = $this->getMockClassCachePath($class);

		if (!$this->filesystem->isFile($path)) {
			return false;
		}

		include $path;

		// Add "class => test" to the map

		return true;
	}

	private function getMockClassCachePath($class)
	{
		$names = explode('\\', "{$class}.php");
		array_unshift($names, Finder::MOCKS, Finder::CLASSES);

		$relativePath = $this->paths->join($names);
		return $this->paths->join($this->cache, $relativePath);
	}

	private function autoloadMockClassFromUser($classFullName)
	{
		if (!$this->autoloadLiveClass($classFullName)) {
			return false;
		}

		$class = new ReflectionClass($classFullName);
		$namespace = $class->getNamespaceName();
		$path = $this->getMockClassCachePath($classFullName);

		if ($this->createMock($namespace, $classFullName, $path)) {
			self::$mustReboot = true;
		}

		exit;
	}

	private function autoloadMockDangerousGlobalClass($classFullName)
	{
		$class = new ReflectionClass($classFullName);
		$className = $class->getShortName();

		if (!$this->isDangerousGlobalClass($className)) {
			return false;
		}

		$namespace = $class->getNamespaceName();
		$path = $this->getMockClassCachePath($classFullName);

		if (!$this->filesystem->isFile($path)) {
			$this->createMock($namespace, $className, $path);
		}

		include $path;

		return true;
	}

	private function isDangerousGlobalClass($class)
	{
		return isset($this->externalClasses[$class]);
	}

	private function createMock($namespace, $class, $path)
	{
		$mockBuilder = new MockBuilder();

		$tagPhp = "<?php";
		$namespacePhp = "namespace {$namespace};";
		$classPhp = $mockBuilder->getMockClassPhp($class);
		$mockPhp = "{$tagPhp}\n\n{$namespacePhp}\n\n{$classPhp}\n";

		return $this->filesystem->write($path, $mockPhp);
	}
}
