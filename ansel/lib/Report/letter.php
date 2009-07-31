<?php
/**
 * Report by letter api class
 *
 * @author  Duck <duck@obala.net>
 * @package Ansel
 */
class Ansel_Report_letter extends Ansel_Report {

    /**
     * Report
     */
    function report($message, $users = array())
    {
        if (!empty($users)) {
            // We are sending a report to to the gallery owner
            $admins = array($users);
        } elseif (empty($GLOBALS['conf']['report_content']['users'])) {
            $admins = $this->getAdmins();
            if (empty($admins)) {
                return true;
            }
        } else {
            $admins = $GLOBALS['conf']['report_content']['users'];
        }

        $title = $this->getTitle();
        $message = $this->getMessage($message);

        return $GLOBALS['registry']->callByPackage(
            'letter', 'sendMessage', array($admins,
                                           array('title' => $title,
                                                 'content' => $message)));
    }
}
