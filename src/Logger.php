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

namespace Lens;

// TODO: use this Logger class
class Logger
{
	const SEVERITY_EMERGENCY = 0;
	const SEVERITY_ALERT = 1;
	const SEVERITY_CRITICAL = 2;
	const SEVERITY_ERROR = 3;
	const SEVERITY_WARNING = 4;
	const SEVERITY_NOTICE = 5;
	const SEVERITY_INFORMATIONAL = 6;
	const SEVERITY_DEBUG = 7;

	/** @var string */
	private $identity;

	/** @var string */
	private $outputThreshold;

	/** @var string */
	private $syslogThreshold;

	/**
	 * Logger constructor.
	 * 
	 * @param string $identity
	 * A unique identifier for the program that generated the message to be logged.
	 * 
	 * @param integer $outputThreshold
	 * Write all messages of this importance (or higher) to the terminal.
	 */
	public function __construct($identity, $outputThreshold = self::SEVERITY_INFORMATIONAL)
	{
		$this->identity = $identity;
		$this->outputThreshold = $outputThreshold;
		$this->syslogThreshold = self::SEVERITY_NOTICE;
	}

	/**
	 * Send yourself debugging information.
	 * 
	 * Example: "state: " . json_encode($this->state)
	 *
	 * @param string $message
	 */
	public function debug($message)
	{
		$this->write(self::SEVERITY_DEBUG, $message);
	}

	/**
	 * Send the user a status update about a completely normal situation.
	 * 
	 * Example: "Connecting to the database"
	 *
	 * @param string $message
	 */
	public function info($message)
	{
		$this->write(self::SEVERITY_INFORMATIONAL, $message);
	}

	/**
	 * Let the user know about an unusual--but possibly normal--situation.
	 *
	 * Example: "No configuration file found: creating a new one using the default settings."
	 * Example: "The 'xdebug' package is not installed, so code coverage has been disabled."
	 *
	 * @param string $message
	 */
	public function note($message)
	{
		$this->write(self::SEVERITY_NOTICE, $message);
	}

	/**
	 * Alert the user to a definitely abnormal situation. No intervention
	 * is needed at this time.
	 *
	 * Example: "The filesystem is nearly full: only 5% free space remaining."
	 *
	 * @param string $message
	 */
	public function warn($message)
	{
		$this->write(self::SEVERITY_WARNING, $message);
	}

	/**
	 * Alert the user to an abnormal situation. Intervention is required,
	 * but the program can continue along in a limited capacity.
	 *
	 * Example: "Unable to write to the cache"
	 *
	 * @param string $message
	 */
	public function error($message)
	{
		$this->write(self::SEVERITY_ERROR, $message);
	}

	/**
	 * Alert the user to an error situation. Intervention is required.
	 * The program cannot continue.
	 *
	 * Example: "Fatal error: failed opening required file"
	 *
	 * @param string $message
	 */
	public function critical($message)
	{
		$this->write(self::SEVERITY_CRITICAL, $message);
	}

	/**
	 * Alert the response team to an error situation.
	 *
	 * Example: "The database cluster is corrupt."
	 *
	 * @param string $message
	 */
	public function alert($message)
	{
		$this->write(self::SEVERITY_ALERT, $message);
	}

	/**
	 * Use the red phone system to call the department head, even though it's 3:00 am.
	 * We'll need a press release and a detailed post-mortem investigation.
	 * Our business will end unless this is fixed immediately.
	 *
	 * Example: "Our product is failing everywhere."
	 *
	 * @param string $message
	 */
	public function emergency($message)
	{
		$this->write(self::SEVERITY_EMERGENCY, $message);
	}

	private function write($severity, $message)
	{
		if ($severity <= $this->outputThreshold) {
			$this->writeOutput($severity, $message);
		}

		if ($severity <= $this->syslogThreshold) {
			$this->writeSyslog($severity, $message);
		}
	}

	private function writeOutput($severity, $message)
	{
		$channel = self::getOutputChannel($severity);

		file_put_contents("php://{$channel}", "{$message}\n");
	}

	private static function getOutputChannel($severity)
	{
		if ($severity <= self::SEVERITY_NOTICE) {
			return 'stderr';
		}

		return 'stdout';
	}

	/*
	private static function getSeverityLabel($severity)
	{
		switch ($severity) {
			case self::SEVERITY_EMERGENCY:
				return 'Emergency';

			case self::SEVERITY_ALERT:
				return 'Alert';

			case self::SEVERITY_CRITICAL:
				return 'Critical';

			case self::SEVERITY_ERROR:
				return 'Error';

			case self::SEVERITY_WARNING:
				return 'Warning';

			case self::SEVERITY_NOTICE:
				return 'Note';

			case self::SEVERITY_INFORMATIONAL:
				return 'Info';

			default:
				return 'Debug';
		}
	}
	*/

	private function writeSyslog($severity, $message)
	{
		openlog($this->identity, LOG_NDELAY, LOG_LOCAL3);
		syslog($severity, $message);
		closelog();
	}
}