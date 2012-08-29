<?php
/**
 * Attach the import encrpyt key javascript code into a page.
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
class IMP_Ajax_Imple_ImportEncryptKey extends Horde_Core_Ajax_Imple
{
    /**
     * @param array $params  Configuration parameters:
     *   - mailbox: (IMP_Mailbox) The mailbox of the message.
     *   - mime_id: (string) The MIME ID of the message part with the key.
     *   - type: (string) Key type. Either 'pgp' or 'smime'.
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
            'mime_id' => $this->_params['mime_id'],
            'type' => $this->_params['type'],
            'uid' => $this->_params['uid']
        );
    }

    /**
     * Variables required in form input:
     *   - mailbox
     *   - mime_id
     *   - type
     *   - uid
     *
     * @return boolean  True on success.
     * @throws IMP_Exception
     */
    protected function _handle(Horde_Variables $vars)
    {
        global $injector, $notification;

        /* Retrieve the key from the message. */
        try {
            $contents = $injector->getInstance('IMP_Factory_Contents')->create(new IMP_Indices(IMP_Mailbox::formFrom($vars->mailbox), $vars->uid));
            $mime_part = $contents->getMIMEPart($vars->mime_id);
            if (empty($mime_part)) {
                throw new IMP_Exception(_("Cannot retrieve public key from message."));
            }

            /* Add the public key to the storage system. */
            switch ($vars->type) {
            case 'pgp':
                $injector->getInstance('IMP_Crypt_Pgp')->addPublicKey($mime_part->getContents());
                $notification->push(_("Successfully added public key from message."), 'horde.success');
                break;

            case 'smime':
                $stream = $vars->mime_id
                    ? $contents->getBodyPart($vars->mime_id, array('mimeheaders' => true, 'stream' => true))
                    : $contents->fullMessageText();
                $raw_text = $mime_part->replaceEOL($stream, Horde_Mime_Part::RFC_EOL);

                $imp_smime = $injector->getInstance('IMP_Crypt_Smime');
                $sig_result = $imp_smime->verifySignature($raw_text);
                $imp_smime->addPublicKey($sig_result->cert);
                $notification->push(_("Successfully added certificate from message."), 'horde.success');
                break;
            }
        } catch (Exception $e) {
            $notification->push($e, 'horde.error');
            return false;
        }

        return true;
    }

}
