<?php

namespace clausi\raidplaner\acp;

class main_module
{
	public $u_action;

	function main($id, $mode)
	{
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
			
			case 'events':
				$this->tpl_name = 'raidplaner_events';
				$this->page_title = $user->lang('ACP_RAIDPLANER_EVENTS');
				
				switch($action) 
				{
					case 'delete':
						$admin_controller->set_page_url($this->u_action);
						$admin_controller->delete_event($request->variable('id', 0));
					break;
					case 'edit':
						$admin_controller->set_page_url($this->u_action);
						$admin_controller->edit_event();
					break;
					default:
						$admin_controller->display_events();
				}
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
					case 'delete':
						$admin_controller->set_page_url($this->u_action);
						$admin_controller->delete_schedule($request->variable('id', 0));
					break;
					default:
						$admin_controller->set_page_url($this->u_action);
						$admin_controller->display_schedule();
				}
				
				
			break;
		}
		
		
	}
}
