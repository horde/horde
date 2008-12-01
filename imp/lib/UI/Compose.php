<?php
/**
 * The IMP_UI_Compose:: class is designed to provide a place to store common
 * code shared among IMP's various UI views for the compose page.
 *
 * Copyright 2006-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package IMP
 */
class IMP_UI_Compose
{
    /**
     */
    function expandAddresses($input, &$imp_compose)
    {
        $result = $imp_compose->expandAddresses($this->getAddressList($input, null, null, null, true));

        if (is_array($result)) {
            $GLOBALS['notification']->push(_("Please resolve ambiguous or invalid addresses."), 'horde.warning');
        } elseif (is_a($result, 'PEAR_Error')) {
            $error = $result;
            $result = array();

            $list = $error->getUserInfo();
            if (is_array($list)) {
                foreach ($list as $entry) {
                    $result[] = is_object($entry)
                        ? $entry->getUserInfo()
                        : $entry;
                }
            }
            $GLOBALS['notification']->push($error, 'horde.warning');
        }

        return $result;
    }

    /**
     * $encoding = DEPRECATED
     */
    function redirectMessage($to, $imp_compose, $contents, $encoding)
    {
        $recip = $imp_compose->recipientList(array('to' => $to));
        if (is_a($recip, 'PEAR_Error')) {
            return $recip;
        }
        $recipients = implode(', ', $recip['list']);

        $identity = &Identity::singleton(array('imp', 'imp'));
        $from_addr = $identity->getFromAddress();

        $headers = $contents->getHeaderOb();
        $headers->addResentHeaders($from_addr, $recip['header']['to']);

        $mime_message = $contents->getMIMEMessage();
        $charset = $mime_message->getCharset();
        if (is_null($charset)) {
            $charset = $encoding;
        }

        /* We need to set the Return-Path header to the current user - see
           RFC 2821 [4.4]. */
        $headers->removeHeader('return-path');
        $headers->addHeader('Return-Path', $from_addr);

        $bodytext = $contents->getBody();
        $status = $imp_compose->sendMessage($recipients, $headers, $bodytext, $charset);
        $error = is_a($status, 'PEAR_Error');

        /* Store history information. */
        if (!empty($GLOBALS['conf']['maillog']['use_maillog'])) {
            IMP_Maillog::log('redirect', $headers->getValue('message-id'), $recipients);
        }

        if ($error) {
            Horde::logMessage($status->getMessage(), __FILE__, __LINE__, PEAR_LOG_ERR);
            return $status;
        }

        $entry = sprintf("%s Redirected message sent to %s from %s",
                         $_SERVER['REMOTE_ADDR'], $recipients, $_SESSION['imp']['uniquser']);
        Horde::logMessage($entry, __FILE__, __LINE__, PEAR_LOG_INFO);

        if ($GLOBALS['conf']['sentmail']['driver'] != 'none') {
            $sentmail = IMP_Sentmail::factory();
            $sentmail->log('redirect', $headers->getValue('message-id'), $recipients);
        }

        return true;
    }

    /**
     */
    function getForwardData(&$imp_compose, &$imp_contents, $type, $index)
    {
        $fwd_msg = $imp_compose->forwardMessage($imp_contents, ($type == 'forward_body'));
        if ($type == 'forward_all') {
            $subject_header = $imp_compose->attachIMAPMessage(array($index), $fwd_msg['headers']);
            if ($subject_header === false) {
                // TODO: notification
            } else {
                $fwd_msg['headers']['subject'] = $subject_header;
            }
        } elseif ($type == 'forward_attachments') {
            $imp_compose->attachFilesFromMessage($imp_contents, array('downloadall' => true, 'notify' => true));
        }

        return $fwd_msg;
    }

    /**
     */
    function attachAutoCompleter($fields)
    {
        /* Attach autocompleters to the compose form elements. */
        foreach ($fields as $val) {
            call_user_func_array(array('IMP_Imple', 'factory'), array('ContactAutoCompleter', array('triggerId' => $val)));
        }
    }

    /**
     */
    function attachSpellChecker($mode, $add_br = false)
    {
        $menu_view = $GLOBALS['prefs']->getValue('menu_view');
        $show_text = ($menu_view == 'text' || $menu_view == 'both');
        $br = ($add_br) ? '<br />' : '';
        $spell_img = Horde::img('spellcheck.png');
        $args = array(
            'id' => ($mode == 'dimp' ? 'DIMP.' : 'IMP.') . 'SpellCheckerObject',
            'targetId' => 'message',
            'triggerId' => 'spellcheck',
            'states' => array(
                'CheckSpelling' => $spell_img . ($show_text ? $br . _("Check Spelling") : ''),
                'Checking' => $spell_img . $br . _("Checking ..."),
                'ResumeEdit' => $spell_img . $br . _("Resume Editing"),
                'Error' => $spell_img . $br . _("Spell Check Failed")
            )
        );
        call_user_func_array(array('IMP_Imple', 'factory'), array('SpellChecker', $args));
    }

    /**
     */
    function getAddressList($to, $to_list = array(), $to_field = array(),
                            $to_new = '', $expand = false)
    {
        $to = rtrim(trim($to), ',');
        if (!empty($to)) {
            // Although we allow ';' to delimit addresses in the UI, need to
            // convert to RFC-compliant ',' delimiter for processing.
            $clean_to = '';
            foreach (Horde_Mime_Address::explode($to, ',;') as $val) {
                $val = trim($val);
                $clean_to .= $val . (($val[String::length($val) - 1] == ';') ? ' ' : ', ');
            }
            if ($expand) {
               return $clean_to;
            } else {
               return IMP_Compose::formatAddr($clean_to);
            }
        }

        $tmp = array();
        if (is_array($to_field)) {
            foreach ($to_field as $key => $address) {
                $tmp[$key] = $address;
            }
        }
        if (is_array($to_list)) {
            foreach ($to_list as $key => $address) {
                if ($address != '') {
                    $tmp[$key] = $address;
                }
            }
        }

        $to_new = rtrim(trim($to_new), ',');
        if (!empty($to_new)) {
            $tmp[] = $to_new;
        }
        return implode(', ', $tmp);
    }

    /**
     */
    function initRTE($mode = 'imp')
    {
        $editor = &Horde_Editor::singleton('fckeditor', array('id' => 'message', 'no_notify' => true));

        $fck_buttons = $GLOBALS['prefs']->getValue('fckeditor_buttons');
        if (!empty($fck_buttons)) {
            $js_onload = array(
                'oFCKeditor.Config.CustomConfigurationsPath = "' . IMP::getCacheURL('fckeditor', null) . '"',
                'oFCKeditor.ToolbarSet = "ImpToolbar"'
            );
            if ($mode == 'imp') {
                $js_onload[] = 'oFCKeditor.Height = $(\'message\').getHeight()';
                $js_onload[] = 'oFCKeditor.ReplaceTextarea()';
            }
            IMP::addInlineScript($js_onload, 'load');
        }

        return $editor->getJS();
    }

}
