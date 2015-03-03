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
use CronHelper\Service\JobTask\CallbackTask;

class CallbackTaskTest extends PHPUnit_Framework_TestCase
{
	public function testConstruct()
	{
		$taskOptions = array(
			'className' => 'YourClass',
			'methodName' => 'doAction',
		);
		$task = new CallbackTask($taskOptions);

		$this->assertInstanceOf('CronHelper\Service\JobTask\CallbackTask', $task);
		$this->assertSame($taskOptions, $task->getOptions());
	}
}