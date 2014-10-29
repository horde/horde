<?php
/**
 * Copyright 2006-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2006-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Common code dealing with message parsing relating to UI display.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2006-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Message_Ui
{
    /**
     * Return a list of "basic" headers w/gettext translations.
     *
     * @return array  Header name -> gettext translation mapping.
     */
    public function basicHeaders()
    {
        return array(
            'date'      =>  _("Date"),
            'from'      =>  _("From"),
            'to'        =>  _("To"),
            'cc'        =>  _("Cc"),
            'bcc'       =>  _("Bcc"),
            'reply-to'  =>  _("Reply-To"),
            'subject'   =>  _("Subject")
        );
    }

    /**
     * Get the list of user-defined headers to display.
     *
     * @return array  The list of user-defined headers.
     */
    public function getUserHeaders()
    {
        $user_hdrs = $GLOBALS['prefs']->getValue('mail_hdr');

        /* Split the list of headers by new lines and sort the list of headers
         * to make sure there are no duplicates. */
        if (is_array($user_hdrs)) {
            $user_hdrs = implode("\n", $user_hdrs);
        }
        $user_hdrs = trim($user_hdrs);
        if (empty($user_hdrs)) {
            return array();
        }

        $user_hdrs = array_filter(array_keys(array_flip(array_map('trim', preg_split("/[\n\r]+/", str_replace(':', '', $user_hdrs))))));
        natcasesort($user_hdrs);

        return $user_hdrs;
    }

    /**
     * Check if we need to send a MDN, and send if needed.
     *
     * @param IMP_Indices $indices         Indices object of the message.
     * @param Horde_Mime_Headers $headers  The headers of the message.
     * @param boolean $confirmed           Has the MDN request been confirmed?
     *
     * @return boolean  True if the MDN request needs to be confirmed.
     */
    public function MDNCheck(
        IMP_Indices $indices, $headers, $confirmed = false
    )
    {
        global $conf, $injector, $prefs;

        $maillog = $injector->getInstance('IMP_Maillog');
        $pref_val = $prefs->getValue('send_mdn');

        list($mbox, ) = $indices->getSingle();

        if (!$pref_val || $mbox->readonly) {
            return false;
        }

        /* Check to see if an MDN has been requested. */
        $mdn = new Horde_Mime_Mdn($headers);
        if (!($return_addr = $mdn->getMdnReturnAddr())) {
            return false;
        }

        $log_msg = new IMP_Maillog_Message($indices);
        if (count($maillog->getLog($log_msg, array('forward', 'redirect', 'reply_all', 'reply_list', 'reply')))) {
            return false;
        }

        /* See if we need to query the user. */
        if (!$confirmed &&
            ((intval($pref_val) == 1) ||
             $mdn->userConfirmationNeeded())) {
            try {
                if ($injector->getInstance('Horde_Core_Hooks')->callHook('mdn_check', 'imp', array($headers))) {
                    return true;
                }
            } catch (Horde_Exception_HookNotSet $e) {
                return true;
            }
        }

        /* Send out the MDN now. */
        $success = false;
        try {
            $mdn->generate(
                false,
                $confirmed,
                'displayed',
                $conf['server']['name'],
                $injector->getInstance('IMP_Mail'),
                array(
                    'charset' => 'UTF-8',
                    'from_addr' => $injector->getInstance('Horde_Core_Factory_Identity')->create()->getDefaultFromAddress()
                )
            );

            $maillog->log($log_msg, new IMP_Maillog_Log_Mdn());

            $success = true;
        } catch (Exception $e) {}

        $injector->getInstance('IMP_Sentmail')->log(
            IMP_Sentmail::MDN,
            '',
            $return_addr,
            $success
        );

        return false;
    }

    /**
     * Returns e-mail information for a mailing list.
     *
     * @param Horde_Mime_Headers $headers  A Horde_Mime_Headers object.
     *
     * @return array  An array with 2 elements: 'exists' and 'reply_list'.
     */
    public function getListInformation($headers)
    {
        $lh = $GLOBALS['injector']->getInstance('Horde_ListHeaders');
        $ret = array('exists' => false, 'reply_list' => null);

        if ($lh->listHeadersExist($headers)) {
            $ret['exists'] = true;

            /* See if the List-Post header provides an e-mail address for the
             * list. */
            if ($val = $headers['List-Post']) {
                foreach ($lh->parse('list-post', $val) as $val2) {
                    if ($val2 instanceof Horde_ListHeaders_NoPost) {
                        break;
                    } elseif (stripos($val2->url, 'mailto:') === 0) {
                        $ret['reply_list'] = substr($val2->url, 7);
                        break;
                    }
                }
            }
        }

        return $ret;
    }

}
