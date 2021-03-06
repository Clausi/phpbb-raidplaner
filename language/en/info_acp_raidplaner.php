<?php

if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

$lang = array_merge($lang, array(
	'ACP_RAIDPLANER_TITLE' => 'Raidplaner Module',
	'ACP_RAIDPLANER_SETTINGS' => 'Settings',
	'ACP_RAIDPLANER_SCHEDULE' => 'Schedule',
	'ACP_RAIDPLANER_EVENTS' => 'Events',
	'ACP_RAIDPLANER_USERMANAGE' => 'Manage raidplaner users',
));
