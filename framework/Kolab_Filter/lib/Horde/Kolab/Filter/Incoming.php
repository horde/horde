<?php
/**
 * @package Kolab_Filter
 */

/** Load the basic filter definition */
require_once dirname(__FILE__) . '/Base.php';

/** Load the Transport library */
require_once dirname(__FILE__) . '/Transport.php';

/**
 * A Kolab Server filter for incoming mails that are parsed for iCal
 * contents.
 *
 * Copyright 2004-2008 Klarälvdalens Datakonsult AB
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html.
 *
 * @author  Steffen Hansen <steffen@klaralvdalens-datakonsult.se>
 * @author  Gunnar Wrobel <wrobel@pardus.de>
 * @package Kolab_Filter
 */
class Horde_Kolab_Filter_Incoming extends Horde_Kolab_Filter_Base
{
    /**
     * A temporary storage place for incoming messages.
     *
     * @param Horde_Kolab_Filter_Temporary
     */
    private $_temporary;

    /**
     * An array of headers to be added to the message
     *
     * @var array
     */
    var $_add_headers;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_Filter_Configuration $config     The configuration.
     * @param Horde_Kolab_Filter_Temporary     $temporaray Temporary storage
     *                                                     location.
     * @param Horde_Kolab_Filter_Logger        $logger     The logging backend.
     */
    public function __construct(
        Horde_Kolab_Filter_Configuration $config,
        Horde_Kolab_Filter_Temporary $temporary,
        Horde_Log_Logger $logger
    ) {
        parent::__construct($config, $logger);
        $this->_temporary = $temporary;
    }

    /**
     * Initialize the filter.
     *
     * @return NULL
     */
    public function init()
    {
        parent::init();
        $this->_temporary->init();
    }

