<?php

namespace Example;

use PDO;

class Database
{
	public function getDrivers()
	{
		return PDO::getAvailableDrivers();
	}
}
