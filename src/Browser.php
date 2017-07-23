<?php

/**
 * Copyright (C) 2017 Spencer Mortensen
 *
 * This file is part of testphp.
 *
 * Testphp is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Testphp is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with testphp. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Spencer Mortensen <spencer@testphp.org>
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL-3.0
 * @copyright 2017 Spencer Mortensen
 */

namespace TestPhp;

class Browser
{
	/** @var Filesystem */
	private $filesystem;

	/** @var Parser */
	private $parser;

	/** @var array */
	private $tests;

	public function __construct(Filesystem $filesystem, Parser $parser)
	{
		$this->filesystem = $filesystem;
		$this->parser = $parser;
		$this->tests = array();
	}

	public function browse(array $paths)
	{
		foreach ($paths as $path) {
			$contents = $this->filesystem->read($path);

			if ($contents === null) {
				throw Exception::invalidTestsPath($path);
			}

			$this->get($path, $contents);
		}

		return $this->tests;
	}

	private function get($path, $contents)
	{
		if (is_array($contents)) {
			$this->getDirectory($path, $contents);
		} else {
			$this->getFile($path, $contents);
		}
	}

	private function getDirectory($path, array $contents)
	{
		foreach ($contents as $childName => $childContents) {
			$childPath = "{$path}/{$childName}";

			$this->get($childPath, $childContents);
		}
	}

	private function getFile($path, $contents)
	{
		// TODO: provide more useful exceptions:
		if (!$this->parser->parse($contents, $fixture, $tests)) {
			throw Exception::invalidTestsFile($path);
		}

		$this->tests[] = array(
			'file' => $path, // TODO: this was a relative path
			'fixture' => $fixture,
			'tests' => $tests
		);
	}
}
