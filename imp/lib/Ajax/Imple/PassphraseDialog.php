<?php
/**
 * Attach the passphrase dialog to the page.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
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
     * The passphrase ID used by this instance.
     *
     * @var string
     */
    protected $_domid;

    /**
     * Constructor.
     *
     * @param array $params  Configuration parameters.
     * <pre>
     * 'id' - [OPTIONAL] The DOM ID to attach to.
     * 'onload' - (boolean) [OPTIONAL] If set, will trigger action on page
     *            load.
     * 'params' - (array) [OPTIONAL] Any additional parameters to pass.
     * 'type' - (string) The dialog type.
     * </pre>
     */
    public function __construct($params)
    {
        if (!isset($params['id'])) {
            $params['id'] = 'imp_passphrase_' . ++self::$_passphraseId;
        }

        $this->_domid = $params['id'];

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

        Horde::addScriptFile('effects.js', 'horde');
        Horde::addScriptFile('redbox.js', 'horde');
        Horde::addScriptFile('dialog.js', 'imp');

        $js = 'IMPDialog.display(' . Horde::escapeJson($js_params, array('urlencode' => true)) . ');';

        if (empty($this->_params['onload'])) {
            $js = '$("' . $this->_domid . '").observe("click", function(e) { ' . $js . 'e.stop(); })';
        }

        Horde::addInlineScript(array($js));
    }

    /**
     * Perform the given action.
     *
     * Variables required in form input:
     * <pre>
     * 'dialog_input' - (string) Input from the dialog screen.
     * 'symmetricid' - (string) The symmetric ID to process.
     * </pre>
     *
     * @param array $args  Not used.
     * @param array $post  Not used.
     *
     * @return object  An object with the following entries:
     * <pre>
     * 'error' - (string) An error message.
     * 'success' - (integer) 1 on success, 0 on failure.
     * </pre>
     */
    public function handle($args, $post)
    {
        $result = new stdClass;
        $result->success = 0;

        $vars = Horde_Variables::getDefaultVariables();

        try {
            Horde::requireSecureConnection();

            switch ($vars->type) {
            case 'pgpPersonal':
            case 'pgpSymmetric':
                if ($vars->dialog_input) {
                    $imp_pgp = $GLOBALS['injector']->getInstance('IMP_Crypt_Pgp');
                    if ((($vars->type == 'pgpPersonal') &&
                         $imp_pgp->storePassphrase('personal', $vars->dialog_input)) ||
                        (($vars->type == 'pgpSymmetric') &&
                         $imp_pgp->storePassphrase('symmetric', $vars->dialog_input, $vars->symmetricid))) {
                        $result->success = 1;
                    } else {
                        $result->error = _("Invalid passphrase entered.");
                    }
                } else {
                    $result->error = _("No passphrase entered.");
                }
                break;

            case 'smimePersonal':
                if ($vars->dialog_input) {
                    $imp_smime = $GLOBALS['injector']->getInstance('IMP_Crypt_Smime');
                    if ($imp_smime->storePassphrase($vars->dialog_input)) {
                        $result->success = 1;
                    } else {
                        $result->error = _("Invalid passphrase entered.");
                    }
                } else {
                    $result->error = _("No passphrase entered.");
                }
                break;
            }
        } catch (Horde_Exception $e) {
            $result->error = $e->getMessage();
        }

        return Horde::prepareResponse($result);
    }

    /**
     * Generates a unique DOM ID.
     *
     * @return string  A unique DOM ID.
     */
    public function getPassphraseId()
    {
        return $this->_domid;
    }

}
