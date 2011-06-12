<?php
/**
 * An IRC server.
 * @author gizmore
 * @version 3.01
 * @since 3.0
 */
final class Lamb_Server extends GDO
{
	# Connect retry
	const RETRY_MAX_TRY = 1000; # Try forever... kinda
	const RETRY_MIN_WAIT = 5;   # 5 seconds min wait
	const RETRY_WAIT_INC = 5;   # 5 seconds increase
	const RETRY_MAX_WAIT = 600; # 10 minutes max wait
	
	private $online = false;
	private $next_retry = 0;
	private $retry_count = 0;
	
	# Options
	const LOG_ON = 0x01;
	const LOG_OFF = 0x02;
	const NO_CLOAK = 0x04;
	
	# Current Message From
	private $lm_from = ''; # latest from/origin:
	private $lm_from_raw = ''; # latest from/origin:!IP.blub
	
	###########
	### GDO ###
	###########
	public function getClassName() { return __CLASS__; }
	public function getTableName() { return GWF_TABLE_PREFIX.'lamb_server'; }
	public function getOptionsName() { return 'serv_options'; }
	public function getColumnDefines()
	{
		return array(
			'serv_id' => array(GDO::AUTO_INCREMENT),
			'serv_host' => array(GDO::TEXT|GDO::UTF8|GDO::CASE_I, GDO::NOT_NULL),
			'serv_version' => array(GDO::TEXT|GDO::UTF8|GDO::CASE_I),
			'serv_ip' => GWF_IP6::gdoDefine(GWF_IP6::BIN_32_128, GDO::NULL),
			'serv_maxusers' => array(GDO::UINT, 0),
			'serv_maxchannels' => array(GDO::UINT, 0),
			'serv_password' => array(GDO::TEXT|GDO::UTF8|GDO::CASE_S),
			'serv_nicknames' => array(GDO::TEXT|GDO::UTF8|GDO::CASE_I),
			'serv_channels' => array(GDO::TEXT|GDO::UTF8|GDO::CASE_I),
			'serv_admins' => array(GDO::TEXT|GDO::UTF8|GDO::CASE_I),
			'serv_flood_amt' => array(GDO::UINT, Lamb_IRC::DEFAULT_FLOOD_AMOUNT),
			'serv_usermodes' => array(GDO::VARCHAR|GDO::ASCII|GDO::CASE_S, '', 255),
			'serv_chanmodes' => array(GDO::VARCHAR|GDO::ASCII|GDO::CASE_S, '', 255),
			'serv_options' => array(GDO::UINT, 0),
		);
	}
	
	###############
	### Getters ###
	###############
	public function getID() { return $this->getVar('serv_id'); }
	public function getHost() { return $this->getVar('serv_host'); }
	public function getPort() { return $this->connection->getPort(); }
	public function getHostname() { return $this->connection->getHostname(); }
	public function getBotsNickname() { return $this->current_nick; }
	public function isSSL() { return $this->connection->isSSL(); }
	public function getIP() { return GWF_IP6::displayIP($this->getVar('serv_ip'), GWF_IP6::BIN_32_128); }
	public function getUsers() { return $this->users; }
	public function getChannels() { return $this->channels; }
	public function getChannelcount() { return count($this->channels); }
	public function getTLD() { return Common::getTLD($this->getHfull_hostname); }
	public function getMaxUsers() { return $this->getVar('serv_maxusers'); }
	public function getMaxChannels() { return $this->getVar('serv_maxchannels'); }
	public function getFrom() { return $this->lm_from; }
	public function getFromRaw() { return $this->lm_from_raw; }
	public function isOnline() { return $this->online; }
	public function setOnline($bool=true) { $this->online = $bool; }
	public function getAutoChannels() { return explode(',', $this->getVar('serv_channels')); }
	public function isAutoChannel($channel_name) { return in_array($channel_name, $this->getAutoChannels(), true); }
	public function getNicknames() { return explode(',', $this->getVar('serv_nicknames')); }
	public function getAdminUsernames() { return explode(',', $this->getVar('serv_admins')); }
	public function isThrottled() { return $this->getInt('serv_flood_amt') > 0; }
	
//	private $id = 0;
//	private $nicknames = 'Lamb,Lamb2,Lamb3';
//	private $password = '';
//	private $nickname_id = -1;
//	private $current_nick = '';
	
//	private $auto_chans = '';
//	private $admins = array();
//	private $full_hostname = '';
//	private $hostname = '';
//	private $port = 6667;
//	private $maxusers = 0;
//	private $maxchannels = 0;
//	private $ip = '';
//	private $options = 0;
	
