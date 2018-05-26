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

use Lens_0_0_56\Lens\Archivist\Archivist;
use Lens_0_0_56\Lens\Filesystem;
use Lens_0_0_56\Lens\Php\Code;
use Lens_0_0_56\SpencerMortensen\Exceptions\Exceptions;
use Lens_0_0_56\SpencerMortensen\RegularExpressions\Re;
use Lens_0_0_56\SpencerMortensen\Paths\Paths;

class Test
{
	/** @var string */
	private $executable;

	/** @var string */
	private $lensCore;

	/** @var string */
	private $src;

	/** @var string */
	private $cache;

	/** @var Archivist */
	private $archivist;

	/** @var Paths  */
	private $paths;

	/** @var Filesystem */
	private $filesystem;

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

	public function __construct($executable, $lensCore, $src, $cache)
	{
		// TODO: dependency injection
		$this->executable = $executable;
		$this->lensCore = $lensCore;
		$this->src = $src;
		$this->cache = $cache;
		$this->archivist = new Archivist();
		$this->paths = Paths::getPlatformPaths();
		$this->filesystem = new Filesystem();
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
		$this->examiner = new Examiner();

		$prePhp = Code::combine($contextPhp, $prePhp);
		$postPhp = Code::combine($contextPhp, $postPhp);

		$autoloader = new Autoloader($this->lensCore, $this->cache, $mockClasses);
		$autoloader->enable();

		Exceptions::on(array($this, 'prePhp'));

		$this->examiner->run($prePhp);

		Exceptions::off();

		$this->prePhp();

		if (!$this->examiner->isUsable()) {
			return;
		}

		Agent::start($contextPhp, $this->script);

		Exceptions::on(array($this, 'postPhp'));

		if ($isActual) {
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
		$output = array(
			'classes' => array(),
			'functions' => array(),
			'traits' => array()
		);

		foreach ($coverage as $file => $lines) {
			if (!$this->isCacheFile($file)) {
				continue;
			}

			$path = $this->getRelativePath($file);

			if (substr($path, 0, 13) === 'classes\\live\\') {
				$class = substr($path, 13);
				$output['classes'][$class] = $lines;
			}

			// TODO: support functions, traits
		}

		return $output;
	}

	private function isCacheFile($file)
	{
		return !self::isEvaluatedCode($file) &&
			$this->paths->isChildPath($this->cache, $file);
	}

	private static function isEvaluatedCode($path)
	{
		return Re::match('\\([0-9]+\\) : eval\\(\\)\'d code$', $path);
	}

	private function getRelativePath($file)
	{
		$relativePath = $this->paths->getRelativePath($this->cache, $file);
		$data = $this->paths->deserialize($relativePath);
		$atoms = $data->getAtoms();
		return substr(implode('\\', $atoms), 0, -4);
	}
}
