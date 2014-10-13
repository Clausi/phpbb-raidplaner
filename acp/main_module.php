<?php

namespace clausi\raidplaner\acp;

class main_module
{
	public $u_action;

	function main($id, $mode)
	{
		// global $db, $user, $auth, $template, $cache, $request;
		// global $config, $phpbb_root_path, $phpbb_admin_path, $phpEx;
		global $phpbb_container, $request, $user;

		$user->add_lang_ext('clausi/raidplaner', 'raidplaner_acp');
		$admin_controller = $phpbb_container->get('clausi.raidplaner.admin.controller');
		$action = $request->variable('action', '');
		$admin_controller->set_page_url($this->u_action);
		
		switch($mode) 
		{
			case 'settings':
				$this->tpl_name = 'raidplaner_settings';
				$this->page_title = $user->lang('ACP_RAIDPLANER_SETTINGS');
				$admin_controller->display_options();
			break;
			
			case 'schedule':
				$this->tpl_name = 'raidplaner_schedule';
				$this->page_title = $user->lang('ACP_RAIDPLANER_SCHEDULE');
				
				switch($action) 
				{
					case 'add':
						$admin_controller->set_page_url($this->u_action);
						$admin_controller->add_schedule();
					break;
					default:
						$admin_controller->set_page_url($this->u_action);
						$admin_controller->display_schedule();
				}
				
				
			break;
		}
		
		
	}
}
