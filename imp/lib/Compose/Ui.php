<?php
/**
 * Copyright 2006-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2006-2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Common UI code shared among IMP's compose pages.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2006-2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Compose_Ui
{
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
            $GLOBALS['injector']->getInstance('Horde_Core_Factory_Imple')->create('IMP_Ajax_Imple_ContactAutoCompleter', array('id' => $val));
        }
    }

    /**
     * Attach the spellchecker to the current compose form.
     */
    public function attachSpellChecker()
    {
        global $injector, $prefs, $registry;

        if ($registry->getView() == Horde_Registry::VIEW_BASIC) {
            $spell_img = '<span class="iconImg spellcheckImg"></span>';
            $br = '<br />';
        } else {
            $spell_img = $br = '';
        }
        $menu_view = $prefs->getValue('menu_view');

        $injector->getInstance('Horde_Core_Factory_Imple')->create('SpellChecker', array(
            'id' => 'spellcheck',
            'states' => array(
                'CheckSpelling' => $spell_img . (in_array($menu_view, array('both', 'text')) ? $br . _("Check Spelling") : ''),
                'Checking' => $spell_img . $br . _("Checking..."),
                'Error' => $spell_img . $br . _("Spell Check Failed"),
                'ResumeEdit' => $spell_img . $br . _("Resume Editing")
            ),
            'targetId' => 'composeMessage'
        ));
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

        $GLOBALS['injector']->getInstance('Horde_Core_Factory_Imple')->create('IMP_Ajax_Imple_PassphraseDialog', array(
            'onload' => true,
            'params' => $params,
            'type' => $type
        ));
    }

    /**
     */
    public function addIdentityJs()
    {
        $identities = array();
        $identity = $GLOBALS['injector']->getInstance('IMP_Identity');

        foreach (array_keys($identity->getAll('id')) as $ident) {
            $sm = $identity->getValue('sent_mail_folder', $ident);

            $identities[] = array(
                // Sent mail mailbox name
                'sm_name' => $sm ? $sm->form_to : '',
                // Save in sent mail mailbox by default?
                'sm_save' => (bool)$identity->saveSentmail($ident),
                // Sent mail display name
                'sm_display' => $sm ? $sm->display_html : '',
                // Bcc addresses to add
                'bcc' => strval($identity->getBccAddresses($ident))
            );
        }

        $GLOBALS['page_output']->addInlineJsVars(array(
            'ImpComposeBase.identities' => $identities
        ));
    }

    /**
     * Convert compose data to/from text/HTML.
     *
     * @param string $data  The message text.
     * @param string $to    Either 'text' or 'html'.
     *
     * @return string  The converted text.
     */
    public function convertComposeText($data, $to)
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
