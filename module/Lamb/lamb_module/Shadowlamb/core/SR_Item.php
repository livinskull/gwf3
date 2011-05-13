<?php
require_once 'item/SR_Rune.php';
require_once 'item/SR_Usable.php';
require_once 'item/SR_QuestItem.php';
require_once 'item/SR_Consumable.php';
require_once 'item/SR_Grenade.php';
require_once 'item/SR_Equipment.php';
require_once 'item/SR_Cyberware.php';
require_once 'item/SR_Amulet.php';
require_once 'item/SR_Armor.php';
require_once 'item/SR_Boots.php';
require_once 'item/SR_Earring.php';
require_once 'item/SR_Helmet.php';
require_once 'item/SR_Legs.php';
require_once 'item/SR_Ring.php';
require_once 'item/SR_Shield.php';
require_once 'item/SR_Weapon.php';
require_once 'item/SR_FireWeapon.php';
require_once 'item/SR_MeleeWeapon.php';

/**
 * A shadowrum item.
 * @author gizmore
 */
class SR_Item extends GDO
{
	####################
	### Static Items ###
	####################
	private static $items = array();
	/**
	 * @param string $name
	 * @return SR_Item
	 */
	public static function getItem($name) { return self::$items[$name]; }
	public static function getAllItems() { return self::$items; }
	public static function includeItem($filename, $fullpath)
	{
		Lamb_Log::log("SR_Item::initItem($filename)");
		require_once $fullpath;
		$itemname = substr($filename, 0, -4);
		$classname = 'Item_'.$itemname;
		if (!class_exists($classname)) {
			Lamb_Log::log("SR_Item::initItem($fullpath) failed: no such class: $classname");
		}
		$item = new $classname(array(
			'sr4it_id' => 0,
			'sr4it_uid' => 0,
			'sr4it_name' => $itemname,
			'sr4it_ammo' => 0,
			'sr4it_amount' => 1,
			'sr4it_health' => 10000,
			'sr4it_modifiers' => NULL,
		));
		self::$items[$itemname] = $item;
	}
	
	public static function deleteAllItems(SR_Player $player)
	{
		$pid = $player->getID();
		return self::table(__CLASS__)->deleteWhere("sr4it_uid=$pid");
	}
	
	###########
	### GDO ###
	###########
	public function getClassName() { return __CLASS__; }
	public function getTableName() { return GWF_TABLE_PREFIX.'sr4_item'; }
	public function getColumnDefines()
	{
		return array(
			'sr4it_id' => array(GDO::AUTO_INCREMENT),
			'sr4it_uid' => array(GDO::UINT|GDO::INDEX, GDO::NOT_NULL),
			'sr4it_name' => array(GDO::VARCHAR|GDO::ASCII|GDO::CASE_S, GDO::NOT_NULL, 63),
			'sr4it_ammo' => array(GDO::UINT, 0),
			'sr4it_amount' => array(GDO::UINT, 1),
			'sr4it_health' => array(GDO::UINT, 10000),
			'sr4it_modifiers' => array(GDO::TEXT|GDO::ASCII|GDO::CASE_S),
		);
	}
	public function getID() { return $this->getInt('sr4it_id'); }
	public function getName() { return $this->getVar('sr4it_name'); }
	public function getOwner() { return Shadowrun4::getPlayerByPID($this->getOwnerID()); }
	public function getOwnerID() { return $this->getVar('sr4it_uid'); }
	public function getAmmo() { return $this->getVar('sr4it_ammo'); }
	public function getAmount() { return $this->getVar('sr4it_amount'); }
	public function getHealth() { return $this->getVar('sr4it_health'); }
	public function getModifiers() { return $this->getVar('sr4it_modifiers'); }
	
	public function isEquipped(SR_Player $player) { return false; }
	
	##################
	### StaticLoad ###
	##################
	public static function getTotalItemCount()
	{
		return count(self::$items);
	}
	/**
	 * @param string $itemname
	 * @param array $data
	 * @return SR_Item
	 */
	protected static function instance($itemname, $data=NULL)
	{
		if (!array_key_exists($itemname, self::$items)) {
			Lamb_log::log(sprintf('SR_Item::instance() failed: Unknown itemname: %s.', $itemname));
			return false;
		}
		
		$classname = 'Item_'.$itemname;

		if ($data === NULL)
		{
			$is_null = true;
			$data = array(
				'sr4it_id' => 0,
				'sr4it_uid' => 0,
				'sr4it_name' => $itemname,
				'sr4it_ammo' => 0,
				'sr4it_amount' => 1,
				'sr4it_health' => 10000,
				'sr4it_modifiers' => NULL,
			);
		}
		else {
			$is_null = false;
		}
		
		$back = new $classname($data);
		
		if ($is_null === true)
		{
			$back->setVar('sr4it_ammo', $back->getBulletsMax());
		}
		
		return $back;
	}
	
