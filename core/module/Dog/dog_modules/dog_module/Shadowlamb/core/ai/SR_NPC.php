<?php
require_once 'SR_NPCBase.php';

/**
 * NPC AI extension.
 * @author gizmore
 */
abstract class SR_NPC extends SR_NPCBase
{
	const NEED_HEAL_MULTI = 0.60;
	const NEED_ETHER_MULTI = 0.20;
	
	public function onInitNPC(SR_Party $ep) {}
	
	/**
	 * Override the default combat timer.
	 * @see SR_Player::combatTimer()
	 */
	public function combatTimer()
	{
		if (!$this->isBusy())
		{
// 			if (LAMB_DEV)
// 			{
// 				SR_AICMD::combatTimer($this);
// 			}
// 			else
// 			{
				$this->combatAI();
// 			}
			parent::combatTimer();
		}
	}
	private function combatAIPushUse(SR_Item $item, $argstr='') { $this->combatPush('use '.$item->getItemName().($argstr===''?'':' '.$argstr)); return true; }
	private function combatAIPushSpell(SR_Spell $spell, $argstr='') { $this->combatPush('spell '.$spell->getName().($argstr===''?'':' '.$argstr)); return true; }
	private function combatAIPushEquip(SR_Item $item) { $this->combatPush('equip '.$item->getItemName()); }
	private function cmd($cmd='say ERROR :D', $args=NULL)
	{
		if (is_string($args))
		{
			$cmd .= $args;
		}
		elseif (is_array($args))
		{
			$cmd .= implode(' ', $args);
		}
		Shadowcmd::onTrigger($player, $cmd);
	}
	/**
	 * The AI is gonna inject a command.
	 */
	private function combatAI()
	{
		if ($this->getParty() === false)
		{
			return;
		}
//		echo __METHOD__.PHP_EOL;
		
		if ($this->getWeapon() instanceof Item_Fists)
		{
			if ($this->combatAIEquipWeapon())
			{
				return;
			}
		}
		
		if ($this->combatAIHeal())
		{
			return;
		}
		
		if ($this->combatAIConsume())
		{
			return;
		}
		
		if ($this->combatAISpell())
		{
			return;
		}
		
		if ($this->combatAIGrenade())
		{
			return;
		}
	}
	

	###############
	### AI Heal ###
	###############
	/**
	 * The AI wants to heal stuff.
	 */
	private function combatAIHeal()
	{
		# We need heal itself!
		if ($this->needsHeal())
		{
			if (false !== ($spell = $this->getHealSpell())) {
				return $this->combatAIPushSpell($spell, $this->getName());
			}
			if (false !== ($food = $this->getFood())) {
				return $this->combatAIPushUse($food);
			}
			if (false !== ($item = $this->getHealItem())) {
				return $this->combatAIPushUse($item);
			}
		}
		
		# We need to heal a friend!
		if (false !== ($target = $this->combatAIGetHealTarget()))
		{
			if (false !== ($spell = $this->getHealSpell())) {
				return $this->combatAIPushSpell($spell, $target->getName());
			}
			if (false !== ($item = $this->getHealItem())) {
				return $this->combatAIPushUse($item, $target->getName());
			}
		}
		
		return false;
	}
	
	###################
	### Heal Target ###
	###################
	private function combatAIGetHealTarget()
	{
		$possible = array();
		foreach ($this->getParty()->getMembers() as $member)
		{
			if ($member->needsHeal())
			{
				$possible[] = $member;
			}
		}
		return count($possible) === 0 ? false : $possible[array_rand($possible, 1)];
	}
	
	##################
	### Heal Spell ###
	##################
	private function hasHealSpell()
	{
		$spells = $this->getHealSpells();
		return count($spells) > 0;
	}
	
	private function getHealSpell()
	{
		$spells = $this->getHealSpells();
		if (count($spells) === 0) {
			return false;
		}
		return $spells[0];
	}
	
	private function getHealSpells()
	{
		$back = array();
		foreach ($this->getSpellData() as $name => $level)
		{
			$spell = SR_Spell::getSpell($name);
			if ($spell instanceof SR_HealSpell)
			{
				$level = $spell->getLevel($this);
				if ($spell->hasEnoughMP($this, $level))
				{
					$back[] = $spell;
				}
			}
		}
		return $back;
	}
	
	#################
	### Heal Item ###
	#################
	private function getHealItem()
	{
		$items = $this->getHealItems();
		return count($items) ? $items[array_rand($items, 1)] : false;
	}
	
	private function getHealItems()
	{
		return $this->getInventory()->getItemsByClass('SR_HealItem');
	}
	
	############
	### Food ###
	############
	private function getFood()
	{
		$items = $this->getFoodItems();
		return count($items) ? $items[array_rand($items, 1)] : false;
	}
	
	private function getFoodItems()
	{
		return $this->getInventory()->getItemsByClass('SR_Food');
	}
	
	##################
	### AI Consume ###
	##################
	
