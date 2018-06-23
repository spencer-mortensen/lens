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

namespace _Lens\Lens\Exceptions;

use Error;
use Exception;
use _Lens\Lens\Displayer;
use _Lens\Lens\LensException;
use _Lens\Lens\Paragraph;
use _Lens\SpencerMortensen\RegularExpressions\Re;

class TerminalMessage
{
	/** @var integer */
	private static $maximumLineLength = 96;

	/** @var LensException */
	private $exception;

	public function __construct(LensException $exception)
	{
		$this->exception = $exception;
	}

	public function getText()
	{
		$output = $this->getSummaryText();

		if ($this->getDetailsText($detailsText)) {
			$output .= "\n\n{$detailsText}";
		}

		if ($this->getHelpText($helpText)) {
			$output .= "\n\nTROUBLESHOOTING\n\n{$helpText}";
		}

		return $output;
	}

	private function getSummaryText()
	{
		$severity = $this->exception->getSeverity();
		$code = $this->exception->getCode();
		$message = $this->exception->getMessage();

		$severityText = self::getSeverityText($severity);
		return "{$severityText} {$code}: {$message}";
	}

	private static function getSeverityText($severity)
	{
		switch ($severity) {
			case LensException::SEVERITY_NOTICE:
				return 'Note';

			case LensException::SEVERITY_WARNING:
				return 'Warning';

			default:
				return 'Error';
		}
	}

	private function getDetailsText(&$output)
	{
		$data = $this->exception->getData();
		$previous = $this->exception->getPrevious();

		return $this->getDataText($data, $output)
			|| $this->getExceptionText($previous, $output);
	}

	private function getDataText(array $data = null, &$output)
	{
		if (count($data) === 0) {
			return false;
		}

		$lines = [];

		$displayer = new Displayer();

		foreach ($data as $key => $value) {
			$keyText = ucfirst($key);
			$valueText = $displayer->display($value);

			$lines[] = "   {$keyText}: {$valueText}";
		}

		$output = implode("\n", $lines);
		return true;
	}

	/**
	 * @param Exception|Error|null $exception
	 * @param string $output
	 * @return string
	 */
	private function getExceptionText($exception, &$output)
	{
		if ($exception === null) {
			return false;
		}

		$extractor = new DataExtractor();
		$data = $extractor->getData($exception);

		$formatter = new DataFormatter();
		$output = $formatter->formatExceptionData($data);

		return true;
	}

	private function getHelpText(&$output)
	{
		$help = $this->exception->getHelp();

		if (count($help) === 0) {
			return false;
		}

		$lines = [];

		foreach ($help as $paragraph) {
			$line = Paragraph::wrap($paragraph);
			$line = Paragraph::indent($line, '   ');
			$line = substr_replace($line, '*', 1, 1);

			$lines[] = $line;
		}

		$output = implode("\n\n", $lines);
		return true;
	}
}
