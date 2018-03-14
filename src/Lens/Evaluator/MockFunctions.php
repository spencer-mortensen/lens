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

namespace Lens_0_0_56\Lens\Evaluator;

use Lens_0_0_56\Lens\Filesystem;
use Lens_0_0_56\Lens\Finder;
use Lens_0_0_56\SpencerMortensen\Paths\Paths;

class MockFunctions
{
	/** @var Filesystem */
	private $filesystem;

	/** @var MockBuilder */
	private $mockBuilder;

	/** @var string */
	private $path;

	public function __construct(Paths $paths, Filesystem $filesystem, $cache)
	{
		$path = $paths->join($cache, Finder::MOCKS, Finder::FUNCTIONS);

		$this->filesystem = $filesystem;
		$this->mockBuilder = new MockBuilder();
		$this->path = $path;
	}

	// TODO: What if some of these unsafe functions were disabled when Lens first built its cache--and the system administrator has just enabled those functions? What if new unsafe functions have appeared in a PHP update?
	// TODO: What if the user has created their own version of one of these unsafe functions--and the user version has a different function signature?
	// TODO: What if the user has created their own unsafe version of a formerly-safe global function?
	public function declareMockFunctions($namespace)
	{
		if ($namespace === null) {
			return;
		}

		$namespacePhp = $this->getNamespacePhp($namespace);
		$mocksPhp = $this->getFunctionMocksPhp();
		$php = "{$namespacePhp}\n\n{$mocksPhp}";

		eval($php);
	}

	private function getNamespacePhp($namespace)
	{
		return "namespace {$namespace};";
	}

	private function getFunctionMocksPhp()
	{
		$php = $this->filesystem->read($this->path);

		if ($php === null) {
			$php = $this->getFunctionMocksFromPhp();

			$this->filesystem->write($this->path, "<?php\n\n{$php}\n");
		} else {
			$php = substr($php, 7, -1);
		}

		return $php;
	}

	private function getFunctionMocksFromPhp()
	{
		$sections = array();

		$functions = PhpExternal::getFunctions();

		foreach ($functions as $function) {
			if (function_exists($function)) {
				$sections[] = $this->mockBuilder->getMockFunctionPhp($function);
			}
		}

		return implode("\n\n", $sections);
	}
}
