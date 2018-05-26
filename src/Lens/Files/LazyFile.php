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

namespace Lens_0_0_56\Lens\Files;

class LazyFile implements File
{
	/** @var File */
	private $file;

	/** @var mixed */
	private $initialValue;

	/** @var mixed */
	private $currentValue;

	/** @var bool */
	private $success;

	public function __construct(File $file)
	{
		$this->file = $file;
	}

	public function read(&$value)
	{
		if ($this->success === null) {
			$this->success = $this->file->read($this->initialValue);
			$this->currentValue = $this->initialValue;
		}

		if ($this->success === true) {
			$value = $this->currentValue;
		}

		return $this->success;
	}

	public function write($value)
	{
		if ($this->success === true) {
			$this->currentValue = $value;
		}

		return $this->success;
	}

	public function getPath()
	{
		return $this->file->getPath();
	}

	public function __destruct()
	{
		if (($this->success === true) && ($this->currentValue !== $this->initialValue)) {
			$this->file->write($this->currentValue);
		}
	}
}
