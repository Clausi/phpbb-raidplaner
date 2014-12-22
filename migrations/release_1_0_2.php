<?php

namespace clausi\raidplaner\migrations;

class release_1_0_2 extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return array('\clausi\raidplaner\migrations\release_1_0_1');
	}

	public function update_schema()
	{
		return array(
			'add_columns'	=> array(
				$this->table_prefix . 'rp_logs'	=> array(
					'changed_user_id' => array('UINT', 0),
					'created' => array('TIMESTAMP', 0),
					'modified' => array('TIMESTAMP', 0),
					'deleted' => array('TIMESTAMP', 0),
				),
			),
			'drop_columns'	=> array(
				$this->table_prefix . 'rp_logs'	=> array(
						'log_time', 'changed_user'
				),
			),
		);
	}

}
