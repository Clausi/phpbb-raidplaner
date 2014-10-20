<?php

namespace clausi\raidplaner\acp;

class main_info
{
	function module()
	{
		return array(
			'filename'	=> '\clausi\raidplaner\acp\main_module',
			'title'		=> 'ACP_RAIDPLANER_TITLE',
			'version'	=> '1.0.0',
			'modes'		=> array(
				'settings' => array(
					'title' => 'ACP_RAIDPLANER_SETTINGS', 
					'auth' => 'ext_clausi/raidplaner && acl_a_raidplaner', 
					'cat' => array('ACP_RAIDPLANER_TITLE')
				),
				'events' => array(
					'title' => 'ACP_RAIDPLANER_EVENTS', 
					'auth' => 'ext_clausi/raidplaner && acl_a_raidplaner', 
					'cat' => array('ACP_RAIDPLANER_TITLE')
				),
				'schedule' => array(
					'title' => 'ACP_RAIDPLANER_SCHEDULE', 
					'auth' => 'ext_clausi/raidplaner && acl_a_raidplaner', 
					'cat' => array('ACP_RAIDPLANER_TITLE')
				),
				'usermanage' => array(
					'title' => 'ACP_RAIDPLANER_USERMANAGE', 
					'auth' => 'ext_clausi/raidplaner && acl_a_raidplaner && acl_m_raidplaner', 
					'cat' => array('ACP_RAIDPLANER_TITLE')
				),
			),
		);
	}
}
