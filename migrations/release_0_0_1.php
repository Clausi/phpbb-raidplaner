<?php

namespace clausi\raidplaner\migrations;

class release_0_0_1 extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return isset($this->config['clausi_raidplaner_goodbye']);
	}

	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v310\alpha2');
	}

	public function update_data()
	{
		return array(
			array('config.add', array('clausi_raidplaner_goodbye', 0)),

			array('module.add', array(
				'acp',
				'ACP_CAT_DOT_MODS',
				'ACP_RAIDPLANER_TITLE'
			)),
			array('module.add', array(
				'acp',
				'ACP_RAIDPLANER_TITLE',
				array(
					'module_basename'	=> '\clausi\raidplaner\acp\main_module',
					'modes'				=> array('settings'),
				),
			)),
		);
	}
}
