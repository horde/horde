<?php
/**
 * Copyright 2002-2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2002-2016 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * S/MIME display.
 *
 * @author    Mike Cochrane <mike@graftonhall.co.nz>
 * @author    Michael Slusarz <slusarz@horde.org>
 * @author    Jan Schneider <jan@horde.org>
 * @category  Horde
 * @copyright 2002-2016 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Basic_Smime extends IMP_Basic_Base
{
    /**
     * @var IMP_Smime
     */
    protected $_smime;

    /**
     */
    protected function _init()
    {
        global $injector, $notification;

        $this->_smime = $injector->getInstance('IMP_Smime');

        /* Run through the action handlers */
        switch ($this->vars->actionID) {
        case 'import_public_key':
            $this->_importKeyDialog('public');
            break;

        case 'process_import_public_key':
            try {
                $publicKey = $this->_getImportKey('upload_key', $this->vars->import_key);

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
            $this->_importKeyDialog('public');
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
        case 'view_personal_public_sign_key':
            $this->_textWindowOutput(
                'S/MIME Personal Public Key',
                $this->_smime->getPersonalPublicKey(
                    $this->vars->actionID == 'view_personal_public_sign_key'
                )
            );
            break;

        case 'info_personal_public_key':
        case 'info_personal_public_sign_key':
            $this->_printCertInfo(
                $this->_smime->getPersonalPublicKey(
                    $this->vars->actionID == 'info_personal_public_sign_key'
                )
            );
            break;

        case 'view_personal_private_key':
        case 'view_personal_private_sign_key':
            $this->_textWindowOutput(
                'S/MIME Personal Private Key',
                $this->_smime->getPersonalPrivateKey(
                    $this->vars->actionID == 'view_personal_private_sign_key'
                )
            );
            break;

        case 'import_personal_certs':
            $this->_importKeyDialog('personal');
            break;

        case 'process_import_personal_certs':
            $reload = false;
            $pkcs12_2nd = false;
            try {
                $pkcs12 = $this->_getImportKey('upload_key');
                $this->_smime->addFromPKCS12($pkcs12, $this->vars->upload_key_pass, $this->vars->upload_key_pk_pass);
                $notification->push(_("S/MIME Public/Private Keypair successfully added."), 'horde.success');
                if ($pkcs12_2nd = $this->_getSecondaryKey()) {
                    $this->_smime->addFromPKCS12($pkcs12, $this->vars->upload_key_pass2, $this->vars->upload_key_pk_pass2, true);
                    $notification->push(_("Secondary S/MIME Public/Private Keypair successfully added."), 'horde.success');
                }
                $reload = true;
            } catch (Horde_Browser_Exception $e) {
                if ($e->getCode() != UPLOAD_ERR_NO_FILE ||
                    !($pkcs12_2nd = $this->_getSecondaryKey())) {
                    $notification->push(_("Personal S/MIME certificates NOT imported."), 'horde.error');
                }
            } catch (Horde_Exception $e) {
                $notification->push(_("Personal S/MIME certificates NOT imported: ") . $e->getMessage(), 'horde.error');
            }
            if (!$reload &&
                ($pkcs12_2nd || ($pkcs12_2nd = $this->_getSecondaryKey()))) {
                if (!$this->_smime->getPersonalPublicKey()) {
                    $notification->push(_("Cannot import secondary personal S/MIME certificates without primary certificates."), 'horde.error');
                } else {
                    try {
                        $this->_smime->addFromPKCS12($pkcs12_2nd, $this->vars->upload_key_pass2, $this->vars->upload_key_pk_pass2, true);
                        $notification->push(_("Secondary S/MIME Public/Private Keypair successfully added."), 'horde.success');
                        $reload = true;
                    } catch (Horde_Exception $e) {
                        $notification->push(_("Personal S/MIME certificates NOT imported: ") . $e->getMessage(), 'horde.error');
                    }
                }
            }

            if ($reload) {
                $this->_reloadWindow();
            }

            $this->vars->actionID = 'import_personal_certs';
            $this->_importKeyDialog('personal');
            break;
        }
    }

    /**
     */
    public static function url(array $opts = array())
    {
        return Horde::url('basic.php')->add('page', 'smime');
    }

    /**
     * Returns the secondary key if uploaded.
     *
     * @return string|boolean  The key contents or false if not uploaded.
     */
    protected function _getSecondaryKey()
    {
        global $notification;

        try {
            return $this->_getImportKey('upload_key2');
        } catch (Horde_Browser_Exception $e) {
            if ($e->getCode() == UPLOAD_ERR_NO_FILE) {
                return false;
            }
            $notification->push(
                _("Secondary personal S/MIME certificates NOT imported."),
                'horde.error'
            );
        } catch (Horde_Exception $e) {
            $notification->push(
                _("Secondary personal S/MIME certificates NOT imported: ")
                    . $e->getMessage(),
                'horde.error'
            );
        }

        return false;
    }

    /**
     * Generates import key dialog.
     *
     * @param string $target  Which dialog to generate, either 'personal' or
     *                        'public'.
     */
    protected function _importKeyDialog($target)
    {
        global $notification, $page_output, $registry;

        $page_output->topbar = $page_output->sidebar = false;
        $page_output->addInlineScript(array(
            '$$("INPUT.horde-cancel").first().observe("click", function() { window.close(); })'
        ), true);

        /* Import CSS located with prefs CSS. */
        $p_css = new Horde_Themes_Element('prefs.css');
        $page_output->addStylesheet($p_css->fs, $p_css->uri);

        $this->title = $target == 'personal'
            ? _("Import Personal S/MIME Certificate")
            : _("Import Public S/MIME Key");

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

        $this->output = $view->render('import_' . $target . '_key');
    }

    /**
     * Reload the window.
     */
    protected function _reloadWindow()
    {
        echo Horde::wrapInlineScript(array(
            'opener.focus();'.
            'opener.location.href="' . base64_decode($this->vars->reload) . '";',
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
     * @param string $filename  Key file name.
     * @param string $key       Key string.
     *
     * @return string  The key contents.
     * @throws Horde_Browser_Exception
     */
    protected function _getImportKey($filename, $key = null)
    {
        if (!empty($key)) {
            return $key;
        }

        $GLOBALS['browser']->wasFileUploaded($filename, _("key"));
        return file_get_contents($_FILES[$filename]['tmp_name']);
    }

}
