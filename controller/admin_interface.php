<?php

namespace clausi\raidplaner\controller;

interface admin_interface
{

	public function display_options();
	public function display_events();
	public function edit_event();
	public function delete_event($id);
	public function display_schedule();
	public function add_schedule();
	public function edit_schedule($id);
	public function delete_schedule($id);
	public function set_page_url($u_action);
	
}
