<?php
/**
 * Attach the passphrase dialog to the page.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */
class IMP_Ajax_Imple_PassphraseDialog extends Horde_Core_Ajax_Imple
{
    /**
     * @param array $params  Configuration parameters.
     *   - onload: (boolean) [OPTIONAL] If set, will trigger action on page
     *             load.
     *   - params: (array) [OPTIONAL] Any additional parameters to pass.
     *   - reloadurl: (Horde_Url) [OPTIONAL] Reload using this URL instead of
     *                refreshing the page.
     *   - type: (string) The dialog type.
     */
    public function __construct(array $params = array())
    {
        parent::__construct($params);
    }

    /**
     */
    protected function _attach($init)
    {
        global $page_output;

        if ($init) {
            $page_output->addScriptPackage('Dialog');
        }

        $params = isset($this->_params['params'])
            ? $this->_params['params']
            : array();

        switch ($this->_params['type']) {
        case 'pgpPersonal':
            $text = _("Enter your personal PGP passphrase.");
            break;

        case 'pgpSymmetric':
            $text = _("Enter the passphrase used to encrypt this message.");
            break;

        case 'smimePersonal':
            $text = _("Enter your personal S/MIME passphrase.");
            break;
        }

        $js_params = array(
            'params' => $params,
            'password' => true,
            'text' => $text,
            'type' => $this->_params['type'],
            'uri' => strval($this->getImpleUrl()->setRaw(true))
        );

        if (isset($this->_params['reloadurl'])) {
            $js_params['reloadurl'] = strval($this->_params['reloadurl']);
        }

        $js = 'HordeDialog.display(' . Horde::escapeJson($js_params, array('nodelimit' => true)) . ')';

        if ($this->_params['onload']) {
            $page_output->addInlineScript(array($js), true);
            return false;
        }

        return $js;
    }

    /**
     * Variables required in form input:
     *   - dialog_input: (string) Input from the dialog screen.
     *   - symmetricid: (string) The symmetric ID to process.
     *
     * @return boolean  True on success.
     */
    protected function _handle(Horde_Variables $vars)
    {
        global $injector, $notification;

        $result = false;

        try {
            Horde::requireSecureConnection();

            switch ($vars->type) {
            case 'pgpPersonal':
            case 'pgpSymmetric':
                if ($vars->dialog_input) {
                    $imp_pgp = $injector->getInstance('IMP_Crypt_Pgp');
                    if ((($vars->type == 'pgpPersonal') &&
                         $imp_pgp->storePassphrase('personal', $vars->dialog_input)) ||
                        (($vars->type == 'pgpSymmetric') &&
                         $imp_pgp->storePassphrase('symmetric', $vars->dialog_input, $vars->symmetricid))) {
                        $result = true;
                        $notification->push(_("PGP passhprase stored in session."), 'horde.success');
                    } else {
                        $notification->push(_("Invalid passphrase entered."), 'horde.error');
                    }
                } else {
                    $notification->push(_("No passphrase entered."), 'horde.error');
                }
                break;

            case 'smimePersonal':
                if ($vars->dialog_input) {
                    $imp_smime = $injector->getInstance('IMP_Crypt_Smime');
                    if ($imp_smime->storePassphrase($vars->dialog_input)) {
                        $result = true;
                        $notification->push(_("S/MIME passphrase stored in session."), 'horde.success');
                    } else {
                        $notification->error(_("Invalid passphrase entered."), 'horde.error');
                    }
                } else {
                    $notification->push(_("No passphrase entered."), 'horde.error');
                }
                break;
            }
        } catch (Horde_Exception $e) {
            $notification->push($e, 'horde.error');
        }

        return $result;
    }

}
