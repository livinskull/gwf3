<?php
$lang = array(
	'en' => array(
		'help' => 'Usage: %CMD% <search terms>. Display a google search link.',
	),
);
$plugin = Dog::getPlugin();
$plugin->reply('https://encrypted.google.com/search?q=' . urlencode($plugin->msg()));
?>
