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

namespace Lens\Updates;

use Exception;
use Lens\Filesystem;
use Lens\Settings;
use SpencerMortensen\Paths\Paths;

class Updater
{
	/** @var Paths */
	private $paths;

	/** @var Filesystem */
	private $filesystem;

	/** @var OldFinder */
	private $old;

	/** @var NewFinder */
	private $new;

	/** @var string|null */
	private $autoload;

	public function __construct(Paths $paths, Filesystem $filesystem)
	{
		$this->paths = $paths;
		$this->filesystem = $filesystem;

		$this->old = new OldFinder($paths, $filesystem);
		$this->new = new NewFinder($paths, $filesystem);
	}

	public function update(array &$paths)
	{
		try {
			$this->old->find($paths);
		} catch (Exception $exception) {
			return;
		}

		$project = $this->old->getProject();

		$this->new->find($project);

		if (!$this->isAlreadyUpdated()) {
			$this->moveFiles($autoload);
			$this->updateSettings($autoload);
			$this->updatePaths($paths);
		}
	}

	private function isAlreadyUpdated()
	{
		return $this->old->getLens() === $this->new->getLens();
	}

	private function moveFiles(&$autoload)
	{
		$this->filesystem->createEmptyDirectory($this->new->getLens());

		$this->renameDirectory($this->old->getCoverage(), $this->new->getCoverage());
		$this->renameDirectory($this->old->getTests(), $this->new->getTests());
		$this->renameFile($this->old->getSettings(), $this->new->getSettings());
		$this->renameAutoload($autoload);

		$this->filesystem->deleteEmptyDirectory($this->old->getLens());
	}

	private function renameDirectory($old, $new)
	{
		return $this->filesystem->isDirectory($old) &&
			$this->filesystem->rename($old, $new);
	}

	private function renameFile($old, $new)
	{
		return $this->filesystem->isFile($old) &&
			$this->filesystem->rename($old, $new);
	}

	private function renameAutoload(&$autoload)
	{
		$oldAutoload = $this->old->getAutoload();
		$autoload = $oldAutoload;

		if ($oldAutoload === null) {
			return;
		}

		if ($this->old->getLens() === $this->old->getProject()) {
			return;
		}

		$relativePath = $this->paths->getRelativePath($this->old->getLens(), $oldAutoload);
		$data = $this->paths->deserialize($relativePath);
		$atoms = $data->getAtoms();

		if (count($atoms) !== 1) {
			return;
		}

		$newAutoload = $this->paths->join($this->new->getLens(), $relativePath);
		$autoload = $newAutoload;

		$this->renameFile($oldAutoload, $newAutoload);
	}

	private function updateSettings($autoload)
	{
		$settingsPath = $this->new->getSettings();
		$settings = new Settings($this->paths, $this->filesystem, $settingsPath);

		$project = $this->new->getProject();

		$src = $this->old->getSrc();
		$srcValue = $this->paths->getRelativePath($project, $src);
		$settings->set('src', $srcValue);

		$autoloadValue = $this->paths->getRelativePath($project, $autoload);
		$settings->set('autoload', $autoloadValue);
	}

	private function updatePaths(array &$paths)
	{
		$oldTestsPath = $this->old->getTests();
		$newTestsPath = $this->new->getTests();

		foreach ($paths as &$path) {
			$relativePath = $this->paths->getRelativePath($oldTestsPath, $path);
			$path = $this->paths->join($newTestsPath, $relativePath);
		}
	}
}
