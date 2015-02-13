<?php

namespace clausi\raidplaner\migrations;

class release_1_0_4 extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return array('\clausi\raidplaner\migrations\release_1_0_3');
	}

	public function update_schema()
	{
		return array(
			'add_columns'	=> array(
				$this->table_prefix . 'rp_raids'	=> array(
					'processed' => array('TIMESTAMP', 0),
				),
			),
			
			'add_tables' => array(
				$this->table_prefix . 'rp_statistics' => array(
					'COLUMNS' => array(
						'user_id' => array('UINT', 0),
						'raids' => array('UINT', 0),
						'accepted' => array('UINT', 0),
						'attending' => array('UINT', 0),
						'substitute' => array('UINT', 0),
						'declined' => array('UINT', 0),
						'deleted' => array('TIMESTAMP', 0),
						'created' => array('TIMESTAMP', 0),
						'modified' => array('TIMESTAMP', 0),
					),
					'PRIMARY_KEY'	=> 'user_id',
				),
			),
		);
	}

}
