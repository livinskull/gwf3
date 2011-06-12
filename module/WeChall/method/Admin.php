<?php
final class WeChall_Admin extends GWF_Method
{
	public function getUserGroups() { return GWF_Group::ADMIN; }
	
	public function execute(GWF_Module $module)
	{
		if (Common::getGet('recalc') === 'all') {
			return $this->onRecalcEverything($module);
		}
		
		if (false !== Common::getGet('fix_challs')) {
			return $this->onFixChalls($module);
		}
		if (false !== Common::getGet('fix_irc')) {
			return $this->onFixIRC($module);
		}
		
		if (false !== Common::getGet('chall_cache')) {
			return $this->onCacheChallTags($module);
		}
		
		if (false !== Common::getGet('sitetags')) {
			return $this->onCacheSiteTags($module);
		}
		
		if (false !== Common::getGet('remote_update')) {
			return $this->templateRemoteUpdate($module);
		}
		
		if (false !== Common::getPost('remote_update')) {
			return $this->onRemoteUpdate($module);
		}
		
		if (false !== Common::getPost('hardlink')) {
			return $this->onHardlink($module).$this->templateAdmin($module);
		}
		
		return $this->templateAdmin($module);
	}
	
	private function templateAdmin(Module_WeChall $module)
	{
		$formHardlink = $this->formHardlink($module);
		$tVars = array(
			'href_ddos' => $module->getMethodURL('UpdateAll'),
			'href_convert' => $module->getMethodURL('Convert'),
			'href_update' => $module->getMethodURL('Admin', '&remote_update=yes'),
			'href_chall_cache' => $module->getMethodURL('Admin', '&chall_cache=yes'),
			'href_recalc_all' => $module->getMethodURL('Admin', '&recalc=all'),
			'href_sitetags' => $module->getMethodURL('Admin', '&sitetags=yes'),
			'href_freeze' => $module->getMethodURL('Freeze'),
			'href_fix_challs' => $module->getMethodURL('Admin', '&fix_challs=yes'),
			'href_fix_irc' => $module->getMethodURL('Admin', '&fix_irc=yes'),
			'form_hardlink' => $formHardlink->templateY($module->lang('ft_hardlink')),
			'href_siteminmail' => $module->getMethodURL('Admin', '&siteminmail=yes'),
		);
		return $module->templatePHP('admin.php', $tVars);
	}
	
	private function onCacheChallTags(Module_WeChall $module)
	{
		$module->cacheChallTags();
		return $module->message('msg_cached_ctags').
		$this->templateAdmin($module);
	}
	
	private function onCacheSiteTags(Module_WeChall $module)
	{
		require_once 'module/WeChall/WC_SiteCats.php';
		WC_SiteCats::fixCatBits();
		$this->fixFavCats($module);
		return $this->templateAdmin($module);
	}
	
	private function fixFavCats(Module_WeChall $module)
	{
		require_once 'module/WeChall/WC_FavCats.php';
		$cats = GWF_TABLE_PREFIX.'wc_sitecat';
		$table = GDO::table('WC_FavCats');
		if (false === $table->deleteWhere("IF((SELECT 1 FROM $cats WHERE sitecat_name=wcfc_cat LIMIT 1), 0, 1)")) {
			echo GWF_HTML::err('ERR_DATABASE', array(__FILE__, __LINE__));
		}
		
		$num_deleted = $table->affectedRows();
		echo GWF_HTML::message('WC', sprintf('Deleted %d invalid favcat links!', $num_deleted));
	}
	
	private function onRecalcEverything(Module_WeChall $module)
	{
		require_once 'module/WeChall/WC_RegAt.php';
		WC_Site::recalcAllSites();
		WC_RegAt::calcTotalscores();
		
		return $this->templateAdmin($module);
	}

	private function onFixChalls(Module_WeChall $module)
	{
		$table = GDO::table('WC_Challenge');
		$solved = GWF_TABLE_PREFIX.'wc_chall_solved';
		
		if (false === ($challs = $table->selectObjects('*')))
//		if (false === ($challs = $table->selectAll()))
		{
			return GWF_HTML::err('ERR_DATABASE', array(__FILE__, __LINE__)).$this->templateAdmin($module);
		}
		
		foreach ($challs as $chall)
		{
			$chall instanceof WC_Challenge;
			if (false === $chall->saveVars(array(
				'chall_creator' => $this->fixComma($chall->getVar('chall_creator')),
				'chall_creator_name' => $this->fixComma($chall->getVar('chall_creator_name')),
				'chall_tags' => $this->fixComma($chall->getVar('chall_tags')),
			))) {
				return GWF_HTML::err('ERR_DATABASE', array(__FILE__, __LINE__)).$this->templateAdmin($module);
			}
		}
		
		if (false === $table->update("chall_solvecount=(SELECT COUNT(*) FROM $solved WHERE csolve_cid=chall_id AND csolve_date!='')")) {
			return GWF_HTML::err('ERR_DATABASE', array(__FILE__, __LINE__)).$this->templateAdmin($module);
		}
		
		return $this->templateAdmin($module);
	}
	