	###########
	### IRC ###
	###########
	/**
	 * @var Lamb_IRC
	 */
	private $connection; # IRC connection
	private $users = array(); # Array of class Lamb_User
	private $channels = array(); # Array of class Lamb_Channel
	private $current_nick = ''; # Current nickname
	private $nickname_id = -1; # And cycle ID
	
//	public function __construct($host, $nicknames, $password, $channels, $admin, $options=0)
//	{
//		$this->full_hostname = $host;
//		$this->hostname = $this->connection->getHostname();
//		$this->port = $this->connection->getPort();
//		$this->nicknames = trim($nicknames);
//		$this->password = trim($password);
//		$this->auto_chans = trim($channels);
//		$this->admins = $admin === '' ? array() : explode(',', $admin);
//		$this->options = abs((int)$options);
//		$this->next_retry = time();
//		$this->syncDB();
//	}
	
	public function __construct($data=NULL)
	{
		parent::__construct($data);
		$this->setupConnection();
	}
	
	############################
	### Current Message From ###
	############################
	/**
	 * Set the current origin of current message.
	 * @param string $from
	 */
	public function setFrom($from)
	{
		$this->lm_from = Common::substrUntil($from, '!');
		$this->lm_from_raw = $from;
	}
	
	public function setupConnection()
	{
		$this->connection = new Lamb_IRC($this->getVar('serv_host'));
		$this->next_retry = time();
	}
	
	public static function factory($host, $nicknames, $password, $channels, $admins)
	{
		if (false !== ($server = self::getByHost(Common::getTLD($host))))
		{
			return $server;
		}
		
		$server = new self(array(
			'serv_id' => '0',
			'serv_host' => $host,
			'serv_version' => NULL,
			'serv_ip' => NULL,
			'serv_maxusers' => '0',
			'serv_maxchannels' => '0',
			'serv_password' => $password,
			'serv_nicknames' => $nicknames,
			'serv_channels' => $channels,
			'serv_admins' => $admins,
			'serv_flood_amt' => Lamb_IRC::DEFAULT_FLOOD_AMOUNT,
			'serv_options' => '0',
		));
		
		return $server;
	}
	
	/**
	 * Get a server by irc message / argument.
	 * @param string $msg
	 * @return Lamb_Server
	 */
	public static function getByMsg($msg)
	{
		return is_numeric($msg) ? self::getByID($msg) : self::getByHost($msg);
	}
	
	/**
	 * Get a server by ID. 
	 * @param int $id
	 * @return Lamb_Server
	 */
	public static function getByID($id)
	{
		return self::table(__CLASS__)->getRow($id);
//		$db = gdo_db();
//		$id = (int)$id;
//		$table = GWF_TABLE_PREFIX.'lamb_server';
//		if (false === ($result = $db->queryFirst("SELECT serv_name,serv_nicknames,serv_password,serv_channels,serv_admins,serv_options from $table WHERE serv_id=$id", false))) {
//			return false;
//		}
//		$result = array_values($result);
//		return new self($result[0], $result[1], $result[2], $result[3], $result[4], $result[5]);
	}
	
	/**
	 * Search a serverid by hostname.
	 * @param string $hostname The hostname
	 * @return int serverid
	 */
	public static function getIDByHost($hostname)
	{
		$h = self::escape($hostname);
		if (false === ($id = self::table(__CLASS__)->selectVar('serv_id', "serv_host LIKE '%{$h}%'")))
		{
			return false;
		}
		return (int)$id;
	}
	
	/**
	 * Get a server by hostname. 
	 * @param string $hostname
	 * @return Lamb_Server
	 */
	public static function getByHost($hostname)
	{
		$h = self::escape($hostname);
		return self::table(__CLASS__)->selectFirstObject('*', "serv_host LIKE '%{$h}%'");
//		$db = gdo_db();
//		$hostname = $db->escape($hostname);
//		$table = GWF_TABLE_PREFIX.'lamb_server';
//		if (false === ($result = $db->queryFirst("SELECT serv_name,serv_nicknames,serv_password,serv_channels,serv_admins,serv_options from $table WHERE serv_name LIKE '%$hostname%'", false))) {
//			return false;
//		}
//		$result = array_values($result);
//		return new self($result[0], $result[1], $result[2], $result[3], $result[4], $result[5]);
	}
	
