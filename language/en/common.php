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
	'RAIDPLANER_RAID' => 'Raid',
	'RAIDPLANER_INACTIVE' => 'Raidplaner currently deactivated.',
	'RAIDPLANER_INVALID_ID' => 'No valid raid id.',

	'ACP_RAIDPLANER_TITLE' => 'Raidplaner Module',
	'ACP_RAIDPLANER_SETTINGS' => 'Settings',
	'ACP_RAIDPLANER_SCHEDULE' => 'Schedule',
	'ACP_RAIDPLANER' => 'Raidplaner',
	'ACP_RAIDPLANER_SETTING_SAVED' => 'Settings have been saved successfully!',
	'ACP_RAIDPLANER_ACTIVE' => 'Raidplaner active?',
));
