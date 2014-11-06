<?php

namespace clausi\raidplaner\migrations;

class profilefield_charname extends \phpbb\db\migration\profilefield_base_migration
{
	static public function depends_on()
	{
		return array(
			'\phpbb\db\migration\data\v310\profilefield_show_novalue',
			'\phpbb\db\migration\data\v310\profilefield_types',
			'\clausi\recruitment\migrations\release_1_0_0',
		);
	}

	public function update_data()
	{
		return array(
			array('custom', array(array($this, 'create_custom_field'))),
		);
	}

	protected $profilefield_name = 'raidplaner_charname';

	protected $profilefield_database_type = array('VCHAR', '');

	protected $profilefield_data = array(
		'field_name'			=> 'raidplaner_charname',
		'field_type'			=> 'profilefields.type.string',
		'field_ident'			=> 'raidplaner_charname',
		'field_length'			=> 30,
		'field_minlen'			=> 0,
		'field_maxlen'			=> 100,
		'field_novalue'			=> '',
		'field_default_value'	=> '',
		'field_validation'		=> '',
		'field_required'		=> 0,
		'field_show_novalue'	=> 0,
		'field_show_on_reg'		=> 0,
		'field_show_on_pm'		=> 0,
		'field_show_on_vt'		=> 0,
		'field_show_profile'	=> 0,
		'field_hide'			=> 0,
		'field_no_view'			=> 0,
		'field_active'			=> 1,
	);
	
}
