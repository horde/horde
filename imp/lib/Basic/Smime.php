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
 * S/MIME display.
 *
 * @author    Mike Cochrane <mike@graftonhall.co.nz>
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2002-2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Basic_Smime extends IMP_Basic_Base
{
    /**
     * @var IMP_Crypt_Smime
     */
    protected $_smime;

    /**
     */
    protected function _init()
    {
        global $injector;

        $this->_smime = $injector->getInstance('IMP_Crypt_Smime');

        /* Run through the action handlers */
        switch ($this->vars->actionID) {
        case 'import_public_key':
            $this->_smime->importKeyDialog('process_import_public_key');
            break;

        case 'process_import_public_key':
            try {
                $publicKey = $this->_getImportKey($this->vars->import_key);

                /* Add the public key to the storage system. */
                $this->_smime->addPublicKey($publicKey);
                $notification->push(_("S/MIME public key successfully added."), 'horde.success');
                $this->_reloadWindow();
            } catch (Horde_Browser_Exception $e) {
                $notification->push(_("No S/MIME public key imported."), 'horde.error');
            } catch (Horde_Exception $e) {
                $notification->push($e);
            }

            $this->vars->actionID = 'import_public_key';
            $this->_importKeyDialog('process_import_public_key');
            break;

        case 'view_public_key':
        case 'info_public_key':
            try {
                $key = $this->_smime->getPublicKey($this->vars->email);
            } catch (Horde_Exception $e) {
                $key = $e->getMessage();
            }
            if ($this->vars->actionID == 'view_public_key') {
                $this->_textWindowOutput('S/MIME Public Key', $key);
            }
            $this->_printCertInfo($key);
            break;

        case 'view_personal_public_key':
            $this->_textWindowOutput('S/MIME Personal Public Key', $this->_smime->getPersonalPublicKey());
            break;

        case 'info_personal_public_key':
            $this->_printCertInfo($this->_smime->getPersonalPublicKey());
            break;

        case 'view_personal_private_key':
            $this->_textWindowOutput('S/MIME Personal Private Key', $this->_smime->getPersonalPrivateKey());
            break;

        case 'import_personal_certs':
            $this->_importKeyDialog('process_import_personal_certs');
            break;

        case 'process_import_personal_certs':
            try {
                $pkcs12 = $this->_smime->_getImportKey($this->vars->import_key);
                $this->_smime->addFromPKCS12($pkcs12, $this->vars->upload_key_pass, $this->vars->upload_key_pk_pass);
                $notification->push(_("S/MIME Public/Private Keypair successfully added."), 'horde.success');
                $this->_reloadWindow();
            } catch (Horde_Browser_Exception $e) {
                $notification->push(_("Personal S/MIME certificates NOT imported."), 'horde.error');
            } catch (Horde_Exception $e) {
                $notification->push(_("Personal S/MIME certificates NOT imported: ") . $e->getMessage(), 'horde.error');
            }

            $this->vars->actionID = 'import_personal_certs';
            $this->_importKeyDialog('process_import_personal_certs');
            break;
        }
    }

    /**
     */
    static public function url(array $opts = array())
    {
        return Horde::url('basic.php')->add('page', 'smime');
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

        $this->title = _("Import Personal S/MIME Certificate");

        /* Need to use regular status notification - AJAX notifications won't
         * show in popup windows. */
        if ($registry->getView() == Horde_Registry::VIEW_DYNAMIC) {
            $notification->detach('status');
            $notification->attach('status');
        }

        $view = new Horde_View(array(
            'templatePath' => IMP_TEMPLATES . '/smime'
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
     * Output text in a window.
     *
     * @param string $name   The window name.
     * @param string $msg    The text contents.
     * @param boolean $html  $msg is HTML format?
     */
    protected function _textWindowOutput($name, $msg, $html = false)
    {
        $GLOBALS['browser']->downloadHeaders($name, 'text/' . ($html ? 'html' : 'plain') . '; charset=' . 'UTF-8', true, strlen($msg));
        echo $msg;
        exit;
    }

    /**
     * Print certificate information.
     *
     * @param string $cert  The S/MIME certificate.
     */
    protected function _printCertInfo($cert = '')
    {
        $cert_info = $this->_smime->certToHTML($cert);

        $this->_textWindowOutput(
            _("S/MIME Key Information"),
            empty($cert_info) ? _("Invalid key") : $cert_info,
            !empty($cert_info)
        );
    }

    /**
     * Attempt to import a key from form/uploaded data.
     *
     * @param string $key  Key string.
     *
     * @return string  The key contents.
     * @throws Horde_Browser_Exception
     */
    protected function _getImportKey($key)
    {
        if (!empty($key)) {
            return $key;
        }

        $GLOBALS['browser']->wasFileUploaded('upload_key', _("key"));
        return file_get_contents($_FILES['upload_key']['tmp_name']);
    }

}
