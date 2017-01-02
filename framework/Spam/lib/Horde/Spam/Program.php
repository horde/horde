<?php
/**
 * Copyright 2013-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl.
 *
 * @category  Horde
 * @copyright 2013-2017 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl LGPL
 * @package   Spam
 */

/**
 * Spam reporting driver utilizing a local binary.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @author    Jan Schneider <jan@horde.org>
 * @category  Horde
 * @copyright 2013-2017 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl LGPL
 * @package   Spam
 */
class Horde_Spam_Program extends Horde_Spam_Base
{
    /**
     * Binary location.
     *
     * @var string
     */
    protected $_binary;

    /**
     * Constructor.
     *
     * @param string $binary  Binary location.
     */
    public function __construct($binary)
    {
        parent::__construct();
        $this->_binary = $binary;
    }

    /**
     */
    public function report(array $msgs, $action)
    {
        foreach ($msgs as $val) {
            if (!$this->_report($val)) {
                return 0;
            }
        }

        return count($msgs);
    }

    /**
     * Reports a single spam message.
     *
     * @param string $message  Message content.
     *
     * @return boolean  False on error, true on success.
     */
    protected function _report($message)
    {
        /* Use a pipe to write the message contents. This should be secure. */
        $proc = proc_open(
            $this->_binary,
            array(
                0 => array('pipe', 'r'),
                1 => array('pipe', 'w'),
                2 => array('pipe', 'w')
            ),
            $pipes
        );
        if (!is_resource($proc)) {
            $this->_logger->err(
                sprintf('Cannot open spam reporting program: %s', $proc)
            );
            return false;
        }

        if (is_resource($message)) {
            rewind($message);
            stream_copy_to_stream($message, $pipes[0]);
        } else {
            fwrite($pipes[0], $message);
        }
        fclose($pipes[0]);

        $stderr = '';
        while (!feof($pipes[2])) {
            $stderr .= fgets($pipes[2]);
        }
        fclose($pipes[2]);
        proc_close($proc);

        if (!empty($stderr)) {
            $this->_logger->err(sprintf('Error reporting spam: %s', $stderr));
            return false;
        }

        return true;
    }
}
