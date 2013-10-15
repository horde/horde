<?php
/**
 * Copyright 2005-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2005-2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Compose code common to various dynamic views.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2005-2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Dynamic_Compose_Common
{
    /**
     * Create content needed to output the compose page.
     *
     * @param IMP_Dynamic_Base $base  Base dynamic view object.
     * @param array $args             Configuration parameters:
     *   - redirect: (string) Display the redirect interface?
     *   - resume: (boolean) Are we resuming a saved draft?
     *   - show_editor: (boolean) Show the HTML editor?
     *   - template: (string) Display the edit template interface?
     *
     * @return string  The compose HTML text.
     */
    public function compose(IMP_Dynamic_Base $base, array $args = array())
    {
        global $injector, $page_output, $prefs;

        $page_output->addScriptPackage('IMP_Script_Package_ComposeBase');
        $page_output->addScriptFile('compose-dimp.js');
        $page_output->addScriptFile('draghandler.js');
        $page_output->addScriptFile('external/murmurhash3.js');

        if (!$prefs->isLocked('default_encrypt') &&
            ($prefs->getValue('use_pgp') || $prefs->getValue('use_smime'))) {
            $page_output->addScriptPackage('Dialog');
            $page_output->addScriptFile('passphrase.js');
        }

        $this->_addComposeVars($base);

        $view = $base->getEmptyView();
        $view->addHelper('Tag');
        $view->addHelper('FormTag');

        $view->compose_enable = IMP_Compose::canCompose();

        /* Attach spellchecker & auto completer. */
        $imp_ui = $injector->getInstance('IMP_Compose_Ui');

        if (!empty($args['redirect'])) {
            $base->js_conf['redirect'] = 1;
            $imp_ui->attachAutoCompleter(array('redirect_to'));
            return $view->render('redirect');
        }

        $imp_ui->attachAutoCompleter(array('to', 'cc', 'bcc', 'redirect_to'));
        $view->spellcheck = $imp_ui->attachSpellChecker();

        $this->_compose($base, $view, $args);
        return $view->render('compose') . $view->render('redirect');
    }

    /**
     */
    protected function _compose($base, $view, $args)
    {
        global $injector, $registry, $prefs, $session;

        $view->title = $args['title'];

        /* Load Identity. */
        $identity = $injector->getInstance('IMP_Identity');
        $selected_identity = intval($identity->getDefault());

        /* Generate identities list. */
        $injector->getInstance('IMP_Compose_Ui')->addIdentityJs();

        if ($session->get('imp', 'rteavail')) {
            $view->compose_html = !empty($args['show_editor']);
            $view->rte = true;

            $injector->getInstance('IMP_Editor')->init();
        }

        /* Create list for sent-mail selection. */
        $imp_imap = $injector->getInstance('IMP_Factory_Imap')->create();
        if ($imp_imap->access(IMP_Imap::ACCESS_FOLDERS) &&
            !$prefs->isLocked('save_sent_mail')) {
            $view->save_sent_mail = true;
            $view->save_sent_mail_select = !$prefs->isLocked(IMP_Mailbox::MBOX_SENT);
        }

        $view->drafts = ($imp_imap->access(IMP_Imap::ACCESS_DRAFTS) &&
            ($draft = IMP_Mailbox::getPref(IMP_Mailbox::MBOX_DRAFTS)) &&
            !$draft->readonly);

        $view->compose_link = $registry->getServiceLink('ajax', 'imp')->url . 'addAttachment';
        $view->resume = !empty($args['resume']);
        $view->is_template = !empty($args['template']);
        $view->read_receipt_set = (strcasecmp($prefs->getValue('request_mdn'), 'always') === 0);
        $view->user = $registry->getAuth();

        if (IMP_Compose::canUploadAttachment()) {
            $view->attach = true;
            $view->max_size = $session->get('imp', 'file_upload');
            $view->save_attach_set = (strcasecmp($prefs->getValue('save_attachments'), 'always') === 0);
        } else {
            $view->attach = false;
        }

        if ($prefs->getValue('use_pgp') &&
            $prefs->getValue('pgp_public_key')) {
            $view->pgp_pubkey = $prefs->getValue('pgp_attach_pubkey');
        }

        if ($registry->hasMethod('contacts/ownVCard')) {
            $view->vcard_attach = true;
        }

        $view->priority = $prefs->getValue('set_priority');
        if (!$prefs->isLocked('default_encrypt') &&
            ($prefs->getValue('use_pgp') || $prefs->getValue('use_smime'))) {
            $view->encrypt = $prefs->getValue('default_encrypt');
        }

        $select_list = array();
        foreach ($identity->getSelectList() as $id => $from) {
            $select_list[] = array(
                'label' => $from,
                'sel' => ($id == $selected_identity),
                'val' => $id
            );
        }
        $view->select_list = $select_list;
    }

    /**
     * Add compose javascript variables to the page.
     */
    protected function _addComposeVars($base)
    {
        global $browser, $injector, $prefs, $registry;

        /* Context menu definitions. */
        $base->js_context['ctx_other'] = new stdClass;
        if (!$prefs->isLocked('request_mdn')) {
            $base->js_context['ctx_other']->rr = _("Read Receipt");
        }

        $base->js_context['ctx_atc'] = new stdClass;

        if (IMP_Compose::canUploadAttachment()) {
            if (!$prefs->isLocked('save_attachments') &&
                (!$prefs->isLocked('save_sent_mail') ||
                 $prefs->getValue('save_sent_mail'))) {
                $base->js_context['ctx_atc']->save = _("Save Attachments in Sent Mailbox");
            }

            $atcfile = new stdClass;
            $atcfile->delete = _("Delete");
            $base->js_context['ctx_atcfile'] = $atcfile;
        }

        if ($prefs->getValue('use_pgp') &&
            $prefs->getValue('pgp_public_key')) {
            $base->js_context['ctx_atc']->pgppubkey = _("Attach Personal PGP Public Key");
        }

        if ($registry->hasMethod('contacts/ownVCard')) {
            $base->js_context['ctx_atc']->vcard = _("Attach contact information");
        }

        /* Variables used in compose page. */
        $compose_cursor = $prefs->getValue('compose_cursor');
        $templates_mbox = IMP_Mailbox::getPref(IMP_Mailbox::MBOX_TEMPLATES);

        $base->js_conf += array_filter(array(
            'URI_MAILBOX' => strval(IMP_Dynamic_Mailbox::url()),

            'compose_cursor' => ($compose_cursor ? $compose_cursor : 'top'),
            'rte_avail' => intval($browser->hasFeature('rte')),
            'spellcheck' => intval($prefs->getValue('compose_spellcheck')),
            'templates_mbox' => $templates_mbox ? $templates_mbox->form_to : null
        ));

        if ($injector->getInstance('IMP_Factory_Imap')->create()->access(IMP_Imap::ACCESS_DRAFTS) &&
            ($drafts_mbox = IMP_Mailbox::getPref(IMP_Mailbox::MBOX_DRAFTS)) &&
            !$drafts_mbox->readonly) {
            $base->js_conf += array_filter(array(
                'auto_save_interval_val' => intval($prefs->getValue('auto_save_drafts')),
                'close_draft' => intval($prefs->getValue('close_draft')),
                'drafts_mbox' => $drafts_mbox->form_to
            ));
        }

        if ($registry->hasMethod('contacts/search')) {
            $base->js_conf['URI_ABOOK'] = strval(IMP_Basic_Contacts::url(array('full' => true)));
        }

        if ($prefs->getValue('set_priority')) {
            $base->js_conf['priority'] = array(
                array(
                    'l' => _("High"),
                    'v' => 'high'
                ),
                array(
                    'l' => _("Normal"),
                    's' => true,
                    'v' => 'normal'
                ),
                array(
                    'l' => _("Low"),
                    'v' => 'low'
                )
            );
        }

        if (!$prefs->isLocked('default_encrypt')) {
            $encrypt = array();
            foreach ($injector->getInstance('IMP_Compose_Ui')->encryptList(null, true) as $key => $val) {
                $encrypt[] = array(
                    'l' => htmlspecialchars($val),
                    'v' => $key
                );
            }

            if (!empty($encrypt)) {
                $base->js_conf['encrypt'] = $encrypt;
            }
        }

        /* Gettext strings used in compose page. */
        $base->js_text += array(
            'compose_cancel' => _("Cancelling this message will permanently discard its contents and will delete auto-saved drafts.\nAre you sure you want to do this?"),
            'nosubject' => _("The message does not have a subject entered.") . "\n" . _("Send message without a subject?"),
            'replyall' => _("%d recipients"),
            'spell_noerror' => _("No spelling errors found."),
            'toggle_html' => _("Discard all text formatting information (by converting from HTML to plain text)? This conversion cannot be reversed."),
            'uploading' => _("Uploading..."),
        );
    }

}
