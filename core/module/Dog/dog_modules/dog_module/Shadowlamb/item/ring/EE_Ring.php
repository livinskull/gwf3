<?php
final class Item_EE_Ring extends SR_Ring
{
	public function getItemLevel() { return 42; }
	public function getItemPrice() { return 5000; }
	public function getItemDropChance() { return 4; }
	public function getItemDescription() { return 'A ring made by the indian natives.'; }

	public function getItemModifiersA(SR_Player $player)
	{
		$back = array();
		switch($player->getRace())
		{
			default:
				$back['attack'] = 10.0;
				$back['wisdom'] = 1.0;
				$back['intelligence'] = 1.0;
		}
		return $back;
	}

	public function getItemModifiersB()
	{
		$mods = parent::getItemModifiersB();
		foreach ($mods as $k => $v)
		{
			$mods[$k] = floor($mods[$k]*2);
		}
		return $mods;
	}
}
?>