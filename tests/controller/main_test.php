<?php
/**
*
* @package phpBB Extension - Acme Demo
* @copyright (c) 2014 phpBB Group
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace clausi\raidplaner\tests\controller;

class main_test extends \phpbb_test_case
{
	public function handle_data()
	{
		return array(
			array(200, 'raidplaner_body.html'),
		);
	}

	/**
	 * @dataProvider handle_data
	 */
	public function test_handle($status_code, $page_content)
	{
		$controller = new \clausi\raidplaner\controller\main(
			new \phpbb\config\config(array()),
			new \clausi\raidplaner\tests\clausi\controller_helper(),
			new \clausi\raidplaner\tests\clausi\template(),
			new \clausi\raidplaner\tests\clausi\user()
		);

		$response = $controller->handle('test');
		$this->assertInstanceOf('\Symfony\Component\HttpFoundation\Response', $response);
		$this->assertEquals($status_code, $response->getStatusCode());
		$this->assertEquals($page_content, $response->getContent());
	}
}
