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

	public function browse($directory)
	{
		$this->readDirectory($directory, '');

		return $this->tests;
	}

	private function readDirectory($absolutePath, $relativePath)
	{
		$files = @scandir($absolutePath, SCANDIR_SORT_NONE);

		if ($files === false) {
			throw Exception::invalidTestsDirectory($absolutePath);
		}

		foreach ($files as $file) {
			if (($file === '.') || ($file === '..')) {
				continue;
			}

			$childAbsolutePath = "{$absolutePath}/{$file}";
			$childRelativePath = ltrim("{$relativePath}/{$file}", '/');

			if (is_dir($childAbsolutePath)) {
				$this->readDirectory($childAbsolutePath, $childRelativePath);
			} elseif (is_file($childAbsolutePath) && (substr($file, -4) === '.php')) {
				$this->readFile($childAbsolutePath, $childRelativePath);
			}
		}
	}

	private function readFile($absolutePath, $relativePath)
	{
		$contents = @file_get_contents($absolutePath);

		if (!is_string($contents)) {
			throw Exception::invalidTestFile($absolutePath);
		}

		// TODO: provide more useful exceptions:
		if (!$this->parser->parse($contents, $fixture, $tests)) {
			throw Exception::invalidTestFile($absolutePath);
		}

		$this->tests[] = array(
			'file' => $relativePath,
			'fixture' => $fixture,
			'tests' => $tests
		);
	}
}
