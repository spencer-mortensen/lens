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

class Test
{
	const LENS_CONSTANT_NAME = 'LENS';

	/** @var Archivist */
	private $archivist;

	/** @var string */
	private $lensDirectory;

	/** @var string */
	private $srcDirectory;

	/** @var string */
	private $bootstrapPath;

	/** @var null|string */
	private $contextPhp;

	/** @var null|array */
	private $script;

	/** @var callable */
	private $onShutdown;

	/** @var Examiner */
	private $examiner;

	/** @var null|array */
	private $preState;

	/** @var null|array */
	private $postState;

	/** @var CoverageExtractor */
	private $coverageExtractor;

	/** @var null|array */
	private $coverage;

	public function __construct($lensDirectory, $srcDirectory, $bootstrapPath)
	{
		$this->archivist = new Archivist();
		$this->lensDirectory = $lensDirectory;
		$this->srcDirectory = $srcDirectory;
		$this->bootstrapPath = $bootstrapPath;
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

	public function run($contextPhp, $beforePhp, $afterPhp, $script, $onShutdown)
	{
		$this->contextPhp = $contextPhp;
		$this->script = $script;
		$this->onShutdown = $onShutdown;

		$prePhp = self::combine($contextPhp, $beforePhp);
		$postPhp = self::combine($contextPhp, $afterPhp);

		$this->examiner = new Examiner();
		$this->prepare();

		$this->examiner->run($prePhp, array($this, 'onPreShutdown'));
		$this->onPreShutdown();

		$this->examiner->run($postPhp, array($this, 'onPostShutdown'));
		$this->onPostShutdown();
	}

	private static function combine()
	{
		return implode("\n\n", array_filter(func_get_args(), 'is_string'));
	}

	private function prepare()
	{
		define(self::LENS_CONSTANT_NAME, "{$this->lensDirectory}/");

		spl_autoload_register(
			function ($class)
			{
				$mockPrefix = 'Lens\\Mock\\';
				$mockPrefixLength = strlen($mockPrefix);

				if (strncmp($class, $mockPrefix, $mockPrefixLength) !== 0) {
					return;
				}

				$parentClass = substr($class, $mockPrefixLength);

				$mockBuilder = new MockBuilder($mockPrefix, $parentClass);
				$mockCode = $mockBuilder->getMock();

				eval($mockCode);
			}
		);

		if (is_string($this->bootstrapPath)) {
			require $this->bootstrapPath;
		}
	}

	public function onPreShutdown()
	{
		$this->preState = $this->getState();

		if ($this->examiner->isTerminated()) {
			call_user_func($this->onShutdown);
		}

		Agent::start($this->contextPhp, $this->script);
		$this->startCoverage();
	}

	private function getState($calls = null)
	{
		$state = $this->examiner->getState();

		if (is_array($state)) {
			unset($state['constants'][self::LENS_CONSTANT_NAME]);
			$state['calls'] = $calls;
		}

		return $this->archivist->archive($state);
	}

	public function onPostShutdown()
	{
		$this->stopCoverage();
		$calls = Agent::stop();

		$this->postState = self::getCleanPostState($this->preState, $this->getState($calls));

		call_user_func($this->onShutdown);
	}

	private static function getCleanPostState(array $pre, array $post = null)
	{
		if ($post === null) {
			return null;
		}

		self::removeDuplicateKeys($pre['variables'], $post['variables']);
		self::removeDuplicateKeys($pre['globals'], $post['globals']);
		self::removeDuplicateKeys($pre['constants'], $post['constants']);

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

	private function startCoverage()
	{
		if ($this->script === null) {
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
