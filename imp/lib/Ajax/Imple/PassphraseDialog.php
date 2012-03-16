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
     * Passphrase DOM ID counter.
     *
     * @var integer
     */
    static protected $_passphraseId = 0;

    /**
     * Constructor.
     *
     * @param array $params  Configuration parameters.
     *   - id: - [OPTIONAL] The DOM ID to attach to.
     *   - onload: (boolean) [OPTIONAL] If set, will trigger action on page
     *            load.
     *   - params: (array) [OPTIONAL] Any additional parameters to pass.
     *   - reloadurl: (Horde_Url) [OPTIONAL] Reload using this URL instead of
     *              refreshing the page.
     *   - type: (string) The dialog type.
     */
    public function __construct($params)
    {
        if (!isset($params['id'])) {
            $params['id'] = 'imp_passphrase_' . ++self::$_passphraseId;
        }

        parent::__construct($params);
    }

    /**
     * Attach the object to a javascript event.
     */
    public function attach()
    {
        $params = isset($this->_params['params'])
            ? $this->_params['params']
            : array();
        $params['type'] = $this->_params['type'];

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

        if (defined('SID')) {
            parse_str(SID, $sid);
            $params = array_merge($params, $sid);
        }

        $js_params = array(
            'cancel_text' => _("Cancel"),
            'ok_text' => _("OK"),
            'params' => $params,
            'password' => true,
            'text' => $text,
            'type' => $this->_params['type'],
            'uri' => strval($this->_getUrl('PassphraseDialog', 'imp', array('sessionWrite' => 1)))
        );

        if (isset($this->_params['reloadurl'])) {
            $js_params['reloadurl'] = strval($this->_params['reloadurl']);
        }

        $page_output = $GLOBALS['injector']->getInstance('Horde_PageOutput');

        $page_output->addScriptFile('effects.js', 'horde');
        $page_output->addScriptFile('redbox.js', 'horde');
        $page_output->addScriptFile('dialog.js', 'horde');

        $js = 'HordeDialog.display(' . Horde::escapeJson($js_params, array('urlencode' => true)) . ');';

        if (empty($this->_params['onload'])) {
            $js = '$("' . $this->_params['id'] . '").observe("click", function(e) { ' . $js . 'e.stop(); })';
        }

        $page_output->addInlineScript(array($js), true);
    }

    /**
     * Perform the given action.
     *
     * Variables required in form input:
     *   - dialog_input: (string) Input from the dialog screen.
     *   - symmetricid: (string) The symmetric ID to process.
     *
     * @param array $args  Not used.
     * @param array $post  Not used.
     *
     * @return object  An object with the following entries:
     *   - error: (string) An error message.
     *   - success: (integer) 1 on success, 0 on failure.
     */
    public function handle($args, $post)
    {
        global $injector, $notification, $registry;

        $result = new stdClass;
        $result->success = 0;

        $dynamic_view = ($registry->getView() == Horde_Registry::VIEW_DYNAMIC);
        $error = $success = null;
        $vars = Horde_Variables::getDefaultVariables();

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
                        $result->success = 1;
                        $success = _("PGP passhprase stored in session.");
                    } else {
                        $error = _("Invalid passphrase entered.");
                    }
                } else {
                    $error = _("No passphrase entered.");
                }
                break;

            case 'smimePersonal':
                if ($vars->dialog_input) {
                    $imp_smime = $injector->getInstance('IMP_Crypt_Smime');
                    if ($imp_smime->storePassphrase($vars->dialog_input)) {
                        $result->success = 1;
                        $success = _("S/MIME passphrase stored in session.");
                    } else {
                        $error = _("Invalid passphrase entered.");
                    }
                } else {
                    $error = _("No passphrase entered.");
                }
                break;
            }
        } catch (Horde_Exception $e) {
            $error = $e->getMessage();
        }

        if ($dynamic_view) {
            $notification->push(is_null($error) ? $success : $error, is_null($error) ? 'horde.success' : 'horde.error');
        } elseif (!is_null($error)) {
            $result->error = $error;
        }

        return new Horde_Core_Ajax_Response($result, $dynamic_view);
    }

    /**
     * Generates a unique DOM ID.
     *
     * @return string  A unique DOM ID.
     */
    public function getPassphraseId()
    {
        return $this->_params['id'];
    }

}
