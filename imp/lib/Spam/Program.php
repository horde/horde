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
 * Spam reporting driver utilizing a local binary.
 *
 * Copyright 2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Spam_Program implements IMP_Spam_Base
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
        $this->_binary = $binary;
    }

    /**
     */
    public function report(IMP_Contents $contents)
    {
        /* Use a pipe to write the message contents. This should be
         * secure. */
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
            Horde::log(sprintf('Cannot open spam reporting program: %s', $proc), 'ERR');
            return false;
        }

        stream_copy_to_stream($contents->fullMessageText(array(
            'stream' => true
        )), $pipes[0]);

        fclose($pipes[0]);

        $stderr = '';
        while (!feof($pipes[2])) {
            $stderr .= fgets($pipes[2]);
        }
        fclose($pipes[2]);
        if (!empty($stderr)) {
            Horde::log(sprintf('Error reporting spam: %s', $stderr), 'ERR');
        }

        proc_close($proc);

        return true;
    }

}
