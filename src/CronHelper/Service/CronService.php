<?php
/**
 * zf2-cron-helper
 *
 * @link https://github.com/ondrejd/zf2-cron-helper for the canonical source repository
 * @copyright Copyright (c) 2015 Ondřej Doněk.
 * @license https://www.mozilla.org/MPL/2.0/ Mozilla Public License 2.0
 */

namespace CronHelper\Service;

use Zend\EventManager\EventManager;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\EventManagerAwareInterface;
use Zend\ServiceManager\ServiceManager;
use Zend\ServiceManager\ServiceManagerAwareInterface;
use CronHelper\Model\JobMapperInterface;
use CronHelper\Model\JobEntity;

/**
 * `CronHelper` service self.
 *
 * @package CronHelper
 * @subpackage Service
 * @author Ondřej Doněk <ondrejd@gmail.com>
 */
class CronService implements CronServiceInterface, ServiceManagerAwareInterface, EventManagerAwareInterface
{
	/**
	 * @var array $options
	 */
	protected $options;

	/**
	 * @var EventManagerInterface $eventManager
	 */
	protected $eventManager;

	/**
	 * @var ServiceManager $serviceManager
	 */
	protected $serviceManager;

	/**
	 * @var JobMapperInterface $databaseMapper
	 */
	protected $databaseMapper;

	/**
	 * @var Zend\Db\ResultSet\HydratingResultSet|null $pendingJobs
	 */
	protected $pendingJobs;

	/**
	 * Constructor.
	 *
	 * @param array $options
	 * @return void
	 */
	public function __construct(array $options = array())
	{
		$this->setOptions($options);
	}

	/**
	 * @return EventManagerInterface
	 */
	public function getEventManager()
	{
		if (!($this->eventManager instanceof EventManagerInterface)) {
			$this->setEventManager(new EventManager());
		}

		return $this->eventManager;
	}

	/**
	 * @param EventManagerInterface $eventManager
	 * @return CronService
	 */
	public function setEventManager(EventManagerInterface $eventManager)
	{
		$eventManager->setIdentifiers(array(
			__CLASS__,
			get_called_class()
		));

		$this->eventManager = $eventManager;
		return $this;
	}

	/**
	 * Get service manager.
	 *
	 * @return ServiceManager
	 */
	public function getServiceManager()
	{
		return $this->serviceManager;
	}

	/**
	 * Set service manager.
	 *
	 * @param ServiceManager $serviceManager
	 * @return CronService
	 */
	public function setServiceManager(ServiceManager $serviceManager)
	{
		$this->serviceManager = $serviceManager;
		return $this;
	}

	/**
	 * Returns TRUE if service has database mapper set.
	 *
	 * @return boolean
	 */
	public function hasDatabaseMapper()
	{
		return ($this->databaseMapper instanceof JobMapperInterface);
	}

	/**
	 * Get database mapper.
	 *
	 * @return JobMapperInterface
	 */
	public function getDatabaseMapper()
	{
		return $this->databaseMapper;
	}

	/**
	 * Set database mapper.
	 *
	 * @param JobMapperInterface $databaseMapper
	 * @return CronService
	 */
	public function setDatabaseMapper(JobMapperInterface $databaseMapper)
	{
		$this->databaseMapper = $databaseMapper;
		return $this;
	}

