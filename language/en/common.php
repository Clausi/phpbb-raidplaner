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
	'ACP_RAIDPLANER_SCHEDULE_EXPLAIN' => 'Here you can add, edit and remove scheduled raids.',
	'ACP_RAIDPLANER' => 'Raidplaner',
	'ACP_RAIDPLANER_SETTING_SAVED' => 'Settings have been saved successfully!',
	'ACP_RAIDPLANER_ACTIVE' => 'Raidplaner active?',
	'ADD_RAIDPLANER_SCHEDULE' => 'Add scheduled raid',
	'REPEAT_START' => 'Start',
	'REPEAT_END' => 'End',
	'REPEATABLE' => 'repeat',
	'NO_REPEAT' => 'no repeat',
	'ONCE' => 'once',
	'DAILY' => 'daily',
	'WEEKLY' => 'weekly',
	'TWOWEEKLY' => 'two weeks',
	'MONTHLY' => 'monthly',
	'YEARLY' => 'yearly',
));
