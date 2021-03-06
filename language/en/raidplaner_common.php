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
	'RAIDPLANER_NOACCESS' => 'Access denied.',
	'RAIDPLANER_INVALID_ID' => 'No valid raid id.',
	'RAIDPLANER_INVALID_STATUS' => 'No valid status.',
	'RAIDPLANER_INVALID_ROLE' => 'No valid role.',
	'RAIDPLANER_INVALID_USERID' => 'No valid user.',
	'RAIDPLANER_INVALID_USER' => 'No permission.',
	'RAIDPLANER_INVALID_RAID' => 'Invalid raid.',
	'RAIDPLANER_ERROR' => 'Error',
	'RAIDPLANER_CLASS' => 'Class',
	'RAIDPLANER_ROLE' => 'Role',
	'RAIDPLANER_CHARNAME' => 'Charactername',
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
	'YOUR_STATUS' => 'Your status',
	'RAIDPLANER_STATUS_UPDATE' => 'Status updated.',
	'COMMENT' => 'Comment',
	
	'STATUS_CHANGE_TITLE' => 'Changed status',
	'STATUS_CHANGE_TEXT' => 'Changed status to "%s" for raid #%s, %s',
	'STATUS_PREVIEW_SAVE' => 'Saved',
	'STATUS_PREVIEW_TEXT' => 'Saved status for raid #%s, %s',
	'SORTABLE' => 'Drag&Drop',
	'ACCEPTED_TITLE' => 'Change status?',
	'ACCEPTED_TITLE_CONFIRM' => 'You have already been accepted for this raid, do you really want to change your status?',
	'STATUSCHANGE_TITLE' => 'Change status?',
	'STATUSCHANGE_TITLE_CONFIRM' => 'Do you really want to change your status? Please enter a comment.',
	'COMMENT_TITLE' => 'Change comment?',
	'COMMENT_TITLE_CONFIRM' => 'Enter your new comment.',
	'COMMENT_CHANGE_TITLE' => 'Comment changed',
	'COMMENT_CHANGE_TEXT' => 'New comment for raid #%s, %s',
	'NOTE_CHANGE_TITLE' => 'Changed raidnote',
	'NOTE_CHANGE_TEXT' => 'New raidnote for Raid #%s, %s',
	'SAVE' => 'Save',
	'SAVE_CHANGES' => 'Changes have not been saved.',
	'NEW_STATUS' => 'Set new status',
	'NEW_ROLE' => 'Set new role',
	'SELECTED' => 'selected',
	
	'PREVIOUS_RAID' => 'Previous raid',
	'NEXT_RAID' => 'Next raid',
	
	'USER' => 'User',
	'CHANGED_USER' => 'Changed user',
	'MOD_CHANGE' => 'Moderator action',
	'LOG_ID' => 'ID',
	
	'POSSIBLE_RAIDS' => 'Possible raids',
	
	'RAIDPLANER_COMMENT_SHORT_TITLE' => 'Please enter a comment',
	'RAIDPLANER_COMMENT_SHORT' => 'You have to enter a comment with at least 5 valid characters if you decline a raid. Status was NOT updated.',
	
	'TANK' => 'Tank',
	'HEAL' => 'Heal',
	'MELEE' => 'Melee',
	'RANGE' => 'Range',
	
	'RAIDINVITE' => 'Invite time',
	'RAIDSTART' => 'Start time',
	'RAIDEND' => 'End time',
	
	'PM_SUBJECT_DECLINE' => "%s changed status on %s!",
	'PM_MESSAGE_DECLINE' => "%s changed status of [url=%s]Raid #%s am %s[/url] after she/he was accepted!
		[i]New status:[/i] %s
		[i]Comment:[/i] %s",
		
	'PM_SUBJECT_COMMENT' => "%s changed comment on %s!",
	'PM_MESSAGE_COMMENT' => "%s changed comment of [url=%s]Raid #%s am %s[/url] after she/he was accepted!
		[i]Comment:[/i] %s",
));
