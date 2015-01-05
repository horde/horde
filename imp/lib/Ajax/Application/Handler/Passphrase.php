<?php
/**
 * Copyright 2012-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2012-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Defines AJAX actions used in the IMP passphrase dialog.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2012-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
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

        if (!$this->vars->dialog_input) {
            $notification->push(_("No passphrase entered."), 'horde.error');
            return $result;
        }

        try {
            Horde::requireSecureConnection();

            switch ($this->vars->type) {
            case 'pgpPersonal':
                $result = $injector->getInstance('IMP_Crypt_Pgp')->storePassphrase('personal', $this->vars->dialog_input);
                break;

            case 'pgpSymmetric':
                $result = $injector->getInstance('IMP_Crypt_Pgp')->storePassphrase('symmetric', $this->vars->dialog_input, $this->vars->symmetricid);
                break;

            case 'smimePersonal':
                $result = $injector->getInstance('IMP_Crypt_Smime')->storePassphrase($this->vars->dialog_input);
                break;
            }

            if ($result) {
                $notification->push(_("Passphrase verified."), 'horde.success');
            } else {
                $notification->push(_("Invalid passphrase entered."), 'horde.error');
            }
        } catch (Horde_Exception $e) {
            $notification->push($e, 'horde.error');
        }

        return ($result && $this->vars->reload)
            ? new Horde_Core_Ajax_Response_HordeCore_Reload($this->vars->reload)
            : $result;
    }

}
