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

namespace _Lens\Lens\Files;

use _Lens\Lens\JsonFile;
use _Lens\SpencerMortensen\Filesystem\Path;

class MetaFile
{
	/** @var JsonFile */
	private $file;

	// TODO: in the "spencermortensen/filesystem" library: create a "File" interface?
	public function __construct(Path $path)
	{
		$this->file = new JsonFile($path);
	}

	public function read()
	{
		$data = $this->file->read();

		return $this->getValidData($data);
	}

	private function getValidData($input)
	{
		if (is_array($input) && isset($input['classes']) && $this->isValidClasses($input['classes'])) {
			$classes = $input['classes'];
		} else {
			$classes = [];
		}

		if (is_array($input) && isset($input['functions']) && $this->isValidFunctions($input['functions'])) {
			$functions = $input['functions'];
		} else {
			$functions = [];
		}

		return [
			'classes' => $classes,
			'functions' => $functions
		];
	}

	private function isValidClasses($input)
	{
		// TODO: validate the classes
		return is_array($input);
	}

	private function isValidFunctions($input)
	{
		// TODO: validate the functions
		return is_array($input);
	}

	public function write(array $data)
	{
		$this->file->write($data);
	}
}
