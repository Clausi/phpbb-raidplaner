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
	'RAIDPLANER_CLASS' => 'Class',
	'RAIDPLANER_ROLE' => 'Role',
	'ALL' => 'All',
	'PAST_RAIDS' => 'past raids',
	'FUTURE_RAIDS' => 'future raids',
	'EVENT' => 'Event',
	'DATE' => 'Date',
	'NOTE' => 'Note',
	'MEMBERS' => 'Members',
	'ATTENDING' => 'Attending',
	'ACCEPT' => 'Accept',
	'DECLINE' => 'Decline',
	'SUBSTITUTE' => 'Substitute',
));
