<?php
/**
 * Login tasks module that presents a TOS Agreement page to user.
 * If user does not accept terms, user is not allowed to login.
 *
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Horde
 */
class Horde_LoginTasks_Task_TosAgreement extends Horde_LoginTasks_Task
{
    /**
     * The interval at which to run the task.
     *
     * @var integer
     */
    public $interval = Horde_LoginTasks::FIRST_LOGIN;

    /**
     * The style of the page output.
     *
     * @var integer
     */
    public $display = Horde_LoginTasks::DISPLAY_AGREE;

    /**
     * The priority of the task.
     *
     * @var integer
     */
    public $priority = Horde_LoginTasks::PRIORITY_HIGH;

    /**
     * Constructor.
     */
    public function __construct()
    {
        global $conf;

        $this->active = false;

        if (!empty($conf['tos']['file'])) {
            if (file_exists($conf['tos']['file'])) {
                $this->active = true;
            } else {
                Horde::logMessage('Terms of Service Agreement file was not found: ' . $conf['tos']['file'], 'ERR');
            }
        }
    }

    /**
     * Determine if user agreed with the terms or not.  If the user does not
     * agree, log him/her out immediately.
     *
     * @throws Horde_Exception_AuthenticationFailure
     */
    public function execute()
    {
        if (Horde_Util::getFormData('not_agree')) {
            throw new Horde_Exception_AuthenticationFailure(_("You did not agree to the Terms of Service agreement, so you were not allowed to login."), Horde_Auth::REASON_MESSAGE);
        }
    }

    /**
     * Returns the TOS agreement for display on the login tasks page.
     *
     * @return string  The terms of service agreement.
     */
    public function describe()
    {
        return file_get_contents($GLOBALS['conf']['tos']['file']);
    }

}
