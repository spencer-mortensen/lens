<?php

namespace Lens;

use _Lens\Lens\Tests\Agent;

function stream_socket_recvfrom($stream, $amount, $flags = null, &$remote_addr = null)
{
	return eval(Agent::call(null, __FUNCTION__, func_get_args()));
}
