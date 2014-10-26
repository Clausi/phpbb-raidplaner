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
	'ACL_A_RAIDPLANER' => 'Can manage raidplaner',
	'ACL_A_RAIDPLANER_USERMANAGE' => 'Can manage raidplaner user',
	'ACL_M_RAIDPLANER' => 'Can moderate raidplaner',
	'ACL_U_RAIDPLANER' => 'Can use raidplaner',
));
