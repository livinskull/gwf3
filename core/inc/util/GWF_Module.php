<?php
class GWF_Module extends GDO
{
	public static $MODULES = array();
	
	private $module_vars;
	private $lang;
	
	const DEFAULT_PRIORITY = 50;
	const ENABLED = 0x01;
	const AUTOLOAD = 0x02;

	###########
	### GDO ###
	###########
	public function getClassName() { return __CLASS__; }
	public function getTableName() { return GWF_TABLE_PREFIX.'module'; }
	public function getOptionsName() { return 'module_options'; } 
	public function getColumnDefines()
	{
		return array(
			'module_id' => array(GDO::AUTO_INCREMENT),
			'module_name' => array(GDO::VARCHAR|GDO::ASCII|GDO::CASE_S|GDO::UNIQUE|GDO::INDEX, false, 64),
			'module_priority' => array(GDO::UINT|GDO::INDEX, self::DEFAULT_PRIORITY),
			'module_version' => array(GDO::DECIMAL, 1.00, array(2, 2)),
			'module_options' => array(GDO::UINT|GDO::INDEX, self::ENABLED),
			'vars' => array(GDO::JOIN, GDO::NULL, array('GWF_ModuleVar', 'module_id', 'modvar_mid')),
		);
	}

	public function getID() { return $this->getVar('module_id'); }
	public function getDir() { return GWF_CORE_PATH.'module/'.$this->getName(); }
	public function getName() { return $this->getVar('module_name'); }
	public function getLang() { return $this->lang; }
	public function getVersion() { return 1.00; }
	public function getVersionFS() { return sprintf('%.02f', $this->getVersion()); }
	public function getVersionDB() { return $this->getVar('module_version'); }
	public function getPriority() { return $this->getVar('module_priority'); }
	public function getDefaultEnabled() { return true; }
	public function getDefaultAutoLoad() { return false; }
	public function getDefaultPriority() { return self::DEFAULT_PRIORITY; }
	public function isCoreModule() { return false; }
	public function getDependencies() { return array(); }
	public function getOptionalDependencies() { return array(); }
	public function getModuleVars() { return $this->module_vars; }
	public function getModuleVar($var, $default=false) { return isset($this->module_vars[$var]) ? $this->module_vars[$var] : $default; }
	public function getModuleVarInt($var, $default=0) { return (int)$this->getModuleVar($var, $default); }
	public function getModuleVarBool($var, $default=false) { return $this->getModuleVar($var, $default) === '1'; }
	public function getModuleVarString($var, $default='') { return $this->getModuleVar($var, $default); }
	public function getClasses() { return array(); }
	public function getMethodURL($method, $app='') { return GWF_WEB_ROOT.'index.php?mo='.$this->getName().'&me='.$method.$app; }
	public function getAdminSectionURL() { return '#'; }
	public function hasAdminSection() { return $this->getAdminSectionURL() !== '#'; }
	public function isEnabled() { return $this->isOptionEnabled(self::ENABLED); }
	public function isInstalled() { return $this->getVersionDB() > 0; }
	public function getModuleFilePath($file) { return GWF_CORE_PATH.'module/'.$this->getName().'/'.$file; } // wont work in new baselayout!
	public function isMethodSelected($method) { return ($_GET['mo'] === $this->getName()) && ($_GET['me'] === $method); }
	public static function getModulesLoaded($format=false)
	{
		if(false === $format)
		{
			return sprintf('<a href="#" title="%s">%s</a>', implode(', ', array_keys(self::$MODULES)), count(self::$MODULES));
		}
		else
		{
			$format = str_replace('%mods%', implode(', ', array_keys(self::$MODULES)), $format);
			$format = str_replace('%count%', count(self::$MODULES), $format);
			return $format;
		}
	}
	
	public function onStartup() {}
	public function onAddHooks() {}
	public function onInstall($dropTable) { return ''; }
	public function onCronjob() {} # Output to stdout for debuglogs. Errors are redirected to stderr.
	public function isAjax() { return isset($_REQUEST['ajax']); }
	
