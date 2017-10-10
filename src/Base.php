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

namespace Lens;

class Base
{
	/** @var null|string */
	private $prefix;

	/** @var null|integer */
	private $prefixLength;

	public function __construct($baseDirectory)
	{
		if ($baseDirectory !== null) {
			$this->prefix = $baseDirectory . '/';
			$this->prefixLength = strlen($this->prefix);
		}
	}

	public function isChildPath($absolutePath)
	{
		if ($this->prefix === null) {
			return true;
		}

		return strncmp($absolutePath . '/', $this->prefix, $this->prefixLength) === 0;
	}

	public function getRelativePath($absolutePath)
	{
		if ($this->prefix === null) {
			return $absolutePath;
		}

		$aTrail = explode('/', trim($this->prefix, '/'));
		$bTrail = explode('/', trim($absolutePath, '/'));

		$aCount = count($aTrail);
		$bCount = count($bTrail);

		for ($i = 0, $n = min($aCount, $bCount); ($i < $n) && ($aTrail[$i] === $bTrail[$i]); ++$i);

		return str_repeat('../', $aCount - $i) . implode('/', array_slice($bTrail, $i));
	}

	public function getAbsolutePath($relativePath)
	{
		if ($this->prefix === null) {
			return $relativePath;
		}

		$aTrail = explode('/', trim($this->prefix, '/'));
		$bTrail = explode('/', trim($relativePath, '/'));

		$aCount = count($aTrail);
		$bCount = count($bTrail);

		for ($i = 0; ($i < $bCount) && ($bTrail[$i] === '..'); ++$i);

		$head = implode('/', array_slice($aTrail, 0, $aCount - $i));
		$tail = implode('/', array_slice($bTrail, $i));

		return '/' . trim("{$head}/{$tail}", '/');
	}
}
