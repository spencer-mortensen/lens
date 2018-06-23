<?php

namespace Lens;

use _Lens\Lens\Tests\Agent;

function stream_socket_sendto($stream, $data, $flags = null, $target_addr = null)
{
	return eval(Agent::call(null, __FUNCTION__, func_get_args()));
}
