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
     *   - params: (array) [OPTIONAL] Any additional parameters to pass to
     *             AJAX action.
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
            $page_output->addScriptFile('passphrase.js', 'imp');
        }

        $params = isset($this->_params['params'])
            ? $this->_params['params']
            : array();
        if (isset($params['reload'])) {
            $params['reload'] = strval($params['reload']);
        }

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
            'hidden' => array_merge($params, array('type' => $this->_params['type'])),
            'text' => $text
        );

        $js = 'ImpPassphraseDialog.display(' . Horde::escapeJson($js_params, array('nodelimit' => true)) . ')';

        if (!empty($this->_params['onload'])) {
            $page_output->addInlineScript(array($js), true);
            return false;
        }

        return $js;
    }

    /**
     */
    protected function _handle(Horde_Variables $vars)
    {
        return false;
    }

}
