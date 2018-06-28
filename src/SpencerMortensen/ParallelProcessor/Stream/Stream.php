<?php

/**
 * Copyright (C) 2017 Spencer Mortensen
 *
 * This file is part of parallel-processor.
 *
 * Parallel-processor is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Parallel-processor is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with parallel-processor. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Spencer Mortensen <spencer@lens.guide>
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL-3.0
 * @copyright 2017 Spencer Mortensen
 */

namespace _Lens\SpencerMortensen\ParallelProcessor\Stream;

use Error;
use Exception;
use _Lens\SpencerMortensen\Exceptions\Exceptions;
use _Lens\SpencerMortensen\ParallelProcessor\Stream\Exceptions\CloseException;
use _Lens\SpencerMortensen\ParallelProcessor\Stream\Exceptions\StreamException;
use _Lens\SpencerMortensen\ParallelProcessor\Stream\Exceptions\ReadException;
use _Lens\SpencerMortensen\ParallelProcessor\Stream\Exceptions\ReadIncompleteException;
use _Lens\SpencerMortensen\ParallelProcessor\Stream\Exceptions\WriteException;
use _Lens\SpencerMortensen\ParallelProcessor\Stream\Exceptions\WriteIncompleteException;

class Stream
{
	/** @var integer */
	private static $CHUNK_SIZE = 8192;

	/** @var mixed */
	private $resource;

	public function __construct($resource)
	{
		$this->resource = $resource;
	}

	public function read()
	{
		if (!is_resource($this->resource)) {
			throw new StreamException($this->resource);
		}

		try {
			Exceptions::on();
			return self::readChunks($this->resource);
		} catch (ReadIncompleteException $exception) {
			throw $exception;
		} catch (Exception $exception) {
			throw new ReadException($exception);
		} catch (Error $error) {
			throw new ReadException($error);
		} finally {
			Exceptions::off();
		}
	}

	private static function readChunks($resource)
	{
		for ($contents = ''; !feof($resource); $contents .= $chunk) {
			$chunk = fread($resource, self::$CHUNK_SIZE);

			if ($chunk === false) {
				$bytesRead = strlen($contents);
				throw new ReadIncompleteException($bytesRead);
			}
		}

		return $contents;
	}

	public function write($contents)
	{
		if (!is_resource($this->resource)) {
			throw new StreamException($this->resource);
		}

		try {
			Exceptions::on();
			$bytesWritten = fwrite($this->resource, $contents);
		} catch (Exception $exception) {
			throw new WriteException($exception);
		} catch (Error $error) {
			throw new WriteException($error);
		} finally {
			Exceptions::off();
		}

		$bytesTotal = strlen($contents);

		if ($bytesWritten !== $bytesTotal) {
			throw new WriteIncompleteException($bytesWritten, $bytesTotal);
		}

		return true;
	}

	public function isOpen()
	{
		return is_resource($this->resource);
	}

	public function close()
	{
		if (!is_resource($this->resource)) {
			return true;
		}

		try {
			Exceptions::on();
			return fclose($this->resource);
		} catch (Exception $exception) {
			throw new CloseException($exception);
		} catch (Error $error) {
			throw new CloseException($error);
		} finally {
			Exceptions::off();
		}
	}

	public function setBlocking()
	{
		try {
			Exceptions::on();
			return stream_set_blocking($this->resource, true);
		} finally {
			Exceptions::off();
		}
	}

	public function setNonBlocking()
	{
		try {
			Exceptions::on();
			return stream_set_blocking($this->resource, false);
		} finally {
			Exceptions::off();
		}
	}
}
