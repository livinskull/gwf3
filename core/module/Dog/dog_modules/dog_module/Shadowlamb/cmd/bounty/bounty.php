<?php
/**
 * Show your bounty stats.
 * @author gizmore
 */
final class Shadowcmd_bounty extends Shadowcmd
{
	public static function execute(SR_Player $player, array $args)
	{
		$bot = Shadowrap::instance($player);
		if (count($args) === 0)
		{
			return self::rply($player, '5088', array(SR_Bounty::displayBountyPlayer($player)));
// 			return $bot->reply(SR_Bounty::displayBountyPlayer($player));
		}
		
		if (count($args) > 1)
		{
			return $bot->reply(Shadowhelp::getHelp($player, 'bounty'));
		}
		
		if (false === ($target = Shadowrun4::loadPlayerByName($args[0])))
		{
			return self::rply($player, '1017');
// 			return $bot->reply('This player is unknown. Try playername{serverid}.');
		}
		
		return self::rply($player, '5089', array(SR_Bounty::displayBountyPlayer($target)));
// 		return $bot->reply(SR_Bounty::displayBountyPlayer($target));
	}
}
?>