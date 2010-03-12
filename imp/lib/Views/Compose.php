<?php
/**
 * DIMP compose view logic.
 *
 * Copyright 2005-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @package IMP
 */
class IMP_Views_Compose
{
    /**
     * Create content needed to output the compose screen.
     *
     * @param array $args  Configuration parameters:
     * <pre>
     * 'composeCache' - The cache ID of the IMP_Compose object.
     * 'qreply' - Is this a quickreply view?
     * </pre>
     *
     * @return array  Array with the following keys:
     * <pre>
     * 'html' - The rendered HTML content.
     * 'js' - An array of javascript code to run immediately.
     * 'jsappend' - Javascript code to append at bottom of page.
     * 'jsonload' - An array of javascript code to run on load.
     * </pre>
     */
    static public function showCompose($args)
    {
        $result = array(
            'html' => '',
            'jsappend' => '',
            'jsonload' => array()
        );

        /* Load Identity. */
        $identity = Horde_Prefs_Identity::singleton(array('imp', 'imp'));
        $selected_identity = $identity->getDefault();

        /* Generate identities list. */
        $imp_ui = new IMP_Ui_Compose();
        $result['js'] = array($imp_ui->identityJs());

        $composeCache = null;
        if (!empty($args['composeCache'])) {
            $imp_compose = IMP_Compose::singleton($args['composeCache']);
            $composeCache = $args['composeCache'];

            if ($imp_compose->numberOfAttachments()) {
                foreach ($imp_compose->getAttachments() as $num => $atc) {
                    $mime = $atc['part'];
                    $result['jsonload'][] = 'DimpCompose.addAttach(' . $num . ', \'' . addslashes($mime->getName(true)) . '\', \'' . addslashes($mime->getType()) . '\', \'' . addslashes($mime->getSize()) . "')";
                }
            }
        }

        if (!empty($args['qreply'])) {
            $result['js'][] = 'DIMP.conf_compose.qreply = 1';
        }

        $compose_html = $rte = false;
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
            foreach (array_keys($identity->getAllSignatures()) as $ident) {
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

        // Buffer output so that we can return a string from this function
        ob_start();
        require IMP_TEMPLATES . '/chunks/compose.php';
        $result['html'] .= ob_get_contents();
        ob_clean();

        return $result;
    }

}
