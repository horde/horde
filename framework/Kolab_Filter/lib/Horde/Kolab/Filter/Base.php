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
     * Configuration.
     *
     * @param Horde_Kolab_Filter_Configuration 
     */
    private $_config;

    /**
     * The log backend that needs to implement the debug(), info() and err()
     * methods.
     *
     * @param Horde_Kolab_Filter_Logger
     */
    protected $_logger;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_Filter_Configuration $config     The configuration.
     * @param Horde_Kolab_Filter_Logger        $logger     The logging backend.
     */
    public function __construct(
        Horde_Kolab_Filter_Configuration $config,
        Horde_Log_Logger $logger
    ) {
        $this->_config    = $config;
        $this->_logger    = $logger;
    }

    /**
     * Initialize the filter.
     *
     * @return NULL
     */
    public function init()
    {
        $this->_config->init();
    }

    /**
     * Handle the message.
     *
     * @param int    $inh  The file handle pointing to the message.
     * @param string $transport  The name of the transport driver.
     *
     * @return NULL
     */
    public function parse($inh = STDIN, $transport = null)
    {
        /* $this->_logger->debug( */
        /*     sprintf( */
        /*         "Arguments: %s", */
        /*         print_r($this->_config->getArguments(), true) */
        /*     ) */
        /* ); */

        $this->_logger->debug(
            sprintf(
                "%s starting up (sender=%s, recipients=%s, client_address=%s)",
                get_class($this),
                $this->_config->getSender(),
                join(', ',$this->_config->getRecipients()),
                $this->_config->getClientAddress()
            )
        );

        $this->_parse($inh, $transport);

        $this->_logger->info(
            sprintf(
                "%s successfully completed (sender=%s, recipients=%s, client_address=%s, id=%s)",
                get_class($this),
                $this->_config->getSender(),
                join(', ',$this->_config->getRecipients()),
                $this->_config->getClientAddress(),
                $this->_id
           )
       );
    }
}

