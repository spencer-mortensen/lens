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

namespace Lens\Evaluator;

class Autoloader
{
	/** @var string */
	private $mockPrefix;

	/** @var integer */
	private $mockPrefixLength;

	/** @var MockBuilder */
	private $mockBuilder;

	public function __construct()
	{
		$this->mockPrefix = 'Lens\\Mock\\';
		$this->mockPrefixLength = strlen($this->mockPrefix);
		$this->mockBuilder = new MockBuilder();
	}

	public function register()
	{
		$autoloader = array($this, 'autoloader');

		spl_autoload_register($autoloader);
	}

	public function autoloader($class)
	{
		eval($this->getMockPhp($class));
	}

	private function getMockPhp($class)
	{
		$namespace = $this->getNamespace($class);

		if (strncmp($class, $this->mockPrefix, $this->mockPrefixLength) === 0) {
			$parentClass = substr($class, $this->mockPrefixLength);

			return $this->getMockClassPhp($namespace, $parentClass);
		}

		return $this->getMockFunctionsPhp($namespace);
	}

	private function getNamespace($class)
	{
		$slash = strrpos($class, '\\');

		if (!is_integer($slash)) {
			return null;
		}

		return substr($class, 0, $slash);
	}

	private function getMockClassPhp($childNamespace, $parentClass)
	{
		$namespacePhp = self::getNamespacePhp($childNamespace);
		$childClassPhp = $this->mockBuilder->getMockClassPhp($parentClass);

		return "{$namespacePhp}\n\n{$childClassPhp}";
	}

	private static function getNamespacePhp($namespace)
	{
		return "namespace {$namespace};";
	}

	public function getMockFunctionsPhp($namespace)
	{
		if ($namespace === null) {
			return null;
		}

		$sections = array(
			self::getNamespacePhp($namespace)
		);

		$functions = array(
			'fgets'
		);

		// $functions = self::getMockedFunctions();

		foreach ($functions as $function) {
			$mock = "{$namespace}\\{$function}";

			if (!function_exists($mock)) {
				$sections[] = $this->getMockFunctionPhp($function);
			}
		}

		return implode("\n\n", $sections);
	}

	private function getMockFunctionPhp($function)
	{
		$php = $this->mockBuilder->getMockFunctionPhp($function);

		return $php;
	}

	private static function getMockedFunctions()
	{
		return array(
			// Program execution functions
			'exec',
			'passthru',
			'proc_close',
			'proc_get_status',
			'proc_nice',
			'proc_open',
			'proc_terminate',
			'shell_exec',
			'system',

			// Filesystem functions
			'chgrp',
			'chmod',
			'clearstatcache',
			'copy',
			'disk_free_space',
			'disk_total_space',
			'diskfreespace',
			'fclose',
			'feof',
			'fflush',
			'fgetc',
			'fgetcsv',
			'fgets',
			'fgetss',
			'file_exists',
			'file_get_contents',
			'file_put_contents',
			'file',
			'fileatime',
			'filectime',
			'filegroup',
			'fileinode',
			'filemtime',
			'fileowner',
			'fileperms',
			'filesize',
			'filetype',
			'flock',
			'fopen',
			'fpassthru',
			'fputcsv',
			'fputs',
			'fread',
			'fscanf',
			'fseek',
			'fstat',
			'ftell',
			'ftruncate',
			'fwrite',
			'glob',
			'is_dir',
			'is_executable',
			'is_file',
			'is_link',
			'is_readable',
			'is_uploaded_file',
			'is_writable',
			'is_writeable',
			'lchgrp',
			'lchown',
			'link',
			'linkinfo',
			'lstat',
			'mkdir',
			'move_uploaded_file',
			'parse_ini_file',
			'pclose',
			'popen',
			'readfile',
			'readlink',
			'realpath_cache_get',
			'realpath_cache_size',
			'realpath',
			'rename',
			'rewind',
			'rmdir',
			// set_file_buffer
			'stat',
			'symlink',
			'tempnam',
			'tmpfile',
			'tmpfile',
			'touch',
			'umask',
			'unlink'
		);
	}
}
