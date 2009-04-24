<?php
/**
 * DIMP compose view logic.
 *
 * Copyright 2005-2009 The Horde Project (http://www.horde.org/)
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
        require_once 'Horde/Identity.php';
        $identity = &Identity::singleton(array('imp', 'imp'));
        $selected_identity = $identity->getDefault();
        $sent_mail_folder = $identity->getValue('sent_mail_folder');
        if (!empty($sent_mail_folder)) {
            $sent_mail_folder = htmlspecialchars('"' . IMP::displayFolder($sent_mail_folder) . '"');
        }

        /* Get user identities. */
        require_once 'Horde/Text/Filter.php';
        $all_sigs = $identity->getAllSignatures();
        foreach ($all_sigs as $ident => $sig) {
            $identities[] = array(
                // 0 = Plain text signature
                $sig,
                // 1 = HTML signature
                str_replace(' target="_blank"', '', Text_Filter::filter($sig, 'text2html', array('parselevel' => TEXT_HTML_MICRO_LINKURL, 'class' => null, 'callback' => null))),
                // 2 = Signature location
                (bool)$identity->getValue('sig_first', $ident),
                // 3 = Sent mail folder name
                $identity->getValue('sent_mail_folder', $ident),
                // 4 = Save in sent mail folder by default?
                (bool)$identity->saveSentmail($ident),
                // 5 = Sent mail display name
                IMP::displayFolder($identity->getValue('sent_mail_folder', $ident)),
                // 6 = Bcc addresses to add
                Horde_Mime_Address::addrArray2String($identity->getBccAddresses($ident))
            );
        }

        $draft_index = $composeCache = null;
        if (!empty($args['composeCache'])) {
            $imp_compose = &IMP_Compose::singleton($args['composeCache']);
            $draft_index = intval($imp_compose->saveDraftIndex());
            $composeCache = $args['composeCache'];

            if ($imp_compose->numberOfAttachments()) {
                foreach ($imp_compose->getAttachments() as $num => $atc) {
                    $mime = $atc['part'];
                    $result['js_onload'][] = 'DimpCompose.addAttach(' . $num . ', \'' . addslashes($mime->getName(true)) . '\', \'' . addslashes($mime->getType()) . '\', \'' . addslashes($mime->getSize()) . "')";
                }
            }
        }

        $result['js'] = array(
            'DIMP.conf_compose.auto_save_interval_val = ' . intval($GLOBALS['prefs']->getValue('auto_save_drafts')),
            'DIMP.conf_compose.identities = ' . Horde_Serialize::serialize($identities, Horde_Serialize::JSON)
        );

        if (!empty($args['qreply'])) {
            $result['js'][] = 'DIMP.conf_compose.qreply = 1';
        }

        $compose_html = $rte = false;
        if ($_SESSION['imp']['rteavail']) {
            $compose_html = $GLOBALS['prefs']->getValue('compose_html');
            $rte = true;

            $imp_ui = new IMP_UI_Compose();
            $result['jsappend'] .= $imp_ui->initRTE('dimp');
        }

        // Buffer output so that we can return a string from this function
        ob_start();
        require IMP_TEMPLATES . '/chunks/compose.php';
        $result['html'] .= ob_get_contents();
        ob_clean();

        return $result;
    }

}