	/**
	 * The AI wants to consume it's consumeables and potions.
	 */
	private function combatAIConsume()
	{
		if (rand(0, 2) !== 0) {
			return false;
		} 
		if (false !== ($item = $this->getPotion())) {
			return $this->combatAIPushUse($item);
		}
		return false;
	}

	private function getPotions()
	{
		return $this->getInventory()->getItemsByClass('SR_Potion');
	}
	
	private function getPotion()
	{
		$potions = $this->getPotions();
		return count($potions) ? $potions[array_rand($potions, 1)] : false;
	}
	
	################
	### AI Magic ###
	################
	/**
	 * The AI wants to cast a combat spell.
	 */
	private function combatAISpell()
	{
		switch (rand(1, 2))
		{
			case 1:
				return $this->combatAISpellSupportive();
			case 2:
				return $this->combatAISpellOffensive();
			default:
				return false;
		}
	}
	
	####################
	### SupportSpell ###
	####################
	private function combatAISpellSupportive()
	{
		if (false !== ($spell = $this->getSupportSpell())) {
			return $this->combatAIPushSpell($spell);
		}
		return false;
	}
	
	private function getSupportSpell()
	{
		$spells = $this->getSupportSpells();
		return count($spells) ? $spells[array_rand($spells, 1)] : false;
	}
	
	private function getSupportSpells()
	{
		$back = array();
		foreach ($this->getSpellData() as $name => $level)
		{
			$spell = SR_Spell::getSpell($name);
			if ($spell instanceof SR_SupportSpell)
			{
				if ($spell->hasEnoughMP($this, $spell->getLevel($this)))
				{
					$back[] = $spell;
				}
			}
		}
		return $back;
	}
	
	####################
	### Combat Spell ###
	####################
	private function combatAISpellOffensive()
	{
		if (false !== ($spell = $this->getCombatSpell())) {
			return $this->combatAIPushSpell($spell, $this->combatAIGetTarget()->getName());
		}
		return false;
	}
	
	private function combatAIGetTarget()
	{
		$p = $this->getParty();
		$ep = $p->getEnemyParty();
//		$emc = $ep->getMemberCount();
		$em = $ep->getMembers();
		return $em[array_rand($em)];
//		return $ep->getMemberByEnum(rand(1, $emc));
	}
	
	private function getCombatSpell()
	{
		$spells = $this->getCombatSpells();
		return count($spells) > 0 ? $spells[array_rand($spells, 1)] : false;
	}
	
	private function getCombatSpells()
	{
		$back = array();
		foreach ($this->getSpellData() as $name => $level)
		{
			$spell = SR_Spell::getSpell($name);
			if ($spell instanceof SR_CombatSpell)
			{
				if ($spell->hasEnoughMP($this, $spell->getLevel($this)))
				{
					$back[] = $spell;
				}
			}
		}
		return $back;
	}
	
	###############
	### Grenade ###
	###############
	private function combatAIGrenade()
	{
		if (rand(0, 4)===0)
		{
			if (false !== ($item = $this->getGrenade())) {
				return $this->combatAIPushUse($item);
			}
		}
		return false;
	}
	
	public function getGrenade()
	{
		$items = $this->getGrenades();
		return count($items) ? $items[array_rand($items, 1)] : false;
	}
	
	public function getGrenades()
	{
		return $this->getInventory()->getItemsByClass('SR_Grenade');
	}

	public function combatAIEquipWeapon()
	{
		if (false !== ($weapon = $this->getBestWeapon()))
		{
			$this->combatAIPushEquip($weapon);
			return true;
		}
		return false;
	}
	
	private function getBestWeapon()
	{
		$best = false;
		foreach ($this->getInventoryItems() as $item)
		{
			if ($item instanceof SR_FireWeapon)
			{
				if (!$this->hasAmmoFor($item))
				{
					continue;
				}
			}
			elseif ($item instanceof SR_MeleeWeapon)
			{
			}
			else
			{
				continue;
			}
			
			if (!$this->canEquip($item))
			{
				continue;
			}
			
			if ($this->isWeaponBetterThan($item, $best))
			{
				$best = $item;
			}
		}
		return $best;
	}
	
	public function canEquip(SR_Equipment $item)
	{
		return false === Shadowfunc::checkRequirements($this, $item->getItemRequirements());
	}
	
	public function hasAmmoFor(SR_FireWeapon $weapon)
	{
		return $this->getInvItemByName($weapon->getAmmoName(), false) !== false;
	}
	
	public function isWeaponBetterThan(SR_Weapon $a, $b)
	{
		if (is_bool($b))
		{
			return true;
		}
		$b instanceof SR_Weapon;
		$amod = $a->getItemModifiersA($this);
		$bmod = $b->getItemModifiersA($this);
		$admg = isset($amod['max_dmg']) ? $amod['max_dmg'] : 0;
		$bdmg = isset($bmod['max_dmg']) ? $bmod['max_dmg'] : 0;
		return $admg > $bdmg;
	}
}
?>
