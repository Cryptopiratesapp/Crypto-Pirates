<?php

namespace app\components;

class ChanceProvider
{
	const CP_UNPOOLED = false;
	const CP_POOLED = true;
	const CP_POOLED_PREFIX = 'p';

	private $_cache = [];
	private $_null = null;
	
	public function reset()
	{
		$this->_cache = [];
	}
	
	/**
	 * 
	 * @param array $items [chance => [item, item], chance => [ ... ], ... ]
	 * @return string composite_key
	 * @throws Exception
	 * 
	 * Pooled means that items with the same chance are combined in pools.
	 * A pool has a chance to be chosen, and then a random item is chosen from the pool.
	 */
	public function setItemsPooled(& $items)
	{
		if (!$items) {
			throw new Exception('no items');
		}

		$chance_keys = array_keys($items);
		$composite_key = self::CP_POOLED_PREFIX . implode('_', $chance_keys);

		if (!array_key_exists($composite_key, $this->_cache)) {
			$sum_chances = array_sum($chance_keys);
			if (isset($items[0]) && $sum_chances < 1000) {
				$sum_chances = 1000;
			}

			if ($sum_chances != 1000) {
				die("pooled sum_chances $sum_chances is not 1000 for composite key $composite_key\n");
			}

			$this->_cache[$composite_key] = array_fill(0, $sum_chances, 0);
			$pos = 0;
			for ($i = 0; $i < count($chance_keys); $i++) {
				$chance = $chance_keys[$i];
				for ($c = 0; $c < $chance; $c++) {
					$this->_cache[$composite_key][$pos++] = $chance;
				}
			}
		}

		return $composite_key;
	}

	/**
	 * 
	 * @param array $items [chance => [item, item], chance => [ ... ], ... ]
	 * @return string composite_key
	 * @throws Exception
	 * 
	 * Unpooled means that items with the same chance are combined in pools.
	 * But each item in the pool has the same chance as the pool itself,
	 * So effectively chance of the pool is multiplied by number of items in it
	 */
	public function setItems(& $items)
	{
		if (!$items) {
			throw new Exception('no items');
		}

		$chance_keys = array_keys($items);
		$composite_key = implode('_', $chance_keys);

		if (!array_key_exists($composite_key, $this->_cache)) {
			$sum_chances = 0;
			for ($i = 0; $i < count($chance_keys); $i++) {
				$chance = (int) $chance_keys[$i];
				$sum_chances += $chance * count($items[$chance]);
			}

			if (isset($items[0])) {
				$sum_chances += count($items[0]);
				if ($sum_chances < 1000) {
					$sum_chances = 1000;
				}
			}

			if ($sum_chances != 1000) {
				die("unpooled sum_chances $sum_chances is not 1000 for composite key $composite_key\n");
			}

			$this->_cache[$composite_key] = array_fill(0, $sum_chances, 0);
			$pos = 0;
			for ($i = 0; $i < count($chance_keys); $i++) {
				$chance = $chance_keys[$i];
				$cnt = $chance * count($items[$chance]);
				for ($c = 0; $c < $cnt; $c++) {
					$this->_cache[$composite_key][$pos++] = $chance;
				}
			}
		}

		return $composite_key;
	}

	/**
	 * list of items: [chance => [item, item], chance => [ ... ], ... ]
	 */
	public function & getItem(& $items, $pooled)
	{
		if (!$items) {
			return $this->_null;
		}

		$composite_key =
			($pooled == self::CP_POOLED ? self::CP_POOLED_PREFIX : null)
			. implode('_', array_keys($items));

		$r = array_rand($this->_cache[$composite_key]);
		$chance = $this->_cache[$composite_key][$r];
		$pool = $items[$chance];

		return $pool[array_rand($pool)];
	}
	
	public function & getItemDynamic(& $items, $pooled)
	{
		if (!$items) {
			return $this->_null;
		}

		$composite_key = null;
		if ($pooled) {
			$composite_key = $this->setItemsPooled($items);
		} else {
			$composite_key = $this->setItems($items);
		}

		$r = array_rand($this->_cache[$composite_key]);
		$chance = $this->_cache[$composite_key][$r];
		$pool = $items[$chance];

		return $pool[array_rand($pool)];
	}
}
