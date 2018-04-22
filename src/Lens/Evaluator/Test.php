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
use Lens_0_0_56\SpencerMortensen\Paths\Paths;

class Test
{
	/** @var string */
	private $executable;

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
	private $script;

	/** @var null|array */
	private $postState;

	/** @var CoverageExtractor */
	private $coverageExtractor;

	/** @var null|array */
	private $coverage;

	public function __construct($executable, $src, $cache)
	{
		// TODO: dependency injection
		$this->executable = $executable;
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

	public function run($contextPhp, $prePhp, array $script = null, $postPhp)
	{
		$prePhp = Code::combine($contextPhp, $prePhp);
		$postPhp = Code::combine($contextPhp, $postPhp);
		$this->script = $script;
		$this->examiner = new Examiner();

		$this->prepare();

		Exceptions::on(array($this, 'prePhp'));

		$this->examiner->run($prePhp);

		Exceptions::off();

		$this->prePhp();

		if (!$this->examiner->isUsable()) {
			return;
		}

		Agent::start($contextPhp, $this->script);
		$this->startCoverage();

		Exceptions::on(array($this, 'postPhp'));

		$this->examiner->run($postPhp);

		Exceptions::off();

		$this->postPhp();
	}

	private function prepare()
	{
		// TODO: this is duplicated elsewhere:
		// TODO: move this to the finder?
		$lensCoreDirectory = dirname(dirname(dirname(__DIR__)));

		define('LENS_CORE_DIRECTORY', $lensCoreDirectory);
		define('LENS_CACHE_DIRECTORY', $this->cache);

		$autoloader = new Autoloader();
		$autoloader->enable();
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
		if (($this->script === null) || ($this->src === null)) {
			return;
		}

		$this->coverageExtractor = new CoverageExtractor($this->src);
		$this->coverageExtractor->start();

	}

	private function stopCoverage()
	{
		if ($this->coverageExtractor === null) {
			return;
		}

		$this->coverageExtractor->stop();
		$this->coverage = $this->coverageExtractor->getCoverage();
	}
}
