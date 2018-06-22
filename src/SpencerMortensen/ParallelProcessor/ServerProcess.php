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

namespace Lens_0_0_57\SpencerMortensen\ParallelProcessor;

use Exception;
use Lens_0_0_57\SpencerMortensen\Exceptions\Exceptions;
use Throwable;

abstract class ServerProcess
{
	const CODE_SUCCESS = 0;
	const CODE_FAILURE = 1;

	/** @var ServerJob */
	private $job;

	public function __construct(ServerJob $job)
	{
		$this->job = $job;
	}

	public function run()
	{
		Exceptions::on([$this, 'sendError']);

		try {
			$result = $this->job->start();
			$this->sendResult($result);
		} catch (Throwable $throwable) {
			$this->sendError($throwable);
		} catch (Exception $exception) {
			$this->sendError($exception);
		}

		Exceptions::off();
	}

	public function sendResult($result)
	{
		$message = Message::serialize(Message::TYPE_RESULT, $result);

		$this->send($message);
	}

	public function sendError($exception)
	{
		$message = Message::serialize(Message::TYPE_ERROR, $exception);

		$this->send($message);
	}

	abstract protected function send($message);
}
