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

use Lens\Archivist\Archivist;
use Lens\Archivist\Archives\ObjectArchive;
use SpencerMortensen\Exceptions\Exceptions;

class Test
{
	/** @var Archivist */
	private $archivist;

	/** @var string */
	private $srcDirectory;

	/** @var string */
	private $autoloadPath;

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

	public function __construct($srcDirectory, $autoloadPath)
	{
		$this->archivist = new Archivist();
		$this->srcDirectory = $srcDirectory;
		$this->autoloadPath = $autoloadPath;
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
		$this->contextPhp = self::getContextPhp($namespace, $uses);
		$prePhp = self::combine($this->contextPhp, $prePhp);
		$postPhp = self::combine($this->contextPhp, $postPhp);

		$this->script = $script;

		$this->examiner = new Examiner();
		$this->prepare($namespace);

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

	private static function getContextPhp($namespace, array $uses)
	{
		$namespacePhp = self::getNamespacePhp($namespace);
		// TODO: mock DateTime et. al.
		$usesPhp = self::getUsesPhp($uses);

		return self::combine($namespacePhp, $usesPhp);
	}

	private static function getNamespacePhp($namespace)
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

		$usesPhp = array();

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

	private static function combine()
	{
		return implode("\n\n", array_filter(func_get_args(), 'is_string'));
	}

	private function prepare($namespace)
	{
		$autoloader = new Autoloader();
		$autoloader->register();

		eval($autoloader->getMockFunctionsPhp($namespace));

		if (is_string($this->autoloadPath)) {
			require $this->autoloadPath;
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
			} else {
				self::removeMockObjectNamespace($object);
			}
		}
	}

	private static function removeMockFunctionNamespace(&$function)
	{
		// TODO: this will remove the namespace prefix from all functions... including non-mocked functions
		$function = self::getTail($function, '\\');
	}

	private static function removeMockObjectNamespace(ObjectArchive $object)
	{
		$class = $object->getClass();

		if (strncmp($class, 'Lens\\Mock\\', 10) !== 0) {
			return;
		}

		$class = substr($class, 10);
		$object->setClass($class);
	}

	private function startCoverage()
	{
		if (($this->script === null) || ($this->srcDirectory === null)) {
			return;
		}

		$this->coverageExtractor = new CoverageExtractor($this->srcDirectory);
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
