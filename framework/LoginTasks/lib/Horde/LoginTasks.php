<?php
/**
 * The Horde_LoginTasks:: class provides a set of methods for dealing with
 * login tasks to run upon login to Horde applications.
 *
 * Copyright 2001-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
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
            $this->_tasklist = $this->_backend->storeTasklistInCache($this->_tasklist);
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
        $lasttask_pref = $this->_backend->getLastRun();

        /* Create time objects for today's date and last task run date. */
        $cur_date = getdate();

        foreach ($this->_backend->getTasks() as $classname => $app) {
            $ob = new $classname($this->_backend);

            /* If marked inactive, skip the task. */
            if (!$ob->active) {
                continue;
            }

            $addtask = false;

            if ($ob->interval == self::FIRST_LOGIN) {
                $addtask = empty($lasttask_pref[$app]);
            } else {
                $lastrun = getdate(empty($lasttask_pref[$app]) ? time() : $lasttask_pref[$app]);

                switch ($ob->interval) {
                case self::YEARLY:
                    $addtask = ($cur_date['year'] > $lastrun['year']);
                    break;

                case self::MONTHLY:
                    $addtask = (($cur_date['year'] > $lastrun['year']) || ($cur_date['mon'] > $lastrun['mon']));
                    break;

                case self::WEEKLY:
                    $addtask = (($cur_date['wday'] < $lastrun['wday']) || ($cur_date['yday'] >= $lastrun['yday'] + 7));
                    break;

                case self::DAILY:
                    $addtask = (($cur_date['year'] > $lastrun['year']) || ($cur_date['yday'] > $lastrun['yday']));
                    break;

                case self::EVERY:
                    $addtask = true;
                    break;

                case self::ONCE:
                    if (empty($lasttask_pref['_once']) ||
                        !in_array($classname, $lasttask_pref['_once'])) {
                        $addtask = true;
                        $lasttask_pref['_once'][] = $classname;
                        $this->_backend->setLastRun($lasttask_pref);
                    }
                    break;
                }
            }

            if ($addtask) {
                if ($ob instanceof Horde_LoginTasks_SystemTask) {
                    $ob->execute();
                } else {
                    $this->_tasklist->addTask($ob);
                }
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
     * @param boolean $confirmed  If true, indicates that any pending actions
     *                            have been confirmed by the user.
     * @param string $url         The URL to redirect to when finished.
     */
    public function runTasks($confirmed = false, $url = null)
    {
        if (!isset($this->_tasklist) ||
            ($this->_tasklist === true)) {
            return;
        }

        if (empty($this->_tasklist->target)) {
            $this->_tasklist->target = $url;
        }

        /* Perform ready tasks now. */
        foreach ($this->_tasklist->ready(!$this->_tasklist->processed || $confirmed) as $key => $val) {
            if (in_array($val->display, array(self::DISPLAY_AGREE, self::DISPLAY_NOTICE, self::DISPLAY_NONE)) ||
                Horde_Util::getFormData('logintasks_confirm_' . $key)) {
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

            return $this->_backend->redirect($url);
        }

        if ((!$processed || $confirmed) && $this->_tasklist->needDisplay()) {
            return $this->_backend->redirect($this->getLoginTasksUrl());
        }
    }

    /**
     * Generate the list of tasks that need to be displayed.
     *
     * This is the function called from the login tasks page every time it
     * is loaded.
     *
     * @return array  The list of tasks that need to be displayed.
     */
    public function displayTasks()
    {
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
            self::YEARLY => _("Yearly"),
            self::MONTHLY => _("Monthly"),
            self::WEEKLY => _("Weekly"),
            self::DAILY => _("Daily"),
            self::EVERY => _("Every Login")
        );
    }

}
