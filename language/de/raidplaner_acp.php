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
	'ACP_RAIDPLANER_ERROR' => 'Fehler',
	
	// Settings
	'ACP_RAIDPLANER_SETTING_SAVED' => 'Einstellungen wurden erfolgreich gespeichert.',
	'ACP_RAIDPLANER_ACTIVE' => 'Raidplaner aktiv?',
	
	// Events
	'ACP_ADD_RAIDEVENT' => 'Ereignis hinzufügen',
	'ACP_EVENTID' => 'Ereignis ID',
	'ACP_EVENT' => 'Ereignis',
	'ACP_EVENTNAME' => 'Ereignisname',
	'ACP_RAIDSIZE' => 'Raidgröße',
	'ACP_EDIT_RAIDEVENT' => 'Bearbeite Ereignis',
	'ACP_RAIDPLANER_EVENT_SAVED' => 'Ereignis gespeichert.',

	// Schedule
	'ACP_RAIDPLANER_SCHEDULE_EXPLAIN' => 'Hier kannst du geplante Raids hinzufügen, bearbeiten und entfernen.',
	'ADD_RAIDPLANER_SCHEDULE' => 'Geplanten Raid hinzufügen',
	'ACP_RAIDPLANER_SCHEDULE_SAVED' => 'Geplanter Raid gespeichert.',
	'ACP_SAVE_RAIDPLANER_SCHEDULE' => 'Geplanten Raid speichern.',
	'REPEAT_START' => 'Wiederholungsbeginn',
	'REPEAT_START_EXPLAIN' => 'Wähle den ersten Tag deines geplanten Raids.',
	'REPEAT_END' => 'Wiederholungsende',
	'REPEAT_END_EXPLAIN' => 'Wähle den letzten Tag deines geplanten Raids, lasse das Feld leer wenn er sich endlos wiederholen soll.',
	'REPEATABLE' => 'Wiederholungsfrequenz',
	'REPEATABLE_EXPLAIN' => 'Beispiel: Wenn du einen Montag als Starttag mit wöchentlicher Frequenz wählst, wird dieser Raid jeden Montag erstellt.',
	'NO_REPEAT' => 'keine Wiederholung',
	'ONCE' => 'einmal',
	'DAILY' => 'täglich',
	'WEEKLY' => 'wöchentlich',
	'TWOWEEKLY' => 'alle zwei Wochen',
	'MONTHLY' => 'monatlich',
	'YEARLY' => 'jährlich',
	
	'RAIDDATE_EXPLAIN' => 'Datumsformat: Y-m-d',
	'RAIDTIME_EXPLAIN' => 'Zeitformat: H:i',

	'AUTOACCEPT' => 'Melde Benutzer automatisch an.',
	
	// Userconfig
	'RAIDPLANER_CLASS' => 'Klasse',
	'RAIDPLANER_ROLE' => 'Rolle',
	'ACP_RAIDPLANER_USERS_SAVED' => 'Benutzerprofile gespeichert.',
));
