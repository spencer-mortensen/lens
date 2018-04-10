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
use Lens_0_0_56\Lens\Php\Code;
use Lens_0_0_56\Lens\Php\ClassParser;
use Lens_0_0_56\Lens\Php\FileParser;
use Lens_0_0_56\SpencerMortensen\Parser\ParserException;
use Lens_0_0_56\SpencerMortensen\Paths\Paths;
use ReflectionClass;
use ReflectionFunction;

class LiveBuilder
{
	/** @var Paths */
	private $paths;

	/** @var Filesystem */
	private $filesystem;

	/** @var string */
	private $cache;

	public function __construct($cache)
	{
		// TODO: dependency injection
		$this->paths = Paths::getPlatformPaths();
		$this->filesystem = new Filesystem();
		$this->cache = $cache;
	}

	public function getClassPhp($class)
	{
		$reflection = new ReflectionClass($class);
		$filePhp = $this->getCode($reflection);
		$classPhp = $this->getDefinitionPhp($reflection, $filePhp);

		$namespace = $reflection->getNamespaceName();
		$uses = $this->getUses($filePhp);

		$calls = $this->getCalls($classPhp);

		// TODO: interpret "self" and "parent"
		// TODO: translate these calls into file dependencies
		// TODO: modify the source code in some cases (e.g. "\time()")
		echo "calls: ", json_encode($calls), "\n";

		$contextPhp = Code::getContextPhp($namespace, $uses);
		$requirePhp = $this->getGlobalFunctionsPhp($namespace);

		return Code::getPhp($contextPhp, $requirePhp, $classPhp);
	}

	/**
	 * @param ReflectionClass|ReflectionFunction $reflection
	 * @return string
	 */
	private function getCode($reflection)
	{
		$file = $reflection->getFileName();
		return $this->filesystem->read($file);
	}

	private function getUses($filePhp)
	{
		$parser = new FileParser();
		return $parser->parse($filePhp);
	}

	private function getCalls($classPhp)
	{
		try {
			$parser = new ClassParser();
			return $parser->parse($classPhp);
		} catch (ParserException $exception) {
			// TODO:
			echo "Exception\n";
			return array();
		}
	}

	/**
	 * @param ReflectionClass|ReflectionFunction $reflection
	 * @param string $filePhp
	 * @return string
	 */
	private function getHeaderPhp($reflection, $filePhp)
	{
		// TODO
		return null;
	}

	private function getGlobalFunctionsPhp($namespace)
	{
		$parts = explode('\\', $namespace);
		$relativeDirectory = $this->paths->join($parts);

		// TODO: this path is repeated elsewhere
		$relativePath = $this->paths->join('functions', 'mock', $relativeDirectory, '.globals.php');
		$relativePathPhp = Code::getValuePhp(DIRECTORY_SEPARATOR . $relativePath);
		$filePhp = "LENS_CACHE_DIRECTORY . {$relativePathPhp}";

		return Code::getRequireOncePhp($filePhp);
	}

	/**
	 * @param ReflectionClass|ReflectionFunction $reflection
	 * @param string $code
	 * @return string
	 */
	private function getDefinitionPhp($reflection, $code)
	{
		$pattern = self::getPattern('\\r?\\n');
		$lines = preg_split($pattern, $code);

		$begin = $reflection->getStartLine() - 1;
		$length = $reflection->getEndLine() - $begin;

		$lines = array_slice($lines, $begin, $length);
		return implode("\n", $lines);
	}

	public function getFunctionPhp($function)
	{
		$reflection = new ReflectionFunction($function);
		$code = $this->getCode($reflection);

		$headerPhp = $this->getHeaderPhp($reflection, $code);
		$functionPhp = $this->getDefinitionPhp($reflection, $code);

		return Code::getPhp($headerPhp, $functionPhp);
	}

	// TODO: use the regular expressions class:
	private static function getPattern($expression, $flags = '')
	{
		$delimiter = "\x03";

		return $delimiter . $expression . $delimiter . $flags . 'XDs';
	}
}
