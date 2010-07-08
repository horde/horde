<?php
/**
 * @package Kolab_Filter
 */

/**
 * A basic definition for a PHP based postfix filter.
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
class Horde_Kolab_Filter_Base
{
    /**
     * The message ID.
     *
     * @var string
     */
    var $_id = '';

    /**
     * A temporary buffer file for storing the message.
     *
     * @var string
     */
    var $_tmpfile;

    /**
     * The file handle for the temporary file.
     *
     * @var int
     */
    var $_tmpfh;

    /**
     * The message sender.
     *
     * @var string
     */
    var $_sender;

    /**
     * The message recipients.
     *
     * @var array
     */
    var $_recipients = array();

    /**
     * The client host trying to send the message.
     *
     * @var string
     */
    var $_client_address;

    /**
     * The client host trying to send the message.
     *
     * @var string
     */
    var $_fqhostname;

    /**
     * The authenticated username of the sender.
     *
     * @var string
     */
    var $_sasl_username;

    /**
     * Comman line parser.
     *
     * @param Horde_Kolab_Filter_Cli 
     */
    private $_cli;

    /**
     * The log backend that needs to implement the debug(), info() and err()
     * methods.
     *
     * @param mixed 
     */
    private $_logger;

    /**
     * Constructor.
     *
     * @param mixed $logger The logger.
     */
    public function __construct(
        Horde_Kolab_Filter_Cli $cli,
        $logger
    ) {
        $this->_cli    = $cli;
        $this->_logger = $logger;
    }

    /**
     * Initialize the class.
     */
    function init()
    {
        /* Parse our arguments */
        $result = $this->_parseArgs();
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }
    }

    /**
     * Handle the message.
     *
     * @param int    $inh  The file handle pointing to the message.
     * @param string $transport  The name of the transport driver.
     */
    function parse($inh = STDIN, $transport = null)
    {
        /* Setup the temporary storage */
        $result = $this->_initTmp();
        if ($result instanceOf PEAR_Error) {
            return $result;
        }

        $this->_logger->debug(
            sprintf(
                "%s starting up (sender=%s, recipients=%s, client_address=%s)",
                get_class($this),
                $this->_sender,
                join(', ',$this->_recipients),
                $this->_client_address
            )
        );

        $result = $this->_parse($inh, $transport);
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }

        Horde::logMessage(sprintf("%s successfully completed (sender=%s, recipients=%s, client_address=%s, id=%s)",
                                  get_class($this), $this->_sender,
                                  join(', ',$this->_recipients),
                                  $this->_client_address, $this->_id),
                          'INFO');
    }

    /**
     * Creates a buffer for temporary storage of the message.
     *
     * @return mixed A PEAR_Error in case of an error, nothing otherwise.
     */
    function _initTmp()
    {
        global $conf;

        if (isset($conf['kolab']['filter']['tempdir'])) {
            $tmpdir = $conf['kolab']['filter']['tempdir'];
        } else {
            $tmpdir = Horde::getTempDir();
        }

        /* Temp file for storing the message */
        $this->_tmpfile = @tempnam($tmpdir, 'IN.' . get_class($this) . '.');
        $this->_tmpfh = @fopen($this->_tmpfile, "w");
        if( !$this->_tmpfh ) {
            $msg = $php_errormsg;
            return PEAR::raiseError(sprintf("Error: Could not open %s for writing: %s",
                                            $this->_tmpfile, $msg),
                                    OUT_LOG | EX_IOERR);
        }

        register_shutdown_function(array($this, '_cleanupTmp'));
    }

    /**
     * A shutdown function for removing the temporary file.
     */
    function _cleanupTmp() {
        if (@file_exists($this->_tmpfile)) {
            @unlink($this->_tmpfile);
        }
    }

    /**
     * Parse the command line arguments provided to the filter and
     * setup the class.
     *
     * @return mixed A PEAR_Error in case of an error, nothing otherwise.
     */
    function _parseArgs()
    {
        global $conf;

        $values = $this->_cli->getOptions();

        if (!empty($values['config']) && file_exists($values['config'])) {
            require_once $values['config'];
        }

        $this->_sender = strtolower($values['sender']);
        $this->_recipients = array_map('strtolower', $values['recipient']);
        $this->_client_address = $values['client'];
        $this->_fqhostname = strtolower($values['host']);
        $this->_sasl_username = strtolower($values['user']);

        Horde::logMessage(sprintf("Arguments: %s", print_r($values, true)), 'DEBUG');

        $GLOBALS['registry']->setCharset('utf-8');

        if (!empty($conf['kolab']['filter']['locale_path'])
            && !empty($conf['kolab']['filter']['locale'])) {
            $GLOBALS['registry']->setTextdomain('Kolab_Filter', $conf['kolab']['filter']['locale_path']);
            setlocale(LC_ALL, $conf['kolab']['filter']['locale']);
        }

        /* This is used as the default domain for unqualified adresses */
        global $_SERVER;
        if (!array_key_exists('SERVER_NAME', $_SERVER)) {
            $_SERVER['SERVER_NAME'] = $conf['kolab']['imap']['server'];
        }

        if (!array_key_exists('REMOTE_ADDR', $_SERVER)) {
            $_SERVER['REMOTE_ADDR'] = $conf['kolab']['imap']['server'];
        }

        if (!array_key_exists('REMOTE_HOST', $_SERVER)) {
            $_SERVER['REMOTE_HOST'] = $conf['kolab']['imap']['server'];
        }

        /* Always display all possible problems */
        ini_set('error_reporting', E_ALL);
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
    }
}