	public function __destruct()
	{
		$this->disconnect(__METHOD__);
		$this->connection = NULL;
	}
	
	
//	private function syncDB()
//	{
//		$db = gdo_db();
//		$table = $this->getTableName();
//		$tld = $this->getTLD();
//		$tld = $db->escape($tld);
//		$query = "SELECT serv_id, serv_maxusers, serv_maxchannels, serv_ip, serv_nicknames, serv_channels, serv_password, serv_admins FROM $table WHERE serv_name LIKE '%$tld%'";
//		if (false === ($result = $db->queryFirst($query))) {
//			return $this->newServer();
//		}
//		$this->id = (int) $result['serv_id'];
//		$this->maxusers = (int) $result['serv_maxusers'];
//		$this->maxchannels = (int) $result['serv_maxchannels'];
//		$this->ip = $result['serv_ip'];
//		$this->nicknames = $result['serv_nicknames'];
//		$this->auto_chans = trim($result['serv_channels']);
//		$this->password = $result['serv_password'];
//		$this->admins = explode(',', $result['serv_admins']);
//		$this->options = (int)$result['serv_options'];
//	}

	public function setupIP()
	{
		$h = $this->getHostname();
		if ($h === ($ip = gethostbyname($h)))
		{
			return false;
		}
		return $this->saveVar('serv_ip', $ip);
	}
	
	public function saveConfigVars($host, $nicks, $chans, $pass, $admin)
	{
		if (false === $this->saveVars(array(
			'serv_host' => $host,
			'serv_nicknames' => $nicks,
			'serv_channels' => $chans,
			'serv_password' => $pass,
			'serv_admins' => $admin,
		)))
		{
			return false;
		}
		$this->setupConnection();
		return true;
//		$db = gdo_db();
//		$id = $this->getID();
//		$table = $this->getTableName();
//		$this->full_hostname = $host; $host = $db->escape($host);
//		$this->nicknames = $nicks; $nicks = $db->escape($nicks);
//		$this->auto_chans = $chans; $chans = $db->escape($chans);
//		$this->password = $pass; $pass = $db->escape($pass);
//		$admins = $db->escape($admin);
//		$this->admins = $admin === '' ? array() : explode(',', $admin);
//		$query = "UPDATE $table SET serv_name='$host', serv_nicknames='$nicks', serv_channels='$chans', serv_password='$pass', serv_admins='$admins' WHERE serv_id=$id";
//		if (false === $db->queryWrite($query)) {
//			die('DB Error: Cannot write server table');
//		}
	}
	
	
	
	/**
	 * Get a channel from memory.
	 * @param string $channel_name
	 * @return Lamb_Channel
	 */
	public function getChannel($channel_name)
	{
		$n = strtolower($channel_name);
		return isset($this->channels[$n]) ? $this->channels[$n] : false;
	}
	
	
//	private function newServer()
//	{
//		$db = gdo_db();
//		$name = $db->escape($this->full_hostname);
//		$ip = gethostbyname($this->hostname);
//		$table = $this->getTableName();
//		$enicks = $db->escape($this->nicknames);
//		$echans = $db->escape(implode(',', $this->channels));
//		$epass = $db->escape($this->password); 
//		$eadmins = $db->escape(implode(',', $this->admins));
//		$query = "INSERT INTO $table (serv_name, serv_ip, serv_nicknames, serv_channels, serv_password, serv_admins) VALUES('$name', '$ip', '$enicks', '$echans', '$epass', '$eadmins')";
//		if (false === $db->queryWrite($query))
//		{
//			return false;
//		}
//		$this->ip = $ip;
//		$this->id = $db->insertID();
//		return true;
//	}
	
	/**
	 * @return Lamb_IRC
	 */
	public function getConnection()
	{
		return $this->connection;
	}
	
	/**
	 * Connect the server to it's IRCD. Return false when giving up after a long time.
	 * @return true|false
	 */
	public function connect()
	{
		# Give up :(
		if ($this->retry_count >= self::RETRY_MAX_TRY)
		{
			return Lamb_Log::logError('Giving up to connect to '.$this->getHostname().PHP_EOL);
		}
		
		# Wait a little ...
		if (time() < $this->next_retry)
		{
			return true;
		}
		
		# Next try!
		$this->retry_count++;
		
		# Connect?
		if (false !== $this->connection->connect(LAMB_BLOCKING_IO))
		{
			$this->login();
			$this->sendNickname();
			$this->setupIP();
			Lamb::instance()->initServer($this);
			return true;
		}
		
		$delay = self::RETRY_MIN_WAIT + self::RETRY_WAIT_INC*$this->retry_count;
		$delay = Common::clamp($delay, self::RETRY_MIN_WAIT, self::RETRY_MAX_WAIT);
		$this->next_retry = time() + $delay;
		
		Lamb_Log::logError(sprintf('Connection to %s failed. Will retry in %d seconds.', $this->getHostname(), $delay));
		
		return true;
	}
	
