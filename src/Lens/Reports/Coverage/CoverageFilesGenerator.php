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

namespace Lens_0_0_57\Lens\Reports\Coverage;

use Lens_0_0_57\Lens\Reports\Coverage\Html5\CodePage;
use Lens_0_0_57\Lens\Reports\Coverage\Html5\IndexPage;

class CoverageFilesGenerator
{
	/** @var array */
	private $files;

	public function __construct()
	{
		$this->files = [];
	}

	public function generate(array $baseAtoms, array $contents)
	{
		$pageAtoms = [];

		$this->getName($baseAtoms, $pageAtoms, $contents, $tested, $testable);

		return $this->files;
	}

	public function getName(array $baseAtoms, array $pageAtoms, array $pageContents, &$tested, &$testable)
	{
		$tested = 0;
		$testable = 0;
		$links = [];

		$isVisible = count($baseAtoms) <= count($pageAtoms);

		foreach ($pageContents as $childName => $childContents) {
			$childAtoms = $pageAtoms;
			$childAtoms[] = $childName;

			if (isset($childContents['.name'])) {
				$this->getName($baseAtoms, $childAtoms, $childContents['.name'], $childTested, $childTestable);

				$tested += $childTested;
				$testable += $childTestable;

				$childDirectoryAtoms = $this->getIndexDirectoryAtoms($baseAtoms, $childAtoms);
				$childFileAtoms = $this->getFileAtoms($childDirectoryAtoms);
				$childUrl = implode('/', array_slice($childFileAtoms, -2));
				$links[] = [$childName, 'name', $childUrl, $childTested, $childTestable];
			}

			if ($isVisible) {
				$this->savePage('class', $baseAtoms, $childAtoms, $childContents, $tested, $testable, $links);
				$this->savePage('function', $baseAtoms, $childAtoms, $childContents, $tested, $testable, $links);
				$this->savePage('trait', $baseAtoms, $childAtoms, $childContents, $tested, $testable, $links);
			}
		}

		if ($isVisible) {
			$this->saveIndex($baseAtoms, $pageAtoms, $links);
		}
	}

	private function saveIndex(array $baseAtoms, array $pageAtoms, array $links)
	{
		$directoryAtoms = $this->getIndexDirectoryAtoms($baseAtoms, $pageAtoms);
		$fileAtoms = $this->getFileAtoms($directoryAtoms);

		$page = new IndexPage($directoryAtoms, $baseAtoms, $pageAtoms, $links);

		$this->write($fileAtoms, (string)$page);
	}

	private function getFileAtoms(array $directoryAtoms)
	{
		$fileAtoms = $directoryAtoms;
		$fileAtoms[] = 'index.html';

		return $fileAtoms;
	}

	private function getIndexDirectoryAtoms(array $baseAtoms, array $pageAtoms)
	{
		return array_slice($pageAtoms, count($baseAtoms));
	}

	private function savePage($type, array $baseAtoms, array $pageAtoms, array $data, &$tested, &$testable, array &$links)
	{
		$key = '.' . $type;

		if (!isset($data[$key])) {
			return;
		}

		$code = $data[$key]['code'];
		$coverage = $data[$key]['coverage'];

		$directoryAtoms = $this->getPageDirectoryAtoms($type, $baseAtoms, $pageAtoms);
		$fileAtoms = $this->getFileAtoms($directoryAtoms);

		// Write the HTML
		$page = new CodePage($type, $directoryAtoms, $baseAtoms, $pageAtoms, $code, $coverage);
		$this->write($fileAtoms, (string)$page);

		// Update the coverage metrics
		$pageTested = count(array_filter($coverage));
		$pageTestable = count($coverage);

		$tested += $pageTested;
		$testable += $pageTestable;

		// Generate links
		$name = end($pageAtoms);
		$url = implode('/', array_slice($fileAtoms, -2));
		// TODO: create Element objects?
		$links[] = [$name, $type, $url, $pageTested, $pageTestable];
	}

	private function getPageDirectoryAtoms($type, array $baseAtoms, array $pageAtoms)
	{
		$iLast = count($pageAtoms) - 1;
		$pageAtoms[$iLast] = $type . '-' . $pageAtoms[$iLast];
		return array_slice($pageAtoms, count($baseAtoms));
	}

	private function write(array $atoms, $data)
	{
		$position = &$this->files;

		foreach ($atoms as $atom) {
			$position = &$position[$atom];
		}

		$position = $data;
	}
}
