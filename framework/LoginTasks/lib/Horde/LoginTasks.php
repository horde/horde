<?php
/**
 * The Horde_LoginTasks:: class provides a set of methods for dealing with
 * login tasks to run upon login to Horde applications.
 *
 * Copyright 2001-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  LoginTasks
 */
class Horde_LoginTasks
{
    /* Interval settings. */
    // Do task yearly (First login after/on January 1).
    const YEARLY = 1;
    // Do task monthly (First login after/on first of month).
    const MONTHLY = 2;
    // Do task weekly (First login after/on a Sunday).
    const WEEKLY = 3;
    // Do task daily (First login of the day).
    const DAILY = 4;
    // Do task every login.
    const EVERY = 5;
    // Do task on first login only.
    const FIRST_LOGIN = 6;
    // Do task once only.
    const ONCE = 7;

    /* Display styles. */
    const DISPLAY_CONFIRM_NO = 1;
    const DISPLAY_CONFIRM_YES = 2;
    const DISPLAY_AGREE = 3;
    const DISPLAY_NOTICE = 4;
    const DISPLAY_NONE = 5;

    /* Priority settings */
    const PRIORITY_HIGH = 1;
    const PRIORITY_NORMAL = 2;

    /**
     * The Horde_LoginTasks_Backend object provides all utilities we need for
     * handling the login tasks.
     *
     * @var Horde_LoginTasks_Backend
     */
    private $_backend;

    /**
     * The Horde_LoginTasks_Tasklist object for this login.
     *
     * @var Horde_LoginTasks_Tasklist
     */
    protected $_tasklist;

    /**
     * Constructor.
     *
     * @param Horde_LoginTasks_Backend $backend  The backend to use.
     */
    public function __construct(Horde_LoginTasks_Backend $backend)
    {
        $this->_backend = $backend;

        /* Retrieves a cached tasklist or make sure one is created. */
        $this->_tasklist = $this->_backend->getTasklistFromCache();

        if (empty($this->_tasklist)) {
            $this->_createTaskList();
        }

        register_shutdown_function(array($this, 'shutdown'));
    }

    /**
     * Tasks to run on session shutdown.
     */
    public function shutdown()
    {
        if (isset($this->_tasklist)) {
            $this->_backend->storeTasklistInCache($this->_tasklist);
        }
    }

    /**
     * Creates the list of login tasks that are available for this session
     * (stored in a Horde_LoginTasks_Tasklist object).
     */
    protected function _createTaskList()
    {
        /* Create a new Horde_LoginTasks_Tasklist object. */
        $this->_tasklist = new Horde_LoginTasks_Tasklist();

        /* Get last task run date(s). Array keys are app names, values are
         * last run timestamps. Special key '_once' contains list of
         * ONCE tasks previously run. */
        $lasttask = $this->_backend->getLastRun();

        /* Create time objects for today's date and last task run date. */
        $cur_date = getdate();

        foreach ($this->_backend->getTasks() as $classname => $app) {
            $ob = new $classname();

            /* If marked inactive, skip the task. */
            if (!$ob->active) {
                continue;
            }

            $addtask = false;

            if ($ob->interval == self::FIRST_LOGIN) {
                $addtask = empty($lasttask[$app]);
            } else {
                $lastrun = getdate(empty($lasttask[$app])
                    ? time()
                    : $lasttask[$app]);

                switch ($ob->interval) {
                case self::YEARLY:
                    $addtask = ($cur_date['year'] > $lastrun['year']);
                    break;

                case self::MONTHLY:
                    $addtask = (($cur_date['year'] > $lastrun['year']) ||
                                ($cur_date['mon'] > $lastrun['mon']));
                    break;

                case self::WEEKLY:
                    $days = date('L', $lastrun[0]) ? 366 : 365;
                    $addtask = (($cur_date['wday'] < $lastrun['wday']) ||
                                (($cur_date['year'] == $lastrun['year']) &&
                                 ($cur_date['yday'] >= $lastrun['yday'] + 7)) ||
                                (($cur_date['year'] > $lastrun['year']) &&
                                 ($cur_date['yday'] >= $lastrun['yday'] + 7 - $days)));
                    break;

                case self::DAILY:
                    $addtask = (($cur_date['year'] > $lastrun['year']) ||
                                ($cur_date['yday'] > $lastrun['yday']));
                    break;

                case self::EVERY:
                    $addtask = true;
                    break;

                case self::ONCE:
                    if (empty($lasttask['_once']) ||
                        !in_array($classname, $lasttask['_once'])) {
                        $addtask = true;
                        $lasttask['_once'][] = $classname;
                        $this->_backend->setLastRun($lasttask);
                    }
                    break;
                }
            }

            if ($addtask) {
                $this->_tasklist->addTask($ob);
            }
        }

        /* If tasklist is empty, we can simply set it to true now. */
        if ($this->_tasklist->isDone()) {
            $this->_tasklist = true;
        }
    }

