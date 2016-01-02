<?php
/**
 * Copyright 2014-2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2014-2016 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Smtp
 */

/**
 * An interface to an LMTP server (RFC 2033).
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014-2016 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Smtp
 * @since     1.5.0
 */
class Horde_Smtp_Lmtp extends Horde_Smtp
{
    /**
     * LMTP only supports LHLO hello command (RFC 2033 [4.1]), not EHLO.
     *
     * HELO isn't supported, but HELO code in _hello() should not be reached
     * since failure of LHLO command will result in 500 error code (instead
     * of 502 necessary to fallback to HELO).
     */
    protected $_ehlo = 'LHLO';

    /**
     * These extensions are required for LMTP (RFC 2033 [5]).
     */
    protected $_requiredExts = array(
        'ENHANCEDSTATUSCODES',
        'PIPELINING'
    );

    /**
     */
    public function __construct(array $params = array())
    {
        // LMTP MUST NOT be on port 25 (RFC 2033 [5]).
        if (isset($params['port']) && ($params['port'] == 25)) {
            throw new InvalidArgumentException(
                'Cannot use port 25 for a LMTP server.'
            );
        }

        parent::__construct($params);
    }

    /**
     */
    protected function _processData($recipients)
    {
        /* RFC 2033 [4.2/4.3]: there is one response for each successful
         * recipient, so need to iterate through the array. If no successful
         * recipients found, throw an exception. */
        $out = array();
        $success = false;

        foreach ($recipients as $val) {
            try {
                $this->_getResponse(250);
                $out[$val] = $success = true;
            } catch (Horde_Smtp_Exception $e) {
                $out[$val] = $e;
            }
        }

        if (!$success) {
            $e = new Horde_Smtp_Exception('Sending to all recipients failed.');
            $e->setSmtpCode(550);
            throw $e;
        }

        return $out;
    }

}
