<?php
namespace app\models\artefact;

class UserArtefact
{
	public $user_id;
	public $af_id;
	public $pos;
	public $active;
	public $turns;

	public function __construct($data)
	{
		$this->user_id = (int) $data['user_id'];
		$this->af_id = (int) $data['af_id'];
		$this->pos = (int) $data['pos'];
		$this->active = (int) $data['active'];
		$this->turns = (int) $data['turns'];
	}
}
