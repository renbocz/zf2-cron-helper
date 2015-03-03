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
 * Cron service interface.
 *
 * This interface is designed according to original `heartsentwined/zf2-cron`
 * but it reflects some changes in module's design which are described
 * in `README.md`.
 *
 * @package CronHelper
 * @subpackage Service
 * @author Ondřej Doněk <ondrejd@gmail.com>
 * @link https://github.com/heartsentwined/zf2-cron/blob/master/src/Heartsentwined/Cron/Service/Cron.php
 */
interface CronServiceInterface
{
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
	public function getDefaultOptions();

	/**
	 * Get options.
	 *
	 * @return array
	 * @see CronService::getDefaultOptions() There is a detailed options array description.
	 */
	public function getOptions();

	/**
	 * Set options.
	 *
	 * @param array $options
	 * @return CronServiceInterface
	 * @see CronService::getDefaultOptions() There is a detailed options array description.
	 */
	public function setOptions(array $options);

	/**
	 * Get time in minutes for how long ahead CRON jobs have to be scheduled.
	 *
	 * @return integer
	 */
	public function getScheduleAhead();

	/**
	 * Set time in minutes for how long ahead CRON jobs have to be scheduled.
	 *.
	 * @param integer $time
	 * @return CronServiceInterface
	 * @throws \InvalidArgumentException Whenever `$time` is not a numeric value.
	 */
	public function setScheduleAhead($time);

	/**
	 * Get time in minutes for how long it takes before the scheduled job
	 * is considered missed.
	 *
	 * @return integer
	 */
	public function getScheduleLifetime();

	/**
	 * Set time in minutes for how long it takes before the scheduled job
	 * is considered missed.
	 *
	 * @param integer $time
	 * @return CronServiceInterface
	 * @throws \InvalidArgumentException Whenever `$time` is not a numeric value.
	 */
	public function setScheduleLifetime($time);

	/**
	 * Get maximal running time (in minutes) for the each CRON job.
	 *
	 * If 0 than no maximal limit is set or the system is used.
	 *
	 * @return integer
	 */
	public function getMaxRunningTime();

	/**
	 * Set maximal running time (in minutes) for the each CRON job.
	 *
	 * If 0 than no maximal limit is set or the system is used.
	 *
	 * @param integer $time
	 * @return CronServiceInterface
	 * @throws \InvalidArgumentException Whenever `$time` is not a numeric value.
	 */
	public function setMaxRunningTime($time);

	/**
	 * Get time in minutes for how long to keep records about successfully
	 * completed CRON jobs.
	 *
	 * @return integer
	 */
	public function getSuccessLogLifetime();

	/**
	 * Set time in minutes for how long to keep records about successfully
	 * completed CRON jobs.
	 *
	 * @param integer $time
	 * @return CronServiceInterface
	 * @throws \InvalidArgumentException Whenever `$time` is not a numeric value.
	 */
	public function setSuccessLogLifetime($time);

	/**
	 * Get time in minutes for how long to keep records about failed CRON jobs.
	 *
	 * @return integer
	 */
	public function getFailureLogLifetime();

	/**
	 * Set time in minutes for how long to keep records about failed CRON jobs.
	 *
	 * @param integer $time
	 * @return CronServiceInterface
	 * @throws \InvalidArgumentException Whenever `$time` is not a numeric value.
	 */
	public function setFailureLogLifetime($time);

	/**
	 * Get TRUE if events are emitted during job processing.
	 *
	 * @return boolean
	 */
	public function getEmitEvents();

	/**
	 * Set TRUE if events are emitted during job processing.
	 *
	 * @param boolean $emitEvents
	 * @return CronServiceInterface
	 * @throws \InvalidArgumentException Whenever `$emitEvents` is not a boolean value.
	 */
	public function setEmitEvents($emitEvents);

	/**
	* Get TRUE if JSON API is allowed.
	*
	* @return boolean
	*/
	public function getAllowJsonApi();

	/**
	* Set TRUE if JSON API is allowed.
	*
	* @param boolean $allowJsonApi
	* @return CronService
	* @throws \InvalidArgumentException Whenever `$allowJsonApi` is not a boolean value.
	*/
	public function setAllowJsonApi($allowJsonApi);

	/**
	 * Get JSON API security hash.
	 *
	 * @return string
	 */
	public function getJsonApiSecurityHash();

	/**
	 * Set JSON API security hash.
	 *
	 * @param string $jsonApiSecurityHash
	 * @return CronService
	 * @throws \InvalidArgumentException Whenever `$jsonApiSecurityHash` is not a string value.
	 */
	public function setJsonApiSecurityHash($jsonApiSecurityHash);

	/**
	 * Returns pending jobs.
	 *
	 * @return \Zend\Db\ResultSet\HydratingResultSet
	 * @throws \RuntimeException Throws exception whenever the database mapper is not set!
	 */
	public function getPending();

	/**
	 * Reset (clear) all pending jobs.
	 *
	 * @return CronServiceInterface
	 */
	public function resetPending();

	/**
	 * Main action - run scheduled jobs and prepare next run.
	 *
	 * @return CronServiceInterface
	 */
	public function run();

	/**
	 * Run sheduled CRON jobs.
	 *
	 * @return CronServiceInterface
	 */
	public function process();

	/**
	 * Shedule CRON jobs.
	 *
	 * Read configuration and insert `CronHelper` database records according to it.
	 *
	 * @return CronServiceInterface
	 */
	public function schedule();

	/**
	 * Cleanup `CronHelper` database according to set timeout options.
	 *
	 * @return CronServiceInterface
	 */
	public function cleanup();

	/**
	 * Recover CRON jobs that exceeded `max_execution_time` st in system's `php.ini`.
	 *
	 * @return CronServiceInterface
	 */
	public function recoverRunning();

	/**
	 * Register CRON job.
	 *
	 * This method is used for creating CRON jobs directly from application's code.
	 *
	 * @param string $code
	 * @param int|string $frequency
	 * @param callback $callback
	 * @param array $options (Optional.)
	 * @return CronServiceInterface
	 */
	public function register($code, $frequency, $callback, array $options = array());
}
