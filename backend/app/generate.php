<?php

use app\base\Db;
use app\components\ShipHelper;

require 'base/Db.php';

require 'components/DbHelper.php';

require 'models/Ship.php';
require 'components/ShipHelper.php';

$config = require '../config.php';
$db = new Db($config['db']);

$uid = 1000;

for ($i = 0; $i < 5000; $i++) {
	ShipHelper::createShip($db, $uid + $i);
}
