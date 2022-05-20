<?php
namespace app\commands;

abstract class Command
{
	const TYPE_CONSOLE = '~';
	const TYPE_NOP = 'NOP';
	const TYPE_STATE = 'STATE';
	const TYPE_REPAIR = 'REPAIR';
	const TYPE_FLEE = 'FLEE';
	const TYPE_VOLLEY_SHOT = 'VOLLEY_SHOT';
	const TYPE_MODE = 'MODE';
	const TYPE_DEPART = 'DEPART';
	const TYPE_NAV = 'NAV';
	
	public abstract function run($ctx, & $params = null);
	
	public abstract function validate($ctx);
}
