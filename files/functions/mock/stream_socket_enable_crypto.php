<?php

namespace Lens;

use _Lens\Lens\Tests\Agent;

function stream_socket_enable_crypto($stream, $enable, $cryptokind = null, $sessionstream = null)
{
	return eval(Agent::call(null, __FUNCTION__, func_get_args()));
}
