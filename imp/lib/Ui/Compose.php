<?php
/**
 * The IMP_Ui_Compose:: class is designed to provide a place to store common
 * code shared among IMP's various UI views for the compose page.
 *
 * Copyright 2006-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package IMP
 */
class IMP_Ui_Compose
{
    /**
     * Expand addresses in a string. Only the last address in the string will
     * be expanded.
     *
     * @param string $input             The input string.
     * @param IMP_Compose $imp_compose  An IMP_Compose object.
     *
     * @return mixed  If a string, this value should be used as the new
     *                input string.  If an array, the first value is the
     *                input string without the search string; the second
     *                value is the search string; and the third value is
     *                the list of matching addresses.
     */
    public function expandAddresses($input, $imp_compose)
    {
        $addr_list = $this->getAddressList($input, array('addr_list' => true));
        if (empty($addr_list)) {
            return '';
        }

        $search = array_pop($addr_list);

        /* Don't search if the search string looks like an e-mail address. */
        if ((strpos($search, '<') !== false) ||
            (strpos($search, '@') !== false)) {
            array_push($addr_list, $search);
            return implode(', ', $addr_list);
        }

        $res = $imp_compose->expandAddresses($search, array('levenshtein' => true));

        if (count($res) == 1) {
            array_push($addr_list, reset($res));
            return implode(', ', $addr_list);
        } elseif (!count($res)) {
            $GLOBALS['notification']->push(sprintf(_("Search for \"%s\" failed: no address found."), $search), 'horde.warning');
            array_push($addr_list, $search);
            return implode(', ', $addr_list);
        }

        $GLOBALS['notification']->push(_("Ambiguous address found."), 'horde.warning');

        return array(
            implode(', ', $addr_list),
            $search,
            $res
        );
    }

    /**
     * Redirect a message.
     *
     * @param string $to                  The To address.
     * @param IMP_Compose $imp_compose    An IMP_Compose object.
     * @param IMP_Contents $imp_contents  An IMP_Contents object.
     *
     * @throws Horde_Exception
     */
    public function redirectMessage($to, $imp_compose, $contents)
    {
        try {
            $recip = $imp_compose->recipientList(array('to' => $to));
        } catch (IMP_Compose_Exception $e) {
            throw new Horde_Exception_Prior($recip);
        }
        $recipients = implode(', ', $recip['list']);

        $identity = Horde_Prefs_Identity::singleton(array('imp', 'imp'));
        $from_addr = $identity->getFromAddress();

        $headers = $contents->getHeaderOb();
        $headers->addResentHeaders($from_addr, $recip['header']['to']);

        $mime_message = $contents->getMIMEMessage();

        /* We need to set the Return-Path header to the current user - see
           RFC 2821 [4.4]. */
        $headers->removeHeader('return-path');
        $headers->addHeader('Return-Path', $from_addr);

        /* Store history information. */
        if (!empty($GLOBALS['conf']['maillog']['use_maillog'])) {
            IMP_Maillog::log('redirect', $headers->getValue('message-id'), $recipients);
        }

        try {
            $imp_compose->sendMessage($recipients, $headers, $mime_message);
        } catch (IMP_Compose_Exception $e) {
            throw new Horde_Exception_Prior($e);
        }

        $entry = sprintf("%s Redirected message sent to %s from %s",
                         $_SERVER['REMOTE_ADDR'], $recipients, Horde_Auth::getAuth());
        Horde::logMessage($entry, __FILE__, __LINE__, PEAR_LOG_INFO);

        if ($GLOBALS['conf']['sentmail']['driver'] != 'none') {
            $sentmail = IMP_Sentmail::factory();
            $sentmail->log('redirect', $headers->getValue('message-id'), $recipients);
        }
    }

    /**
     * Attach the auto-completer to the current compose form.
     *
     * @param array $fields  The list of DOM IDs to attach the autocompleter
     *                       to.
     */
    public function attachAutoCompleter($fields)
    {
        /* Attach autocompleters to the compose form elements. */
        foreach ($fields as $val) {
            Horde_Ajax_Imple::factory(array('imp', 'ContactAutoCompleter'), array('triggerId' => $val))->attach();
        }
    }

    /**
     * Attach the spellchecker to the current compose form.
     */
    public function attachSpellChecker()
    {
        $menu_view = $GLOBALS['prefs']->getValue('menu_view');
        $spell_img = Horde::img('spellcheck.png');

        if (IMP::getViewMode() == 'imp') {
            $br = '<br />';
            $id = 'IMP';
        } else {
            $br = '';
            $id = 'DIMP';
        }

        $args = array(
            'id' => $id . '.SpellChecker',
            'targetId' => 'composeMessage',
            'triggerId' => 'spellcheck',
            'states' => array(
                'CheckSpelling' => $spell_img . (($menu_view == 'text' || $menu_view == 'both') ? $br . _("Check Spelling") : ''),
                'Checking' => $spell_img . $br . _("Checking..."),
                'ResumeEdit' => $spell_img . $br . _("Resume Editing"),
                'Error' => $spell_img . $br . _("Spell Check Failed")
            )
        );

        Horde_Ajax_Imple::factory('SpellChecker', $args)->attach();
    }

    /**
     * Given an address input, parses the input to obtain the list of
     * addresses to use on the compose page.
     *
     * @param string $addr   The value of the header string.
     * @param array $opts  Additional options:
     * <pre>
     * 'addr_list' - (boolean) Return the list of address components?
     *               DEFAULT: false
     * </pre>
     *
     * @return mixed  TODO
     */
    public function getAddressList($addr, $opts = array())
    {
        $addr = rtrim(trim($addr), ',');
        $addr_list = array();

        if (!empty($addr)) {
            // Although we allow ';' to delimit addresses in the UI, need to
            // convert to RFC-compliant ',' delimiter for processing.
            foreach (Horde_Mime_Address::explode($addr, ',;') as $val) {
                $addr_list[] = IMP_Compose::formatAddr(trim($val));
            }
        }

        return empty($opts['addr_list'])
            ? implode(', ', $addr_list)
            : $addr_list;
    }

