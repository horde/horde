<?php
/**
 * Defines AJAX actions used in the IMP passphrase dialog.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */
class IMP_Ajax_Application_Handler_Passphrase extends Horde_Core_Ajax_Application_Handler
{
    /**
     * AJAX action: Check passphrase.
     *
     * Variables required in form input:
     *   - dialog_input: (string) Input from the dialog screen.
     *   - reload: (mixed) If set, reloads page instead of returning data.
     *   - symmetricid: (string) The symmetric ID to process.
     *   - type: (string) The passphrase type.
     *
     * @return boolean  True on success.
     */
    public function checkPassphrase()
    {
        global $injector, $notification;

        $result = false;

        try {
            Horde::requireSecureConnection();

            switch ($this->vars->type) {
            case 'pgpPersonal':
            case 'pgpSymmetric':
                if ($this->vars->dialog_input) {
                    $imp_pgp = $injector->getInstance('IMP_Crypt_Pgp');
                    if ((($this->vars->type == 'pgpPersonal') &&
                         $imp_pgp->storePassphrase('personal', $this->vars->dialog_input)) ||
                        (($this->vars->type == 'pgpSymmetric') &&
                         $imp_pgp->storePassphrase('symmetric', $this->vars->dialog_input, $this->vars->symmetricid))) {
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
                if ($this->vars->dialog_input) {
                    $imp_smime = $injector->getInstance('IMP_Crypt_Smime');
                    if ($imp_smime->storePassphrase($this->vars->dialog_input)) {
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

        return ($result && $this->vars->reload)
            ? new Horde_Core_Ajax_Response_HordeCore_Reload($this->vars->reload)
            : $result;
    }

}