	/**
	 * Save a module var the quick way. No validation is performed.
	 * @param string $key
	 * @param string $value
	 * @return true|false
	 */
	public function saveModuleVar($key, $value)
	{
		$id = $this->getID();
		$key = self::escape($key);
		$value = self::escape($value);
		if (false === GDO::table('GWF_ModuleVar')->update("mv_val='{$value}', mv_value='{$value}'", "mv_mid={$id} AND mv_key='{$key}'"))
		{
			return false;
		}
		$this->module_vars[$key] = $value;
		return true;
	}
	
	############
	### Lang ###
	############
	public function getLanguage() { return $this->lang; }
	public function loadLanguage($path) { if ($this->lang === NULL) { $this->lang = new GWF_LangTrans(sprintf('%smodule/%s/%s', GWF_CORE_PATH, $this->getName(), $path)); } }
	public function onLoadLanguage() { $this->lang = GWF_HTML::getLang(); }
	public function error($key, $args=NULL, $log=true, $to_smarty=false) { return GWF_HTML::error($this->getName(), $this->lang($key, $args), $log, $to_smarty); }
	public function message($key, $args=NULL, $log=true, $to_smarty=false) { return GWF_HTML::message($this->getName(), $this->lang($key, $args), $log, $to_smarty); }
	public function lang($key, $args=NULL) { return $this->lang->lang($key, $args); }
	public function langAdmin($key, $args=NULL) { return $this->lang->langAdmin($key, $args); }
	public function langUser(GWF_User $user, $key, $args=NULL) { return $this->lang->langUser($user, $key, $args); }
	public function langISO($iso, $key, $args=NULL) { return $this->lang->langISO($iso, $key, $args); }
	public function includeAjaxLang($iso=NULL) { GWF_Website::addJavascriptInline('var LANG_'.$this->getName().' = '.json_encode($this->getLang()->getTrans($iso)).';'); }
	
	################
	### Template ###
	################
	public function template($filename, $tVars=array())
	{
		$tVars['lang'] = $this->lang;
		$tVars['module'] = $this;
		return GWF_Template::templateModule($this, $filename, $tVars);
	}
	public function templatePHP($filename, $tVars=array())
	{
		$tVars['lang'] = $this->lang;
		$tVars['module'] = $this;
		return GWF_Template::templatePHPModule($this, $filename, $tVars);
	}
	
	##############
	### Loader ###
	##############
	/**
	 * Get a loaded module.
	 * @param string $modulename
	 * @return GWF_Module
	 */
	public static function getModule($modulename)
	{
		return isset(self::$MODULES[$modulename]) ? self::$MODULES[$modulename] : false;
	}
	
	/**
	 * Load a module from the database.
	 * @param string $modulename
	 * @return GWF_Module
	 */
	public static function loadModuleDB($modulename, $include=false, $load_lang=false, $check_enabled=false)
	{
		if (false === isset(self::$MODULES[$modulename]))
		{
			$enabled = $check_enabled ? ' AND module_options&'.self::ENABLED : '';
			if (false === ($data = self::table(__CLASS__)->selectFirst('*', 'module_name=\''.self::escape($modulename).'\''.$enabled)))
			{
				return false;
			}
			
			if (false === ($module = self::initModuleB($modulename, $data)))
			{
				return false;
			}
		}
		else
		{
			$module = self::$MODULES[$modulename];
		}
		
//		if (false === $module->isEnabled())
//		{
//			return false;
//		}
		
		
		if (true === $include)
		{
			$module->onInclude();
		}
		
		if (true === $load_lang)
		{
			$module->onLoadLanguage();
		}
		
		return $module;
	}
	