	/**
	 * Get default options.
	 *
	 * Options array (names of keys correspond with methods of this class
	 * so look there for detailed description of a single option):
	 * <ul>
	 *   <li><code>scheduleAhead</code> <i><u>integer</u></i> time in minutes</li>
	 *   <li><code>scheduleLifetime</code> <i><u>integer</u></i> time in minutes</li>
	 *   <li><code>maxRunningTime</code> <i><u>integer</u></i> time in minutes</li>
	 *   <li><code>successLogLifetime</code> <i><u>integer</u></i> time in minutes</li>
	 *   <li><code>failureLogLifetime</code> <i><u>integer</u></i> time in minutes</li>
	 *   <li><code>emitEvents</code> <i><u>boolean</u></i> emit process events?</li>
	 *   <li><code>allowJsonApi</code> <i><u>boolean</u></i> allow JSON API?</li>
	 *   <li><code>jsonApiSecurityHash</code> <i><u>string</u></i> security hash for accessing JSON API</li>
	 * </ul>
	 *
	 * @return array
	 */
	public function getDefaultOptions()
	{
		return array(
			'scheduleAhead' => 1440, // one day before
			'scheduleLifetime' => 15,
			'maxRunningTime' => 0,
			'successLogLifetime' => 1440, // one day
			'failureLogLifetime' => 2880, // two days
			'emitEvents' => false,/* @todo Should be TRUE */
			'allowJsonApi' => false,/* @todo Should be TRUE */
			'jsonApiSecurityHash' => 'SECURITY_HASH'
		);
	}

	/**
	 * Get options.
	 *
	 * @return array
	 * @see CronService::getDefaultOptions() There is a detailed options array description.
	 */
	public function getOptions()
	{
		return $this->options;
	}

	/**
	 * Set options.
	 *
	 * @param array $options
	 * @return CronService
	 * @see CronService::getDefaultOptions() There is a detailed options array description.
	 */
	public function setOptions(array $options)
	{
		if (!array_key_exists('options', $options)) {
			$options['options'] = array();
		}

		$this->options = array_merge($this->getDefaultOptions(), $options['options']);
		return $this;
	}

	/**
	 * Get time in minutes for how long ahead CRON jobs have to be scheduled.
	 *
	 * @return integer
	 */
	public function getScheduleAhead()
	{
		return (int) $this->options['scheduleAhead'];
	}

	/**
	 * Set time in minutes for how long ahead CRON jobs have to be scheduled.
	 *.
	 * @param integer $time
	 * @return CronService
	 */
	public function setScheduleAhead($time)
	{
		if (!is_numeric($time)) {
			throw new \InvalidArgumentException('`scheduleAhead` expects integer value!');
		}

		$this->options['scheduleAhead'] = (int) $time;
		return $this;
	}

	/**
	 * Get time in minutes for how long it takes before the scheduled job
	 * is considered missed.
	 *
	 * @return integer
	 */
	public function getScheduleLifetime()
	{
		return (int) $this->options['scheduleLifetime'];
	}

	/**
	 * Set time in minutes for how long it takes before the scheduled job
	 * is considered missed.
	 *
	 * @param integer $time
	 * @return CronService
	 */
	public function setScheduleLifetime($time)
	{
		if (!is_numeric($time)) {
			throw new \InvalidArgumentException('`scheduleLifetime` expects integer value!');
		}

		$this->options['scheduleLifetime'] = (int) $time;
		return $this;
	}

	/**
	 * Get maximal running time (in minutes) for the each CRON job.
	 *
	 * If 0 than no maximal limit is set or the system is used.
	 *
	 * @return integer
	 */
	public function getMaxRunningTime()
	{
		return (int) $this->options['maxRunningTime'];
	}

	/**
	 * Set maximal running time (in minutes) for the each CRON job.
	 *
	 * If 0 than no maximal limit is set or the system is used.
	 *
	 * @param integer $time
	 * @return CronService
	 */
	public function setMaxRunningTime($time)
	{
		if (!is_numeric($time)) {
			throw new \InvalidArgumentException('`maxRunningTime` expects integer value!');
		}

		$this->options['maxRunningTime'] = (int) $time;
		return $this;
	}

	/**
	 * Get time in minutes for how long to keep records about successfully
	 * completed CRON jobs.
	 *
	 * @return integer
	 */
	public function getSuccessLogLifetime()
	{
		return (int) $this->options['successLogLifetime'];
	}

	/**
	 * Set time in minutes for how long to keep records about successfully
	 * completed CRON jobs.
	 *
	 * @param integer $time
	 * @return CronService
	 */
	public function setSuccessLogLifetime($time)
	{
		if (!is_numeric($time)) {
			throw new \InvalidArgumentException('`successLogLifetime` expects integer value!');
		}

		$this->options['successLogLifetime'] = (int) $time;
		return $this;
	}

