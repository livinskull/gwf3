<?php
#rank:score:solved_challenges:usercount:maxscore:challcount
class WCSite_TDH extends WC_Site
{
	public function parseStats($url)
	{
		if (false === ($result = GWF_HTTP::getFromURL($url, false))) {
			return htmlDisplayError(WC_HTML::lang('err_response', array(GWF_HTML::display($result), $this->displayName())));
		}
		$stats = explode(":", $result);
		if (count($stats) !== 6) {
			return htmlDisplayError(WC_HTML::lang('err_response', array(GWF_HTML::display($result), $this->displayName())));
		}

		$rank = intval($stats[0]);
		$onsitescore = intval($stats[1]);
		$onsitescore = Common::clamp($onsitescore, 0);
		$solved_challs = intval($stats[2]);
		$usercount = intval($stats[3]);
		$maxscore = intval($stats[4]);
		$challcount = intval($stats[5]);
		if ($maxscore === 0 || $challcount === 0 || $usercount === 0) {
			return htmlDisplayError(WC_HTML::lang('err_response', array(GWF_HTML::display($result), $this->displayName())));
		}
		
		return array($onsitescore, $rank, $solved_challs, $maxscore, $usercount, $challcount);
	}
}
?>
