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

namespace Lens_0_0_56\SpencerMortensen\ParallelProcessor\Stream;

use Exception;
use Lens_0_0_56\SpencerMortensen\Exceptions\Exceptions;
use Lens_0_0_56\SpencerMortensen\ParallelProcessor\Stream\Exceptions\CloseException;
use Lens_0_0_56\SpencerMortensen\ParallelProcessor\Stream\Exceptions\StreamException;
use Lens_0_0_56\SpencerMortensen\ParallelProcessor\Stream\Exceptions\ReadException;
use Lens_0_0_56\SpencerMortensen\ParallelProcessor\Stream\Exceptions\ReadIncompleteException;
use Lens_0_0_56\SpencerMortensen\ParallelProcessor\Stream\Exceptions\WriteException;
use Lens_0_0_56\SpencerMortensen\ParallelProcessor\Stream\Exceptions\WriteIncompleteException;
use Throwable;

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

		Exceptions::on();

		try {
			$contents = self::readChunks($this->resource);
		} catch (ReadIncompleteException $exception) {
			Exceptions::off();
			throw $exception;
		} catch (Throwable $throwable) {
			Exceptions::off();
			throw new ReadException($throwable);
		} catch (Exception $exception) {
			Exceptions::off();
			throw new ReadException($exception);
		}

		Exceptions::off();
		return $contents;
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

		Exceptions::on();

		try {
			$bytesWritten = fwrite($this->resource, $contents);
		} catch (Throwable $throwable) {
			Exceptions::off();
			throw new WriteException($throwable);
		} catch (Exception $exception) {
			Exceptions::off();
			throw new WriteException($exception);
		}

		Exceptions::off();

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

		Exceptions::on();

		try {
			$success = fclose($this->resource);
		} catch (Throwable $throwable) {
			Exceptions::off();
			throw new CloseException($throwable);
		} catch (Exception $exception) {
			Exceptions::off();
			throw new CloseException($exception);
		}

		Exceptions::off();
		return $success;
	}

	public function setBlocking()
	{
		Exceptions::on();

		try {
			$success = stream_set_blocking($this->resource, true);
		} catch (Throwable $throwable) {
			Exceptions::off();
			throw $throwable;
		} catch (Exception $exception) {
			Exceptions::off();
			throw $exception;
		}

		Exceptions::off();
		return $success;
	}

	public function setNonBlocking()
	{
		Exceptions::on();

		try {
			$success = stream_set_blocking($this->resource, false);
		} catch (Throwable $throwable) {
			Exceptions::off();
			throw $throwable;
		} catch (Exception $exception) {
			Exceptions::off();
			throw $exception;
		}

		Exceptions::off();
		return $success;
	}
}
