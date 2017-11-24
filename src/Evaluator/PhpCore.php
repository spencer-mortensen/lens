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

class PhpCore
{
	private static $externalFunctions = array(
		// TODO: Finish the date/time functions <http://php.net/manual/en/ref.datetime.php>
		// Time functions
		'microtime' => 'microtime',
		'time' => 'time',

		// Program execution functions
		'exec' => 'exec',
		'passthru' => 'passthru',
		'proc_close' => 'proc_close',
		'proc_get_status' => 'proc_get_status',
		'proc_nice' => 'proc_nice',
		'proc_open' => 'proc_open',
		'proc_terminate' => 'proc_terminate',
		'shell_exec' => 'shell_exec',
		'system' => 'system',

		// Directory functions
		'chdir' => 'chdir',
		'chroot' => 'chroot',
		'closedir' => 'closedir',
		'dir' => 'dir',
		'getcwd' => 'getcwd',
		'opendir' => 'opendir',
		'readdir' => 'readdir',
		'rewinddir' => 'rewinddir',
		'scandir' => 'scandir',

		// Filesystem functions
		'chgrp' => 'chgrp',
		'chmod' => 'chmod',
		'clearstatcache' => 'clearstatcache',
		'copy' => 'copy',
		'disk_free_space' => 'disk_free_space',
		'disk_total_space' => 'disk_total_space',
		'diskfreespace' => 'diskfreespace',
		'fclose' => 'fclose',
		'feof' => 'feof',
		'fflush' => 'fflush',
		'fgetc' => 'fgetc',
		'fgetcsv' => 'fgetcsv',
		'fgets' => 'fgets',
		'fgetss' => 'fgetss',
		'file_exists' => 'file_exists',
		'file_get_contents' => 'file_get_contents',
		'file_put_contents' => 'file_put_contents',
		'file' => 'file',
		'fileatime' => 'fileatime',
		'filectime' => 'filectime',
		'filegroup' => 'filegroup',
		'fileinode' => 'fileinode',
		'filemtime' => 'filemtime',
		'fileowner' => 'fileowner',
		'fileperms' => 'fileperms',
		'filesize' => 'filesize',
		'filetype' => 'filetype',
		'flock' => 'flock',
		'fopen' => 'fopen',
		'fpassthru' => 'fpassthru',
		'fputcsv' => 'fputcsv',
		'fputs' => 'fputs',
		'fread' => 'fread',
		'fscanf' => 'fscanf',
		'fseek' => 'fseek',
		'fstat' => 'fstat',
		'ftell' => 'ftell',
		'ftruncate' => 'ftruncate',
		'fwrite' => 'fwrite',
		'glob' => 'glob',
		'is_dir' => 'is_dir',
		'is_executable' => 'is_executable',
		'is_file' => 'is_file',
		'is_link' => 'is_link',
		'is_readable' => 'is_readable',
		'is_uploaded_file' => 'is_uploaded_file',
		'is_writable' => 'is_writable',
		'is_writeable' => 'is_writeable',
		'lchgrp' => 'lchgrp',
		'lchown' => 'lchown',
		'link' => 'link',
		'linkinfo' => 'linkinfo',
		'lstat' => 'lstat',
		'mkdir' => 'mkdir',
		'move_uploaded_file' => 'move_uploaded_file',
		'parse_ini_file' => 'parse_ini_file',
		'pclose' => 'pclose',
		'popen' => 'popen',
		'readfile' => 'readfile',
		'readlink' => 'readlink',
		'realpath_cache_get' => 'realpath_cache_get',
		'realpath_cache_size' => 'realpath_cache_size',
		'realpath' => 'realpath',
		'rename' => 'rename',
		'rewind' => 'rewind',
		'rmdir' => 'rmdir',
		// set_file_buffer
		'stat' => 'stat',
		'symlink' => 'symlink',
		'tempnam' => 'tempnam',
		'tmpfile' => 'tmpfile',
		'touch' => 'touch',
		'umask' => 'umask',
		'unlink' => 'unlink'
	);

	public static function getExternalFunctions()
	{
		return self::$externalFunctions;
	}

	public static function isExternalFunction($function)
	{
		return isset(self::$externalFunctions[$function]);
	}
}
