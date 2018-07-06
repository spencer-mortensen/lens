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

use _Lens\Lens\Paragraph;
use _Lens\Lens\Xdebug;
use _Lens\SpencerMortensen\Filesystem\File;

class StatementsExtractor
{
	/** @var Autoloader */
	private $autoloader;

	/** @var Xdebug */
	private $xdebug;

	public function __construct(Autoloader $autoloader)
	{
		$this->autoloader = $autoloader;
		$this->xdebug = new Xdebug(false);
	}

	public function getLineNumbers(File $file)
	{
		$contents = $file->read();

		if ($contents === null) {
			return null;
		}

		$contents = Paragraph::standardizeNewlines($contents);
		$lines = explode("\n", $contents);

		$path = (string)$file->getPath();
		$coverage = $this->getCoverage($path);

		return $this->getStatementLineNumbers($coverage[$path], $lines);
	}

	private function getCoverage($path)
	{
		$this->autoloader->enable();

		$this->xdebug->start();
		require_once $path;
		$this->xdebug->stop();

		return $this->xdebug->getCoverage();
	}

	private function getStatementLineNumbers(array $coverage, array $lines)
	{
		$output = [];

		foreach ($coverage as $lineNumber) {
			$lineText = &$lines[$lineNumber];

			if ($this->isStatement($lineText)) {
				$output[] = $lineNumber;
			}
		}

		return $output;
	}

	private function isStatement($code)
	{
		$code = trim($code);

		if (strlen(trim($code, '{}')) === 0) {
			return false;
		}

		// TODO: handle "public function add(...$arguments)"
		// TODO: handle "use Flier;" statements (these are always executed)
		// TODO: handle "abstract" and "final" classes (e.g. "abstract class Path")
		if (substr($code, 0, 6) === 'class ') {
			return false;
		}

		if (substr($code, 0, 9) === 'function ') {
			return false;
		}

		if (substr($code, 0, 6) === 'trait ') {
			return false;
		}

		return true;
	}
}
