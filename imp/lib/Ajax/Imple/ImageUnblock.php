<?php
/**
 * Attach the image unblock javascript code to MIME part data.
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
class IMP_Ajax_Imple_ImageUnblock extends Horde_Core_Ajax_Imple
{
    /**
     * Constructor.
     *
     * @param array $params  Configuration parameters:
     *   - mailbox: (IMP_Mailbox) The mailbox of the message.
     *   - uid: (string) The UID of the message.
     */
    public function __construct(array $params = array())
    {
        parent::__construct($params);
    }

    /**
     */
    protected function _attach($init)
    {
        if ($init) {
            $this->_jsOnComplete('e.element().up("TR").remove()');
        }

        return array(
            'mailbox' => $this->_params['mailbox']->form_to,
            'uid' => $this->_params['uid']
        );
    }

    /**
     * Variables required in form input:
     *   - mailbox
     *   - uid
     *
     * @return boolean  True on success.
     *   - success: (integer) 1 on success, 0 on failure.
     */
    protected function _handle(Horde_Variables $vars)
    {
        global $injector, $notification;

        try {
            $contents = $injector->getInstance('IMP_Factory_Contents')->create(new IMP_Indices(IMP_Mailbox::formFrom($vars->mailbox), $vars->uid));

            $imgview = new IMP_Ui_Imageview();
            $imgview->addSafeAddress(IMP::bareAddress($contents->getHeader()->getValue('from')));
        } catch (Exception $e) {
            $notification->push($e, 'horde.error');
            return false;
        }

        return true;
    }

}
