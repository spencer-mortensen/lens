<?php

namespace Lens;

use Lens_0_0_57\Lens\Tests\Agent;

function stream_socket_accept($serverstream, $timeout = null, &$peername = null)
{
	return eval(Agent::call(null, __FUNCTION__, func_get_args()));
}
