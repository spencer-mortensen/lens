<?php

/**
 * Copyright (C) 2017 Spencer Mortensen
 *
 * This file is part of TestPHP.
 *
 * TestPHP is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * TestPHP is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with TestPHP. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Spencer Mortensen <spencer@spencermortensen.com>
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL-3.0
 * @copyright 2017 Spencer Mortensen
 */

namespace TestPhp;

class Table
{
	private $rows;

	public function __construct($rows = null)
	{
		if (!is_array($rows)) {
			$rows = array();
		}

		$this->rows = $rows;
	}

	public function addRow()
	{
		$this->rows[] = func_get_args();
	}

	public function getText()
	{
		list($rows, $widths, $heights) = self::getDimensions($this->rows);

		list($row, $columns) = each($rows);

		$out = self::getDivider('╭', '─', '┬', '╮', $widths);
		$out .= self::getRow('│', $columns, $widths, $heights[$row]);
		$out .= self::getDivider('╞', '═', '╪', '╡', $widths);

		while (list($row, $columns) = each($rows)) {
			$out .= self::getRow('│', $columns, $widths, $heights[$row]);
		}

		$out .= self::getDivider('╰', '─', '┴', '╯', $widths);

		return $out;
	}

	private static function getRow($c, $columns, $widths, $height)
	{
		$out = '';

		for ($i = 0; $i < $height; ++$i) {
			foreach ($columns as $column => $lines) {
				$out .= $c . self::getCellBody((string)@$lines[$i], $widths[$column]);
			}

			$out .= $c . "\n";
		}

		return $out;
	}

	private static function getCellBody($text, $width)
	{
		return ' ' . self::multibyteLeftPad($text, $width) . ' ';
	}

	private static function getDimensions($rows)
	{
		$data = array();
		$widths = array();
		$heights = array();

		foreach ($rows as $row => $columns) {
			foreach ($columns as $column => $text) {
				$lines = array_filter(explode("\n", $text));

				$cellWidth = (int)@max(array_map('iconv_strlen', $lines));
				$cellHeight = count($lines);

				$widths[$column] = max($cellWidth, (int)@$widths[$column]);
				$heights[$row] = max($cellHeight, (int)@$heights[$row]);
				$data[$row][$column] = $lines;
			}
		}

		return array($data, $widths, $heights);
	}

	private static function getDivider($begin, $middle, $separator, $end, $widths)
	{
		$segments = array();

		foreach ($widths as $width) {
			$segments[] = str_repeat($middle, $width + 2);
		}

		return $begin . implode($separator, $segments) . $end . "\n";
	}

	private static function multibyteLeftPad($string, $desiredStringLength)
	{
		$stringLength = iconv_strlen($string);
		$paddingLength = max($desiredStringLength - $stringLength, 0);

		return $string . str_repeat(' ', $paddingLength);
	}
}
