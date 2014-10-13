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
	'ACP_RAIDPLANER_SCHEDULE_EXPLAIN' => 'Here you can add, edit and remove scheduled raids.',
	'ACP_RAIDPLANER' => 'Raidplaner',
	'ACP_RAIDPLANER_SETTING_SAVED' => 'Settings have been saved successfully!',
	'ACP_RAIDPLANER_SCHEDULE_SAVED' => 'Schedule has been added!',
	'ACP_RAIDPLANER_ACTIVE' => 'Raidplaner active?',
	'ADD_RAIDPLANER_SCHEDULE' => 'Add scheduled raid',
	
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
));
