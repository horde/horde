<?php
/**
 * Copyright 2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Spam reporting driver via e-mail.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Spam_Email implements IMP_Spam_Base
{
    /**
     * Reporting e-mail.
     *
     * @var string
     */
    protected $_email;

    /**
     * E-mail format.
     *
     * @var string
     */
    protected $_format;

    /**
     * Constructor.
     *
     * @param string $email   Reporting e-mail.
     * @param string $format  E-mail format.
     */
    public function __construct($email, $format)
    {
        $this->_email = $email;
        $this->_format = $format;
    }

    /**
     */
    public function report(IMP_Contents $contents, $action)
    {
        global $injector, $registry;

        $imp_compose = $injector->getInstance('IMP_Factory_Compose')->create();

        switch ($this->_format) {
        case 'redirect':
            /* Send the message. */
            try {
                $imp_compose->redirectMessage($contents->getIndicesOb());
                $imp_compose->sendRedirectMessage($this->_email, false);
                return true;
            } catch (IMP_Compose_Exception $e) {
                $e->log();
            }
            break;

        case 'digest':
        default:
            try {
                $from_line = $injector->getInstance('IMP_Identity')->getFromLine();
            } catch (Horde_Exception $e) {
                $from_line = null;
            }

            /* Build the MIME structure. */
            $mime = new Horde_Mime_Part();
            $mime->setType('multipart/digest');

            $rfc822 = new Horde_Mime_Part();
            $rfc822->setType('message/rfc822');
            $rfc822->setContents($contents->fullMessageText(array(
                'stream' => true
            )));

            $mime->addPart($rfc822);

            $spam_headers = new Horde_Mime_Headers();
            $spam_headers->addMessageIdHeader();
            $spam_headers->addHeader('Date', date('r'));
            $spam_headers->addHeader('To', $this->_email);
            if (!is_null($from_line)) {
                $spam_headers->addHeader('From', $from_line);
            }
            $spam_headers->addHeader('Subject', sprintf(_("%s report from %s"), $action == IMP_Spam::SPAM ? 'spam' : 'innocent', $registry->getAuth()));

            /* Send the message. */
            try {
                $recip_list = $imp_compose->recipientList(array(
                    'to' => $this->_email
                ));
                $imp_compose->sendMessage($recip_list['list'], $spam_headers, $mime, 'UTF-8');
                return true;
            } catch (IMP_Compose_Exception $e) {
                $e->log();
            }
            break;
        }

        return false;
    }

}
