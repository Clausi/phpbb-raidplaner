<?php
/**
*
* @package phpBB Extension - Acme Demo
* @copyright (c) 2014 phpBB Group
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace clausi\raidplaner\tests\functional;

/**
* @group functional
*/
class demo_test extends \phpbb_functional_test_case
{
	static protected function setup_extensions()
	{
		return array('clausi/raidplaner');
	}

	public function test_raidplaner_clausi()
	{
		$crawler = self::request('GET', 'app.php/raidplaner/clausi');
		$this->assertContains('raidplaner', $crawler->filter('h2')->text());

		$this->add_lang_ext('clausi/raidplaner', 'common');
		$this->assertContains($this->lang('RAIDPLANER_HELLO', 'raidplaner'), $crawler->filter('h2')->text());
		$this->assertNotContains($this->lang('RAIDPLANER_GOODBYE', 'raidplaner'), $crawler->filter('h2')->text());

		$this->assertNotContainsLang('ACP_RAIDPLANER', $crawler->filter('h2')->text());
	}

	public function test_demo_world()
	{
		$crawler = self::request('GET', 'app.php/raidplaner/world');
		$this->assertNotContains('raidplaner', $crawler->filter('h2')->text());
		$this->assertContains('world', $crawler->filter('h2')->text());
	}
}
