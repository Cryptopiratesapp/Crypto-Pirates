<?php
namespace app\models\artefact;

class Artefact
{
	const AF_OFFENSIVE = 1;
	const AF_DEFENSIVE = 2;

	public $id;
	public $name;
	public $title;
	public $descr;
	public $type;
	public $pos;
	public $turns;

	public function __construct($data)
	{
		$this->id = (int) $data['id'];
		$this->name = $data['name'];
		$this->title = $data['title'];
		$this->descr = $data['descr'];
		$this->type = (int) $data['type'];
		$this->pos = (int) $data['pos'];
		$this->turns = (int) $data['turns'];
	}
}