	/**
	 * Get time in minutes for how long to keep records about failed CRON jobs.
	 *
	 * @return integer
	 */
	public function getFailureLogLifetime()
	{
		return (int) $this->options['failureLogLifetime'];
	}

	/**
	 * Set time in minutes for how long to keep records about failed CRON jobs.
	 *
	 * @param integer $time
	 * @return CronService
	 * @throws \InvalidArgumentException Whenever `$time` is not a numeric value.
	 */
	public function setFailureLogLifetime($time)
	{
		if (!is_numeric($time)) {
			throw new \InvalidArgumentException('`failureLogLifetime` expects integer value!');
		}

		$this->options['failureLogLifetime'] = (int) $time;
		return $this;
	}

	/**
	 * Get TRUE if events are emitted during job processing.
	 *
	 * @return boolean
	 */
	public function getEmitEvents()
	{
		return (bool) $this->options['emitEvents'];
	}

	/**
	 * Set TRUE if events are emitted during job processing.
	 *
	 * @param boolean $emitEvents
	 * @return CronService
	 * @throws \InvalidArgumentException Whenever `$emitEvents` is not a boolean value.
	 */
	public function setEmitEvents($emitEvents)
	{
		if (!is_bool($emitEvents)) {
			throw new \InvalidArgumentException('`emitEvents` expects boolean value!');
		}

		$this->options['emitEvents'] = (bool) $emitEvents;
		return $this;
	}

	/**
	 * Get TRUE if JSON API is allowed.
	 *
	 * @return boolean
	 */
	public function getAllowJsonApi()
	{
		return (bool) $this->options['allowJsonApi'];
	}

	/**
	 * Set TRUE if JSON API is allowed.
	 *
	 * @param boolean $allowJsonApi
	 * @return CronService
	 * @throws \InvalidArgumentException Whenever `$allowJsonApi` is not a boolean value.
	 */
	public function setAllowJsonApi($allowJsonApi)
	{
		if (!is_bool($allowJsonApi)) {
			throw new \InvalidArgumentException('`allowJsonApi` expects boolean value!');
		}

		$this->options['allowJsonApi'] = (bool) $allowJsonApi;
		return $this;
	}

	/**
	 * Get JSON API security hash.
	 *
	 * @return string
	 */
	public function getJsonApiSecurityHash()
	{
		return $this->options['jsonApiSecurityHash'];
	}

	/**
	 * Set JSON API security hash.
	 *
	 * @param string $jsonApiSecurityHash
	 * @return CronService
	 * @throws \InvalidArgumentException Whenever `$jsonApiSecurityHash` is not a string value.
	 */
	public function setJsonApiSecurityHash($jsonApiSecurityHash)
	{
		if (!is_string($jsonApiSecurityHash)) {
			throw new \InvalidArgumentException('`jsonApiSecurityHash` expects string value!');
		}

		$this->options['jsonApiSecurityHash'] = (string) $jsonApiSecurityHash;
		return $this;
	}

	/**
	 * Returns pending jobs.
	 *
	 * @return \Zend\Db\ResultSet\HydratingResultSet
	 * @throws \RuntimeException Throws exception whenever the database mapper is not set!
	 */
	public function getPending()
	{
		if (!($this->pendingJobs instanceof \Zend\Db\ResultSet\HydratingResultSet)) {
			if ($this->hasDatabaseMapper() !== true) {
				throw new \RuntimeException('Unable to get pending jobs because service is missing the database mapper!');
			}

			$this->pendingJobs = $this->getDatabaseMapper()->getPending();
		}

		return $this->pendingJobs;
	}

	/**
	 * Reset (clear) all pending jobs.
	 *
	 * @return CronService
	 */
	public function resetPending()
	{
		$this->pendingJobs = null;
		return $this;
	}

