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

namespace Lens_0_0_56\Lens\Tests;

use Lens_0_0_56\Lens\Archivist\Archivist;
use Lens_0_0_56\Lens\Php\Code;
use Lens_0_0_56\Lens\Xdebug;
use Lens_0_0_56\SpencerMortensen\Exceptions\Exceptions;
use Lens_0_0_56\SpencerMortensen\Filesystem\Filesystem;
use Lens_0_0_56\SpencerMortensen\Filesystem\Paths\Path;
use Lens_0_0_56\SpencerMortensen\RegularExpressions\Re;

class Test
{
	/** @var Path */
	private $core;

	/** @var Path|null */
	private $cache;

	/** @var Filesystem */
	private $filesystem;

	/** @var Archivist */
	private $archivist;

	/** @var Examiner */
	private $examiner;

	/** @var null|array */
	private $preState;

	/** @var null|array */
	private $postState;

	/** @var array */
	private $script;

	/** @var Xdebug */
	private $xdebug;

	/** @var null|array */
	private $coverage;

	public function __construct(Path $core, Path $cache = null)
	{
		$this->core = $core;
		$this->cache = $cache;
		// TODO: dependency injection
		$this->filesystem = new Filesystem();
		$this->archivist = new Archivist();
		$this->examiner = new Examiner();
	}

	public function getPreState()
	{
		return $this->preState;
	}

	public function getPostState()
	{
		return $this->postState;
	}

	public function getCoverage()
	{
		return $this->coverage;
	}

	public function run($contextPhp, $prePhp, $postPhp, array $script, array $mockClasses, $isActual)
	{
		$this->script = $script;

		$prePhp = Code::combine($contextPhp, $prePhp);
		$postPhp = Code::combine($contextPhp, $postPhp);

		if ($this->cache !== null) {
			$autoloader = new Autoloader($this->filesystem, $this->core, $this->cache, $mockClasses);
			$autoloader->enable();
		}

		Agent::start($contextPhp, $this->script);

		Exceptions::on([$this, 'prePhp']);

		$this->examiner->run($prePhp);

		Exceptions::off();

		$this->prePhp();

		if (!$this->examiner->isUsable()) {
			return;
		}

		Exceptions::on([$this, 'postPhp']);

		if ($isActual && isset($autoloader)) {
			$autoloader->enableLiveMode();
			$this->startCoverage();
		}

		$this->examiner->run($postPhp);

		Exceptions::off();

		$this->postPhp();
	}

	public function prePhp()
	{
		$this->preState = $this->getState();
	}

	public function postPhp()
	{
		$this->stopCoverage();
		$calls = Agent::stop();

		$this->postState = self::getCleanPostState($this->preState, $this->getState($calls));
	}

	private function getState($calls = null)
	{
		$state = $this->examiner->getState();

		if (is_array($state)) {
			$state['calls'] = $calls;

			unset($state['constants']['LENS_CORE_DIRECTORY'], $state['constants']['LENS_CACHE_DIRECTORY']);
		}

		return $this->archivist->archive($state);
	}

	private static function getCleanPostState(array $pre, array $post = null)
	{
		if ($post === null) {
			return null;
		}

		self::removeDuplicateKeys($pre['variables'], $post['variables']);
		self::removeDuplicateKeys($pre['globals'], $post['globals']);
		self::removeDuplicateKeys($pre['constants'], $post['constants']);
		self::removeMockNamespaces($post['calls']);

		return $post;
	}

	private static function removeDuplicateKeys(array $a, array &$b)
	{
		foreach ($b as $key => $value) {
			if (array_key_exists($key, $a)) {
				unset($b[$key]);
			}
		}
	}

	private static function removeMockNamespaces(array &$calls)
	{
		foreach ($calls as &$call) {
			$object = &$call[0];
			$function = &$call[1];

			$object = self::removeLensMockNamespace($object);
			$function = self::removeLensMockNamespace($function);
		}
	}

	private static function removeLensMockNamespace($namespace)
	{
		if (is_string($namespace) && (strncmp($namespace, 'Lens\\', 5) === 0)) {
			return substr($namespace, 5);
		}

		return $namespace;
	}

	private function startCoverage()
	{
		$this->xdebug = new Xdebug(true);
		$this->xdebug->start();
	}

	private function stopCoverage()
	{
		if ($this->xdebug === null) {
			return;
		}

		$this->xdebug->stop();
		$coverage = $this->xdebug->getCoverage();

		if ($coverage === null) {
			return;
		}

		$this->coverage = $this->getRelevantCoverage($coverage);
	}

	private function getRelevantCoverage(array $coverage)
	{
		$output = [
			'classes' => [],
			'functions' => [],
			'traits' => []
		];

		foreach ($coverage as $file => $lines) {
			if (!$this->isCacheFile($file)) {
				continue;
			}

			$path = $this->getRelativePath($file);

			// TODO: this is fragile:
			if (substr($path, 0, 13) === 'classes\\live\\') {
				$class = substr($path, 13);
				$output['classes'][$class] = $lines;
				continue;
			}

			// TODO: this is fragile:
			if (substr($path, 0, 15) === 'functions\\live\\') {
				$function = substr($path, 15);
				$output['functions'][$function] = $lines;
				continue;
			}

			// TODO: this is fragile:
			if (substr($path, 0, 12) === 'traits\\live\\') {
				$trait = substr($path, 12);
				$output['traits'][$trait] = $lines;
				continue;
			}
		}

		return $output;
	}

	private function isCacheFile($file)
	{
		return !self::isEvaluatedCode($file) &&
			$this->cache->contains($file);
	}

	private static function isEvaluatedCode($path)
	{
		return Re::match('\\([0-9]+\\) : eval\\(\\)\'d code$', $path);
	}

	private function getRelativePath($file)
	{
		$relativePath = $this->cache->getRelativePath(substr($file, 0, -4));
		$atoms = $relativePath->getAtoms();
		return implode('\\', $atoms);
	}
}
