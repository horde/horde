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
 * Common UI code shared among IMP's compose pages.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2006-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Compose_Ui
{
    /**
     * True if spellchecker has been attached.
     *
     * @var boolean
     */
    protected $_spellInit = false;

    /**
     * Attach the spellchecker to the current compose form.
     *
     * @return boolean  True if spellchecker is active.
     */
    public function attachSpellChecker()
    {
        global $conf, $injector, $registry;

        if (empty($conf['spell']['driver'])) {
            return false;
        } elseif ($this->_spellInit) {
            return true;
        }

        $injector->getInstance('Horde_Core_Factory_Imple')->create('SpellChecker', array(
            'id' => 'spellcheck',
            'states' => array(
                'CheckSpelling' => _("Check Spelling"),
                'Checking' => _("Checking..."),
                'Error' => _("Spell Check Failed"),
                'ResumeEdit' => _("Resume Editing")
            ),
            'targetId' => 'composeMessage'
        ));

        $this->_spellInit = true;

        return true;
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
        global $injector, $page_output;

        $identities = array();
        $identity = $injector->getInstance('IMP_Identity');

        $sigs = $identity->hasSignature(true);

        foreach (array_keys(iterator_to_array($identity)) as $ident) {
            $sm = $identity->getValue(IMP_Mailbox::MBOX_SENT, $ident);

            $entry = array(
                // Sent mail mailbox name
                'sm_name' => $sm ? $sm->form_to : '',
                // Save in sent mail mailbox by default?
                'sm_save' => (bool)$identity->saveSentmail($ident),
                // Sent mail display name
                'sm_display' => $sm ? $sm->display_html : '',
                // Bcc addresses to add
                'bcc' => strval($identity->getBccAddresses($ident))
            );

            if ($sigs) {
                $sig = $identity->getSignature('text', $ident);
                $html_sig = $identity->getSignature('html', $ident);
                if (!strlen($html_sig) && strlen($sig)) {
                    $html_sig = IMP_Compose::text2html($sig);
                }
                $sig_dom = new Horde_Domhtml($html_sig, 'UTF-8');
                $html_sig = '';
                foreach ($sig_dom->getBody()->childNodes as $child) {
                    $html_sig .= $sig_dom->dom->saveXml($child);
                }

                $entry['sig'] = trim($sig);
                $entry['hsig'] = $html_sig;
            }

            $identities[] = $entry;
        }

        $page_output->addInlineJsVars(array(
            'ImpCompose.identities' => $identities
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

    /**
     * Return a list of valid encrypt HTML option tags.
     *
     * @param string $default      The default encrypt option.
     * @param boolean $returnList  Whether to return a hash with options
     *                             instead of the options tag.
     *
     * @return mixed  The list of option tags. This is empty if no encryption
     *                is available.
     */
    public function encryptList($default = null, $returnList = false)
    {
        global $conf, $injector, $prefs;

        if (is_null($default)) {
            $default = $prefs->getValue('default_encrypt');
        }

        $enc_opts = array();
        $output = '';

        if (!empty($conf['gnupg']['path']) && $prefs->getValue('use_pgp')) {
            $enc_opts += $injector->getInstance('IMP_Crypt_Pgp')->encryptList();
        }

        if ($prefs->getValue('use_smime')) {
            $enc_opts += $injector->getInstance('IMP_Crypt_Smime')->encryptList();
        }

        if (!empty($enc_opts)) {
            $enc_opts = array_merge(
                array(IMP::ENCRYPT_NONE => _("None")),
                $enc_opts
            );
        }

        if ($returnList) {
            return $enc_opts;
        }

        foreach ($enc_opts as $key => $val) {
             $output .= '<option value="' . $key . '"' . (($default == $key) ? ' selected="selected"' : '') . '>' . $val . "</option>\n";
        }

        return $output;
    }

}
