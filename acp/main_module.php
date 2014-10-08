<?php

namespace clausi\raidplaner\acp;

class main_module
{
	var $u_action;

	function main($id, $mode)
	{
		global $db, $user, $auth, $template, $cache, $request;
		global $config, $phpbb_root_path, $phpbb_admin_path, $phpEx;

		$user->add_lang('acp/common');
		$this->tpl_name = 'raidplaner_body';
		$this->page_title = $user->lang('ACP_RAIDPLANER_TITLE');
		add_form_key('clausi/raidplaner');

		if ($request->is_set_post('submit'))
		{
			if (!check_form_key('clausi/raidplaner'))
			{
				trigger_error('FORM_INVALID');
			}

			$config->set('clausi_raidplaner_goodbye', $request->variable('clausi_raidplaner_goodbye', 0));

			trigger_error($user->lang('ACP_RAIDPLANER_SETTING_SAVED') . adm_back_link($this->u_action));
		}

		$template->assign_vars(array(
			'U_ACTION'				=> $this->u_action,
			'CLAUSI_RAIDPLANER_GOODBYE'		=> $config['clausi_raidplaner_goodbye'],
		));
	}
}
