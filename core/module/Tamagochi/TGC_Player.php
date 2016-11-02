<?php
final class TGC_Player extends GDO
{
	private $connectionInterface = null;
	private $jsonUser = null;
	private $secret = null;
	private $lat = null;
	private $lng = null;
	
	public function getClassName() { return __CLASS__; }
	public function getTableName() { return GWF_TABLE_PREFIX.'tgc_players'; }
	public function getColumnDefines()
	{
		return array(
			'p_uid' => array(GDO::PRIMARY_KEY|GDO::UINT),

			'p_active_color' => array(GDO::ENUM, TGC_Const::NONE, TGC_Const::$COLORS),
			'p_active_element' => array(GDO::ENUM, TGC_Const::NONE, TGC_Const::$ELEMENTS),
			'p_active_skill' => array(GDO::ENUM, TGC_Const::NONE, TGC_Const::$SKILLS),
			'p_active_mode' => array(GDO::ENUM, TGC_Const::NONE, TGC_Const::$MODES),

			'p_last_color_change' => array(GDO::DATE, GDO::NULL, 14),
			'p_last_element_change' => array(GDO::DATE, GDO::NULL, 14),
			'p_last_skill_change' => array(GDO::DATE, GDO::NULL, 14),
			'p_last_mode_change' => array(GDO::DATE, GDO::NULL, 14),

			'p_fighter_xp' => array(GDO::UINT, 0),
			'p_ninja_xp' => array(GDO::UINT, 0),
			'p_priest_xp' => array(GDO::UINT, 0),
			'p_wizard_xp' => array(GDO::UINT, 0),

			'p_fighter_level' => array(GDO::UINT, 0),
			'p_ninja_level' => array(GDO::UINT, 0),
			'p_priest_level' => array(GDO::UINT, 0),
			'p_wizard_level' => array(GDO::UINT, 0),
				
			'user' => array(GDO::JOIN, GDO::NOT_NULL, array('GWF_User', 'p_uid', 'user_id')),
		);
	}
	
	private static function createPlayer(GWF_User $user)
	{
		$player = new self(array(
			'p_uid' => $user->getID(),
			'p_active_color' => TGC_Const::NONE,
			'p_active_element' => TGC_Const::NONE,
			'p_active_skill' => TGC_Const::NONE,
			'p_active_mode' => TGC_Const::NONE,
			'p_last_color_change' => null,
			'p_last_element_change' => null,
			'p_last_skill_change' => null,
			'p_last_mode_change' => null,
			'p_fighter_xp' => '0',
			'p_ninja_xp' => '0',
			'p_priest_xp' => '0',
			'p_wizard_xp' => '0',
			'p_fighter_level' => '0',
			'p_ninja_level' => '0',
			'p_priest_level' => '0',
			'p_wizard_level' => '0',
		));
		$player->insert();
		$player->setVar('user_name', $user->getVar('user_name'));
		return $player;
	}
	
	public static function getJSONUser()
	{
		if (0 == ($uid = GWF_Session::getUserID())) {
			return false;
		}
		return self::table('GWF_User')->selectFirst("user_id, user_regdate, user_gender, user_lastlogin, user_lastactivity, user_birthdate, user_countryid, user_langid, user_langid2", "user_id=$uid");
	}
	
	private function getSecret()
	{
		$uid = $this->getVar('p_uid');
		return substr(self::table('GWF_User')->selectVar('user_password', "user_id=$uid"), TGC_Const::SECRET_CUT);
	}
	
	public function getName()
	{
		return $this->getVar('user_name');
	}
	
	public static function getCurrent($create=false)
	{
		$uid = GWF_Session::getUserID();
		if ($uid == 0) {
			return false;
		}
		if ($player = self::table(__CLASS__)->selectFirstObject('*, user_name', "p_uid=$uid", '', '', array('user'))) {
			return $player;
		}
		if ($create) {
			return self::createPlayer(GWF_Session::getUser());
		}
		return false;
	}
	
