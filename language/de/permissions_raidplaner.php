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
	'ACL_A_RAIDPLANER' => 'Kann Raidplaner verwalten',
	'ACL_A_RAIDPLANER_USERMANAGE' => 'Kann Raidplanerbenutzer verwalten',
	'ACL_M_RAIDPLANER' => 'Kann Raidplaner moderieren',
	'ACL_U_RAIDPLANER' => 'Kann Raidplaner benutzen',
));
