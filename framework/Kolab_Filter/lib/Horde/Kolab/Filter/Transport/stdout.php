<?php
/**
 * @package Kolab_Filter
 */

/**
 * Delivers a mail to STDOUT for debugging.
 *
 * Copyright 2008 Klarälvdalens Datakonsult AB
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author  Gunnar Wrobel <wrobel@pardus.de>
 * @package Kolab_Filter
 */
class Horde_Kolab_Filter_Transport_stdout extends Horde_Kolab_Filter_Transport
{
    /**
     * Create the transport handler.
     *
     * @return StdOutWrapper Wraps STDOUT as transport
     */
    function _createTransport()
    {
        $transport = new StdOutWrapper();
        return $transport;
    }
}

/**
 * Defines a STDOUT wrapper that provides functionality comparable to
 * the Net/*MTP.php classes.
 *
 * Copyright 2008 Klarälvdalens Datakonsult AB
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author  Gunnar Wrobel <wrobel@pardus.de>
 * @package Kolab_Filter
 */
class StdOutWrapper
{
    /**
     * Pretends to connect to STDOUT.
     *
     * @return boolean Always true.
     */
    function connect()
    {
        return true;
    }

    /**
     * Pretends to disconnect from STDOUT.
     *
     * @return boolean Always true.
     */
    function disconnect()
    {
        return true;
    }

    /**
     * Set the sender.
     *
     * @return mixed Result from writing the sender to STDOUT.
     */
    function mailFrom($sender)
    {
        return fwrite(STDOUT, sprintf("Mail from sender: %s\n", $sender));
    }

    /**
     * Set the recipient.
     *
     * @return mixed Result from writing the recipient to STDOUT.
     */
    function rcptTo($recipient)
    {
        return fwrite(STDOUT, sprintf("Mail to recipient: %s\n", $recipient));
    }

    /**
     * Pretends to send commands to STDOUT.
     *
     * @param string $cmd The command.
     *
     * @return boolean Always true.
     */
    function _put($cmd)
    {
        return true;
    }

    /**
     * Pretends to handle STDOUT responses.
     *
     * @param string $code The response to parse.
     *
     * @return boolean Always true.
     */
    function _parseResponse($code)
    {
        return true;
    }

    /**
     * Write data to STDOUT.
     *
     * @param string $data The data to write.
     *
     * @return mixed Result from writing data to STDOUT.
     */
    function _send($data)
    {
        return fwrite(STDOUT, $data);
    }
}
