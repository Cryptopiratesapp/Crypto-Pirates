<?php
namespace app\models;

class Dock
{
	public $id;
	public $hop;
	public $name;
	public $title;
	public $img_url;

	public function __construct($data)
	{
		$this->id = (int) $data['id'];
		$this->name = $data['name'];
		$this->hop = (int) $data['hop'];
		$this->title = $data['title'];
		$this->img_url = $data['img_url'];
	}
}
