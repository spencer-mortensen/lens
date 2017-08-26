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

namespace Lens\Engine;

class Test implements Job
{
	/** @var callable */
	private $callable;

	/** @var Code */
	private $code;

	/** @var array */
	private $output;

	const KEY_FIXTURE = 'fixture';
	const KEY_EXPECTED = 'expected';
	const KEY_ACTUAL = 'actual';

	/** @var string */
	private $contextPhp;

	/** @var string */
	private $fixturePhp;

	/** @var string */
	private $expectedPhp;

	/** @var string */
	private $actualPhp;

	public function __construct($context, $fixture, $expected, $actual)
	{
		$this->contextPhp = $context;
		$this->fixturePhp = $fixture;
		$this->expectedPhp = $expected;
		$this->actualPhp = $actual;
	}

	public function run($callable)
	{
		$this->callable = $callable;

		$this->code = new Code();

		$this->output = array(
			self::KEY_FIXTURE => null,
			self::KEY_EXPECTED => null,
			self::KEY_ACTUAL => null
		);

		$this->runFixturePhp();
	}

	public function onReadyFixture(array $results)
	{
		$this->output[self::KEY_FIXTURE] = self::getState($results);

		if ($this->code->isBroken()) {
			$this->sendResults();
			return;
		}

		$expectedResults = $this->runExpectedPhp();
		$this->output[self::KEY_EXPECTED] = self::getState($expectedResults);

		$this->runActualPhp($expectedResults[1]);
	}

	private function runFixturePhp()
	{
		$fixturePhp = self::combine($this->contextPhp, $this->fixturePhp);

		$this->runInternal($fixturePhp, Code::MODE_PLAY, 'onReadyFixture');
	}

	private function runExpectedPhp()
	{
		$agentPhp = self::getAgentStartRecordingPhp();
		$expectedPhp = self::combine($this->contextPhp, $agentPhp, $this->expectedPhp);

		return $this->runExternal($expectedPhp, Code::MODE_RECORD);
	}

	private function runActualPhp($script)
	{
		$agentPhp = self::getAgentStartPlayingPhp($script);
		$actualPhp = self::combine($this->contextPhp, $agentPhp, $this->actualPhp);

		$this->runInternal($actualPhp, Code::MODE_PLAY, 'onReadyActual');
	}

	public function onReadyActual($results)
	{
		$this->output[self::KEY_ACTUAL] = self::getState($results);

		// TODO: add code coverage

		$this->sendResults();
	}

	private function runInternal($code, $mode, $method)
	{
		$this->code->setCode($code);
		$this->code->setMode($mode);
		$this->code->run(array($this, $method));
	}

	private function runExternal($code, $mode)
	{
		$this->code->setCode($code);
		$this->code->setMode($mode);

		$processor = new Processor();
		$processor->run($this->code, $results);
		$processor->halt();

		return $results;
	}

	private function sendResults()
	{
		call_user_func($this->callable, $this->output);
	}

	private static function getAgentStartRecordingPhp()
	{
		return "\\Lens\\Engine\\Agent::startRecording();";
	}

	private static function getAgentStartPlayingPhp($script)
	{
		if (!is_string($script)) {
			return null;
		}

		$scriptArgumentCode = var_export($script, true);
		return "\\Lens\\Engine\\Agent::startPlaying({$scriptArgumentCode});";
	}

	private static function combine()
	{
		return implode("\n\n", array_filter(func_get_args(), 'is_string'));
	}

	private static function getState($results)
	{
		if (!is_array($results)) {
			return null;
		}

		$state = $results[0];

		if (!is_array($state)) {
			return null;
		}

		return $state;
	}
}
