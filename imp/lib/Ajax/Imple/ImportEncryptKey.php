<?php
/**
 * Copyright 2012-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2012-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Attach the import encrpyt key javascript code into a page.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2012-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Ajax_Imple_ImportEncryptKey extends Horde_Core_Ajax_Imple
{
    /**
     * @param array $params  Configuration parameters:
     *   - mime_id: (string) The MIME ID of the message part with the key.
     *   - muid: (string) The MUID of the message.
     *   - type: (string) Key type. Either 'pgp' or 'smime'.
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
            'mime_id' => $this->_params['mime_id'],
            'muid' => $this->_params['muid'],
            'type' => $this->_params['type']
        );
    }

    /**
     * Variables required in form input:
     *   - mime_id
     *   - muid
     *   - type
     *
     * @return boolean  True on success.
     * @throws IMP_Exception
     */
    protected function _handle(Horde_Variables $vars)
    {
        global $injector, $notification;

        /* Retrieve the key from the message. */
        try {
            $contents = $injector->getInstance('IMP_Factory_Contents')->create(new IMP_Indices_Mailbox($vars));
            if (!($mime_part = $contents->getMIMEPart($vars->mime_id))) {
                throw new IMP_Exception(
                    _("Cannot retrieve public key from message.")
                );
            }

            /* Add the public key to the storage system. */
            switch ($vars->type) {
            case 'pgp':
                $injector->getInstance('IMP_Crypt_Pgp')->addPublicKey($mime_part->getContents());
                $notification->push(_("Successfully added public key from message."), 'horde.success');
                break;

            case 'smime':
                $stream = $vars->mime_id
                    ? $contents->getBodyPart($vars->mime_id, array('mimeheaders' => true, 'stream' => true))->data
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
