<?php

namespace Lens;

use _Lens\Lens\Tests\Agent;

function touch($filename, $time = null, $atime = null)
{
	return eval(Agent::call(null, __FUNCTION__, func_get_args()));
}
