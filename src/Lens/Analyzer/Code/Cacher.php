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

namespace _Lens\Lens\Analyzer\Code;

use _Lens\Lens\Analyzer\Code\Parser\Parser;
use _Lens\Lens\Analyzer\Watcher;
use _Lens\Lens\JsonFile;
use _Lens\Lens\Php\Namespacing;
use _Lens\SpencerMortensen\Filesystem\Directory;
use _Lens\SpencerMortensen\Filesystem\File;
use _Lens\SpencerMortensen\Filesystem\Path;

class Cacher
{
	/** @var Parser */
	private $parser;

	/** @var Namespacing */
	private $namespacing;

	/** @var Path */
	private $functionsPath;

	/** @var Path */
	private $classesPath;

	/** @var Path */
	private $interfacesPath;

	/** @var Path */
	private $traitsPath;

	public function cache(Path $projectPath, Path $srcPath, Path $cachePath)
	{
		$srcRelativePath = $projectPath->getRelativePath($srcPath);
		$watcherPath = $cachePath->add('sources', $srcRelativePath);
		$codePath = $cachePath->add('code');

		$this->functionsPath = $codePath->add('functions', 'live');
		$this->classesPath = $codePath->add('classes', 'live');
		$this->interfacesPath = $codePath->add('interfaces');
		$this->traitsPath = $codePath->add('traits', 'live');

		$changes = $this->getChanges($srcPath, $watcherPath);

		$this->updateDirectory($srcPath, $watcherPath, $codePath, $changes);
	}

	private function getChanges(Path $srcPath, Path $watcherPath)
	{
		$srcDirectory = new Directory($srcPath);
		$watcherFilePath = $watcherPath->add('modified.json');
		$watcherFile = new JsonFile($watcherFilePath);

		$watcher = new Watcher();
		return $watcher->watch($srcDirectory, $watcherFile);
	}

	private function updateDirectory(Path $live, Path $cached, Path $code, array $changes)
	{
		foreach ($changes as $name => $type) {
			$liveChild = $live->add($name);

			if (is_array($type)) {
				$this->updateDirectory($liveChild, $cached, $code, $type);
			} else {
				$this->updateFile($liveChild, $cached, $code, $type);
			}
		}
	}

	private function updateFile(Path $live, Path $cached, Path $code, $type)
	{
		if ($this->parser === null) {
			$this->parser = new Parser();
		}

		if ($this->namespacing === null) {
			$this->namespacing = new Namespacing();
		}

		$file = new File($live);
		$php = $file->read();
		$sections = $this->parser->parse($php);

		foreach ($sections as $section) {
			$this->writeSectionCode($section);
		}
	}

	private function writeSectionCode(array $section)
	{
		$namespace = $section['namespace'];
		$uses = $section['uses'];
		$definitions = $section['definitions'];

		$this->namespacing->setContext($namespace, $uses);

		foreach ($definitions['functions'] as $name => $php) {
			$fullName = $this->namespacing->getAbsoluteFunction($name);
			$this->writeSourceFile($this->functionsPath, $fullName, $php);
		}

		foreach ($definitions['classes'] as $name => $php) {
			$fullName = $this->namespacing->getAbsoluteClass($name);
			$this->writeSourceFile($this->classesPath, $fullName, $php);
		}

		foreach ($definitions['interfaces'] as $name => $php) {
			$fullName = $this->namespacing->getAbsoluteClass($name);
			$this->writeSourceFile($this->interfacesPath, $fullName, $php);
		}

		foreach ($definitions['traits'] as $name => $php) {
			$fullName = $this->namespacing->getAbsoluteClass($name);
			$this->writeSourceFile($this->traitsPath, $fullName, $php);
		}
	}

	private function writeSourceFile(Path $basePath, $fullName, $php)
	{
		$relativeFilePath = implode('/', explode('\\', $fullName)) . '.php';
		$filePath = $basePath->add($relativeFilePath);

		$file = new File($filePath);
		$file->write("<?php\n\n{$php}\n");
	}
}
