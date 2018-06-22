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

namespace Lens_0_0_57\Lens\Reports\Coverage\Html5;

use InvalidArgumentException;
use Lens_0_0_57\SpencerMortensen\Html5\Elements\Span;
use Lens_0_0_57\SpencerMortensen\Html5\Node;

class Percent implements Node
{
	/** @var Node */
	private $node;

	public function __construct($numerator, $denominator)
	{
		$this->node = $this->getNode($numerator, $denominator);
	}

	private function getNode($numerator, $denominator)
	{
		if (!is_int($numerator) || !is_int($denominator)) {
			throw new InvalidArgumentException();
		}

		$ratio = $this->getRatio($numerator, $denominator);
		return $this->getRatioNode($ratio);
	}

	private function getRatio($numerator, $denominator)
	{
		if ($denominator === 0) {
			return 0;
		}

		return $numerator / $denominator;
	}

	private function getRatioNode($ratio)
	{
		$class = $this->getClass($ratio);
		$width = $this->getWidth($ratio);

		$attributes = [
			'class' => "percent {$class}",
			'style' => "width:{$width}"
		];

		return new Span($attributes);
	}

	private function getClass($ratio)
	{
		if ($ratio < 1/3) {
			return 'low';
		}

		if ($ratio < 2/3) {
			return 'medium';
		}

		if ($ratio < 1) {
			return 'high';
		}

		return 'perfect';
	}

	private function getWidth($ratio)
	{
		$width = ltrim((string)round($ratio * 6, 4), '0');

		if (strlen($width) === 0) {
			return '0';
		}

		return $width . 'em';
	}

	public function __toString()
	{
		return (string)$this->node;
	}
}
