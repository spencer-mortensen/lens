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

use Lens_0_0_56\SpencerMortensen\RegularExpressions\Re;

class WindowsPaths extends Paths
{
	public function serialize($data)
	{
		$isAbsolute = $data->isAbsolute();
		$atoms = $data->getAtoms();

		$path = $this->getPath($isAbsolute, $atoms, '\\');

		$drive = $data->getDrive();

		if ($drive !== null) {
			$path = "{$drive}:{$path}";
		}

		return $path;
	}

	public function deserialize($path)
	{
		Re::match('^(?:(?<drive>[a-zA-Z]):)?(?<path>.*)$', $path, $match);

		$drive = self::getNonEmptyString($match['drive']);
		$path = self::getNonEmptyString($match['path']);
		$atoms = self::getAtoms($path);
		$isAbsolute = self::isAbsolute($path);

		return new WindowsPathData($drive, $atoms, $isAbsolute);
	}

	private static function getNonEmptyString(&$input)
	{
		if (!is_string($input) || (strlen($input) === 0)) {
			return null;
		}

		return $input;
	}

	private static function getAtoms($path)
	{
		if ($path === null) {
			return array();
		}

		return Re::split('\\\\|/', $path);
	}

	private static function isAbsolute($path)
	{
		if ($path === null) {
			return false;
		}

		$character = substr($path, 0, 1);

		return ($character === '\\') || ($character === '/');
	}

	public function isChildPath($aPath, $bPath)
	{
		$aData = $this->deserialize($aPath);
		$bData = $this->deserialize($bPath);

		$aAtoms = $aData->getAtoms();
		$bAtoms = $bData->getAtoms();

		// TODO: take into account the drive letters
		return $aData->isAbsolute() &&
			$bData->isAbsolute() &&
			($aAtoms === array_slice($bAtoms, 0, count($aAtoms)));
	}
}
