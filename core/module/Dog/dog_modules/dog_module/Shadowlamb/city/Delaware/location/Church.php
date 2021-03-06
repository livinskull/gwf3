<?php
final class Delaware_Church extends SR_Location
{
	public function getFoundPercentage() { return 80.00; }
	public function getNPCS(SR_Player $player) { return array('pray' => 'Delaware_Priest'); }

// 	public function getFoundText(SR_Player $player) { return 'You find a church. The billboard reads: "Your Sins are Forgiven. The path is still open."'; }
// 	public function getEnterText(SR_Player $player) { return 'You enter the church. You use #pray to talk to a priest here.'; }
	public function getFoundText(SR_Player $player) { return $this->lang($player, 'found'); }
	public function getEnterText(SR_Player $player) { return $this->lang($player, 'enter'); }
}
?>
