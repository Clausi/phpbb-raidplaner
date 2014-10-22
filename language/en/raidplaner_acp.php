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
	'ACP_RAIDPLANER' => 'Raidplaner',
	'ACP_RAIDPLANER_ERROR' => 'Error',
	
	// Settings
	'ACP_RAIDPLANER_SETTING_SAVED' => 'Settings have been saved successfully!',
	'ACP_RAIDPLANER_ACTIVE' => 'Raidplaner active?',
	
	// Events
	'ACP_ADD_RAIDEVENT' => 'Add event',
	'ACP_EVENTID' => 'Event ID',
	'ACP_EVENT' => 'Event',
	'ACP_EVENTNAME' => 'Event name',
	'ACP_RAIDSIZE' => 'raidsize',
	'ACP_EDIT_RAIDEVENT' => 'Edit raid event',
	'ACP_RAIDPLANER_EVENT_SAVED' => 'Event saved.',

	// Schedule
	'ACP_RAIDPLANER_SCHEDULE_EXPLAIN' => 'Here you can add, edit and remove scheduled raids.',
	'ADD_RAIDPLANER_SCHEDULE' => 'Add scheduled raid',
	'ACP_RAIDPLANER_SCHEDULE_SAVED' => 'Schedule has been saved.',
	'ACP_SAVE_RAIDPLANER_SCHEDULE' => 'Save schedule',
	'REPEAT_START' => 'Repeat start',
	'REPEAT_START_EXPLAIN' => 'Select the first day of your planned raids.',
	'REPEAT_END' => 'Repeat end',
	'REPEAT_END_EXPLAIN' => 'Select the last day of your planned raids, leave empty if you want it to repeat indefinitely.',
	'REPEATABLE' => 'Repeat frequency',
	'REPEATABLE_EXPLAIN' => 'Example: If you select a Monday as start day and frequency weekly, this raid will be created every Monday.',
	'NO_REPEAT' => 'no repeat',
	'ONCE' => 'once',
	'DAILY' => 'daily',
	'WEEKLY' => 'weekly',
	'TWOWEEKLY' => 'two weeks',
	'MONTHLY' => 'monthly',
	'YEARLY' => 'yearly',
	'RAIDSTART' => 'Raid start time',
	'RAIDDATE_EXPLAIN' => 'Dateformat: Y-m-d',
	'RAIDTIME_EXPLAIN' => 'Timeformat: H:i',
	'RAIDINVITE' => 'Raid invite time',
	'RAIDEND' => 'Raid end time',
	'AUTOACCEPT' => 'Set "sign on" status to members by default',
	
	// Userconfig
	'RAIDPLANER_CLASS' => 'Class',
	'RAIDPLANER_ROLE' => 'Role',
	'ACP_RAIDPLANER_USERS_SAVED' => 'User profiles saved.',
));