	/**
	 * @param unknown_type $itemid
	 * @return SR_Item
	 */
	public static function getByID($itemid)
	{
		if (0 >= ($itemid = (int) $itemid)) {
			return false;
		}
		
		$db = gdo_db();
		$table = GWF_TABLE_PREFIX.'sr4_item';
		if (false === ($result = $db->queryFirst("SELECT * FROM $table WHERE sr4it_id=$itemid"))) {
			return false;
		}

		if (false === ($item = self::instance($result['sr4it_name'], $result))) {
			return false;
		}

		$item->initModifiersB();

		return $item;
	}

	public static function checkModifiers($modstring)
	{
		foreach (explode(',', $modstring) as $modstr)
		{
			list($k, $v) = explode(':', $modstr);
			if (!self::isValidModifier($k)) {
				Lamb_Log::log(sprintf('Invalid modstring: %s. Invalid modifier: %s.', $modstring, $k));
				return false;
			}
		}
		return true;
	}
	
	private static function isValidModifier($k)
	{
		if (in_array($k, SR_Player::$ATTRIBUTE)) {
			return true;
		}
		if (in_array($k, SR_Player::$SKILL)) {
			return true;
		}
		if (in_array($k, SR_Player::$STATS)) {
			return true;
		}
		return false;
	}
	
	/**
	 * LeatherVest_of_strength:1,quickness:4,marm:4,foo:4
	 * @param string $itemname
	 * @return SR_Item
	 */
	public static function createByName($itemname, $amount=true, $insert=true)
	{
		$name = Common::substrUntil($itemname, '_of_', $itemname);
		if (!array_key_exists($name, self::$items)) {
			Lamb_log::log(sprintf('SR_Item::createByName(%s) failed: Unknown itemname: %s.', $itemname, $name));
			return false;
		}
		$classname = "Item_$name";

		
		if ('' === ($modstring = Common::substrFrom($itemname, '_of_', ''))) {
			$modifiers = NULL;
		}
		else {
			$modifiers = $modstring;
			if (false === self::checkModifiers($modstring)) {
				return false;
			}
		}
		
		$item = self::instance($name);
		
		if ($amount === true)
		{
			$amount = self::$items[$name]->getItemDefaultAmount();
		}
		$item->setVar('sr4it_amount', $amount);
		$item->setVar('sr4it_modifiers', $modifiers);
		if ($insert === true)
		{
			if (false === $item->insert()) {
				return false;
			}
		}
		$item->initModifiersB();
		return $item;
	}
	
	############
	### Item ###
	############
	private $store_price = false;
	public function setStorePrice($price) { $this->store_price = $price; }
	public function getStorePrice() { return $this->store_price === false ? $this->getItemPrice() : $this->store_price; }
	
	private $modifiers = NULL;
	public function getItemModifiersB() { return $this->modifiers; }
	public function initModifiersB()
	{
		if (NULL === ($modstring = $this->getVar('sr4it_modifiers'))) {
			$this->modifiers = NULL;
			return;
		}
		$this->modifiers = array();
		foreach (explode(',', $modstring) as $data)
		{
			$data = explode(':', $data);
			$this->modifiers[$data[0]] = (float)$data[1];
		}
	}
	
	public function addModifiers(array $modifers, $update=true)
	{
		foreach ($modifers as $k => $v)
		{
			if (isset($this->modifiers[$k])) {
				$this->modifiers[$k] += $v;
			} else {
				$this->modifiers[$k] = $v;
			}
		}
		return $update === false ? true : $this->updateModifiers();
	}
	
	public function updateModifiers()
	{
		if (count($this->modifiers) === 0) {
			$modstring = NULL;
		}
		else {
			$modstring = '';
			foreach ($this->modifiers as $k => $v)
			{
				$modstring .= sprintf(',%s:%s', $k, $v);
			}
			$modstring = substr($modstring, 1);
		}
		return $this->saveVar('sr4it_modifiers', $modstring);		
	}
	
	
	public function getItemName()
	{
		$back = $this->getName();
		if ($this->modifiers === NULL) {
			return $back;
		}
		$mod = '';
		foreach ($this->modifiers as $key => $value)
		{
			$mod .= sprintf(',%s:%s', $key, $value);
		}
		return $back.'_of_'.substr($mod, 1);
	}
	
	public function deleteItem(SR_Player $owner)
	{
		if (false === $owner->removeFromInventory($this)) {
			Lamb_Log::log(sprintf('Item %s(%d) can not remove from inventory!', $this->getItemName(), $this->getID()));
			return false;
		}
		if (false === $this->delete()) {
			Lamb_Log::log(sprintf('Item %s(%d) can not delete me!', $this->getItemName(), $this->getID()));
			return false;
		}
		return true;
	}
	
