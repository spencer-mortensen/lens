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

use ErrorException;

class Settings
{
	/** @var IniFile */
	private $file;

	/** @var Logger */
	private $logger;

	public function __construct(IniFile $file, Logger $logger)
	{
		$this->file = $file;
		$this->logger = $logger;
	}

	public function setPath($filePath)
	{
		$this->file->setPath($filePath);
	}

	public function getPath()
	{
		return $this->file->getPath();
	}

	public function read()
	{
		try {
			$settings = $this->file->read();
		} catch (ErrorException $exception) {
			throw $this->invalidSettingsFileException($exception);
		}

		if ($settings === null) {
			$settings = self::getDefaultSettings();
			$this->writeNewFile($settings);
		}

		// TODO: validate recognized keys (e.g. the autoload path)
		// TODO: insert missing keys (with a note-level message): map missing key to a null value
		// TODO: preserve unrecognized keys (with a warning-level message)

		return $settings;
	}

	private function invalidSettingsFileException(ErrorException $exception)
	{
		$path = $this->file->getPath();
		$message = $exception->getMessage();

		return Exception::invalidSettingsFile($path, $message);
	}

	private function writeNewFile(array $settings)
	{
		$path = $this->file->getPath();
		$pathText = json_encode($path);

		$this->logger->note("Settings file missing ({$pathText})");
		$this->logger->note("Creating settings file ({$pathText})");

		$this->file->write($settings);
	}

	private static function getDefaultSettings()
	{
		return array(
			'src' => null,
			'autoload' => null
		);
	}
}
