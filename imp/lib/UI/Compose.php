<?php
/**
 * The IMP_UI_Compose:: class is designed to provide a place to dump common
 * code shared among IMP's various UI views for the compose page.
 *
 * $Horde: imp/lib/UI/Compose.php,v 1.30 2008/10/20 03:54:40 slusarz Exp $
 *
 * Copyright 2006-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package IMP
 * @since   IMP 4.2
 */
class IMP_UI_Compose {

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
                    if (is_object($entry)) {
                        $result[] = $entry->getUserInfo();
                    } else {
                        $result[] = $entry;
                    }
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

        /* We need to rebuild the headers ourselves to ensure that all MIME
         * decoding stays as-is. */
        require_once IMP_BASE . '/lib/MIME/Headers.php';
        $imp_imap = &IMP_IMAP::singleton();
        $imp_imap->changeMbox($contents->getMessageMailbox(), IMP_IMAP_AUTO);
        $headers = new IMP_Headers($contents->getMessageIndex());
        $headers->buildHeaders(false);

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
            require_once IMP_BASE . '/lib/Maillog.php';
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
            require_once IMP_BASE . '/lib/Sentmail.php';
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
            $imp_compose->attachIMAPMessage(array($index), $fwd_msg['headers']);
        } elseif ($type == 'forward_attachments') {
            $err = $imp_compose->attachFilesFromMessage($imp_contents, true);
            if (!empty($err)) {
                foreach ($err as $val) {
                    $GLOBALS['notification']->push($val, 'horde.warning');
                }
            }
        }

        return $fwd_msg;
    }

    /**
     */
    function attachAutoCompleter($lib, $fields)
    {
        /* Attach autocompleters to the compose form elements. */
        foreach ($fields as $val) {
            call_user_func_array(array($lib, 'factory'), array('ContactAutoCompleter', array('triggerId' => $val)));
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
        call_user_func_array(array($mode == 'dimp' ? 'Dimple' : 'Imple', 'factory'), array('SpellChecker', $args));
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
            require_once 'Horde/String.php';
            $clean_to = '';
            foreach (Horde_MIME_Address::explode($to, ',;') as $val) {
                $val = trim($val);
                $clean_to .= $val . (($val[String::length($val) - 1] == ';') ? ' ' : ', ');
            }
            if ($expand) {
               return $clean_to;
            } else {
               require_once IMP_BASE . '/lib/Compose.php';
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
        require_once 'Horde/Editor.php';
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
