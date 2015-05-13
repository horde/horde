<?php
/**
 * Copyright 2013-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2013-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Spam reporting driver via e-mail.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013-2015 Horde LLC
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
     * Additional options.
     *
     * @var array
     */
    protected $_opts;

    /**
     * Constructor.
     *
     * @param string $email   Reporting e-mail.
     * @param string $format  E-mail format.
     * @param array $opts     Additional options:
     *   - digest_limit_msgs: (integer) Maximum number of messages allowed in
     *                        a digest.
     *   - digest_limit_size: (integer) Maximum size of a digest.
     */
    public function __construct($email, $format, array $opts = array())
    {
        $this->_email = $email;
        $this->_format = $format;
        $this->_opts = $opts;
    }

    /**
     */
    public function report(array $msgs, $action)
    {
        global $injector;

        $ret = 0;

        switch ($this->_format) {
        case 'redirect':
            /* Send the message. */
            foreach ($msgs as $val) {
                try {
                    $imp_compose = $injector->getInstance('IMP_Factory_Compose')
                        ->create();
                    $imp_compose->redirectMessage($val->getIndicesOb());
                    $imp_compose->sendRedirectMessage($this->_email, false);
                    ++$ret;
                } catch (IMP_Compose_Exception $e) {
                    $e->log();
                }
            }
            break;

        case 'digest':
        default:
            try {
                $from_line = $injector->getInstance('IMP_Identity')
                    ->getFromLine();
            } catch (Horde_Exception $e) {
                $from_line = null;
            }
            $self = $this;

            $reportDigest = function($m) use($action, $from_line, $self) {
                global $injector, $registry;

                if (empty($m)) {
                    return 0;
                }

                /* Build the MIME structure. */
                $mime = new Horde_Mime_Part();
                $mime->setType('multipart/digest');

                foreach ($m as $val) {
                    $rfc822 = new Horde_Mime_Part();
                    $rfc822->setType('message/rfc822');
                    $rfc822->setContents($val->fullMessageText(array(
                        'stream' => true
                    )));
                    $mime[] = $rfc822;
                }

                $spam_headers = new Horde_Mime_Headers();
                $spam_headers->addHeaderOb(
                    Horde_Mime_Headers_MessageId::create()
                );
                $spam_headers->addHeaderOb(
                    Horde_Mime_Headers_Date::create()
                );
                $spam_headers->addHeader('To', $this->_email);
                if (!is_null($from_line)) {
                    $spam_headers->addHeader('From', $from_line);
                }
                $spam_headers->addHeader(
                    'Subject',
                    sprintf(
                        _("%s report from %s"),
                        ($action === IMP_Spam::SPAM) ? 'spam' : 'innocent',
                        $registry->getAuth()
                    )
                );

                /* Send the message. */
                try {
                    $imp_compose = $injector->getInstance('IMP_Factory_Compose')
                        ->create();
                    $recip_list = $imp_compose->recipientList(array(
                        'to' => $this->_email
                    ));
                    $imp_compose->sendMessage(
                        $recip_list['list'],
                        $spam_headers,
                        $mime,
                        'UTF-8'
                    );
                    return count($m);
                } catch (IMP_Compose_Exception $e) {
                    $e->log();
                    return 0;
                }
            };

            $mlimit = $orig_mlimit = empty($this->_opts['digest_limit_msgs'])
                ? null
                : $this->_opts['digest_limit_msgs'];
            $slimit = $orig_slimit = empty($this->_opts['digest_limit_size'])
                ? null
                : $this->_opts['digest_limit_size'];

            $todo = array();

            foreach ($msgs as $val) {
                $process = false;
                $todo[] = $val;

                if (!is_null($mlimit) && !(--$mlimit)) {
                    $process = true;
                }

                if (!is_null($slimit) &&
                    (($slimit -= $val->getMIMEMessage()->getBytes()) < 0)) {
                    $process = true;
                    /* If we have exceeded size limits with this single
                     * message, it exceeds the maximum limit and we can't
                     * send it at all. Don't confuse the user and instead
                     * report is as a "success" for UI purposes. */
                    if (count($todo) === 1) {
                        ++$ret;
                        $todo = array();
                        Horde::log(
                            'Could not send spam/innocent reporting message because original message was too large.',
                            'NOTICE'
                        );
                    }
                }

                if ($process) {
                    $ret += $reportDigest($todo);
                    $todo = array();
                    $mlimit = $orig_mlimit;
                    $slimit = $orig_slimit;
                }
            }

            $ret += $reportDigest($todo);
            break;
        }

        return $ret;
    }

}
