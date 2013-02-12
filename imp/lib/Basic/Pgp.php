<?php
/**
 * Copyright 2002-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2002-2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * PGP display.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2002-2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Basic_Pgp extends IMP_Basic_Base
{
    /**
     * @var IMP_Crypt_Pgp
     */
    protected $_pgp;

    /**
     */
    protected function _init()
    {
        global $browser, $injector;

        $this->_pgp = $injector->getInstance('IMP_Crypt_Pgp');

        /* Run through the action handlers */
        switch ($this->vars->actionID) {
        case 'import_public_key':
            $this->_importKeyDialog('process_import_public_key');
            break;

        case 'process_import_public_key':
            $import_keys = $this->_pgp->getKeys($this->vars->import_key);
            try {
                $browser->wasFileUploaded('upload_key', _("key"));
                $import_keys = array_merge_recursive($import_keys, $this->_pgp->getKeys(file_get_contents($_FILES['upload_key']['tmp_name'])));
            } catch (Horde_Browser_Exception $e) {}

            if (count($import_keys['public'])) {
                foreach ($import_keys['public'] as $val) {
                    $key_info = $this->_pgp->addPublicKey($val);
                    foreach ($key_info['signature'] as $sig) {
                        $notification->push(sprintf(_("PGP Public Key for \"%s (%s)\" was successfully added."), $sig['name'], $sig['email']), 'horde.success');
                    }
                }
                $this->_reloadWindow();
            }

            $notification->push(_("No PGP public key imported."), 'horde.error');
            $this->vars->actionID = 'import_public_key';
            $this->_importKeyDialog('process_import_public_key');
            break;

        case 'import_personal_key':
            $this->_importKeyDialog('process_import_personal_key');
            break;

        case 'process_import_personal_key':
            $import_key = $this->_pgp->getKeys($this->vars->import_key);

            if (empty($import_key['public']) || empty($import_key['private'])) {
                try {
                    $browser->wasFileUploaded('upload_key', _("key"));
                    $import_key = array_merge_recursive($import_key, $this->_pgp->getKeys(file_get_contents($_FILES['upload_key']['tmp_name'])));
                } catch (Horde_Browser_Exception $e) {
                    if ($e->getCode() != UPLOAD_ERR_NO_FILE) {
                        $notification->push($e, 'horde.error');
                    }
                }
            }

            if (!empty($import_key['public']) &&
                !empty($import_key['private'])) {
                $this->_pgp->addPersonalPublicKey($import_key['public'][0]);
                $this->_pgp->addPersonalPrivateKey($import_key['private'][0]);
                $notification->push(_("Personal PGP key successfully added."), 'horde.success');
                $this->_reloadWindow();
            }

            $notification->push(_("Personal PGP key not imported."), 'horde.error');

            $this->vars->actionID = 'import_personal_key';
            $this->_importKeyDialog('process_import_personal_key');
            break;

        case 'view_public_key':
        case 'info_public_key':
            try {
                $key = $this->_pgp->getPublicKey($this->vars->email, array('noserver' => true));
            } catch (Horde_Exception $e) {
                $key = $e->getMessage();
            }

            if ($this->vars->actionID == 'view_public_key') {
                $this->_textWindowOutput('PGP Public Key', $key);
            }
            $this->_printKeyInfo($key);
            break;

        case 'view_personal_public_key':
            $this->_textWindowOutput('PGP Personal Public Key', $this->_pgp->getPersonalPublicKey());
            break;

        case 'info_personal_public_key':
            $this->_printKeyInfo($this->_pgp->getPersonalPublicKey());
            break;

        case 'view_personal_private_key':
            $this->_textWindowOutput('PGP Personal Private Key', $this->_pgp->getPersonalPrivateKey());
            break;

        case 'info_personal_private_key':
            $this->_printKeyInfo($this->_pgp->getPersonalPrivateKey());
            break;
        }
    }

    /**
     */
    static public function url(array $opts = array())
    {
        return Horde::url('basic.php')->add('page', 'pgp');
    }

    /**
     * Generate import key dialog.
     *
     * @param string $target  Action ID for the UI screen.
     */
    protected function _importKeyDialog($target)
    {
        global $notification, $page_output, $registry;

        $page_output->topbar = $page_output->sidebar = false;
        $page_output->addInlineScript(array(
            '$$("INPUT.horde-cancel").first().observe("click", function() { window.close(); })'
        ), true);

        $this->title = _("Import PGP Key");

        /* Need to use regular status notification - AJAX notifications won't
         * show in popup windows. */
        if ($registry->getView() == Horde_Registry::VIEW_DYNAMIC) {
            $notification->detach('status');
            $notification->attach('status');
        }

        $view = new Horde_View(array(
            'templatePath' => IMP_TEMPLATES . '/pgp'
        ));
        $view->addHelper('Text');

        $view->reload = $this->vars->reload;
        $view->selfurl = self::url();
        $view->target = $target;

        $this->output = $view->render('import_key');
    }

    /**
     * Reload the window.
     */
    protected function _reloadWindow()
    {
        global $session;

        $href = $session->retrieve($this->vars->reload);
        $session->purge($this->vars->reload);

        echo Horde::wrapInlineScript(array(
            'opener.focus();'.
            'opener.location.href="' . $href . '";',
            'window.close();'
        ));
        exit;
    }

    /**
     * Print PGP Key information.
     *
     * @param string $key  The PGP key.
     */
    protected function _printKeyInfo($key = '')
    {
        try {
            $key_info = $this->_pgp->pgpPrettyKey($key);
        } catch (Horde_Crypt_Exception $e) {
            Horde::log($e, 'INFO');
            $key_info = $e->getMessage();
        }

        $this->_textWindowOutput('PGP Key Information', $key_info);
    }

    /**
     * Output text in a window.
     *
     * @param string $name  The window name.
     * @param string $msg   The text contents.
     */
    protected function _textWindowOutput($name, $msg)
    {
        $GLOBALS['browser']->downloadHeaders($name, 'text/plain; charset=' . 'UTF-8', true, strlen($msg));
        echo $msg;
        exit;
    }

}
