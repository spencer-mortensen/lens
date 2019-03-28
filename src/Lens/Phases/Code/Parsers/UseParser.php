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

namespace _Lens\Lens\Phases\Code\Parsers;

use _Lens\Lens\Phases\Code\Input;
use _Lens\Lens\Php\Lexer;

// TODO: clean up this code:
class UseParser
{
	/** @var NameParser */
	private $nameParser;

	/** @var Input */
	private $input;

	public function __construct(NameParser $nameParser)
	{
		$this->nameParser = $nameParser;
	}

	public function parse(Input $input, &$output)
	{
		$this->input = $input;
		$position = $this->input->getPosition();

		if (
			$this->input->get(Lexer::USE_) &&
			$this->getUseBody($output) &&
			$this->input->get(Lexer::SEMICOLON_)
		) {
			return true;
		}

		$this->input->setPosition($position);
		return false;
	}

	private function getUseBody(&$output)
	{
		return $this->getUseFunction($output) ||
			$this->getUseClass($output);
	}

	private function getUseFunction(&$output)
	{
		$position = $this->input->getPosition();

		if (
			$this->input->get(Lexer::FUNCTION_) &&
			$this->getUseMaps($useMap)
		) {
			$output = ['function', $useMap];
			return true;
		}

		$this->input->setPosition($position);
		return false;
	}

	private function getUseMaps(&$output)
	{
		return $this->getUseMapList($output) ||
			$this->getUseMapGroup($output);
	}

	private function getUseMapList(&$output)
	{
		$position = $this->input->getPosition();

		if (
			$this->getUseMap($useMap) &&
			$this->getAnyNamespaceMapLinks($anyNamespaceMapLinks)
		) {
			if (0 < count($anyNamespaceMapLinks)) {
				$output = array_merge($useMap, $anyNamespaceMapLinks);
			} else {
				$output = $useMap;
			}
			return true;
		}

		$this->input->setPosition($position);
		return false;
	}

	private function getUseMap(&$output)
	{
		$position = $this->input->getPosition();

		if (
			$this->nameParser->parse($this->input, $name) &&
			$this->getMaybeAlias($maybeAlias)
		) {
			if (isset($maybeAlias)) {
				$alias = $maybeAlias;
			} else {
				$slash = strrpos($name, '\\');

				if (is_int($slash)) {
					$alias = substr($name, $slash + 1);
				} else {
					$alias = $name;
				}
			}

			$output = [
				$alias => $name
			];
			return true;
		}

		$this->input->setPosition($position);
		return false;
	}

	private function getMaybeAlias(&$output)
	{
		if ($this->getAlias($alias)) {
			$output = $alias;
		} else {
			$output = null;
		}

		return true;
	}

	private function getAlias(&$alias)
	{
		$position = $this->input->getPosition();

		if (
			$this->input->get(Lexer::NAMESPACE_AS_) &&
			$this->input->get(Lexer::IDENTIFIER_, $alias)
		) {
			return true;
		}

		$this->input->setPosition($position);
		return false;
	}

	private function getAnyNamespaceMapLinks(&$output)
	{
		$useMapLinks = [];

		while ($this->getUseMapLink($input)) {
			$useMapLinks[] = $input;
		}

		if (0 < count($useMapLinks)) {
			$output = call_user_func_array('array_merge', $useMapLinks);
		} else {
			$output = [];
		}

		return true;
	}

	private function getUseMapLink(&$output)
	{
		$position = $this->input->getPosition();

		if (
			$this->input->get(Lexer::COMMA_) &&
			$this->getUseMap($output)
		) {
			return true;
		}

		$this->input->setPosition($position);
		return false;
	}

	private function getUseMapGroup(&$output)
	{
		$position = $this->input->getPosition();

		if ($this->getSomeNamespaceNameLinks($useNameLinks) &&
			$this->input->get(Lexer::BRACE_LEFT_) &&
			$this->getUseMap($useMapList) &&
			$this->input->get(Lexer::BRACE_RIGHT_)
		) {
			$prefix = implode('\\', $useNameLinks);

			foreach ($useMapList as $alias => &$path) {
				$path = "{$prefix}\\{$path}";
			}

			$output = $useMapList;
			return true;
		}

		$this->input->setPosition($position);
		return false;
	}

	private function getSomeNamespaceNameLinks(&$output)
	{
		$output = [];

		if (!$this->getUseNameLink($output[])) {
			return false;
		}

		while ($this->getUseNameLink($input)) {
			$output[] = $input;
		}

		return true;
	}

	private function getUseNameLink(&$output)
	{
		$position = $this->input->getPosition();

		if (
			$this->input->get(Lexer::IDENTIFIER_, $output) &&
			$this->input->get(Lexer::NAMESPACE_SEPARATOR_)
		) {
			return true;
		}

		$this->input->setPosition($position);
		return false;
	}

	private function getUseClass(&$output)
	{
		if ($this->getUseMaps($useMap)) {
			$output = ['class', $useMap];
			return true;
		}

		return false;
	}
}
