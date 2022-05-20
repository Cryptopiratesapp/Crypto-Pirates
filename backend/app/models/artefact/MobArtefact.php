<?php
namespace app\models\artefact;

class MobArtefact
{
	public $mob_id;
	public $af_id;
	public $chance;

	public function __construct($data)
	{
		$this->mob_id = (int) $data['mob_id'];
		$this->af_id = (int) $data['af_id'];
		$this->chance = (int) $data['chance'];
	}
}