	##################
	### Connection ###
	##################
	public function send($messageText)
	{
		if ($this->isConnected()) {
			$this->connectionInterface->send($messageText);
		}
	}

	public function disconnect($reason="NO_REASON")
	{
		if ($this->isConnected()) {
			$this->send("CLOSE:".$reason);
			$this->connectionInterface = null;
			$this->connectionInterface = null;
			$this->jsonUser = null;
			$this->secret = null;
			$this->lat = null;
			$this->lng = null;
		}
	}
	
	public function isConnected()
	{
		return $this->connectionInterface !== null;
	}
	
	public function hasPosition()
	{
		return $this->lat !== null;
	}
	
	public function setPosition($lat, $lng)
	{
		if ($lat && $lng) {
			$this->lat = $lat;
			$this->lng = $lng;
		}
	}
	
	public function setConnectionInterface($conn)
	{
		if ($this->isConnected()) {
			$this->disconnect();
		}
		$this->connectionInterface = $conn;
		$this->rehash();
	}
	
	###################
	### For Near me ###
	###################
	public function isNearMe(TGC_Player $player)
	{
		if (($player == $this) || (!$this->hasCoordinates() || (!$player->hasCoordinates()))) {
			return false;
		}
		return TGC_Logic::arePlayersNearEachOther($this, $player);
	}
	
	public function forNearMe($callback)
	{
		foreach (TGC_Globals::$PLAYERS as $name=> $player) {
			if ($this->isNearMe($player)) {
				call_user_func($callback, $player);
			}
		}
	}
	
	###########
	### API ###
	###########
	public function moveTo($newLat, $newLng)
	{
		$oldLat = $this->lat;
		$oldLng = $this->lng;

		$this->setPosition($newLat, $newLng);
		
		foreach (TGC_Globals::$PLAYERS as $name=> $player) {
			$oldNear = $player->isNearPosition($oldLat, $oldLng);
			$newNear = $player->isNearPosition($newLat, $newLng);
			if ($oldNear != $newNear) {
				if ($newNear === true) {
					$player->send(sprintf('APPEAR:%s:%.06f:%.06f', $this->getName(), $this->lat, $this->lng));
				}
				else {
					$player->send(sprintf('DISAPPEAR:%s:%.06f:%.06f', $this->getName(), $this->lat, $this->lng));
				}
			}
			else if ($newNear === true) {
				$player->send(sprintf('POS:%s:%.06f:%.06f', $this->getName(), $this->lat, $this->lng));
			}
		}
	}
	
	##############
	### Rehash ###
	##############
	public function rehash()
	{
		$this->rehashLevels();
		$this->rehashJSONUser();
		$this->rehashSecret();
	}
	
	private function rehashLevels()
	{
		$this->rehashSkill('fighter');
		$this->rehashSkill('ninja');
		$this->rehashSkill('priest');
		$this->rehashSkill('wizard');
	}
	
	private function rehashSkill($skill)
	{
		$xp = $this->getVar(sprintf('p_%s_xp', $skill));
		$levelvar = sprintf('p_%s_level', $skill);
		$oldLevel = (int) $this->getVar($levelvar);
		$newLevel = TGC_Logic::levelForXP($xp);
		if ($oldLevel !== $newLevel) {
			$this->setVar($levelvar, $newLevel.'');
			$this->onLevelChanged($skill, $oldLevel, $newLevel);
		}
	}
	
	private function rehashJSONUser()
	{
		if ($this->jsonUser === null) {
			$this->jsonUser = $this->getJSONUser();
		}
	}
	
	private function rehashSecret()
	{
		if ($this->secret === null) {
			$this->secret = $this->getSecret();
		}
	}
	
	private function onLevelChanged($skill, $oldLevel, $newLevel)
	{
		self::forNearMe(function($player) {
			$player->send(sprintf('LVLUP:%s:%s:%s', $skill, $oldLevel, $newLevel));
		});
	}
	
}
