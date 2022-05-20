<?php
namespace app\commands;

class CommandFactory
{
	private $commands;

	public function __construct()
	{
		$this->commands = [
			Command::TYPE_CONSOLE => new ConsoleCommand(),
			Command::TYPE_REPAIR => new RepairCommand(),
			Command::TYPE_FLEE => new FleeCommand(),
			Command::TYPE_MODE => new ModeCommand(),
			Command::TYPE_VOLLEY_SHOT => new VolleyShotCommand(),
			Command::TYPE_NAV => new NavCommand()
		];
	}

	public function getCommand($type, $params = null)
	{
		if (!isset($this->commands[$type])) {
			return null;
		}
		
		$cmd = $this->commands[$type];
		$cmd->params = $params;

		return $cmd;
	}
}