    /**
     * Handle the message.
     *
     * @param int    $inh        The file handle pointing to the message.
     * @param string $transport  The name of the transport driver.
     *
     * @return mixed A PEAR_Error in case of an error, nothing otherwise.
     */
    function _parse($inh, $transport)
    {
        global $conf;

        if (empty($transport)) {
            if (isset($conf['kolab']['filter']['delivery_backend'])) {
                $transport = $conf['kolab']['filter']['delivery_backend'];
            } else {
                $transport = 'lmtp';
            }
        }

        $this->_tmpfh = $this->_temporary->getHandle();

        $ical = false;
        $add_headers = array();
        $headers_done = false;

        /* High speed section START */
        $headers_done = false;
        while (!feof($inh) && !$headers_done) {
            $buffer = fgets($inh, 8192);
            $line = rtrim( $buffer, "\r\n");
            if ($line == '') {
                /* Done with headers */
                $headers_done = true;
            } else if (preg_match('#^Content-Type: text/calendar#i', $line)) {
                Horde::logMessage("Found iCal data in message", 'DEBUG');
                $ical = true;
            } else if (preg_match('#^Message-ID: (.*)#i', $line, $regs)) {
                $this->_id = $regs[1];
            }
            if (@fwrite($this->_tmpfh, $buffer) === false) {
                $msg = $php_errormsg;
                return PEAR::raiseError(sprintf("Error: Could not write to %s: %s",
                                                $this->_tmpfile, $msg),
                                        OUT_LOG | EX_IOERR);
            }
        }

        if ($ical) {
            /* iCal already identified. So let's just pipe the rest of
             * the message through.
             */
            while (!feof($inh)) {
                $buffer = fread($inh, 8192);
                if (@fwrite($this->_tmpfh, $buffer) === false) {
                    $msg = $php_errormsg;
                    return PEAR::raiseError(sprintf("Error: Could not write to %s: %s",
                                                    $this->_tmpfile, $msg),
                                            OUT_LOG | EX_IOERR);
                }
            }
        } else {
            /* No ical yet? Let's try to identify the string
             * "text/calendar". It's likely that we have a mime
             * multipart message including iCal then.
             */
            while (!feof($inh)) {
                $buffer = fread($inh, 8192);
                if (@fwrite($this->_tmpfh, $buffer) === false) {
                    $msg = $php_errormsg;
                    return PEAR::raiseError(sprintf("Error: Could not write to %s: %s",
                                                    $this->_tmpfile, $msg),
                                            OUT_LOG | EX_IOERR);
                }
                if (strpos($buffer, 'text/calendar')) {
                    $ical = true;
                }
            }
        }
        /* High speed section END */

        if (@fclose($this->_tmpfh) === false) {
            $msg = $php_errormsg;
            return PEAR::raiseError(sprintf("Error: Failed closing %s: %s",
                                            $this->_tmpfile, $msg),
                                    OUT_LOG | EX_IOERR);
        }

        if ($ical) {
            require_once 'Horde/Kolab/Resource.php';
            $newrecips = array();
            foreach ($this->_recipients as $recip) {
                if (strpos($recip, '+')) {
                    list($local, $rest)  = explode('+', $recip, 2);
                    list($rest, $domain) = explode('@', $recip, 2);
                    $resource = $local . '@' . $domain;
                } else {
                    $resource = $recip;
                }
                Horde::logMessage(sprintf("Calling resmgr_filter(%s, %s, %s, %s)",
                                          $this->_fqhostname, $this->_sender,
                                          $resource, $this->_tmpfile), 'DEBUG');
                $r = new Kolab_Resource();
                $rc = $r->handleMessage($this->_fqhostname, $this->_sender,
                                        $resource, $this->_tmpfile);
                $r->cleanup();
                if (is_a($rc, 'PEAR_Error')) {
                    return $rc;
                } else if (is_a($rc, 'Horde_Kolab_Resource_Reply')) {
                    $result = $this->_transportItipReply($rc);
                    if (is_a($result, 'PEAR_Error')) {
                        return $result;
                    }
                    Horde::logMessage('Successfully sent iTip reply', 'DEBUG');
                } else if ($rc === true) {
                    $newrecips[] = $resource;
                }
            }
            $this->_recipients = $newrecips;
            $this->_add_headers[] = 'X-Kolab-Scheduling-Message: TRUE';
        } else {
            $this->_add_headers[] = 'X-Kolab-Scheduling-Message: FALSE';
        }

        /* Check if we still have recipients */
        if (empty($this->_recipients)) {
            $this->_logger->debug("No recipients left.", 'DEBUG');
            return;
        } else {
            $result = $this->_deliver($transport);
            if (is_a($result, 'PEAR_Error')) {
                return $result;
            }
        }

        $this->_logger->debug("Filter_Incoming successfully completed.", 'DEBUG');
    }

    private function _transportItipReply(Horde_Kolab_Resource_Reply $reply)
    {
        global $conf;

        if (isset($conf['kolab']['filter']['itipreply'])) {
            $driver = $conf['kolab']['filter']['itipreply']['driver'];
            $host   = $conf['kolab']['filter']['itipreply']['params']['host'];
            $port   = $conf['kolab']['filter']['itipreply']['params']['port'];
        } else {
            $driver = 'smtp';
            $host   = 'localhost';
            $port   = 25;
        }

        $transport = Horde_Kolab_Filter_Transport::factory(
            $driver,
            array('host' => $host, 'port' => $port)
        );

        $result = $transport->start($reply->getSender(), $reply->getRecipient());
        if (is_a($result, 'PEAR_Error')) {
            return PEAR::raiseError('Unable to send iTip reply: ' . $result->getMessage(),
                                    OUT_LOG | EX_TEMPFAIL);
        }

        $result = $transport->data($reply->getData());
        if (is_a($result, 'PEAR_Error')) {
            return PEAR::raiseError('Unable to send iTip reply: ' . $result->getMessage(),
                                    OUT_LOG | EX_TEMPFAIL);
        }

        $result = $transport->end();
        if (is_a($result, 'PEAR_Error')) {
            return PEAR::raiseError('Unable to send iTip reply: ' . $result->getMessage(),
                                    OUT_LOG | EX_TEMPFAIL);
        }
    }

