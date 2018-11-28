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

namespace _Lens\Lens\Tests;

use _Lens\Lens\Exceptions\ParsingException;
use _Lens\Lens\LensException;
use _Lens\Lens\Paragraph;
use _Lens\Lens\Tests\Parse\Parser;
use _Lens\SpencerMortensen\Filesystem\Directory;
use _Lens\SpencerMortensen\Filesystem\File;
use _Lens\SpencerMortensen\Filesystem\Filesystem;
use _Lens\SpencerMortensen\Filesystem\Path;

class GetSuites
{
	/** @var Path */
	private $tests;

	/** @var Filesystem */
	private $filesystem;

	/** @var Parser */
	private $parser;

	public function __construct(Path $tests)
	{
		$this->tests = $tests;
		$this->filesystem = new Filesystem();
		$this->parser = new Parser();
	}

	public function getSuites(array $paths)
	{
		$suites = [];

		// TODO: get suites from cache (watching for changes to the original tests files)
		$children = $this->getChildren($paths);

		$this->readChildren($children, $suites);

		// TODO: let the user provide the project name in the configuration file:
		return [
			'name' => 'Lens',
			'suites' => $suites
		];
	}

	private function getChildren(array $paths)
	{
		$children = [];

		foreach ($paths as $path) {
			$children[] = $this->getChild($path);
		}

		return $children;
	}

	private function getChild(Path $path)
	{
		if ($this->filesystem->isDirectory($path)) {
			return new Directory($path);
		}

		return new File($path);
	}

	private function readChildren(array $children, array &$files)
	{
		foreach ($children as $child) {
			if ($child instanceof Directory) {
				$this->readChildren($child->read(), $files);
			} else {
				$this->readFile($child, $files);
			}
		}
	}

	private function readFile(File $file, array &$files)
	{
		$absolutePath = $file->getPath();

		if ($this->isTestsFile($absolutePath)) {
			$relativePath = $this->tests->getRelativePath($absolutePath);

			$php = $file->read();
			$php = Paragraph::standardizeNewlines($php);

			try {
				$files[(string)$relativePath] = $this->parser->parse($php);
			} catch (ParsingException $exception) {
				throw LensException::invalidTestsFileSyntax($absolutePath, $exception);
			}
		}
	}

	// TODO: this is duplicated elsewhere
	private function isTestsFile($path)
	{
		return substr($path, -4) === '.php';
	}
}
