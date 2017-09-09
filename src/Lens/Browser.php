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

class Browser
{
	/** @var Filesystem */
	private $filesystem;

	/** @var Parser */
	private $parser;

	/** @var array */
	private $suites;

	/** @var integer */
	private $testsPrefixLength;

	public function __construct(Filesystem $filesystem, Parser $parser)
	{
		$this->filesystem = $filesystem;
		$this->parser = $parser;
		$this->suites = array();
	}

	/*
	suites: {
		<file>: <suite>
	}

	suite: {
		"fixture": "..."
		"tests": {
			<line>: <test>
		}
	}

	test: {
		"subject": "...",
		"cases": {
			<line>: <case>
		}
	}

	case: {
		"input": "...",
		"output": "...",
		"result": <result>
	}

	result: {
		"fixture": <state>,
		"expected": <state>|null,
		"actual": <state>|null
	}

	state: {...}
	*/
	public function browse($testsDirectory, array $paths)
	{
		$testsPrefix = $testsDirectory . '/';
		$testsPrefixLength = strlen($testsPrefix);

		$this->testsPrefixLength = $testsPrefixLength;

		foreach ($paths as $path) {
			// TODO: explain that this path is invalid because it lies outside the tests directory:
			if (strncmp($path . '/', $testsPrefix, $testsPrefixLength) !== 0) {
				throw Exception::invalidTestsPath($path);
			}

			$contents = $this->filesystem->read($path);

			if ($contents === null) {
				throw Exception::invalidTestsPath($path);
			}

			$this->get($path, $contents);
		}

		return $this->suites;
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
		if (!$this->isTestsFile($path)) {
			return;
		}

		$suite = $this->parser->parse($contents);

		if ($suite === null) {
			throw Exception::invalidTestsFile($path);
		}

		$relativePath = $this->getRelativePath($path);

		$this->suites[$relativePath] = $suite;
	}

	private function isTestsFile($path)
	{
		return substr($path, -4) === '.php';
	}

	private function getRelativePath($path)
	{
		return substr($path, $this->testsPrefixLength);
	}
}
