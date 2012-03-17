<?php
/**
 * @package Kolab_Filter
 */

/**
 * Provides DovecotLDA delivery.
 *
 * Copyright 2008 Intevation GmbH
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author  Sascha Wilde <wilde@intevation.de>
 * @package Kolab_Filter
 */
class Horde_Kolab_Filter_Transport_lda extends Horde_Kolab_Filter_Transport
{
    /**
     * Create the transport handler.
     *
     * @return DovecotLDA The LDA handler.
     */
    function _createTransport()
    {
        require_once __DIR__ . '/DovecotLDA.php';

        $transport = new Dovecot_LDA();

        return $transport;
    }
}
