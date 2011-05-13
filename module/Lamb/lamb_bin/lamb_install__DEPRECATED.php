<?php
#########################################################################
# to do a clean reinstall, run this script with --drop-tables argument! #
#########################################################################
chdir('../../../');
# CHANGE THIS! THEN IT MIGHT WORK!
define('GWF_CONFIG_NAME', 'protected/config_lamb_dev.php');
die("CHANGE THIS!\n");
require_once 'inc/_gwf_include.php';


# Args
$drop_tables = isset($argv[1]) && $argv[1] === '--drop-tables';

if ($drop_tables === true) {
	echo "Flushing all core tables!!!\n";
}

$db = gdo_db();
//$db->connect();

# GWF
GDO::table('GWF_Counter')->createTable($drop_tables);
GDO::table('GWF_Settings')->createTable($drop_tables);

# Lamb
require_once 'module/Lamb/Lamb_User.php';
require_once 'module/Lamb/Lamb_Channel.php';
require_once 'module/Lamb/Lamb_IRCFrom.php';
require_once 'module/Lamb/Lamb_IRCTo.php';
GDO::table('Lamb_User')->createTable($drop_tables);
GDO::table('Lamb_Channel')->createTable($drop_tables);
GDO::table('Lamb_IRCFrom')->createTable($drop_tables);
GDO::table('Lamb_IRCTo')->createTable($drop_tables);
$table = GWF_TABLE_PREFIX.'lamb_server';
if ($drop_tables === true) {
	$db->queryWrite("DROP TABLE IF EXISTS $table");
}
$query =
'CREATE TABLE IF NOT EXISTS '.$table.' ('.
'serv_id    INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '.
'serv_name  VARCHAR(255) CHARACTER SET ascii COLLATE ascii_bin, '.
'serv_ip    VARCHAR(40)  CHARACTER SET ascii COLLATE ascii_bin, '.
'serv_maxusers INT(11) UNSIGNED NOT NULL DEFAULT 0, '.
'serv_maxchannels INT(11) UNSIGNED NOT NULL DEFAULT 0, '.
'serv_version VARCHAR(255) CHARACTER SET ascii COLLATE ascii_bin DEFAULT "", '.
'serv_options INT(11) UNSIGNED NOT NULL DEFAULT 0, '.
'serv_password VARCHAR(255) CHARACTER SET ascii COLLATE ascii_bin, '.
'serv_nicknames TEXT CHARACTER SET ascii COLLATE ascii_bin, '.
'serv_channels TEXT CHARACTER SET ascii COLLATE ascii_bin, '.
'serv_admins TEXT CHARACTER SET ascii COLLATE ascii_bin '.
') ENGINE=InnoDB DEFAULT CHARSET=utf8';
$db->queryWrite($query);
?>
