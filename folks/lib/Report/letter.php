<?php
/**
 * Report by letter api class
 *
 * $Horde: Folks/lib/Report/letter.php,v 1.5 2008/07/03 04:13:35 mrubinsk Exp $
 *
 * @author  Duck <duck@obala.net>
 * @package Folks
 */
class Folks_Report_letter extends Folks_Report {

    /**
     * Report
     */
    public function report($message, $users = array())
    {
        if (!empty($users)) {
            // We are sending a report to to the gallery owner
            $admins = array($users);
        } elseif (empty($GLOBALS['conf']['report_content']['users'])) {
            $admins = $this->getAdmins();
            if ($admins instanceof PEAR_Error) {
                return $admins;
            }
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