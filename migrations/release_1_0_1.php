<?php

namespace clausi\raidplaner\migrations;

class release_1_0_1 extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v310\rc5');
	}

	public function update_data()
	{
		return array(
			array('config.add', array('clausi_raidplaner_user', 2)),
		);
	}

}
