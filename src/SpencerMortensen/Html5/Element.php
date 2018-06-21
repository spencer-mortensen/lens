<?php

/**
 * Copyright (C) 2018 Spencer Mortensen
 *
 * This file is part of Html5.
 *
 * Html5 is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Html5 is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with Html5. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Spencer Mortensen <spencer@lens.guide>
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL-3.0
 * @copyright 2018 Spencer Mortensen
 */

namespace Lens_0_0_56\SpencerMortensen\Html5;

abstract class Element
{
	/** @var string */
	private $name;

	/** @var array */
	private $attributes;

	/** @var Element[] */
	private $children;

	/** @var bool */
	private $hasClosingTag;

	public function __construct($name, array $attributes = null, $contents = null, $hasClosingTag = true)
	{
		$this->name = $name;
		$this->attributes = (array)$attributes;
		$this->children = $this->getChildren($contents);
		$this->hasClosingTag = $hasClosingTag;
	}

	private function getChildren($contents)
	{
		if (is_array($contents)) {
			return $contents;
		}

		if (is_object($contents)) {
			return [$contents];
		}

		return [];
	}

	public function __toString()
	{
		$attributesHtml = $this->getAttributesHtml();

		if (!$this->hasClosingTag) {
			return "<{$this->name}{$attributesHtml}>";
		}

		$innerHtml = $this->getInnerHtml();

		return "<{$this->name}{$attributesHtml}>{$innerHtml}</{$this->name}>";
	}

	private function getAttributesHtml()
	{
		if (count($this->attributes) === 0) {
			return '';
		}

		$html = '';

		foreach ($this->attributes as $name => $value) {
			$valueHtml = self::attributeEncode($value);
			$html .= " {$name}=\"{$valueHtml}\"";
		}

		return $html;
	}

	private static function attributeEncode($attribute)
	{
		return htmlspecialchars($attribute, ENT_HTML5 | ENT_COMPAT | ENT_DISALLOWED | ENT_COMPAT, 'UTF-8');
	}

	private function getInnerHtml()
	{
		return implode('', $this->children);
	}
}
