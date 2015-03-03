<?php
/**
 * zf2-cron-helper
 *
 * @link https://github.com/ondrejd/zf2-cron-helper for the canonical source repository
 * @copyright Copyright (c) 2015 Ondřej Doněk.
 * @license https://www.mozilla.org/MPL/2.0/ Mozilla Public License 2.0
 */

namespace CronHelperTest\Service\JobTask;

use PHPUnit_Framework_TestCase;
use CronHelper\Service\JobTask\RouteTask;

class RouteTaskTest extends PHPUnit_Framework_TestCase
{
	public function testConstruct()
	{
		$taskOptions = array(
			'routeName' => 'cron_job1',
		);
		$task = new RouteTask($taskOptions);

		$this->assertInstanceOf('CronHelper\Service\JobTask\RouteTask', $task);
		$this->assertSame($taskOptions, $task->getOptions());
	}
}