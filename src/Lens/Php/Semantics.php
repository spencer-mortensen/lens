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

namespace Lens_0_0_57\Lens\Php;

class Semantics
{
	public static function isKeyword($name)
	{
		return isset(self::$keywords[$name]);
	}

	public static function isClassIdentifier($name)
	{
		return isset(self::$classIdentifiers[$name]);
	}

	public static function isPhpFunction($name)
	{
		if (self::$phpFunctions === null) {
			$names = get_defined_functions()['internal'];
			self::$phpFunctions = array_combine($names, $names);
		}

		return isset(self::$phpFunctions[$name]);
	}

	public static function getUnsafeFunctions()
	{
		return self::$unsafeFunctions;
	}

	public static function isUnsafeFunction($name)
	{
		return isset(self::$unsafeFunctions[$name]);
	}

	public static function getUnsafeClasses()
	{
		return self::$unsafeClasses;
	}

	public static function isUnsafeClass($name)
	{
		return isset(self::$unsafeClasses[$name]);
	}

	private static $classIdentifiers = [
		'parent' => 'parent',
		'self' => 'self',
		'static' => 'static'
	];

	private static $keywords = [
		'__halt_compiler' => '__halt_compiler',
		'abstract' => 'abstract',
		'and' => 'and',
		'array' => 'array',
		'as' => 'as',
		'break' => 'break',
		'callable' => 'callable',
		'case' => 'case',
		'catch' => 'catch',
		'class' => 'class',
		'clone' => 'clone',
		'const' => 'const',
		'continue' => 'continue',
		'declare' => 'declare',
		'default' => 'default',
		'die' => 'die',
		'do' => 'do',
		'echo' => 'echo',
		'else' => 'else',
		'elseif' => 'elseif',
		'empty' => 'empty',
		'enddeclare' => 'enddeclare',
		'endfor' => 'endfor',
		'endforeach' => 'endforeach',
		'endif' => 'endif',
		'endswitch' => 'endswitch',
		'endwhile' => 'endwhile',
		'eval' => 'eval',
		'exit' => 'exit',
		'extends' => 'extends',
		'final' => 'final',
		'finally' => 'finally',
		'for' => 'for',
		'foreach' => 'foreach',
		'function' => 'function',
		'global' => 'global',
		'goto' => 'goto',
		'if' => 'if',
		'implements' => 'implements',
		'include' => 'include',
		'include_once' => 'include_once',
		'instanceof' => 'instanceof',
		'insteadof' => 'insteadof',
		'interface' => 'interface',
		'isset' => 'isset',
		'list' => 'list',
		'namespace' => 'namespace',
		'new' => 'new',
		'or' => 'or',
		'print' => 'print',
		'private' => 'private',
		'protected' => 'protected',
		'public' => 'public',
		'require' => 'require',
		'require_once' => 'require_once',
		'return' => 'return',
		'static' => 'static',
		'switch' => 'switch',
		'throw' => 'throw',
		'trait' => 'trait',
		'try' => 'try',
		'unset' => 'unset',
		'use' => 'use',
		'var' => 'var',
		'while' => 'while',
		'xor' => 'xor',
		'yield' => 'yield'
	];

	private static $unsafeFunctions = [
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
		'unlink' => 'unlink',

		// Network functions
		'gethostname' => 'gethostname',
		'gethostbyname' => 'gethostbyname',
		'gethostbyaddr' => 'gethostbyaddr',
		'php_uname' => 'php_uname',

		// Options/Info Functions
		'getmygid' => 'getmygid',
		'getmypid' => 'getmypid',
		'getmyuid' => 'getmyuid',
		'get_current_user' => 'get_current_user',
		'getmyinode' => 'getmyinode',
		'getlastmod' => 'getlastmod',

		// Stream functions
		// 'set_socket_blocking' => 'set_socket_blocking', # PHP < 7.0.0
		'stream_bucket_append' => 'stream_bucket_append',
		'stream_bucket_make_writeable' => 'stream_bucket_make_writeable',
		'stream_bucket_new' => 'stream_bucket_new',
		'stream_bucket_prepend' => 'stream_bucket_prepend',
		'stream_context_create' => 'stream_context_create',
		'stream_context_get_default' => 'stream_context_get_default',
		'stream_context_get_options' => 'stream_context_get_options',
		'stream_context_get_params' => 'stream_context_get_params',
		'stream_context_set_default' => 'stream_context_set_default',
		'stream_context_set_option' => 'stream_context_set_option',
		'stream_context_set_params' => 'stream_context_set_params',
		'stream_copy_to_stream' => 'stream_copy_to_stream',
		// 'stream_encoding' => 'stream_encoding', # Unknown
		'stream_filter_append' => 'stream_filter_append',
		'stream_filter_prepend' => 'stream_filter_prepend',
		'stream_filter_register' => 'stream_filter_register',
		'stream_filter_remove' => 'stream_filter_remove',
		'stream_get_contents' => 'stream_get_contents',
		'stream_get_filters' => 'stream_get_filters',
		'stream_get_line' => 'stream_get_line',
		'stream_get_meta_data' => 'stream_get_meta_data',
		'stream_get_transports' => 'stream_get_transports',
		'stream_get_wrappers' => 'stream_get_wrappers',
		'stream_is_local' => 'stream_is_local',
		// 'stream_isatty' => 'stream_isatty', # PHP >= 7.2.0
		// 'stream_notification_callback' => 'stream_notification_callback', # Unknown
		'stream_register_wrapper' => 'stream_register_wrapper',
		'stream_resolve_include_path' => 'stream_resolve_include_path',
		'stream_select' => 'stream_select',
		'stream_set_blocking' => 'stream_set_blocking',
		'stream_set_chunk_size' => 'stream_set_chunk_size',
		'stream_set_read_buffer' => 'stream_set_read_buffer',
		'stream_set_timeout' => 'stream_set_timeout',
		'stream_set_write_buffer' => 'stream_set_write_buffer',
		'stream_socket_accept' => 'stream_socket_accept',
		'stream_socket_client' => 'stream_socket_client',
		'stream_socket_enable_crypto' => 'stream_socket_enable_crypto',
		'stream_socket_get_name' => 'stream_socket_get_name',
		'stream_socket_pair' => 'stream_socket_pair',
		'stream_socket_recvfrom' => 'stream_socket_recvfrom',
		'stream_socket_sendto' => 'stream_socket_sendto',
		'stream_socket_server' => 'stream_socket_server',
		'stream_socket_shutdown' => 'stream_socket_shutdown',
		'stream_supports_lock' => 'stream_supports_lock',
		'stream_wrapper_register' => 'stream_wrapper_register',
		'stream_wrapper_restore' => 'stream_wrapper_restore',
		'stream_wrapper_unregister' => 'stream_wrapper_unregister',

		// Miscellaneous
		'sleep' => 'sleep',
		'time_nanosleep' => 'time_nanosleep',
		'usleep' => 'usleep'
	];

	// TODO: Finish this list:
	private static $unsafeClasses = [
		'DateTime' => 'DateTime',
		'PDO' => 'PDO',
		'PDOStatement' => 'PDOStatement'
	];

	private static $phpFunctions;
}
