<?php
/**
 * Copyright 2010-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2010-2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * A Horde_Injector based factory for IMP's configuration of
 * Horde_Mail_Transport.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2010-2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Factory_Mail extends Horde_Core_Factory_Injector implements Horde_Shutdown_Task
{
    /**
     * Debug stream.
     *
     * @var resource
     */
    private $_debug;

    /**
     * Return the Horde_Mail_Transport instance.
     *
     * @return Horde_Mail_Transport  The singleton instance.
     * @throws Horde_Exception
     */
    public function create(Horde_Injector $injector)
    {
        $factory = $injector->getInstance('Horde_Core_Factory_Mail');
        list($transport, $params) = $factory->getConfig();

        if ($transport == 'smtp') {
            $params = array_merge(
                $params,
                $injector->getInstance('IMP_Imap')->config->smtp
            );

            if (!empty($params['debug'])) {
                $this->_debug = fopen($params['debug'], 'a');
                stream_filter_register('horde_eol', 'Horde_Stream_Filter_Eol');
                stream_filter_append($this->_debug, 'horde_eol', STREAM_FILTER_WRITE, array(
                    'eol' => "\n"
                ));

                unset($params['debug']);
            }
        }

        $ob = $factory->create(array(
            'params' => $params,
            'transport' => $transport
        ));

        if (isset($this->_debug)) {
            $ob->getSMTPObject()->setDebug(true, array($this, 'smtpDebug'));
            Horde_Shutdown::add($this);
        }

        return $ob;
    }

    /**
     * SMTP debug handler.
     */
    public function smtpDebug($smtp, $message)
    {
        if ($this->_debug) {
            fwrite($this->_debug, $message);
            if (substr($message, -1) !== "\n") {
                fwrite($this->_debug, "\n");
            }
        }
    }

    /**
     * SMTP debug shutdown handler.
     */
    public function shutdown()
    {
        if ($this->_debug) {
            @fclose($this->_debug);
            $this->_debug = null;
        }
    }

}
