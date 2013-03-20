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
 * A Horde_Injector based factory for IMP's configuration of Horde_Mail.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2010-2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Factory_Mail extends Horde_Core_Factory_Injector
{
    /**
     * Debug stream.
     *
     * @var resource
     */
    private $_debug;

    /**
     * Return the Horde_Mail instance.
     *
     * @return Horde_Mail  The singleton instance.
     * @throws Horde_Exception
     */
    public function create(Horde_Injector $injector)
    {
        global $conf;

        $params = $conf['mailer']['params'];

        if ($conf['mailer']['type'] == 'smtp') {
            $params = array_merge(
                $params,
                $injector->getInstance('IMP_Imap')->config->smtp
            );
        }

        if (!empty($params['debug'])) {
            $this->_debug = fopen($params['debug'], 'a');
            stream_filter_register('horde_eol', 'Horde_Stream_Filter_Eol');
            stream_filter_append($this->_debug, 'horde_eol', STREAM_FILTER_WRITE, array(
                'eol' => "\n"
            ));

            unset($params['debug']);
        }

        $class = $this->_getDriverName($GLOBALS['conf']['mailer']['type'], 'Horde_Mail_Transport');
        $ob = new $class($params);

        if (isset($this->_debug)) {
            $ob->getSMTPObject()->setDebug(true, array($this, 'smtpDebug'));
        }

        return $ob;
    }

    /**
     * SMTP debug handler.
     */
    public function smtpDebug($smtp, $message)
    {
        fwrite($this->_debug, $message);
        if (substr($message, -1) !== "\n") {
            fwrite($this->_debug, "\n");
        }
    }

}
