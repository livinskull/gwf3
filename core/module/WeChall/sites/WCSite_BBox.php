<?php
#challs:maxchalls:usercount
class WCSite_BBox extends WC_Site
{
	public function parseStats($url)
	{
		if (false === ($result = GWF_HTTP::getFromURL($url, false)))
		{
			return htmlDisplayError(WC_HTML::lang('err_response', array(GWF_HTML::display($result), $this->displayName())));
		}
		
		$stats = explode(":", $result);
		if (count($stats) < 3)
		{
			return htmlDisplayError(WC_HTML::lang('err_response', array(GWF_HTML::display($result), $this->displayName())));
		}
		$onsitescore = intval($stats[0]);
		$onsitescore = Common::clamp($onsitescore, 0);
		$maxscore = intval($stats[1]);
		$usercount = intval($stats[2]);
		$onsiterank = isset($stats[3]) ? intval($stats[3]) : -1;
		
		$challcount = $maxscore;
		if ($maxscore === 0 || $challcount === 0 || $usercount === 0)
		{
			return htmlDisplayError(WC_HTML::lang('err_response', array(GWF_HTML::display($result), $this->displayName())));
		}
		return array($onsitescore, $onsiterank, $onsitescore, $maxscore, $usercount, $challcount);
	}
}
