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

	public function testRegistry()
	{
		$jobs = $this->usedConfig['jobs'];
		$registry = Registry::getInstance();

		$this->assertInstanceOf('CronHelper\Service\Registry', $registry);

		$this->assertSame(0, $registry->count());
		$this->assertSame(false, $registry->has('job1'));
		$this->assertSame(false, $registry->has('job2'));
		$this->assertSame(false, $registry->has('job3'));

		foreach ($jobs as $code => $definition) {
			$frequency = array_key_exists('frequency', $definition) ? $definition['frequency'] : '';
			$args = array_key_exists('args', $definition) ? $definition['args'] : array();

			Registry::register($code, $frequency, $definition['task'], $args);
		}

		// The rest is same as test before...
		// XXX Maybe it can be written better...
		$this->assertSame(3, $registry->count());
		$this->assertSame(true, $registry->has('job1'));
		$this->assertSame(true, $registry->has('job2'));
		$this->assertSame(true, $registry->has('job3'));

		$job1 = $registry->get('job1');
		$this->assertArrayHasKey('frequency', $job1);
		$this->assertArrayHasKey('task', $job1);
		$this->assertArrayHasKey('args', $job1);
		$this->assertSame('0 20 * * *', $job1['frequency']);
		$this->assertInstanceOf('CronHelper\Service\JobTask\RouteTask', $job1['task']);
		$this->assertSame(1, count($job1['task']->getOptions()));
		$this->assertSame(1, count($job1['args']));

		$job2 = $registry->get('job2');
		$this->assertArrayHasKey('frequency', $job2);
		$this->assertArrayHasKey('task', $job2);
		$this->assertArrayHasKey('args', $job2);
		$this->assertSame('0 0 1 * *', $job2['frequency']);
		$this->assertInstanceOf('CronHelper\Service\JobTask\CallbackTask', $job2['task']);
		$this->assertSame(2, count($job2['task']->getOptions()));
		$this->assertSame(0, count($job2['args']));

		$job3 = $registry->get('job3');
		$this->assertArrayHasKey('frequency', $job3);
		$this->assertArrayHasKey('task', $job3);
		$this->assertArrayHasKey('args', $job3);
		$this->assertSame('', $job3['frequency']);
		$this->assertInstanceOf('CronHelper\Service\JobTask\ExternalTask', $job3['task']);
		$this->assertSame(1, count($job3['task']->getOptions()));
		$this->assertSame(0, count($job3['args']));

		// Test destroy
		Registry::destroy();
		$registry = Registry::getInstance();

		$this->assertSame(0, $registry->count());
		$this->assertSame(false, $registry->has('job1'));
		$this->assertSame(false, $registry->has('job2'));
		$this->assertSame(false, $registry->has('job3'));
	}

	/**
	 * @expectedException \RuntimeException
	 * @expectedExceptionMessageRegExp /Job "\s+" is already registered!\w+/
	 */
	public function testAlreadyRegistered()
	{
		$jobTask = array('type' => 'route', 'options' => array('route' => 'home'));

		Registry::register('job1', '0 0 * * *', $jobTask);
		Registry::register('job1', '0 1 * * *', $jobTask);
	}

	/**
	 * @expectedException \InvalidArgumentException
	 * @expectedExceptionMessage Job task is not defined properly!
	 */
	public function testBadTaskDefinition()
	{
		$badJobTask = array('type' => 'BAD TYPE', 'options' => array('route' => 'home'));
		Registry::register('bad_job1', '0 0 * * *', $badJobTask);
	}
}
