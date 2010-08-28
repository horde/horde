<?php
/**
 * The configuration of the Kolab_Filter package.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Filter
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Filter
 */

/**
 * The configuration of the Kolab_Filter package.
 *
 * Copyright 2008 Klarälvdalens Datakonsult AB
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html.
 *
 * @category Kolab
 * @package  Kolab_Filter
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Filter
 */
class Horde_Kolab_Filter_Configuration
{
    /**
     * The message sender.
     *
     * @var string
     */
    private $_sender;

    /**
     * The message recipients.
     *
     * @var array
     */
    private $_recipients = array();

    /**
     * The client host trying to send the message.
     *
     * @var string
     */
    private $_client_address;

    /**
     * The client host trying to send the message.
     *
     * @var string
     */
    private $_fqhostname;

    /**
     * The authenticated username of the sender.
     *
     * @var string
     */
    private $_sasl_username;

    /**
     * The parameters from the configuration file.
     *
     * @var array
     */
    private $_conf;

    /**
     * Command line parser.
     *
     * @param Horde_Kolab_Filter_Cli 
     */
    private $_cli;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_Filter_Cli $cli The CLI parser.
     */
    public function __construct(
        Horde_Kolab_Filter_Cli $cli
    ) {
        $this->_cli = $cli;
    }

    /**
     * Initialize the configuration.
     *
     * @return NULL
     */
    public function init()
    {
        $values = $this->_cli->getOptions();

        $this->_sender = strtolower($values['sender']);
        $this->_recipients = array_map('strtolower', $values['recipient']);
        $this->_client_address = $values['client'];
        $this->_fqhostname = strtolower($values['host']);
        $this->_sasl_username = strtolower($values['user']);

        global $conf;

        if (!empty($values['config']) && file_exists($values['config'])) {
            require_once $values['config'];
        }

        Horde_Nls::setCharset('utf-8');

        if (!empty($conf['kolab']['filter']['locale_path'])
            && !empty($conf['kolab']['filter']['locale'])) {
            Horde_Nls::setTextdomain('Kolab_Filter', $conf['kolab']['filter']['locale_path'], Horde_Nls::getCharset());
            setlocale(LC_ALL, $conf['kolab']['filter']['locale']);
        }

        /* This is used as the default domain for unqualified adresses */
        /* @todo: What do we need this for? Which libraries grab these infos from global scope? MIME? */
        if (isset($conf['kolab']['imap']['server'])) {
            if (!array_key_exists('SERVER_NAME', $_SERVER)) {
                $_SERVER['SERVER_NAME'] = $conf['kolab']['imap']['server'];
            }

            if (!array_key_exists('REMOTE_ADDR', $_SERVER)) {
                $_SERVER['REMOTE_ADDR'] = $conf['kolab']['imap']['server'];
            }

            if (!array_key_exists('REMOTE_HOST', $_SERVER)) {
                $_SERVER['REMOTE_HOST'] = $conf['kolab']['imap']['server'];
            }
        }

        /* Always display all possible problems */
        ini_set('error_reporting', E_ERROR);
        ini_set('track_errors', '1');

        /* Setup error logging */
        if (isset($conf['kolab']['filter']['error_log'])) {
            ini_set('log_errors', '1');
            ini_set('error_log', $conf['kolab']['filter']['error_log']);
        }

        /* Print PHP messages to StdOut if we are debugging */
        if (isset($conf['kolab']['filter']['debug'])
            && $conf['kolab']['filter']['debug']) {
            ini_set('display_errors', '1');
        }

        /* Provide basic syslog debugging if nothing has been
         * specified
         */
        if (!isset($conf['log'])) {
            $conf['log']['enabled']          = true;
            $conf['log']['priority']         = 'DEBUG';
            $conf['log']['type']             = 'syslog';
            $conf['log']['name']             = LOG_MAIL;
            $conf['log']['ident']            = 'kolabfilter';
            $conf['log']['params']           = array();
        }

        $this->_conf = $conf;
    }

    public function getSender()
    {
        return $this->_sender;
    }

    public function getRecipients()
    {
        return $this->_recipients;
    }

    public function getClientAddress()
    {
        return $this->_client_address;
    }

    public function getFqHostname()
    {
        return $this->_fqhostname;
    }

    public function getSaslUsername()
    {
        return $this->_sasl_username;
    }

    public function getConf()
    {
        return $this->_conf;
    }
}