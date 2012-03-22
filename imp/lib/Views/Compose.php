<?php
/**
 * DIMP compose view logic.
 *
 * Copyright 2005-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */
class IMP_Views_Compose
{
    /**
     * Create content needed to output the compose screen.
     *
     * @param array $args  Configuration parameters:
     *   - composeCache: (string) The cache ID of the IMP_Compose object.
     *   - fwdattach: (boolean) Are we forwarding and attaching the original
     *     message?
     *   - qreply: (boolean) Is this a quickreply view?
     *   - redirect: (string) Display the redirect interface?
     *   - show_editor: (boolean) Show the HTML editor?
     *   - template: (string) Display the edit template interface?
     *
     * @return array  Array with the following keys:
     *   - html: (string) The rendered HTML content.
     *   - js: (array) Javascript code to run immediately.
     *   - jsonload: (array) Javascript code to run on load.
     */
    static public function showCompose($args)
    {
        global $conf, $injector, $prefs, $registry, $session;

        $result = array(
            'html' => '',
            'js' => array(),
            'jsonload' => array()
        );

        $t = $injector->createInstance('Horde_Template');
        $t->setOption('gettext', true);

        if (!empty($args['composeCache'])) {
            $t->set('composeCache', $args['composeCache']);
        }

        if (empty($args['redirect'])) {
            /* Load Identity. */
            $identity = $injector->getInstance('IMP_Identity');
            $t->set('selected_identity', intval($identity->getDefault()));

            /* Generate identities list. */
            $result['js'] = array_merge($result['js'], $injector->getInstance('IMP_Ui_Compose')->identityJs());

            $imp_compose = $injector->getInstance('IMP_Factory_Compose')->create(isset($args['composeCache']) ? $args['composeCache'] : null);

            if ($t->get('composeCache') && count($imp_compose)) {
                foreach ($imp_compose as $num => $atc) {
                    $mime = $atc['part'];
                    $opts = array(
                        'name' => $mime->getName(true),
                        'num' => intval($num),
                        'size' => $mime->getSize(),
                        'type' => $mime->getType()
                    );
                    if (!empty($args['fwdattach'])) {
                        $opts['fwdattach'] = 1;
                    }
                    $result['jsonload'][] = 'DimpCompose.addAttach(' . Horde_Serialize::serialize($opts, Horde_Serialize::JSON, 'UTF-8') . ')';
                }
            }

            if (!empty($args['qreply'])) {
                $result['js'][] = 'DIMP.conf.qreply = 1';
            }

            if ($session->get('imp', 'rteavail')) {
                $t->set('compose_html', !empty($args['show_editor']));
                $t->set('rte', true);

                IMP_Ui_Editor::init(!$t->get('compose_html'));
            }

            /* Create list for sent-mail selection. */
            if ($injector->getInstance('IMP_Factory_Imap')->create()->access(IMP_Imap::ACCESS_FOLDERS)) {
                $t->set('save_sent_mail', !$prefs->isLocked('save_sent_mail'));

                if (!empty($conf['user']['select_sentmail_folder']) &&
                    !$prefs->isLocked('sent_mail_folder')) {
                    /* Check to make sure the sent-mail mailboxes are created;
                     * they need to exist to show up in drop-down list. */
                    foreach (array_keys($identity->getAll('id')) as $ident) {
                        $mbox = $identity->getValue('sent_mail_folder', $ident);
                        if ($mbox instanceof IMP_Mailbox) {
                            $mbox->create();
                        }
                    }

                    $flist = array();
                    $imaptree = $injector->getInstance('IMP_Imap_Tree');

                    foreach ($imaptree as $val) {
                        $tmp = array(
                            'f' => $val->display,
                            'l' => Horde_String::abbreviate(str_repeat(' ', 2 * $val->level) . $val->basename, 30),
                            'v' => $val->container ? '' : $val->form_to
                        );
                        if ($tmp['f'] == $tmp['v']) {
                            unset($tmp['f']);
                        }
                        $flist[] = $tmp;
                    }
                    $result['js'] = array_merge($result['js'], $injector->getInstance('Horde_PageOutput')->addInlineJsVars(array(
                        'DIMP.conf.flist' => $flist
                    ), array('ret_vars' => true)));
                }
            }

            $compose_link = Horde::getServiceLink('ajax', 'imp');
            $compose_link->pathInfo = 'addAttachment';
            $t->set('compose_link', $compose_link);

            $t->set('spell_button', IMP_Dimp::actionButton(array(
                'id' => 'spellcheck',
                'title' => _("Check Spelling")
            )));

            if (empty($args['template'])) {
                $t->set('send_button', IMP_Dimp::actionButton(array(
                    'icon' => 'Forward',
                    'id' => 'send_button',
                    'title' => _("Send")
                )));
                $t->set('draft_button', IMP_Dimp::actionButton(array(
                    'icon' => 'Drafts',
                    'id' => 'draft_button',
                    'title' => _("Save as Draft")
                )));
            } else {
                $t->set('template_button', IMP_Dimp::actionButton(array(
                    'icon' => 'Templates',
                    'id' => 'template_button',
                    'title' => _("Save Template")
                )));
            }

            $d_read = $prefs->getValue('request_mdn');
            if ($d_read != 'never') {
                $t->set('read_receipt_set', ($d_read != 'ask'));
            }

            $t->set('priority', $prefs->getValue('set_priority'));
            if (!$prefs->isLocked('default_encrypt') &&
                ($prefs->getValue('use_pgp') || $prefs->getValue('use_smime'))) {
                $t->set('encrypt', $prefs->getValue('default_encrypt'));
            }

            $select_list = array();
            foreach ($identity->getSelectList() as $id => $from) {
                $select_list[] = array(
                    'label' => htmlspecialchars($from),
                    'sel' => ($id == $t->get('selected_identity')),
                    'val' => htmlspecialchars($id)
                );
            }
            $t->set('select_list', $select_list);

            $save_attach = $prefs->getValue('save_attachments');
            if (strpos($save_attach, 'prompt') !== false) {
                $t->set('save_attach_set', strpos($save_attach, 'yes') !== false);
            }
        } else {
            $result['js'] = array_merge($result['js'], $injector->getInstance('Horde_PageOutput')->addInlineJsVars(array(
                '-DIMP.conf.redirect' => 1
            ), array('ret_vars' => true)));
        }

        $t->set('bcc', $prefs->getValue('compose_bcc'));
        $t->set('cc', $prefs->getValue('compose_cc'));
        $t->set('bcc_or_cc', $t->get('bcc') || $t->get('cc'));
        $t->set('compose_enable', IMP::canCompose());
        $t->set('forminput', Horde_Util::formInput());
        $t->set('redirect_button', IMP_Dimp::actionButton(array(
            'icon' => 'Forward',
            'id' => 'send_button_redirect',
            'title' => _("Redirect")
        )));

        $result['html'] = $t->fetch(IMP_TEMPLATES . '/dimp/compose/compose.html');

        return $result;
    }

}
