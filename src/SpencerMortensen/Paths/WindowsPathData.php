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

class WindowsPathData
{
	/** @var string */
	private $drive;

	/** @var array */
	private $atoms;

	/** @var boolean */
	private $isAbsolute;

	public function __construct($drive, $atoms, $isAbsolute)
	{
		$this->drive = $drive;
		$this->atoms = $atoms;
		$this->isAbsolute = $isAbsolute;
	}

	public function getDrive()
	{
		return $this->drive;
	}

	public function getAtoms()
	{
		return $this->atoms;
	}

	public function setAtoms(array $atoms)
	{
		$this->atoms = $atoms;
	}

	public function isAbsolute()
	{
		return $this->isAbsolute;
	}
}
