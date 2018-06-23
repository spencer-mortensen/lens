<?php

namespace Lens;

use _Lens\Lens\Tests\Agent;

function stream_set_write_buffer($fp, $buffer)
{
	return eval(Agent::call(null, __FUNCTION__, func_get_args()));
}
