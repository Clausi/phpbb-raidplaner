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
	'RAIDPLANER_INVALID_STATUS' => 'No valid status.',
	'RAIDPLANER_INVALID_ROLE' => 'No valid role.',
	'RAIDPLANER_INVALID_USERID' => 'No valid user.',
	'RAIDPLANER_INVALID_USER' => 'No permission.',
	'RAIDPLANER_INVALID_RAID' => 'Invalid raid.',
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
	'ACCEPT' => 'Accepted',
	'DECLINE' => 'Declined',
	'SUBSTITUTE' => 'Substitute',
	'NOT_SIGNUP' => 'Not signed up',
	'CHANGE_ALL_SELECTED' => 'Change all selected raids',
	'STATUS' => 'Status',
	'RAIDPLANER_STATUS_UPDATE' => 'Status updated.',
	'COMMENT' => 'Comment',
	'STATUS_CHANGE_TITLE' => 'Changed status',
	'STATUS_CHANGE_TEXT' => 'Changed status to "%s" for raid #%s, %s',
	'ACCEPTED_TITLE' => 'Change status?',
	'ACCEPTED_TITLE_CONFIRM' => 'You have already been accepted for this raid, do you really want to change your status?',
	'STATUSCHANGE_TITLE' => 'Change status?',
	'STATUSCHANGE_TITLE_CONFIRM' => 'Do you really want to change your status? Please enter a comment.',
	'TANK' => 'Tank',
	'HEAL' => 'Heal',
	'MELEE' => 'Melee',
	'RANGE' => 'Range',
));
