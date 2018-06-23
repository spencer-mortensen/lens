<?php

namespace Lens;

use _Lens\Lens\Tests\Agent;

function fputs($fp, $str, $length = null)
{
	return eval(Agent::call(null, __FUNCTION__, func_get_args()));
}
