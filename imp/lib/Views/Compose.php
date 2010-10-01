<?php
/**
 * DIMP compose view logic.
 *
 * Copyright 2005-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@curecanti.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
 */
class IMP_Views_Compose
{
    /**
     * Create content needed to output the compose screen.
     *
     * @param array $args  Configuration parameters:
     * <pre>
     * 'composeCache' - (string) The cache ID of the IMP_Compose object.
     * 'redirect' - (string) Display the redirect interface?
     * 'qreply' - (boolean) Is this a quickreply view?
     * </pre>
     *
     * @return array  Array with the following keys:
     * <pre>
     * 'html' - (string) The rendered HTML content.
     * 'js' - (array) Javascript code to run immediately.
     * 'jsonload' - (array) Javascript code to run on load.
     * </pre>
     */
    static public function showCompose($args)
    {
        global $conf, $injector, $prefs, $registry;

        $result = array(
            'html' => '',
            'js' => array(),
            'jsonload' => array()
        );

        $t = $injector->createInstance('Horde_Template');
        $t->setOption('gettext', true);

        if (!empty($args['composeCache'])) {
            $imp_compose = $injector->getInstance('IMP_Compose')->getOb($args['composeCache']);
            $t->set('composeCache', $args['composeCache']);
        }

        if (empty($args['redirect'])) {
            /* Load Identity. */
            $identity = $injector->getInstance('IMP_Identity');
            $t->set('selected_identity', intval($identity->getDefault()));

            /* Generate identities list. */
            $imp_ui = $injector->getInstance('IMP_Ui_Compose');
            $result['js'] = array_merge($result['js'], $imp_ui->identityJs());

            if ($t->get('composeCache') && count($imp_compose)) {
                foreach ($imp_compose as $num => $atc) {
                    $mime = $atc['part'];
                    $opts = Horde_Serialize::serialize(array(
                        'name' => $mime->getName(true),
                        'num' => intval($num),
                        'size' => $mime->getSize(),
                        'type' => $mime->getType()
                    ), Horde_Serialize::JSON, $registry->getCharset());
                    $result['jsonload'][] = 'DimpCompose.addAttach(' . $opts . ')';
                }
            }

            if (!empty($args['qreply'])) {
                $result['js'][] = 'DIMP.conf_compose.qreply = 1';
            }

            if ($_SESSION['imp']['rteavail']) {
                $t->set('compose_html', $prefs->getValue('compose_html'));
                $t->set('rte', true);

                $imp_ui->initRTE(!$t->get('compose_html'));
            }

            /* Create list for sent-mail selection. */
            if (!empty($conf['user']['select_sentmail_folder']) &&
                !$prefs->isLocked('sent_mail_folder')) {
                $imp_folder = $injector->getInstance('IMP_Folder');

                /* Check to make sure the sent-mail folders are created - they
                 * need to exist to show up in drop-down list. */
                foreach (array_keys($identity->getAll('id')) as $ident) {
                    $val = $identity->getValue('sent_mail_folder', $ident);
                    if (!$imp_folder->exists($val)) {
                        $imp_folder->create($val, true);
                    }
                }

                $flist = array();
                $imaptree = $injector->getInstance('IMP_Imap_Tree');

                foreach ($imaptree as $val) {
                    $tmp = array(
                        'f' => $val->display,
                        'l' => Horde_String::abbreviate(str_repeat(' ', 2 * $val->level) . $val->label, 30),
                        'v' => $val->container ? '' : $val->value
                    );
                    if ($tmp['f'] == $tmp['v']) {
                        unset($tmp['f']);
                    }
                    $flist[] = $tmp;
                }
                $result['js'] = array_merge($result['js'], Horde::addInlineJsVars(array(
                    'DIMP.conf_compose.flist' => $flist
                ), true));
            }

            $compose_link = Horde::getServiceLink('ajax', 'imp');
            $compose_link->pathInfo = 'addAttachment';
            $t->set('compose_link', $compose_link);

            $t->set('send_button', IMP_Dimp::actionButton(array(
                'icon' => 'Forward',
                'id' => 'send_button',
                'title' => _("Send")
            )));
            $t->set('spell_button', IMP_Dimp::actionButton(array(
                'icon' => 'Spellcheck',
                'id' => 'spellcheck',
                'title' => _("Check Spelling")
            )));
            $t->set('draft_button', IMP_Dimp::actionButton(array(
                'icon' => 'Drafts',
                'id' => 'draft_button',
                'title' => _("Save as Draft")
            )));

            $d_read = $prefs->getValue('disposition_request_read');
            if ($conf['compose']['allow_receipts'] &&
                ($d_read != 'never')) {
                $t->set('read_receipt', true);
                $t->set('read_receipt_set', $d_read != 'ask');
            }

            $t->set('save_sent_mail', ($conf['user']['allow_folders'] && !$prefs->isLocked('save_sent_mail')));
            $t->set('priority', $prefs->getValue('set_priority'));
            if (!$prefs->isLocked('default_encrypt') &&
                ($prefs->getValue('use_pgp') || $prefs->getValue('use_smime'))) {
                $t->set('encrypt', IMP::ENCRYPT_NONE);
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
                $t->set('save_attach', true);
                $t->set('save_attach_set', strpos($save_attach, 'yes') !== false);
            }
        } else {
            $result['js'] = array_merge($result['js'], Horde::addInlineJsVars(array(
                '-DIMP.conf_compose.redirect' => 1
            ), true));
        }

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
