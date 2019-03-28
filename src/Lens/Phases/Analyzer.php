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

namespace _Lens\Lens\Phases;

use _Lens\Lens\Files\MetaFile;
use _Lens\Lens\Phases\Code\CodePhase;
use _Lens\Lens\Phases\Tests\TestsPhase;

class Analyzer
{
	public function analyze(Finder $finder)
	{
		$metaFile = $this->getMetaFile($finder);

		$meta = $metaFile->read();

		$codePhase = new CodePhase();
		$codePhase->execute($finder, $meta);

		// TODO: generate code coverage information

		exit;

		// TODO: pass the "Finder" to the "TestsPhase" (instead of all of these little paths)
		$tests = $finder->getTestsPath();
		$cache = $finder->getCachePath();

		$testsPhase = new TestsPhase();
		$testsPhase->cache($tests, $cache);

		$metaFile->write($meta);
	}

	private function getMetaFile(Finder $finder)
	{
		$cachePath = $finder->getCachePath();
		$metaPath = $cachePath->add('meta.json');
		return new MetaFile($metaPath);
	}
}
