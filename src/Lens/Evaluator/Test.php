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

	/** @var null|string */
	private $contextPhp;

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
		$this->executable = $executable;
		$this->src = $src;
		$this->cache = $cache;
		$this->archivist = new Archivist();
		$this->paths = Paths::getPlatformPaths();
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

	public function run($namespace, array $uses, $prePhp, array $script = null, $postPhp)
	{
		$this->contextPhp = Code::getContextPhp($namespace, $uses);
		$prePhp = self::combine($this->contextPhp, $prePhp);
		$postPhp = self::combine($this->contextPhp, $postPhp);
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

		Agent::start($this->contextPhp, $this->script);
		$this->startCoverage();

		Exceptions::on(array($this, 'postPhp'));

		$this->examiner->run($postPhp);

		Exceptions::off();

		$this->postPhp();
	}

	private function prepare()
	{
		define('LENS_CACHE_DIRECTORY', $this->cache);
		spl_autoload_register(array($this, 'autoload'));
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

	private static function getTail($haystack, $needle)
	{
		$position = strrpos($haystack, $needle);

		if (is_integer($position)) {
			return substr($haystack, $position + 1);
		}

		return $haystack;
	}

	private static function combine()
	{
		return implode("\n\n", array_filter(func_get_args(), 'is_string'));
	}

	public function autoload($class)
	{
		$parts = explode('\\', $class);
		$relativeFilePath = $this->paths->join($parts) . '.php';
		// TODO: autoload mock classes when necessary:
		$absoluteFilePath = $this->paths->join(LENS_CACHE_DIRECTORY, 'classes', 'live', $relativeFilePath);

		if ($this->filesystem->isFile($absoluteFilePath)) {
			include $absoluteFilePath;
		}
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

			if ($object === null) {
				self::removeMockFunctionNamespace($function);
			}
		}
	}

	private static function removeMockFunctionNamespace(&$function)
	{
		// TODO: this will remove the namespace prefix from all functions... including non-mocked functions
		$function = self::getTail($function, '\\');
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
