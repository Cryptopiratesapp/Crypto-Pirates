<?php
namespace app\components;

use app\models\artefact\Artefact;
use Exception;
use PDO;

class ArtefactMap
{
	private $_ctx;
	private $_updateUserAfSth;
	private $_selectAllSth;
	private $_selectUserAfSth;
	private $_deleteUserAfSth;
	private $_artefacts = [];

	/** @param Context $ctx */
	public function __construct($ctx)
	{
		$this->_ctx = $ctx;
		$this->_selectAllSth = $ctx->pdo->prepare('select * from artefact');
		$this->_selectUserAfSth = $ctx->pdo->prepare('select * from user_artefact');
		$this->_deleteUserAfSth = $ctx->pdo->prepare('delete from user_artefact where user_id=:uid and pos=:pos');
	}

	public function load()
	{
		$this->_artefacts = [];

		$res = $this->_selectAllSth->execute();
		if (!$res) {
			throw new Exception('Error loading artefacts from db');
		}

		while ($data = $this->_selectAllSth->fetch(PDO::FETCH_ASSOC)) {
			$artefact = new Artefact($data);
			$this->_artefacts[$artefact->id] = $artefact;
		}

		$this->_selectAllSth->closeCursor();
		
		return count($this->_artefacts);
	}

	public function get($id)
	{
		return $this->_artefacts[$id];
	}

	function save($user_af)
	{
		$this->_updateUserAfSth->bindValue(':user_id', $user_af->user_id);
		$this->_updateShipSth->bindValue(':pos', $user_af->pos);

		if (!$this->_updateShipSth->execute()) {
			fwrite(STDERR, "could not update user_af for uid={$user_af->user_id}\n");
			
			return false;
		}

		return true;
	}
}
