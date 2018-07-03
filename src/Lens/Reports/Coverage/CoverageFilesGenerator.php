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

namespace _Lens\Lens\Reports\Coverage;

use _Lens\Lens\Reports\Coverage\Html5\CodePage;
use _Lens\Lens\Reports\Coverage\Html5\IndexPage;

class CoverageFilesGenerator
{
	/** @var array */
	private $files;

	public function __construct()
	{
		$this->files = [];
	}

	public function generate(array $baseComponents, array $contents)
	{
		$pageComponents = [];

		$this->getName($baseComponents, $pageComponents, $contents, $tested, $testable);

		return $this->files;
	}

	public function getName(array $baseComponents, array $pageComponents, array $pageContents, &$tested, &$testable)
	{
		$tested = 0;
		$testable = 0;
		$links = [];

		$isVisible = count($baseComponents) <= count($pageComponents);

		foreach ($pageContents as $childName => $childContents) {
			$childComponents = $pageComponents;
			$childComponents[] = $childName;

			if (isset($childContents['.name'])) {
				$this->getName($baseComponents, $childComponents, $childContents['.name'], $childTested, $childTestable);

				$tested += $childTested;
				$testable += $childTestable;

				$childDirectoryComponents = $this->getIndexDirectoryComponents($baseComponents, $childComponents);
				$childFileComponents = $this->getFileComponents($childDirectoryComponents);
				$childUrl = implode('/', array_slice($childFileComponents, -2));
				$links[] = [$childName, 'name', $childUrl, $childTested, $childTestable];
			}

			if ($isVisible) {
				$this->savePage('class', $baseComponents, $childComponents, $childContents, $tested, $testable, $links);
				$this->savePage('function', $baseComponents, $childComponents, $childContents, $tested, $testable, $links);
				$this->savePage('trait', $baseComponents, $childComponents, $childContents, $tested, $testable, $links);
			}
		}

		if ($isVisible) {
			$this->saveIndex($baseComponents, $pageComponents, $links);
		}
	}

	private function saveIndex(array $baseComponents, array $pageComponents, array $links)
	{
		$directoryComponents = $this->getIndexDirectoryComponents($baseComponents, $pageComponents);
		$fileComponents = $this->getFileComponents($directoryComponents);

		$page = new IndexPage($directoryComponents, $baseComponents, $pageComponents, $links);

		$this->write($fileComponents, (string)$page);
	}

	private function getFileComponents(array $directoryComponents)
	{
		$fileComponents = $directoryComponents;
		$fileComponents[] = 'index.html';

		return $fileComponents;
	}

	private function getIndexDirectoryComponents(array $baseComponents, array $pageComponents)
	{
		return array_slice($pageComponents, count($baseComponents));
	}

	private function savePage($type, array $baseComponents, array $pageComponents, array $data, &$tested, &$testable, array &$links)
	{
		$key = '.' . $type;

		if (!isset($data[$key])) {
			return;
		}

		$code = $data[$key]['code'];
		$coverage = $data[$key]['coverage'];

		$directoryComponents = $this->getPageDirectoryComponents($type, $baseComponents, $pageComponents);
		$fileComponents = $this->getFileComponents($directoryComponents);

		// Write the HTML
		$page = new CodePage($type, $directoryComponents, $baseComponents, $pageComponents, $code, $coverage);
		$this->write($fileComponents, (string)$page);

		// Update the coverage metrics
		$pageTested = count(array_filter($coverage));
		$pageTestable = count($coverage);

		$tested += $pageTested;
		$testable += $pageTestable;

		// Generate links
		$name = end($pageComponents);
		$url = implode('/', array_slice($fileComponents, -2));
		// TODO: create Element objects?
		$links[] = [$name, $type, $url, $pageTested, $pageTestable];
	}

	private function getPageDirectoryComponents($type, array $baseComponents, array $pageComponents)
	{
		$iLast = count($pageComponents) - 1;
		$pageComponents[$iLast] = $type . '-' . $pageComponents[$iLast];
		return array_slice($pageComponents, count($baseComponents));
	}

	private function write(array $components, $data)
	{
		$position = &$this->files;

		foreach ($components as $component) {
			$position = &$position[$component];
		}

		$position = $data;
	}
}
