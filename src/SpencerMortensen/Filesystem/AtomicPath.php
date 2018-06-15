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

namespace Lens_0_0_56\SpencerMortensen\Filesystem;

use Exception;
use InvalidArgumentException;

class AtomicPath
{
	/** @var boolean */
	private $isAbsolute;

	/** @var array */
	private $atoms;

	/** @var string */
	private $delimiter;

	public function __construct($isAbsolute, array $atoms, $delimiter)
	{
		$this->isAbsolute = $isAbsolute;
		$this->atoms = $atoms;
		$this->delimiter = $delimiter;
	}

	public static function fromString($path, $delimiter)
	{
		if (!is_string($path)) {
			throw new InvalidArgumentException();
		}

		$isAbsolute = self::isStringAbsolute($delimiter, $path);
		$atoms = self::getStringAtoms($delimiter, $isAbsolute, $path);

		return new self($isAbsolute, $atoms, $delimiter);
	}

	private static function isStringAbsolute($delimiter, $string)
	{
		return $delimiter === substr($string, 0, 1);
	}

	private static function getStringAtoms($delimiter, $isAbsolute, $string)
	{
		$input = explode($delimiter, $string);
 		$output = [];

		self::appendAtoms($isAbsolute, $output, $input);

		return $output;
	}

	private static function appendAtoms($isAbsolute, array &$output, array $input)
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
			return $this->delimiter . implode($this->delimiter, $this->atoms);
		}

		if (count($this->atoms) === 0) {
			return '.';
		}

		return implode($this->delimiter, $this->atoms);
	}

	public function isAbsolute()
	{
		return $this->isAbsolute;
	}

	public function getAtoms()
	{
		return $this->atoms;
	}

	/**
	 * @param AtomicPath[] $arguments
	 * @return AtomicPath
	 */
	public function add(array $arguments)
	{
		$delimiter = $this->delimiter;
		$isAbsolute = $this->isAbsolute;
		$atoms = $this->atoms;

		foreach ($arguments as $argument) {
			$argumentAtoms = $argument->getAtoms();

			// TODO: add unit tests for this:
			if ($argument->isAbsolute()) {
				$isAbsolute = true;
				$atoms = $argumentAtoms;
			} else {
				self::appendAtoms($isAbsolute, $atoms, $argumentAtoms);
			}
		}

		return new self($isAbsolute, $atoms, $delimiter);
	}

	public function contains(AtomicPath $path)
	{
		if (!$this->isAbsolute()) {
			throw new Exception();
		}

		return $path->isAbsolute() && $this->isChildPath($this->atoms, $path->getAtoms());
	}

	private function isChildPath(array $aAtoms, array $bAtoms)
	{
		$aCount = count($aAtoms);
		$bCount = count($bAtoms);

		return ($aCount < $bCount) && ($aAtoms === array_slice($bAtoms, 0, $aCount));
	}

	public function getRelativePath(AtomicPath $path)
	{
		if (!$this->isAbsolute()) {
			throw new Exception();
		}

		if (!$path->isAbsolute()) {
			throw new InvalidArgumentException();
		}

		$delimiter = $this->delimiter;
		$isAbsolute = false;

		$pathAtoms = $path->getAtoms();
		$atoms = $this->getRelativeAtoms($this->atoms, $pathAtoms);

		return new self($isAbsolute, $atoms, $delimiter);
	}

	private function getRelativeAtoms(array $aAtoms, array $bAtoms)
	{
		$aCount = count($aAtoms);
		$bCount = count($bAtoms);

		for ($i = 0, $n = min($aCount, $bCount); ($i < $n) && ($aAtoms[$i] === $bAtoms[$i]); ++$i);

		return array_merge(array_fill(0, $aCount - $i, '..'), array_slice($bAtoms, $i));
	}
}
