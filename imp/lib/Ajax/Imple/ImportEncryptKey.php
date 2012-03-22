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
     * Import DOM ID counter.
     *
     * @var integer
     */
    static protected $_importId = 0;

    /**
     * Constructor.
     *
     * @param array $params  Configuration parameters:
     *   - id: (string) [OPTIONAL] The DOM ID to attach to.
     *   - mailbox: (IMP_Mailbox) The mailbox of the message.
     *   - mime_id: (string) The MIME ID of the message part with the key.
     *   - type: (string) Key type. Either 'pgp' or 'smime'.
     *   - uid: (string) The UID of the message.
     */
    public function __construct($params)
    {
        if (!isset($params['id'])) {
            $params['id'] = 'imp_importencryptkey' . self::$_importId;
        }

        ++self::$_importId;

        parent::__construct($params);
    }

    /**
     * Attach the object to a javascript event.
     */
    public function attach()
    {
        $js_params = array(
            'mailbox' => $this->_params['mailbox']->form_to,
            'mime_id' => $this->_params['mime_id'],
            'type' => $this->_params['type'],
            'uid' => $this->_params['uid']
        );

        if (defined('SID')) {
            parse_str(SID, $sid);
            $js_params = array_merge($js_params, $sid);
        }

        $page_output = $GLOBALS['injector']->getInstance('Horde_PageOutput');

        if (self::$_importId == 1) {
            $page_output->addScriptFile('importencryptkey.js');
            $page_output->addInlineJsVars(array(
                'IMPImportEncryptKey.uri' => strval($this->_getUrl('ImportEncryptKey', 'imp', array('sessionWrite' => 1)))
            ), array('onload' => true));
        }

        $page_output->addInlineJsVars(array(
            'IMPImportEncryptKey.handles[' . Horde_Serialize::serialize($this->getImportId(), Horde_Serialize::JSON) . ']' => $js_params
        ), array('onload' => true));
    }

    /**
     * Perform the given action.
     *
     * Variables required in form input:
     *   - mailbox
     *   - mime_id
     *   - type
     *   - uid
     *
     * @param array $args  Not used.
     * @param array $post  Not used.
     *
     * @return object  An object with the following entries:
     *   - success: (integer) 1 on success, 0 on failure.
     */
    public function handle($args, $post)
    {
        global $injector, $notification;

        $result = 0;
        $vars = Horde_Variables::getDefaultVariables();

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

            $result = 1;
        } catch (Exception $e) {
            $notification->push($e, 'horde.error');
        }

        return new Horde_Core_Ajax_Response($result, true);
    }

    /**
     * Generates a unique DOM ID.
     *
     * @return string  A unique DOM ID.
     */
    public function getImportId()
    {
        return $this->_params['id'];
    }

}
