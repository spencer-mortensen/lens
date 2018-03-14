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

namespace Lens_0_0_56\Lens\Exceptions;

use Lens_0_0_56\Lens\LensException;

class LogMessage
{
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
			$output .= " {$detailsText}";
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
		$previous = $this->exception->getPrevious();

		if ($previous === null) {
			return false;
		}

		$extractor = new DataExtractor();
		$data = $extractor->getData($previous);

		$output = json_encode($data);
		return true;
	}
}
