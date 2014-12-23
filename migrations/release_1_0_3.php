<?php

namespace clausi\raidplaner\migrations;

class release_1_0_3 extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return array('\clausi\raidplaner\migrations\release_1_0_2');
	}

	public function update_schema()
	{
		return array(
			'add_columns'	=> array(
				$this->table_prefix . 'rp_logs'	=> array(
					'new_role' => array('TINT:4', '0'),
				),
			),
		);
	}

}
