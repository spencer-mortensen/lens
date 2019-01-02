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

namespace _Lens\Lens\Phases\Analysis\Code\Generators;

use _Lens\Lens\Php\Code;

abstract class MockGenerator
{
	protected function getContextPhp($namespace)
	{
		$classes = [
			'Agent' => '_Lens\\Lens\\Tests\\Agent'
		];

		$functions = [];

		return Code::getFullContextPhp($namespace, $classes, $functions);
	}

	protected function getRangePhp(array $range, array $map, array $inflatedTokens)
	{
		$iBeginDeflated = key($range);
		$iEndDeflated = $range[$iBeginDeflated];

		$iBeginInflated = $map[$iBeginDeflated];
		$iEndInflated = $map[$iEndDeflated];

		$length = $iEndInflated - $iBeginInflated + 1;

		$tokens = array_slice($inflatedTokens, $iBeginInflated, $length);
		return $this->getPhpFromTokens($tokens);
	}

	protected function getMethodPhp($isAbstract, $isStatic, $isPrivate, $signaturePhp)
	{
		if ($isPrivate) {
			return $this->getConcreteMethodPhp($signaturePhp, null);
		}

		if ($isAbstract) {
			return $this->getAbstractMethodPhp($signaturePhp);
		}

		if ($isStatic) {
			$bodyPhp = $this->getMethodBodyPhp('__CLASS__', '__FUNCTION__', 'func_get_args()');
			return $this->getConcreteMethodPhp($signaturePhp, $bodyPhp);
		}

		$bodyPhp = $this->getMethodBodyPhp('$this', '__FUNCTION__', 'func_get_args()');
		return $this->getConcreteMethodPhp($signaturePhp, $bodyPhp);
	}

	protected function getMethodBodyPhp($contextPhp, $functionPhp, $argumentsPhp)
	{
		return "return eval(Agent::call({$contextPhp}, {$functionPhp}, {$argumentsPhp}));";
	}

	private function getAbstractMethodPhp($signaturePhp)
	{
		return "\t{$signaturePhp};";
	}

	protected function getConcreteMethodPhp($signaturePhp, $bodyPhp)
	{
		if ($bodyPhp === null) {
			return "\t{$signaturePhp}\n\t{\n\t}";
		}

		return "\t{$signaturePhp}\n\t{\n\t\t{$bodyPhp}\n\t}";
	}

	// TODO: this is duplicated elsewhere
	private function getPhpFromTokens(array $tokens)
	{
		ob_start();

		foreach ($tokens as $token) {
			echo current($token);
		}

		return ob_get_clean();
	}
}
