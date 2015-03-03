<?php
/**
 * zf2-cron-helper
 *
 * @link https://github.com/ondrejd/zf2-cron-helper for the canonical source repository
 * @copyright Copyright (c) 2015 Ondřej Doněk.
 * @license https://www.mozilla.org/MPL/2.0/ Mozilla Public License 2.0
 */

namespace CronHelperTest\Service;

use PHPUnit_Framework_TestCase;
use CronHelper\Service\Registry;

class RegistryTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @var array $usedConfig
	 */
	protected $usedConfig;

	public function setUp()
	{
		$config = require(TEST_DIR . '/config/cronhelper.config.php');
		$this->usedConfig = $config['cron_helper'];
	}

	public function testConstruct()
	{
		$config = $this->usedConfig['jobs'];
		$registry = new Registry($config);

		$this->assertSame(true, $registry->has('job1'));
		$this->assertSame(true, $registry->has('job2'));
		$this->assertSame(true, $registry->has('job3'));

		$job1 = $registry->get('job1');
		$this->assertArrayHasKey('frequency', $job1);
		$this->assertArrayHasKey('task', $job1);
		$this->assertArrayHasKey('args', $job1);
		$this->assertSame($job1['frequency'], '0 20 * * *');
		$this->assertInstanceOf('CronHelper\Service\JobTask\RouteTask', $job1['task']);
		$this->assertSame(count($job1['args']), 1);

		$job2 = $registry->get('job2');
		$this->assertArrayHasKey('frequency', $job2);
		$this->assertArrayHasKey('task', $job2);
		$this->assertArrayHasKey('args', $job2);
		$this->assertSame($job2['frequency'], '0 0 1 * *');
		$this->assertInstanceOf('CronHelper\Service\JobTask\CallbackTask', $job2['task']);
		$this->assertSame(count($job2['args']), 0);

		$job3 = $registry->get('job3');
		$this->assertArrayHasKey('frequency', $job3);
		$this->assertArrayHasKey('task', $job3);
		$this->assertArrayHasKey('args', $job3);
		$this->assertInstanceOf('CronHelper\Service\JobTask\ExternalTask', $job3['task']);
		$this->assertSame(count($job3['args']), 0);
	}
}
