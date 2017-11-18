<?php

namespace Example;

class Terminal
{
	public function read()
	{
		return rtrim(fgets(STDIN), "\n");
	}

	public function write($line)
	{
		fputs(STDOUT, $line . "\n");
	}
}
