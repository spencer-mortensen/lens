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

class Html5
{
	// TODO: use this inside the "Web" class
	public static function getElementHtml($name, array $attributes = null, $innerHtml = null)
	{
		$attributesHtml = self::getAttributesHtml($attributes);
		return "<{$name}{$attributesHtml}>{$innerHtml}</{$name}>";
	}

	private static function getAttributesHtml($attributes)
	{
		if (count($attributes) === 0) {
			return '';
		}

		$html = '';

		foreach ($attributes as $name => $value) {
			$valueHtml = self::attributeEncode($value);
			$html .= " {$name}=\"{$valueHtml}\"";
		}

		return $html;
	}

	public static function getTextHtml($text)
	{
		$text = htmlspecialchars($text, ENT_HTML5 | ENT_COMPAT | ENT_DISALLOWED | ENT_NOQUOTES, 'UTF-8');
		$text = str_replace("\t", '     ', $text);
		$text = str_replace(' ', '&nbsp;', $text);

		return $text;
	}

	private static function attributeEncode($attribute)
	{
		return htmlspecialchars($attribute, ENT_HTML5 | ENT_COMPAT | ENT_DISALLOWED | ENT_QUOTES, 'UTF-8');
	}
}
