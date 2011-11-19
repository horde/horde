<?php
/**
 * The IMP_Ui_Compose:: class is designed to provide a place to store common
 * code shared among IMP's various UI views for the compose page.
 *
 * Copyright 2006-2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
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
     * Attach the auto-completer to the current compose form.
     *
     * @param array $fields  The list of DOM IDs to attach the autocompleter
     *                       to.
     */
    public function attachAutoCompleter($fields)
    {
        /* Attach autocompleters to the compose form elements. */
        foreach ($fields as $val) {
            $GLOBALS['injector']->getInstance('Horde_Core_Factory_Imple')->create(array('imp', 'ContactAutoCompleter'), array('triggerId' => $val));
        }
    }

    /**
     * Attach the spellchecker to the current compose form.
     */
    public function attachSpellChecker()
    {
        $menu_view = $GLOBALS['prefs']->getValue('menu_view');
        $spell_img = '<span class="iconImg spellcheckImg"></span>';

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

        $GLOBALS['injector']->getInstance('Horde_Core_Factory_Imple')->create('SpellChecker', $args);
    }

    /**
     * Given an address input, parses the input to obtain the list of
     * addresses to use on the compose page.
     *
     * @param string $addr  The value of the header string.
     * @param array $opts   Additional options:
     *   - addr_list: (boolean) Return the list of address components?
     *                DEFAULT: false
     *
     * @return mixed  List of addresses, or a string of addresses if
     *                'addr_list' is true.
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
     * Create the IMP_Contents objects needed to create a message.
     *
     * @param Horde_Variables $vars  The variables object.
     *
     * @return IMP_Contents  The IMP_Contents object.
     * @throws IMP_Exception
     */
    public function getContents($vars = null)
    {
        $indices = $ob = null;

        if (is_null($vars) || !isset($vars->uids)) {
            $indices = IMP::$thismailbox->getIndicesOb(IMP::$uid);
        } else {
            $indices = new IMP_Indices_Form($vars->uids);
        }

        if (!is_null($indices)) {
            try {
                $ob = $GLOBALS['injector']->getInstance('IMP_Factory_Contents')->create($indices);
            } catch (Horde_Exception $e) {}
        }

        if (is_null($ob)) {
            if (!is_null($vars)) {
                $vars->uid = null;
                $vars->type = 'new';
            }

            throw new IMP_Exception(_("Could not retrieve message data from the mail server."));
        }

        return $ob;
    }

    /**
     * Generate mailbox return URL.
     *
     * @param string $url  The URL to use instead of the default.
     *
     * @return string  The mailbox return URL.
     */
    public function mailboxReturnUrl($url = null)
    {
        if (!$url) {
            $url = Horde::url('mailbox.php');
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
        $menu->add(Horde::url('compose.php'), _("New Message"), 'compose.png');
        $menu->add(new Horde_Url(''), _("Close this window"), 'close.png', null, null, 'window.close();');
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

        $GLOBALS['injector']->getInstance('Horde_Core_Factory_Imple')->create(array('imp', 'PassphraseDialog'), array(
            'onload' => true,
            'params' => $params,
            'type' => $type
        ));
    }

    /**
     * @return array  See Horde::addInlineJsVars().
     */
    public function identityJs()
    {
        $identities = array();
        $identity = $GLOBALS['injector']->getInstance('IMP_Identity');

        foreach (array_keys($identity->getAll('id')) as $ident) {
            $smf = $identity->getValue('sent_mail_folder', $ident);

            $identities[] = array(
                // Sent mail folder name
                'smf_name' => $smf ? $smf->form_to : '',
                // Save in sent mail folder by default?
                'smf_save' => (bool)$identity->saveSentmail($ident),
                // Sent mail display name
                'smf_display' => $smf ? $smf->display : '',
                // Bcc addresses to add
                'bcc' => Horde_Mime_Address::addrArray2String($identity->getBccAddresses($ident), array('charset' => 'UTF-8'))
            );
        }

        return Horde::addInlineJsVars(array(
            'ImpComposeBase.identities' => $identities
        ), array('ret_vars' => true));
    }

    /**
     * Convert compose data to/from text/HTML.
     *
     * @param string $data       The message text.
     * @param string $to         Either 'text' or 'html'.
     * @param integer $identity  The current identity.
     *
     * @return string  The converted text.
     */
    public function convertComposeText($data, $to, $identity)
    {
        switch ($to) {
        case 'html':
            return IMP_Compose::text2html($data);

        case 'text':
            return $GLOBALS['injector']->getInstance('Horde_Core_Factory_TextFilter')->filter($data, 'Html2text', array(
                'wrap' => false
            ));
        }
    }

}
