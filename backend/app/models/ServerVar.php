<?php
namespace app\models;
use PDO;

class ServerVar
{
	const P_MAX_HOPS = 'p_max_hops';
	const CD_CMD_VOLLEY_SHOT = 'cd_cmd_volley_shot';
	const CD_AUTOREPAIR_ = 'cd_autorepair_';
	const LIM_M_ATK_MIN = 'lim_m_atk_min';
	const LIM_M_ATK_MAX_MIN = 'lim_m_atk_max_min';
	const LIM_M_ATK_MAX_MAX = 'lim_m_atk_max_max';
	const LIM_MISS_MAX = 'lim_miss_max';
	const T_AUTOREPAIR_ = 't_autorepair_';
	const METEOR_DMG = 'meteor_dmg';
	const M_EVT_LOSE_RES = 'm_evt_lose_res';
	const M_EVT_LOSE_GOLD = 'm_evt_lose_gold';
	const M_EVT_LOSS_RES = 'm_evt_loss_res';
	const M_EVT_LOSS_GOLD = 'm_evt_loss_gold';
	const M_EVT_GAIN_RES = 'm_evt_gain_res';
	const M_EVT_GAIN_GOLD = 'm_evt_gain_gold';
	const C_CMD_FLEE = 'c_cmd_flee';
	const M_DROP = 'm_drop';
	const M_ACC_ = 'm_acc_';
	const M_ATK_ = 'm_atk_';
	const M_VOLLEY_SHOT = 'm_volley_shot';
	const M_VOLLEY_SHOT_ = 'm_volley_shot_';
	const M_MISS = 'm_miss';
	const M_ABS_ = 'm_abs_';
	const M_SPD_ = 'm_spd_';
	const M_MAN_ = 'm_man_';
	const P_REPAIR_HP_ = 'p_repair_hp_';
	const P_REPAIR_DEF_ = 'p_repair_def_';
	const M_REPAIR_ = 'm_repair_';
	const COST_REPAIR_ = 'cost_repair_';
	const COST_AUTOREPAIR_ = 'cost_autorepair_';
	const M_COST_REPAIR_ = 'm_cost_repair_';
	const M_MOB_ATK = 'm_mob_atk';
	const P_AUTOREPAIR_HP_ = 'p_autorepair_hp_';
	const P_AUTOREPAIR_DEF_ = 'p_autorepair_def_';
	const P_PVP_RANGE = 'p_pvp_range';
	const P_EVT_WORMHOLE_HOPS = 'p_evt_wormhole_hops';

	const P_MOB_PARAM_STEP = 'p_mob_param_step';
	const P_MOB_PARAM_A1 = 'p_mob_param_a1';
	const P_MOB_PARAM_D = 'p_mob_param_d';
	const P_MOB_PARAM_DIV = 'p_mob_param_div';
	

	const REL_TYPE_MUL = '*';
	const REL_TYPE_PERCENT = '%';
	
	private $_map = [];
	private $_db = null;

	public function __construct($db)
	{
		$this->_db = $db;
	}
	
	public function load()
	{
		$sth = $this->_db->getPdo()->prepare('select * from server_var');
		$sth->execute();
		while($row = $sth->fetch(PDO::FETCH_ASSOC)) {
			$id = $row['name'];
			$this->_map[$id] = $row;
		}
		$sth->closeCursor();
	}

	public function reload()
	{
		$this->_map = [];
		$this->load();
	}
	
	public function & get($id)
	{
		if (isset($this->_map[$id])) {
			return $this->_map[$id];
		}
		
		$o = null;
		return $o;
	}
	
	public function getInt($id, $default = false)
	{
		$var = $this->get($id);
		if ($var) {
			return intval($var['min_value']);
		}

		return $default;
	}

	public function getRelValue($id, $model = null, $default = false)
	{
		$var = $this->get($id);
		if (!$var) {
			return $default;
		}

		$val = floatval($var['min_value']);
		if (!empty($var['max_value'])) {
			$diff = floatval($var['max_value']) - $val;
			$val += $diff * mt_rand(0, 1000) / 1000;
		}
		
		if (!$model || !isset($var['rel'])) {
			return $val;
		}

		$rel = $var['rel'];
		if (property_exists($model, $rel)) {
			switch ($var['type']) {
				case static::REL_TYPE_MUL: return $val * $model->$rel;
				case static::REL_TYPE_PERCENT: return $model->$rel * $val / 100;
				default: die('ERROR: ServerVar ' . $id . ' type out of range: ' . $var['type'] . "\n");
			}
		}

		return $val;
	}

	public function getPDiffValue($id)
	{
		$v = (int) (100 * $this->getRelValue($id)) - 100;
		if ($v > 0) {
			$v = '+' . $v;
		}

		return $v;
	}

	public function getRangeInt($id) {
		$var = $this->get($id);
		if (!$var) {
			return false;
		}

		return mt_rand($var['min_value'], $var['max_value']);
	}
}