	/**
	 * Autoload modules with autoload flag.
	 * @todo GWF_Result or return integer / modules which could not be loaded
	 * @return boolean
	 */
	public static function autoloadModules()
	{
		if (false === ($modules = self::table(__CLASS__)->selectAll('*', 'module_options&3=3', 'module_priority ASC')))
		{
			return false; # Database Problem
		}
		$ret = true;
		foreach ($modules as $data)
		{
			$modulename = $data['module_name'];
			if (true === isset(self::$MODULES[$modulename]))
			{
				continue;
			}
			if (false === ($module = self::initModuleB($modulename, $data)))
			{
				GWF_Log::logError('Could not autoload module '.$modulename);
				// $ret = false; # die in GWF3 if one module could not be loaded?
			}
		}
		return $ret;
	}
	
	/**
	 * Init a module from assoc data array.
	 * @param string $modulename
	 * @param array $data
	 * @return GWF_Module
	 */
	private static function initModuleB($modulename, array $data)
	{
		$classname = "Module_$modulename";
		$pre = GWF_CORE_PATH;
		$path = "module/{$modulename}/{$classname}.php";
		# for using modules which aren't in include_path
		if (false === Common::isFile($pre.$path))
		{
			# required if GWF is in include_path
			if (false === Common::isFile(GWF_WWW_PATH.$path))
			{
				return false;
			}
			else
			{
				$pre = GWF_WWW_PATH;
			}
		}
		require_once $pre.$path;
		self::$MODULES[$modulename] = $m = new $classname($data);
		if (false === $m->loadVars())
		{
			return false;
		}
		
		if (true === $m->isEnabled())
		{
			$m->onStartup();
		}
		return $m;
	}
	
	public function loadVars()
	{
		$db = gdo_db();
		$id = $this->getID();
		if (false === ($result = self::table('GWF_ModuleVar')->select('mv_key,mv_val', "mv_mid=$id")))
		{
			return false;
		}
		$this->module_vars = array();
		while (false !== ($row = $db->fetchRow($result)))
		{
			$this->module_vars[$row[0]] = $row[1];
		}
		$db->free($result);
		return true;
	}
	
	############
	### Exec ###
	############
	/**
	 * Execute a method. Arguments are given via $_GET and _$POST.
	 * @param string $methodname
	 * @return string html
	 */
	public function execute($methodname)
	{
		if (false === $this->isEnabled())
		{
			return '';
		}
		
		if (false === ($method = $this->getMethod($methodname)))
		{
			return GWF_HTML::err('ERR_METHOD_MISSING', array(htmlspecialchars($methodname), $this->getName()));
		}
		
		if (false === $method->hasPermission())
		{
			return GWF_HTML::err('ERR_NO_PERMISSION');
		}
		
		return $method->execute($this);
	}

	/**
	 * Include all classes returned by $this->getClasses();
	 * You can think of it as an autoloader ... this is lame :(
	 */
	public function onInclude()
	{
		foreach ($this->getClasses() as $class)
		{
			$this->includeClass($class);
		}
	}
	
	/**
	 * Include a file in the modules directory.
	 * This function is not sanitized!
	 * @param string $class
	 */
	public function includeClass($class)
	{
		require_once GWF_CORE_PATH.'module/'.$this->getName().'/'.$class.'.php';
	}
	
	/**
	 * @param string $methodname
	 * @return GWF_Method
	 */
	public function getMethod($methodname)
	{
		$name = $this->getName();
		$methodname = str_replace('/', '', $methodname); # LFI
		$path = GWF_CORE_PATH."module/$name/method/$methodname.php";
		if (false === Common::isFile($path))
		{
			if (false === Common::isFile(GWF_PATH.$path))
			{
				return false;
			} else
			{
				$path = GWF_PATH.$path;
			}
		}
		require_once $path;
		$classname = $name.'_'.$methodname;
		if (!class_exists($classname))
		{
			return false;
		}
		return new $classname();
	}
	
	public function requestMethodB($methodname, $get=NULL, $post=NULL)
	{
		# Copy vars.
		if (is_array($post))
		{
			$_POST = $post;
			$_REQUEST = $post;
		}
		elseif (is_array($get))
		{
			$_GET = $get;
			$_REQUEST = $get;
		}
		return $this->getMethod($methodname)->execute($this);
	}
}
?>
