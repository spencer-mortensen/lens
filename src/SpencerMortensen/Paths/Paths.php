<?php

/**
 * Copyright (C) 2017 Spencer Mortensen
 *
 * This file is part of paths.
 *
 * Paths is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Paths is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with paths. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Spencer Mortensen <spencer@lens.guide>
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL-3.0
 * @copyright 2017 Spencer Mortensen
 */

namespace Lens_0_0_56\SpencerMortensen\Paths;

abstract class Paths
{
	public static function getPlatformPaths($separator = DIRECTORY_SEPARATOR)
	{
		if ($separator === '\\') {
			return new WindowsPaths();
		}

		return new PosixPaths();
	}

	abstract public function serialize($data);
	abstract public function deserialize($path);

	protected function getPath($isAbsolute, array $atoms, $separator)
	{
		if ($isAbsolute) {
			return $this->getAbsolute($atoms, $separator);
		} else {
			return $this->getRelative($atoms, $separator);
		}
	}

	private function getAbsolute(array $atoms, $separator)
	{
		return $separator . implode($separator, $atoms);
	}

	private function getRelative(array $atoms, $separator)
	{
		if (count($atoms) === 0) {
			return '.';
		}

		return implode($separator, $atoms);
	}

	public function join()
	{
		$arguments = func_get_args();
		$fragments = $this->getFragments($arguments);

		if (count($fragments) === 0) {
			return null;
		}

		$parts = array();

		foreach ($fragments as $fragment) {
			$parts[] = $this->deserialize($fragment);
		}

		$data = current($parts);

		$atoms = array();

		foreach ($parts as $part) {
			// TODO: consider rejecting path fragments that start with a conflicting drive letter
			$newAtoms = $part->getAtoms();
			$atoms = array_merge($atoms, $newAtoms);
		}

		$data->setAtoms($atoms);

		return $this->serialize($data);
	}

	private function getFragments(array $arguments)
	{
		$fragments = array();

		foreach ($arguments as $argument) {
			if (is_string($argument)) {
				$fragments[] = $argument;
			} elseif (is_array($argument)) {
				$fragments = array_merge($fragments, $argument);
			} else {
				// Throw a type error
			}
		}

		return $fragments;
	}

	public function getRelativePath($aPath, $bPath)
	{
		$aParts = $this->deserialize($aPath);
		$bParts = $this->deserialize($bPath);

		if (!$aParts->isAbsolute() || !$bParts->isAbsolute()) {
			return null;
		}

		$aAtoms = $aParts->getAtoms();
		$bAtoms = $bParts->getAtoms();

		$aCount = count($aAtoms);
		$bCount = count($bAtoms);

		for ($i = 0, $n = min($aCount, $bCount); ($i < $n) && ($aAtoms[$i] === $bAtoms[$i]); ++$i);

		$atoms = array_fill(0, $aCount - $i, '..');
		$atoms = array_merge($atoms, array_slice($bAtoms, $i));

		$cData = new WindowsPathData(null, $atoms, false);

		return $this->serialize($cData);
	}

	abstract public function isChildPath($aPath, $bPath);
}
