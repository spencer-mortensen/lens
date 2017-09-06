<?php

namespace Lens;

interface Evaluator
{
	public function run($lensDirectory, $srcDirectory, array $suites);
}
