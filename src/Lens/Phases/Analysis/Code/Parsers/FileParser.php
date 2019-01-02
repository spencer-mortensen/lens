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

namespace _Lens\Lens\Phases\Analysis\Code\Parsers;

use _Lens\Lens\Phases\Analysis\Code\Input;
use _Lens\Lens\Php\Lexer;

class FileParser
{
	/** @var NameParser */
	private $nameParser;

	/** @var UseParser */
	private $useParser;

	/** @var ClassParser */
	private $classParser;

	/** @var FunctionParser */
	private $functionParser;

	/** @var InterfaceParser */
	private $interfaceParser;

	/** @var TraitParser */
	private $traitParser;

	/** @var Input */
	private $input;

	public function __construct()
	{
		$nameParser = new NameParser();
		$useParser = new UseParser($nameParser);
		$blockParser = new BlockParser();
		$pathParser = new PathParser();
		$classCallParser = new ClassCallParser($pathParser);
		$functionCallParser = new FunctionCallParser($pathParser);
		$functionParser = new FunctionParser($classCallParser, $functionCallParser);
		$methodParser = new MethodParser($functionParser);
		$classParser = new ClassParser($pathParser, $methodParser);
		$interfaceParser = new InterfaceParser($pathParser, $blockParser);
		$traitParser = new TraitParser($methodParser);

		// TODO: eliminate the NameParser?
		$this->nameParser = $nameParser;
		$this->useParser = $useParser;
		$this->classParser = $classParser;
		$this->functionParser = $functionParser;
		$this->interfaceParser = $interfaceParser;
		$this->traitParser = $traitParser;
	}

	public function parse(Input $input, &$namespaces = null)
	{
		$this->input = $input;
		$position = $this->input->getPosition();

		if (
			$this->input->get(Lexer::PHP_BEGIN_) &&
			$this->getNamespaces($namespaces)
		) {
			return true;
		}

		$this->input->setPosition($position);
		return false;
	}


	private function getNamespaces(&$namespaces)
	{
		$namespaces = [];

		while ($this->getNamespace($namespace)) {
			$namespaces[] = $namespace;
		}

		return true;
	}

	private function getNamespace(&$namespace)
	{
		if (!$this->input->read()) {
			return false;
		}

		$namespace = [
			'context' => [
				'namespace' => null,
				'classes' => [],
				'functions' => []
			],
			'definitions' => [
				'classes' => [],
				'functions' => [],
				'interfaces' => [],
				'traits' => []
			]
		];

		$this->getNamespaceName($namespace['context']['namespace']);

		while ($this->input->read($type) && ($type !== Lexer::NAMESPACE_)) {
			if (!$this->getNamespaceStatement($type, $namespace['context'], $namespace['definitions'])) {
				$this->input->move(1);
			}
		}

		return true;
	}

	private function getNamespaceName(&$name)
	{
		if (!$this->input->get(Lexer::NAMESPACE_)) {
			return false;
		}

		$this->nameParser->parse($this->input, $name);
		return true;
	}

	private function getNamespaceStatement($type, &$context, &$definitions)
	{
		switch ($type) {
			case Lexer::USE_:
				return $this->getUseStatement($context['classes'], $context['functions']);

			case Lexer::FINAL_:
			case Lexer::ABSTRACT_:
			case Lexer::CLASS_:
				return $this->getClassStatement($definitions['classes']);

			case Lexer::INTERFACE_:
				return $this->getInterfaceStatement($definitions['interfaces']);

			case Lexer::TRAIT_:
				return $this->getTraitStatement($definitions['traits']);

			case Lexer::FUNCTION_:
				return $this->getFunctionStatement($definitions['functions']);

			default:
				return false;
		}
	}

	private function getUseStatement(&$classes, &$functions)
	{
		if (!$this->useParser->parse($this->input, $definition)) {
			return false;
		}

		list($type, $aliases) = $definition;

		if ($type === 'class') {
			$classes = array_merge($classes, $aliases);
		} else {
			$functions = array_merge($functions, $aliases);
		}

		return true;
	}

	private function getClassStatement(&$classes)
	{
		if (!$this->classParser->parse($this->input, $class)) {
			return false;
		}

		$classes[] = $class;
		return true;
	}

	private function getInterfaceStatement(&$interfaces)
	{
		if (!$this->interfaceParser->parse($this->input, $definition)) {
			return false;
		}

		$interfaces[] = $definition;
		return true;
	}

	private function getTraitStatement(&$traits)
	{
		if (!$this->traitParser->parse($this->input, $definition)) {
			return false;
		}

		$traits[] = $definition;
		return true;
	}

	private function getFunctionStatement(&$functions)
	{
		$classPaths = [];
		$functionPaths = [];

		if (!$this->functionParser->parse($this->input, $function, $classPaths, $functionPaths)) {
			return false;
		}

		$function['classPaths'] = $classPaths;
		$function['functionPaths'] = $functionPaths;

		$functions[] = $function;
		return true;
	}
}
