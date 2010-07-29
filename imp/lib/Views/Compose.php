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
        $result = array(
            'html' => '',
            'js' => array(),
            'jsonload' => array()
        );

        $compose_html = $redirect = $rte = false;

        if (empty($args['composeCache'])) {
            $composeCache = null;
        } else {
            $imp_compose = $GLOBALS['injector']->getInstance('IMP_Compose')->getOb($args['composeCache']);
            $composeCache = $args['composeCache'];
        }

        if (empty($args['redirect'])) {
            /* Load Identity. */
            $identity = $GLOBALS['injector']->getInstance('IMP_Identity');
            $selected_identity = $identity->getDefault();

            /* Generate identities list. */
            $imp_ui = $GLOBALS['injector']->getInstance('IMP_Ui_Compose');
            $result['js'][] = $imp_ui->identityJs();

            if ($composeCache &&
                $imp_compose->numberOfAttachments()) {
                foreach ($imp_compose->getAttachments() as $num => $atc) {
                    $mime = $atc['part'];
                    $opts = Horde_Serialize::serialize(array(
                        'name' => $mime->getName(true),
                        'num' => intval($num),
                        'size' => $mime->getSize(),
                        'type' => $mime->getType()
                    ), Horde_Serialize::JSON, $GLOBALS['registry']->getCharset());
                    $result['jsonload'][] = 'DimpCompose.addAttach(' . $opts . ')';
                }
            }

            if (!empty($args['qreply'])) {
                $result['js'][] = 'DIMP.conf_compose.qreply = 1';
            }

            if ($_SESSION['imp']['rteavail']) {
                $compose_html = $GLOBALS['prefs']->getValue('compose_html');
                $rte = true;

                $imp_ui->initRTE(!$compose_html);
            }

            /* Create list for sent-mail selection. */
            if (!empty($GLOBALS['conf']['user']['select_sentmail_folder']) &&
                !$GLOBALS['prefs']->isLocked('sent_mail_folder')) {
                $imp_folder = $GLOBALS['injector']->getInstance('IMP_Folder');

                /* Check to make sure the sent-mail folders are created - they
                 * need to exist to show up in drop-down list. */
                foreach (array_keys($identity->getAll('id')) as $ident) {
                    $val = $identity->getValue('sent_mail_folder', $ident);
                    if (!$imp_folder->exists($val)) {
                        $imp_folder->create($val, true);
                    }
                }

                $flist = array();
                foreach ($imp_folder->flist() as $val) {
                    $tmp = array('l' => $val['abbrev'], 'v' => $val['val']);
                    $tmp2 = IMP::displayFolder($val['val']);
                    if ($val['val'] != $tmp2) {
                        $tmp['f'] = $tmp2;
                    }
                    $flist[] = $tmp;
                }
                $result['js'][] = 'DIMP.conf_compose.flist = ' . Horde_Serialize::serialize($flist, Horde_Serialize::JSON);
            }
        } else {
            $result['js'][] = 'DIMP.conf_compose.redirect = 1';
            $redirect = true;
        }

        // Buffer output so that we can return a string from this function
        Horde::startBuffer();
        require IMP_TEMPLATES . '/dimp/chunks/compose.php';
        $result['html'] .= Horde::endBuffer();

        return $result;
    }

}