	public function disconnect($message)
	{
		$this->connection->disconnect($message);
		$this->channels = array();
		$this->users = array();
	}
	
	public function login()
	{
		$this->connection->send(sprintf('USER %s %s %s :%s', LAMB_USERNAME, LAMB_HOSTNAME, $this->connection->getHostname(), LAMB_REALNAME));
	}
	
	public function sendBotMode()
	{
		$this->connection->send(sprintf('MODE '.$this->current_nick.' +B'));
		$this->connection->send(sprintf('MODE '.$this->current_nick.' +b'));
	}
	
	public function sendNickname()
	{
		# Select next nickname to try.
//		$nicks = explode(',', $this->nicknames);
		$nicks = $this->getNicknames();
		$this->nickname_id++;
		
		if ($this->nickname_id >= count($nicks))
		{
			$this->nickname_id = -1;
			$this->current_nick = $nicks[0].Common::randomKey(4, '123456789');
		}
		else
		{
			$this->current_nick = $nicks[$this->nickname_id];
		}
		
		$this->changeNick($this->current_nick);
	}
	
	public function changeNick($nickname)
	{
		$this->current_nick = $nickname;
		$this->connection->send('NICK '.$nickname);
	}
	
	public function identify()
	{
//		if ($this->nickname_id === 0)
//		{
			$this->connection->sendPrivmsg('NickServ', 'IDENTIFY '.$this->password);
//		}
	}
	
	public function sendPrivmsg($to, $message)
	{
		$this->getConnection()->sendPrivmsg($to, $message);
	}
	
	public function sendCTCPReply($to, $message)
	{
		$this->getConnection()->sendCTCPReply($to, $message);
	}
	
	public function sendCTCPRequest($to, $message)
	{
		$this->getConnection()->sendCTCPRequest($to, $message);
	}
	
	public function sendNotice($to, $message)
	{
		$this->getConnection()->sendNotice($to, $message);
	}
	
	public function sendAction($to, $message)
	{
		$this->getConnection()->sendAction($to, $message);
	}
	
	public function reply($to, $message)
	{
		if ($message === '')
		{
			return true;
		}
		
		# PRIVMSG to the bot
		if ($to === $this->current_nick)
		{
			$message = preg_replace('#https?://#', '', $message);
			$to = $this->getFrom();
		}
		elseif (LAMB_REPLY_ISSUING_NICK)
		{
			$message = $this->getFrom().': '.$message;
		}
		
		return $this->connection->sendPrivmsg($to, $message);
	}
	
	
	public function autojoinChannels()
	{
		if ('' !== ($channels = $this->getVar('serv_channels')))
		{
			$this->join($channels);
		}
	}
	
	public function join($channel)
	{
		$this->connection->send(sprintf('JOIN %s', $channel));
	}
	
	public function part($channel)
	{
		unset($this->channels[$channel]);
		$this->connection->send(sprintf('PART %s', $channel));
	}
	
	public function pong($hash)
	{
		$this->connection->send(sprintf('PONG %s', $hash));
	}
	
	public function quit($message='')
	{
		$this->connection->disconnect($message);
	}
	
	public function addUser($username)
	{
		$u = strtolower($username);
		if (!isset($this->users[$u]))
		{
			$this->users[$u] = Lamb_User::getOrCreate($this, $username);
		}
	}
	
	public function remUser($username)
	{
		$u = strtolower($username);
		foreach ($this->channels as $channel)
		{
			$channel instanceof Lamb_Channel;
			$channel->removeUser($username);
		}
		unset($this->users[$u]);
	}
	
	public function getChannelsFor($username)
	{
		$back = array();
		foreach ($this->channels as $c)
		{
			$c instanceof Lamb_Channel;
			if ($c->isUserInChannel($username))
			{
				$back[] = $c;
			}
		}
		return $back;
	}
	
