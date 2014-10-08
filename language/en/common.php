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
	'RAIDPLANER_PAGE' => 'Raidplaner',
	'RAIDPLANER_HELLO' => 'Hello %s!',
	'RAIDPLANER_GOODBYE' => 'Goodbye %s!',

	'ACP_RAIDPLANER_TITLE' => 'Raidplaner Module',
	'ACP_RAIDPLANER_SETTINGS' => 'Settings',
	'ACP_RAIDPLANER' => 'Raidplaner',
	'ACP_RAIDPLANER_GOODBYE' => 'Should say goodbye?',
	'ACP_RAIDPLANER_SETTING_SAVED' => 'Settings have been saved successfully!',
));