    /**
     * Initialize the Rich Text Editor (RTE).
     *
     * @param boolean $mini  Load the basic ckeditor stub?
     */
    public function initRTE($basic = false)
    {
        $editor = Horde_Editor::singleton('Ckeditor', array('basic' => $basic));

        $config = array(
            /* To more closely match "normal" textarea behavior, send <BR> on
             * enter instead of <P>. */
            // CKEDITOR.ENTER_BR
            'enterMode: 2',
            // CKEDITOR.ENTER_P
            'shiftEnterMode: 1',

            /* Don't load the config.js file. */
            'customConfig: ""',

            /* Disable resize of the textarea. */
            'resize_enabled: false',

            /* Use the old skin for now. */
            'skin: "v2"',

            /* Default display font. This is NOT the font used to send
             * the message, however. */
            'contentsCss: "body { font-family: Arial; font-size: 12px; }"',
            'font_defaultLabel: "Arial"',
            'fontSize_defaultLabel: "12px"'
        );

        $buttons = $GLOBALS['prefs']->getValue('ckeditor_buttons');
        if (!empty($buttons)) {
            $config[] = 'toolbar: ' . $GLOBALS['prefs']->getValue('ckeditor_buttons');
        }

        Horde::addInlineScript(array(
            'window.IMP = window.IMP || {}',
            'IMP.ckeditor_config = {' . implode(',', $config) . '}'
        ));
    }

    /**
     * Get the IMP_Contents:: object for a Mailbox -> UID combo.
     *
     * @param integer $uid     Message UID.
     * @param string $mailbox  Message mailbox.
     *
     * @return boolean|IMP_Contents  The contents object, or false on error.
     */
    public function getIMPContents($uid, $mailbox)
    {
        if (!empty($uid)) {
            try {
                return IMP_Contents::singleton($uid . IMP::IDX_SEP . $mailbox);
            } catch (Horde_Exception $e) {
                $GLOBALS['notification']->push(_("Could not retrieve the message from the mail server."), 'horde.error');
            }
        }

        return false;
    }

    /**
     * Generate mailbox return URL.
     *
     * @param string $url  The URL to use instead of the default.
     *
     * @return string  The mailbox return URL.
     */
    public function mailboxReturnUrl($url)
    {
        if (!$url) {
            $url = Horde::applicationUrl('mailbox.php')->setRaw(true);
        }

        foreach (array('start', 'page', 'mailbox', 'thismailbox') as $key) {
            if (($param = Horde_Util::getFormData($key))) {
                $url->add($key, $param);
            }
        }

        return $url;
    }

    /**
     * Generate a compose message popup success window (compose.php).
     */
    public function popupSuccess()
    {
        $menu = new Horde_Menu(Horde_Menu::MASK_NONE);
        $menu->add(Horde::applicationUrl('compose.php'), _("New Message"), 'compose.png');
        $menu->add('', _("Close this window"), 'close.png', null, null, 'window.close();');
        require IMP_TEMPLATES . '/common-header.inc';
        $success_template = $GLOBALS['injector']->createInstance('Horde_Template');
        $success_template->set('menu', $menu->render());
        echo $success_template->fetch(IMP_TEMPLATES . '/imp/compose/success.html');
        IMP::status();
        require $GLOBALS['registry']->get('templates', 'horde') . '/common-footer.inc';
    }

    /**
     * Outputs the script necessary to generate the passphrase dialog box.
     *
     * @param string $type     Either 'pgp', 'pgp_symm', or 'smime'.
     * @param string $cacheid  Compose cache ID (only needed for 'pgp_symm').
     */
    public function passphraseDialog($type, $cacheid = null)
    {
        $params = array('onload' => true);

        switch ($type) {
        case 'pgp':
            $type = 'pgpPersonal';
            break;

        case 'pgp_symm':
            $params = array('symmetricid' => 'imp_compose_' . $cacheid);
            $type = 'pgpSymmetric';
            break;

        case 'smime':
            $type = 'smimePersonal';
            break;
        }

        Horde_Ajax_Imple::factory(array('imp', 'PassphraseDialog'), array('params' => $params, 'type' => $type))->attach();
    }

    /**
     */
    public function identityJs()
    {
        $identities = array();
        $identity = Horde_Prefs_Identity::singleton(array('imp', 'imp'));

        foreach ($identity->getAllSignatures() as $ident => $sig) {
            $identities[] = array(
                // Plain text signature
                'sig' => $sig,
                // HTML signature
                'sig_html' => str_replace(' target="_blank"', '', Horde_Text_Filter::filter($sig, 'text2html', array('parselevel' => Horde_Text_Filter_Text2html::MICRO_LINKURL, 'class' => null, 'callback' => null))),
                // Signature location
                'sig_loc' => (bool)$identity->getValue('sig_first', $ident),
                // Sent mail folder name
                'smf_name' => $identity->getValue('sent_mail_folder', $ident),
                // Save in sent mail folder by default?
                'smf_save' => (bool)$identity->saveSentmail($ident),
                // Sent mail display name
                'smf_display' => IMP::displayFolder($identity->getValue('sent_mail_folder', $ident)),
                // Bcc addresses to add
                'bcc' => Horde_Mime_Address::addrArray2String($identity->getBccAddresses($ident))
            );
        }

        return 'IMP_Compose_Base.identities = ' . Horde_Serialize::serialize($identities, Horde_Serialize::JSON);
    }


}
