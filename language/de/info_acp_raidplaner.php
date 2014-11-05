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
	'ACP_RAIDPLANER_TITLE' => 'Raidplaner Modul',
	'ACP_RAIDPLANER_SETTINGS' => 'Einstellungen',
	'ACP_RAIDPLANER_SCHEDULE' => 'Planung',
	'ACP_RAIDPLANER_EVENTS' => 'Ereignisse',
	'ACP_RAIDPLANER_USERMANAGE' => 'Verwalte Raidplanerbenutzer',
));
