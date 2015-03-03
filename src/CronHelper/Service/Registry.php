<?php
/**
 * zf2-cron-helper
 *
 * @link https://github.com/ondrejd/zf2-cron-helper for the canonical source repository
 * @copyright Copyright (c) 2015 Ondřej Doněk.
 * @license https://www.mozilla.org/MPL/2.0/ Mozilla Public License 2.0
 */

namespace CronHelper\Service;

/**
 * Job registry.
 *
 * Is simple storage of defined CRON jobs. These jobs are in-time scheduled 
 * (e.g. inserted into database) and than processed in schedule time.
 *
 * This class is meaned as an internal class not for using outside the module.
 * The only entry point outside the module is `CronService` self.
 *
 * @package CronHelper
 * @subpackage Service
 * @author Ondřej Doněk <ondrejd@gmail.com>
 */
class Registry
{
	/**
	 * @var JobRegistry $instance
	 */
	public static $instance = null;

	/**
	 * @var array $registry
	 */
	protected $registry = array();

	/**
	 * Returns count of currently registered jobs.
	 *
	 * @return integer
	 */
	public function count()
	{
		return count($this->registry);
	}

	/**
	 * Returns TRUE whenever given code already exists in the registry.
	 *
	 * @param string $code
	 * @return boolean
	 */
	public function has($code)
	{
		return array_key_exists($code, $this->registry);
	}

	/**
	 * Get job.
	 *
	 * @param $string $code
	 * @return array
	 * @throws \RuntimeException Whenever job with given code doesn't exist.
	 */
	public function get($code)
	{
		return $this->registry[$code];
	}

	/**
	 * Set job.
	 *
	 * @param string $code
	 * @param array $job
	 * @return void
	 */
	public function set($code, array $job)
	{
		$this->registry[$code] = $job;
	}

	/**
	 * Clear (remove) registered jobs.
	 *
	 * @return void
	 */
	public function clear()
	{
		$this->registry = array();
	}

	/**
	 * @internal
	 * @param array $definition Job's task definition
	 * @return JobTask\TaskInterface
	 */
	public function prepareJobTask(array $definition)
	{
		$jobTask = null;

		switch ($definition['type']) {
			case 'callback':
			case 'CronHelper\Service\JobTask\CallbackTask':
				$jobTask = new JobTask\CallbackTask($definition['options']);
				break;

			case 'external':
			case 'CronHelper\Service\JobTask\ExternalTask':
				$jobTask = new JobTask\ExternalTask($definition['options']);
				break;

			case 'route': 
			case 'CronHelper\Service\JobTask\RouteTask':
				$jobTask = new JobTask\RouteTask($definition['options']); 
				break;
		}

		return $jobTask;
	}

	/**
	 * Get instance of `JobRegistry`.
	 * 
	 * Part of singleton implementation.
	 *
	 * @return JobRegistry
	 */
	public static function getInstance()
	{
		if (!(self::$instance instanceof self)) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Destroy actual job registry.
	 *
	 * Clear the singleton.
	 *
	 * @return void
	 */
	public static function destroy()
	{
		self::$instance->clear();
		self::$instance = null;
	}

	/**
	 * Register a cron job.
	 *
	 * @see Cron::trySchedule() for allowed cron expression syntax
	 *
	 * @param string $code Job identifier.
	 * @param string $frequency Frequency of job's task executing.
	 * @param array $task Description of job's task.
	 * @param array $args Additional arguments for job's task.
	 * @return void
	 * @throws \RuntimeException Whenever is given code already registered.
	 * @throws \InvalidArgumentException Whenever array describing task is not correct.
	 */
	public static function register($code, $frequency, array $task, array $args = array())
	{
		$registry = self::getInstance();

		if ($registry->has($code)) {
			throw new \RuntimeException(sprintf('Job "%s" is already registered!', $code));
		}

		$jobTask = $registry->prepareJobTask($task);

		if (!($jobTask instanceof JobTask\TaskInterface)) {
			throw new \InvalidArgumentException('Job task is not defined properly!');
		}

		$registry->set($code, array(
			'frequency' => $frequency,
			'task' => $jobTask,
			'args' => $args,
		));
	}
}