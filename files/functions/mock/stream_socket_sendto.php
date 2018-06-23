<?php

namespace Lens;

use Lens_0_0_57\Lens\Tests\Agent;

function stream_socket_sendto($stream, $data, $flags = null, $target_addr = null)
{
	return eval(Agent::call(null, __FUNCTION__, func_get_args()));
}
