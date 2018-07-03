<?php

/**
 * Copyright (C) 2018 Spencer Mortensen
 *
 * This file is part of Filesystem.
 *
 * Filesystem is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Filesystem is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with Filesystem. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Spencer Mortensen <spencer@lens.guide>
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL-3.0
 * @copyright 2018 Spencer Mortensen
 */

namespace _Lens\SpencerMortensen\Filesystem;

use Exception;
use InvalidArgumentException;

class CorePath
{
	/** @var boolean */
	private $isAbsolute;

	/** @var array */
	private $components;

	/** @var string */
	private $delimiter;

	public function __construct($isAbsolute, array $components, $delimiter)
	{
		$this->isAbsolute = $isAbsolute;
		$this->components = $components;
		$this->delimiter = $delimiter;
	}

	public static function fromString($path, $delimiter)
	{
		if (!is_string($path)) {
			throw new InvalidArgumentException();
		}

		$isAbsolute = self::isStringAbsolute($delimiter, $path);
		$components = self::getStringComponents($delimiter, $isAbsolute, $path);

		return new self($isAbsolute, $components, $delimiter);
	}

	private static function isStringAbsolute($delimiter, $string)
	{
		return $delimiter === substr($string, 0, 1);
	}

	private static function getStringComponents($delimiter, $isAbsolute, $string)
	{
		$input = explode($delimiter, $string);
		$output = [];

		self::appendComponents($isAbsolute, $output, $input);

		return $output;
	}

	private static function appendComponents($isAbsolute, array &$output, array $input)
	{
		foreach ($input as $atom) {
			if ((strlen($atom) === 0) || ($atom === '.')) {
				continue;
			}

			if (($atom === '..') && self::isPoppable($isAbsolute, $output)) {
				array_pop($output);
				continue;
			}

			$output[] = $atom;
		}
	}

	private static function isPoppable($isAbsolute, array $output)
	{
		return $isAbsolute || (
			(0 < count($output)) &&
			(end($output) !== '..')
		);
	}

	public function __toString()
	{
		if ($this->isAbsolute) {
			return $this->delimiter . implode($this->delimiter, $this->components);
		}

		if (count($this->components) === 0) {
			return '.';
		}

		return implode($this->delimiter, $this->components);
	}

	public function isAbsolute()
	{
		return $this->isAbsolute;
	}

	public function getComponents()
	{
		return $this->components;
	}

	/**
	 * @param CorePath[] $arguments
	 * @return CorePath
	 */
	public function add(array $arguments)
	{
		$delimiter = $this->delimiter;
		$isAbsolute = $this->isAbsolute;
		$components = $this->components;

		foreach ($arguments as $argument) {
			$argumentComponents = $argument->getComponents();

			if ($argument->isAbsolute()) {
				$isAbsolute = true;
				$components = $argumentComponents;
			} else {
				self::appendComponents($isAbsolute, $components, $argumentComponents);
			}
		}

		return new self($isAbsolute, $components, $delimiter);
	}

	public function contains(CorePath $path)
	{
		if (!$this->isAbsolute()) {
			throw new Exception();
		}

		return $path->isAbsolute() && $this->isChildPath($this->components, $path->getComponents());
	}

	private function isChildPath(array $aComponents, array $bComponents)
	{
		$aCount = count($aComponents);
		$bCount = count($bComponents);

		return ($aCount < $bCount) && ($aComponents === array_slice($bComponents, 0, $aCount));
	}

	public function getRelativePath(CorePath $path)
	{
		if (!$this->isAbsolute()) {
			throw new Exception();
		}

		if (!$path->isAbsolute()) {
			throw new InvalidArgumentException();
		}

		$delimiter = $this->delimiter;
		$isAbsolute = false;

		$pathComponents = $path->getComponents();
		$components = $this->getRelativeComponents($this->components, $pathComponents);

		return new self($isAbsolute, $components, $delimiter);
	}

	private function getRelativeComponents(array $aComponents, array $bComponents)
	{
		$aCount = count($aComponents);
		$bCount = count($bComponents);

		for ($i = 0, $n = min($aCount, $bCount); ($i < $n) && ($aComponents[$i] === $bComponents[$i]); ++$i);

		return array_merge(array_fill(0, $aCount - $i, '..'), array_slice($bComponents, $i));
	}
}
