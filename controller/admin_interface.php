<?php

namespace clausi\raidplaner\controller;

interface admin_interface
{

	public function display_options();
	public function display_schedule();
	public function add_schedule();
	public function set_page_url($u_action);
	
}