	public function getItemModifiers(SR_Player $player)
	{
		$weight = $this->getItemWeight();
		if ($this->isItemStackable()) {
			$weight *= $this->getAmount();
		}
		
		$back = array_merge(array('weight'=> $weight), $this->getItemModifiersA($player));

		if (NULL !== ($modB = $this->getItemModifiersB())) {
			$back = self::mergeModifiers($back, $modB);
		}
		return $back;
	}
	
	public function getItemInfo(SR_Player $player)
	{
		return sprintf(
			'%s%s.%s%s%s%s',
			$this->getItemDescription(),
//			$this->getItemTypeDescr($player),
			$this->displayModifiersA($player),
			$this->displayModifiersB($player),
			$this->displayRequirements($player),
			$this->displayWeightB(),
			$this->displayWorth()
		);
	}
	
	private function displayWorth()
	{
		$price = $this->getItemPrice();
		if ($price > 0) {
			return sprintf(' Worth: %s.', Shadowfunc::displayPrice($price));
		}
		return '';
	}
	
	public function useAmount(SR_Player $player, $amount=1)
	{
		if ($amount > $this->getAmount()) {
			Lamb_Log::log(sprintf('Item %s(%d) can not use amount %d!', $this->getItemName(), $this->getID(), $amount));
			return false;
		}
		if (false === $this->increase('sr4it_amount', -$amount)) {
			Lamb_Log::log(sprintf('Item %s(%d) can not decrease amount %d!', $this->getItemName(), $this->getID(), $amount));
			return false;
		}
		if ($this->getAmount() <= 0) {
			return $this->deleteItem($player);
		}
		return true;
	}
	
	###############
	### Display ###
	###############
	public function displayType()
	{
		return '';
		return sprintf('%s(%s)', $this->getItemType(), $this->getItemSubType());
	}
	
	public function displayRequirements(SR_Player $player)
	{
		return Shadowfunc::getRequirements($player, $this->getItemRequirements());
	}
	
	public function displayModifiersA(SR_Player $player)
	{
		return Shadowfunc::getModifiers($this->getItemModifiersA($player));
	}
	
	public function displayModifiersB(SR_Player $player)
	{
		if ($this->modifiers === NULL) {
			return '';
		}
		return sprintf(' Modifiers: %s.', Shadowfunc::getModifiers($this->getItemModifiersB()));
	}
	
	private function displayWeightB()
	{
		return ('' === ($s = $this->displayWeight())) ? '' : ' Weight: '.$s.'.';
	}
	
	public function displayWeight()
	{
		$weight = $this->getItemWeight()*$this->getAmount();
		if ($weight <= 0) {
			return '';
		}
		return Shadowfunc::displayWeight($weight);
	}
	
	public function getItemPriceStatted()
	{
		$price = $this->getItemPrice();
		if (NULL === ($mods = $this->getItemModifiersB())) {
			return $price;
		}
		return $price + SR_Rune::calcPrice($mods);
	}
	
	
	######################
	### Item Overrides ###
	######################
	public function getBulletsMax() { return 0; }
	public function getBulletsPerRound() { return 1; }
	public function getItemType() { return 'item'; }
	public function getItemSubType() { return 'item'; }
	public function getItemPrice() { return -1; }
	public function getItemWeight() { return -987654321; }
	public function getItemUsetime() { return 60; }
	public function getItemDescription() { return 'ITEM DESCRIPTION'; }
	public function getItemTypeDescr(SR_Player $player) { return ''; }
	public function getItemDefaultAmount() { return 1; }
	public function getItemRequirements() { return array(); }
	public function getItemDropChance() { return 100.00; }
	public function getItemAvail() { return 100.00; }
	public function isItemSellable() { return $this->getItemPrice() > 0; }
	public function isItemTradeable() { return true; }
	public function isItemStackable() { return true; }
	public function isItemStatted() { return $this->modifiers !== NULL; }
	public function isItemDropable() { return true; }
	public function isItemFriendly() { return false; }
	public function isItemOffensive() { return false; }
	public function getItemModifiersA(SR_Player $player) { return array(); }
	public function getItemLevel() { return -1; }
	public function getItemRange() { return 0.0; }
	
	################
	### Triggers ###
	################
	public function onItemUse(SR_Player $player, array $args)
	{
		$player->message('You can not use this item.');
		return false;
	}
	
	public function onItemEquip(SR_Player $player)
	{
		$player->message('You can not equip this item.');
		return false;
	}
	
	public function onItemUnequip(SR_Player $player)
	{
		$player->message('You can not equip this item.');
		return false;
	}
	
	############
	### Util ###
	############
	public static function mergeModifiers()
	{
		$back = array();
		foreach (func_get_args() as $arg)
		{
			if (is_array($arg))
			{
				foreach ($arg as $k => $v)
				{
					if (isset($back[$k]))
					{
						$back[$k] += $v;
					}
					else
					{
						$back[$k] = $v;
					}
				}
			}
		}
		return $back;
	}
}
?>