	/**
	 * Main action - run scheduled jobs and prepare next run.
	 *
	 * @return CronService
	 */
	public function run()
	{
		$emitEvents = $this->getEmitEvents();
		$eventManager = ($emitEvents === true) ? $this->getEventManager() : null;

		// Trigger event
		if ($emitEvents === true) {
			$eventManager->trigger('run.pre', $this, array());
		}

		$this
			->schedule()
			->process()
			->cleanup();

		// Trigger event
		if ($emitEvents === true) {
			$eventManager->trigger('run.post', $this, array());
		}

		return $this;
	}

	/**
	 * Run sheduled CRON jobs.
	 *
	 * @return CronService
	 */
	public function process()
	{
		$emitEvents = $this->getEmitEvents();
		$eventManager = ($emitEvents === true) ? $this->getEventManager() : null;
		$registry = null; // FIXME Get CronRegistry!
		$mapper = $this->getDatabaseMapper();
		$pendingJobs = $this->getPending();
		$scheduleLifetime = $this->getScheduleLifetime() * 60;
		$now = \Datetime();

		// Trigger event
		if ($emitEvents === true) {
			$eventManager->trigger('process.pre', $this, array('started' => $now, 'pendingJobs' => $pendingJobs));
		}

		foreach ($pendingJobs as $job) {
			// Trigger event
			if ($emitEvents === true) {
				$eventManager->trigger('process_job.pre', $this, array('job' => $job));
			}

			$scheduled = $job->getScheduled();

			if ($scheduled > $now) {
				continue;
			}

			try {
				$errorStatus = JobEntity::STATUS_ERROR;

				$missedTime = clone $now;
				$timestamp = $missedTime->getTimestamp();
				$timestamp -= $scheduleLifetime;
				$missedTime->setTimestamp($timestamp);

				if ($scheduled < $missedTime) {
					$errorStatus = JobEntity::STATUS_MISSED;
					throw new \RuntimeException('The CRON job is missed!');
				}

				$code = $job->getCode();

				if ($cronRegistry->has($code) !== true) {
					throw new \RuntimeException(sprintf('The CRON job "%s" is undefined in registry!', $code));
				}

				if ($this->tryLockJob($job) !== true) {
					// Another CRON started this job intermittently. Skip it.
					continue;
				}

				// Get job task
				$jobTask = $cronRegistry->get($code);
				// Executed time
				$executed = new \Datetime();
				// Execute job task
				$jobTask->execute();

				$job
					->setFinished(new \Datetime())
					->setExecuted($executed)
					->setStatus(JobEntity::STATUS_SUCCESS);
			} catch (\Exception $exception) {
				$job
					->setStatus($errorStatus)
					->setErrorMsg($exception->getMessage())
					->setStackTrace($exception->getTraceAsString());
			}

			$mapper->save($job);

			// Trigger event
			if ($emitEvents === true) {
				$eventManager->trigger('process_job.post', $this, array('job' => $job));
			}
		}

		// Trigger event
		if ($emitEvents === true) {
			$eventManager->trigger('process.post', $this, array('started' => $now, 'finished' => \Datetime()));
		}

		return $this;
	}

