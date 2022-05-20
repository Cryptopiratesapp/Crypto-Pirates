<?php
return [
	'db' => [
		'pdo' => [
			'dsn' => 'mysql:host=localhost;dbname=cp;user=root;password=123qwe',
		],
		'redis' => [
			'host' => '127.0.0.1',
			'port' => 6379
		]
	],
	'auth_cookie_name' => 'cps',
	//'listener' => 'wss://89.111.136.45/ws/',
	'listener' => 'ws://127.0.0.1:8888',
	'listener_mobile' => '127.0.0.1:8889',
];
