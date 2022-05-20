<?php
namespace app\components\auth;

use app\base\Security;
use PDO;

class AuthMobile
{
	private $_pdo;
	private $_selectSth;
	public $uid;
	public $output;

	public function __construct($db)
	{
		$this->_pdo = $db->getPdo();
		$this->_selectSth = $this->_pdo->prepare('select id, salt, password from user where email=:email');
	}
	
	public function process(& $msg)
	{
		$this->_uid = null;
		$this->_output = null;
		
		$yarr = explode(' ', $msg);
		
		if (count($yarr) < 3 || $yarr[0] !== 'YARR') {
			return false;
		}
		
		$email = $yarr[1];
		$password = $yarr[2];

		$this->_selectSth->bindValue(':email', $email);
		$this->_selectSth->execute();
		$data = $this->_selectSth->fetch(PDO::FETCH_ASSOC);
		$this->_selectSth->closeCursor();

		if (!empty($data)) {
			$hash = Security::hashPassword($data['salt'], $password);
			if ($hash == $data['password']) {
				$this->uid = (int) $data['id'];
				
				return true;
			}
		}
		
		return false;
	}
}