	/**
	 * Shedule CRON jobs.
	 *
	 * Read configuration and insert `CronHelper` database records according to it.
	 *
	 * @return CronService
	 */
	public function schedule()
	{
		$emitEvents = $this->getEmitEvents();
		$eventManager = ($emitEvents === true) ? $this->getEventManager() : null;
		$registry = null; // FIXME Get CronRegistry!
		$pendingJobs = $this->getPending();
        $exists = array();
        foreach ($pending as $job) {
            $identifier = $job->getCode();
            $identifier .= $job->getScheduleTime()->getTimeStamp();
            $exists[$identifier] = true;
        }

		// Trigger event
		if ($emitEvents === true) {
			$eventManager->trigger('schedule.pre', $this, array());
		}

        $scheduleAhead = $this->getScheduleAhead() * 60;

        foreach ($registry as $code => $item) {
        	// ...
        }

		throw new \Exception('CronService::schedule - Finish this!');
		// ...
/*
$scheduleAhead = $this->getScheduleAhead() * 60;

$cronRegistry = Registry::getCronRegistry();
foreach ($cronRegistry as $code => $item) {
    $now = time();
    $timeAhead = $now + $scheduleAhead;

    for ($time = $now; $time < $timeAhead; $time += 60) {
        $scheduleTime = new \DateTime();
        $scheduleTime->setTimestamp($time);
        $scheduleTime->setTime(
            $scheduleTime->format('H'),
            $scheduleTime->format('i')
        );
        $scheduleTimestamp = $scheduleTime->getTimestamp();

        $identifier = $code . $scheduleTimestamp;
        if (isset($exists[$identifier])) {
            //already scheduled
            continue;
        }

        $job = new Entity\Job;
        if (Parser::matchTime(
            $scheduleTimestamp, $item['frequency'])) {
            $job
                ->setCode($code)
                ->setStatus(Repository\Job::STATUS_PENDING)
                ->setCreateTime(new \DateTime)
                ->setScheduleTime($scheduleTime);
            $em->persist($job);
            $exists[$identifier] = true;
        }
    }
}

$em->flush();
*/

		// Trigger event
		if ($emitEvents === true) {
			$eventManager->trigger('schedule.post', $this, array());
		}

		return $this;
	}

	/**
	 * Cleanup `CronHelper` database according to set timeout options.
	 *
	 * @return CronService
	 */
	public function cleanup()
	{
		$emitEvents = $this->getEmitEvents();
		$eventManager = ($emitEvents === true) ? $this->getEventManager() : null;
		$mapper = $this->getDatabaseMapper();
		$lifetime = array(
			JobEntity::STATUS_SUCCESS => $this->getSuccessLogLifetime() * 60,
			JobEntity::STATUS_MISSED => $this->getFailureLogLifetime() * 60,
			JobEntity::STATUS_ERROR => $this->getFailureLogLifetime() * 60,
		);
		$history = $mapper->getHistory();
		$removed = array();

		// Trigger event
		if ($emitEvents === true) {
			$eventManager->trigger('cleanup.pre', $this, array('history' => $history));
		}

		$now = time();
		foreach ($history as $job) {
			$status = $job->getStatus();
			$executed = $job->getExecuted();

			if ($executed && $executed->getTimestamp() < $now - $lifetime[$status]) {
				if ($emitEvents === true) {
					$eventManager->trigger('remove_job.pre', $this, array('job' => $job));
				}

				// Note: We would be able to delete all jobs at once but 
				// we don't as we are not expecting to many jobs to remove.
				$mapper->removeById($job->getId());

				if ($emitEvents === true) {
					$eventManager->trigger('remove_job.post', $this, array('job' => $job));
				}

				$removed[] = $job;
			}
		}

		// Trigger event
		if ($emitEvents === true) {
			$eventManager->trigger('cleanup.post', $this, array('removedJobs' => $removed));
		}

		return $this;
	}

	/**
	 * Register CRON job.
	 *
	 * This method is used for creating CRON jobs directly from application's code.
	 *
	 * @param string $code
	 * @param int|string $frequency
	 * @param callback $callback
	 * @param array $options (Optional.)
	 * @return CronService
	 */
	public function register($code, $frequency, $callback, array $options = array())
	{
		$emitEvents = $this->getEmitEvents();
		$eventManager = ($emitEvents === true) ? $this->getEventManager() : null;

		// Trigger event
		if ($emitEvents === true) {
			$eventManager->trigger('register.pre', $this, array());
		}

		// ...

		// Trigger event
		if ($emitEvents === true) {
			$eventManager->trigger('register.post', $this, array());
		}

		return $this;
	}

	/**
	 * Recover CRON jobs that exceeded `max_execution_time` st in system's `php.ini`.
	 *
	 * @return CronService
	 */
	public function recoverRunning()
	{
		// ...
	}

	/**
	 * Set a job running (only if is currently pending).
	 *
	 * @param JobEntity $job
	 * @return boolean
	 */
	public function tryLockJob(JobEntity $job)
	{
		// ...
	}
}
