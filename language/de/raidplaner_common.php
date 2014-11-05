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
	'RAIDPLANER_INACTIVE' => 'Raidplaner ist derzeit deaktiviert.',
	'RAIDPLANER_INVALID_ID' => 'Keine gültige Raid ID.',
	'RAIDPLANER_INVALID_STATUS' => 'Keine gültiger Status.',
	'RAIDPLANER_INVALID_ROLE' => 'Keine gültige Rolle.',
	'RAIDPLANER_INVALID_USERID' => 'Keine gültiger User.',
	'RAIDPLANER_INVALID_USER' => 'Keine Berechtigung.',
	'RAIDPLANER_INVALID_RAID' => 'Ungültiger Raid.',
	'RAIDPLANER_CLASS' => 'Klasse',
	'RAIDPLANER_ROLE' => 'Rolle',
	'ALL' => 'Alle',
	'PAST_RAIDS' => 'vergangene Raids',
	'FUTURE_RAIDS' => 'zukünftige Raids',
	'EVENT' => 'Ereignis',
	'DATE' => 'Datum',
	'NOTE' => 'Notiz',
	'MEMBERS' => 'Benutzer',
	'ATTENDING' => 'Angemeldet',
	'ACCEPT' => 'Bestätigt',
	'DECLINE' => 'Abgemeldet',
	'SUBSTITUTE' => 'Ersatz',
	'NOT_SIGNUP' => 'Nicht angemeldet',
	'CHANGE_ALL_SELECTED' => 'Ändere alle gewählten Raids',
	'STATUS' => 'Status',
	'RAIDPLANER_STATUS_UPDATE' => 'Status aktualisiert.',
	'COMMENT' => 'Kommentar',
	'STATUS_CHANGE_TITLE' => 'Status geändert',
	'STATUS_CHANGE_TEXT' => 'Neuer Status "%s" für Raid #%s, %s',
	'ACCEPTED_TITLE' => 'Status ändern?',
	'ACCEPTED_TITLE_CONFIRM' => 'Du wurdest bereits für diesen Raid bestätigt, möchtest du wirklich deinen Status ändern?',
	'STATUSCHANGE_TITLE' => 'Status ändern?',
	'STATUSCHANGE_TITLE_CONFIRM' => 'Möchtest du wirklich deinen Status ändern? Bitte gebe einen Kommentar ein.',
	'TANK' => 'Schutz',
	'HEAL' => 'Heilung',
	'MELEE' => 'Nahkampf',
	'RANGE' => 'Fernkampf',
	
	'RAIDINVITE' => 'Einladen',
	'RAIDSTART' => 'Start',
	'RAIDEND' => 'Ende',
));