	public function updateMaxchannels($channelcount)
	{
		return $this->saveVar('serv_maxchannels', $channelcount);
//		$channelcount = (int) $channelcount;
//		if ($channelcount > $this->maxchannels)
//		{
//			if (false === $this->updateInt($field, $value))
//			{
//				return false;
//			}
//			$this->maxchannels = $channelcount;
//			
//			$db = gdo_db();
//			$id = $this->getID();
//			$table = $this->getTableName();
//			if (false === $db->queryWrite("UPDATE $table SET serv_maxchannels=$channelcount WHERE serv_id=$id"))
//			{
//				return false;
//			}
//		}
//		return true;
	}
	
//	public function updateOptions($options)
//	{
//		$options = (int)$options;
//		if ($options !== $this->options)
//		{
//			$db = gdo_db();
//			$id = $this->getID();
//			$table = $this->getTableName();
//			if (false === $db->queryWrite("UPDATE `{$table}` SET serv_options={$options} WHERE serv_id={$id}"))
//			{
//				return false;
//			}
//			$this->options = $options;
//		}
//		return true;
//	}

	/**
	 * Get a channel for this server.
	 * @param string $channel_name
	 * @return Lamb_Channel
	 */
	public function getOrCreateChannel($channel_name)
	{
		$cn = strtolower($channel_name);
		if (isset($this->channels[$cn]))
		{
			return $this->channels[$cn];
		}
		
		if ($cn === strtolower($this->getBotsNickname()))
		{
			return false;
		}
		
		if (false === ($channel = Lamb_Channel::getByName($this, $channel_name)))
		{
			if (false === ($channel = Lamb_Channel::createChannel($this, $channel_name)))
			{
				return Lamb_Log::logError("Lamb_Server::getOrCreateChannel($channel) FAILED!");
			}
		}
		
		$this->channels[$channel_name] = $channel;

		return $channel;
	}

	/**
	 * Get user from origin and channel.
	 * The only true permission safe call.
	 * @param string $origin
	 * @param string $channel_name
	 * @return Lamb_User
	 */
	public function getUserFromOrigin($origin, $channel_name=NULL)
	{
		if (strpos($origin, '!') === false)
		{
			return false;
		}
		$username = trim(Common::substrUntil($origin, '!'), ': ');
		
		if (false === ($user = $this->getUserByNickname($username, $channel_name)))
		{
//			if (false === ($user = Lamb_User::getOrCreate($this, $username))) {
				Lamb_Log::logError(sprintf('Can not find user %s ($origin=%s, $channel_name=%s', $username, $origin, $channel_name));
				return false;
//			}
		}
		
		return $user;
	}
	
	/**
	 * Get a user by nickname. Case insensitive.
	 * @param string $nickname
	 * @return Lamb_User
	 */
	public function getUser($nickname)
	{
		$n = strtolower($nickname);
		return isset($this->users[$n]) ? $this->users[$n] : false;
	}
	
	
	/**
	 * Get a user by nickname.
	 * @param $username
	 * @return Lamb_User
	 */
	public function getUserByNickname($username, $channel_name=NULL)
	{
		$u = strtolower($username);
		
		if (!isset($this->users[$u]))
		{
			$this->users[$u] = Lamb_User::getOrCreate($this, $username);
		}
		
		if ($channel_name !== NULL)
		{
			if (false !== ($channel = $this->getChannel($channel_name)))
			{
				if (false !== ($mode = $channel->getModeByName($username)))
				{
					$this->users[$u]->setChannelModes($mode);
				}
			}
		}
		
		return $this->users[$u];
	}
	
	public function reloadUser(Lamb_User $user)
	{
		$n = strtolower($user->getName());
		unset($this->users[$n]);
		if (false === ($u = self::getUserByNickname($n)))
		{
			return false;
		}
		
		if ($user->isLoggedIn())
		{
			$u->setLoggedIn(true);
		}
		
		$this->users[$n] = $u;
		foreach ($this->getChannelsFor($n) as $c)
		{
			$c instanceof Lamb_Channel;
			$c->removeUser($n);
			$c->addUser($u);
		}
		
		return true;
	}
	
	public function getUserByNickAndChannel($username, $channel_name)
	{
		if (false === ($channel = $this->getChannel($channel_name)))
		{
			return false;
		}
		
		if (false === ($user = $channel->isUserInChannel($username)))
		{
			return false;
		}
		
		return $this->getUserByNickname($username, $channel_name);
	}
	
	public function isAdminUsername($username)
	{
		$u = strtolower($username);
		foreach ($this->getAdminUsernames() as $admin)
		{
			if ($u === strtolower($admin))
			{
				return true;
			}
		}
		return false;
	}
	
	public function isLogging()
	{
		if ($this->isOptionEnabled(self::LOG_ON))
		{
			return true;
		}
		if ($this->isOptionEnabled(self::LOG_OFF))
		{
			return false;
		}
		return LAMB_LOGGING;
	}
}
?>