	private function fixComma($s)
	{
		$s = preg_replace('/[: ]/', ',', $s);
		$s = trim($s, ', ');
		$s = preg_replace('/[,]{2,}/', ',', $s);
		return ",$s,";
	}
	
	private function onFixIRC(Module_WeChall $module)
	{
		if (false === ($sites = GDO::table('WC_Site')->selectObjects())) {
			return GWF_HTML::err('ERR_DATABASE', array(__FILE__, __LINE__)).$this->templateAdmin($module);
		}
		foreach ($sites as $site)
		{
			$site instanceof WC_Site;
			$site->saveVar('site_irc', $this->fixIRC($site->getVar('site_irc')));
		}
		return $this->templateAdmin($module);
	}
	
	private function fixIRC($url)
	{
		if ($url === '') {
			return '';
		}
		if (1 === preg_match('#^ircs?://#', $url)) {
			return $url;
		}
		return 'irc://'.$url;
	}
	
	################
	### Hardlink ###
	################
	private $user;
	private $site;
	public function validate_username(Module_WeChall $module, $arg)
	{
		if (false === ($this->user = GWF_User::getByName($arg))) {
			return GWF_HTML::lang('ERR_UNKNOWN_USER');
		}
		return false;
	}
	public function validate_onsitename(Module_WeChall $m, $arg)
	{
		if ($arg === '') {
			if ($this->site === false) {
				return $m->lang('err_onsitename', array('Site'));
			}
			else {
				return $m->lang('err_onsitename', array($this->site->displayName()));
			}
		}
		return false;
	}
	public function validate_site(Module_WeChall $module, $arg)
	{
		if (false === ($this->site = WC_Site::getByID($arg))) {
			return $module->lang('err_site');
		}
		return false;
	}
	private function formHardlink(Module_WeChall $module)
	{
		$data = array(
			'site' => array(GWF_Form::SELECT, $this->getSiteSelect(), $module->lang('th_site_name')),
			'username' => array(GWF_Form::STRING, '', $module->lang('th_user_name')),
			'onsitename' => array(GWF_Form::STRING, '', $module->lang('th_onsitename')),
			'hardlink' => array(GWF_Form::SUBMIT, $module->lang('btn_hardlink')),
		);
		return new GWF_Form($this, $data);
	}
	private function getSiteSelect()
	{
		$data = array();
		foreach (WC_Site::getSitesRanked('site_name ASC') as $site)
		{
			$data[] = array($site->getVar('site_name'), $site->getID());
		}
		$selected = (int)Common::getPost('site', 0);
		return GWF_Select::display('site', $data, $selected);
	}
	private function onHardlink(Module_WeChall $module)
	{
		$form = $this->formHardlink($module);
		if (false !== ($error = $form->validate($module))) {
			return $error;
		}
		$onsitename = Common::getPost('onsitename');
		
		$site = $this->site;
		$user = $this->user;
		
		require_once 'module/WeChall/WC_RegAt.php';
		require_once 'module/WeChall/WC_Freeze.php';
		
		if (false !== WC_RegAt::getRegatRow($user->getID(), $site->getID())) {
			return $module->error('err_already_linked', $site->displayName());
		}
		if (WC_Freeze::isUserFrozenOnSite($user->getID(), $site->getID())) {
			return $module->error('err_site_ban', $site->displayName());
		}
		if (false !== ($regat = WC_RegAt::getByOnsitename($site->getID(), $onsitename))) {
			if (false === ($user = $regat->getUser())) {
				return GWF_HTML::err('ERR_DATABASE', array(__FILE__, __LINE__));
			}
			else {
				return $module->error('err_onsitename_taken', htmlspecialchars($onsitename), $site->displayName(), $user->displayUsername());
			}
		}
		
		$entry = new WC_RegAt(array(
			'regat_uid' => $user->getID(),
			'regat_sid' => $site->getID(),
			'regat_onsitename' => $onsitename,
			'regat_onsitescore' => 0,
			'regat_challcount' => 0,
			'regat_options' => 0,
			'regat_lastdate' => '',
			'regat_linkdate' => GWF_Time::getDate(GWF_Date::LEN_DAY),
			'regat_solved' => 0.0,
			'regat_score' => 0,
			'regat_langid' => 0,
			'regat_tagbits' => 0,
			'regat_onsiterank' => 0,
		));
		
		if (false === $entry->insert()) {
			return GWF_HTML::err('ERR_DATABASE', array(__FILE__, __LINE__));
		}
		
		return $module->message('msg_hardlinked', $user->displayUsername(), $site->displayName(), GWF_HTML::display($onsitename));
	}
}

?>