    /**
     * Deliver the message.
     *
     * @param string $transport  The name of the transport driver.
     *
     * @return mixed A PEAR_Error in case of an error, nothing otherwise.
     */
    function _deliver($transport)
    {
        global $conf;

        if (isset($conf['kolab']['filter']['lmtp_host'])) {
            $host = $conf['kolab']['filter']['lmtp_host'];
        } else {
            $host = 'localhost';
        }
        if (isset($conf['kolab']['filter']['lmtp_port'])) {
            $port = $conf['kolab']['filter']['lmtp_port'];
        } else {
            $port = 2003;
        }

        /* Load the LDAP library */
        require_once 'Horde/Kolab/Server.php';

        $server = &Horde_Kolab_Server::singleton();
        if (is_a($server, 'PEAR_Error')) {
            $server->code = OUT_LOG | EX_TEMPFAIL;
            return $server;
        }

        $hosts = array();
        foreach ($this->_recipients as $recipient) {
            if (strpos($recipient, '+')) {
                list($local, $rest)  = explode('+', $recipient, 2);
                list($rest, $domain) = explode('@', $recipient, 2);
                $real_recipient = $local . '@' . $domain;
            } else {
                $real_recipient = $recipient;
            }
            $dn = $server->uidForIdOrMail($real_recipient);
            if (is_a($dn, 'PEAR_Error')) {
                return $dn;
            }
            if (!$dn) {
                Horde::logMessage(sprintf('User %s does not exist!', $real_recipient), 'DEBUG');
            }
            try {
                $user = $server->fetch($dn, 'Horde_Kolab_Server_Object_Kolab_User');
            } catch (Horde_Kolab_Server_Exception $e) {
                Horde::logMessage(sprintf('Failed fetching user object %s. Error was:',
                                          $dn, $e->getMessage()), 'DEBUG');
                $user->code = OUT_LOG | EX_TEMPFAIL;
                return $user;
            }
            $imapserver = $user->get(Horde_Kolab_Server_Object_Kolab_User::ATTRIBUTE_IMAPHOST);
            if (is_a($imapserver, 'PEAR_Error')) {
                $imapserver->code = OUT_LOG | EX_TEMPFAIL;
                return $imapserver;
            }
            if (!empty($imapserver)) {
                $uhost = $imapserver;
            } else {
                $uhost = $host;
            }
            $hosts[$uhost][] = $recipient;
        }

        foreach (array_keys($hosts) as $imap_host) {
            $params =  array('host' => $imap_host, 'port' => $port);
            if ($imap_host != $host) {
                $params['user'] = $conf['kolab']['filter']['lmtp_user'];
                $params['pass'] = $conf['kolab']['filter']['lmtp_pass'];
            }
            $transport = &Horde_Kolab_Filter_Transport::factory($transport, $params);

            $tmpf = $this->_temporary->getReadHandle();
            if (!$tmpf) {
                $msg = $php_errormsg;
                return PEAR::raiseError(sprintf("Error: Could not open %s for writing: %s",
                                                $this->_tmpfile, $msg),
                                        OUT_LOG | EX_IOERR);
            }

            $result = $transport->start($this->_sender, $hosts[$imap_host]);
            if (is_a($result, 'PEAR_Error')) {
                return $result;
            }

            $headers_done = false;
            while (!feof($tmpf) && !$headers_done) {
                $buffer = fgets($tmpf, 8192);
                if (!$headers_done && rtrim($buffer, "\r\n") == '') {
                    $headers_done = true;
                    foreach ($this->_add_headers as $h) {
                        $result = $transport->data("$h\r\n");
                        if (is_a($result, 'PEAR_Error')) {
                            return $result;
                        }
                    }
                }
                $result = $transport->data($buffer);
                if (is_a($result, 'PEAR_Error')) {
                    return $result;
                }
            }

            while (!feof($tmpf)) {
                $buffer = fread($tmpf, 8192);
                $len = strlen($buffer);

                /* We can't tolerate that the buffer breaks the data
                 * between \r and \n, so we try to avoid that. The limit
                 * of 100 reads is to battle abuse
                 */
                while ($buffer{$len-1} == "\r" && $len < 8192 + 100) {
                    $buffer .= fread($tmpf, 1);
                    $len++;
                }
                $result = $transport->data($buffer);
                if (is_a($result, 'PEAR_Error')) {
                    return $result;
                }
            }
            return $transport->end();
        }
    }
}
?>
