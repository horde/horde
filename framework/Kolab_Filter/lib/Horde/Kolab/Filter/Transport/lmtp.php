<?php
/**
 * @package Kolab_Filter
 */

/**
 * Provides LMTP for delivering a mail.
 *
 * Copyright 2004-2008 Klarälvdalens Datakonsult AB
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author  Steffen Hansen <steffen@klaralvdalens-datakonsult.se>
 * @author  Gunnar Wrobel <wrobel@pardus.de>
 * @package Kolab_Filter
 */
class Horde_Kolab_Filter_Transport_lmtp extends Horde_Kolab_Filter_Transport
{
    /**
     * Create the transport handler.
     *
     * @return Net_LMTP The LMTP handler.
     */
    function _createTransport()
    {
        require_once __DIR__ . '/LMTPTLS.php';

        if (!isset($this->_params['host'])) {
            $this->_params['host'] = '127.0.0.1';
        }

        if (!isset($this->_params['port'])) {
            $this->_params['port'] = 2003;
        }

        $transport = new Net_LMTP_TLS($this->_params['host'],
                                      $this->_params['port']);

        return $transport;
    }
}