    /**
     * Do operations needed for this login.
     *
     * This function will generate the list of tasks to perform during this
     * login and will redirect to the login tasks page if necessary.  This is
     * the function that should be called from the application upon login.
     *
     * @param array $opts  Options:
     *   - confirmed: (array) The list of confirmed tasks.
     *   - url: (string) The URL to redirect to when finished.
     *   - user_confirmed: (boolean) If true, indicates that any pending
     *                     actions have been confirmed by the user.
     *
     * @return mixed Null in case no redirection took place, the return value
     *               from the backend redirect() call otherwise.
     */
    public function runTasks(array $opts = array())
    {
        if (!isset($this->_tasklist) ||
            ($this->_tasklist === true)) {
            return;
        }

        $opts = array_merge(array(
            'confirmed' => array(),
            'url' => null,
            'user_confirmed' => false
        ), $opts);

        if (empty($this->_tasklist->target)) {
            $this->_tasklist->target = $opts['url'];
        }

        /* Perform ready tasks now. */
        foreach ($this->_tasklist->ready(!$this->_tasklist->processed || $opts['user_confirmed']) as $key => $val) {
            if (($val instanceof Horde_LoginTasks_SystemTask) ||
                in_array($val->display, array(self::DISPLAY_AGREE, self::DISPLAY_NOTICE, self::DISPLAY_NONE)) ||
                in_array($key, $opts['confirmed'])) {
                $val->execute();
            }
        }

        $processed = $this->_tasklist->processed;
        $this->_tasklist->processed = true;

        /* If we've successfully completed every task in the list (or skipped
         * it), record now as the last time login tasks was run. */
        if ($this->_tasklist->isDone()) {
            $this->_backend->markLastRun();

            $url = $this->_tasklist->target;

            /* This will prevent us from having to store the entire tasklist
             * object in the session, while still indicating we have
             * completed the login tasks for this application. */
            $this->_tasklist = true;

            if ($opts['user_confirmed']) {
                return $this->_backend->redirect($url);
            }
        } elseif ((!$processed || $opts['user_confirmed']) &&
            $this->_tasklist->needDisplay()) {
            return $this->_backend->redirect($this->getLoginTasksUrl());
        }
    }

    /**
     * Generate the list of tasks that need to be displayed.
     * This is the function called from the login tasks page every time it
     * is loaded.
     *
     * @return array  The list of tasks that need to be displayed.
     */
    public function displayTasks()
    {
        if (!isset($this->_tasklist) ||
            ($this->_tasklist === true)) {
            return;
        }

        return $this->_tasklist->needDisplay(true);
    }

    /**
     * Generate the login tasks URL.
     *
     * @return string  The login tasks URL.
     */
    public function getLoginTasksUrl()
    {
        return $this->_backend->getLoginTasksUrl();
    }

    /**
     * Labels for the class constants.
     *
     * @return array  A mapping of constant to gettext string.
     */
    static public function getLabels()
    {
        return array(
            self::YEARLY => Horde_LoginTasks_Translation::t("Yearly"),
            self::MONTHLY => Horde_LoginTasks_Translation::t("Monthly"),
            self::WEEKLY => Horde_LoginTasks_Translation::t("Weekly"),
            self::DAILY => Horde_LoginTasks_Translation::t("Daily"),
            self::EVERY => Horde_LoginTasks_Translation::t("Every Login")
        );
    }
}
