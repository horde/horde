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
 * did not receive this file, see http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html.
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
        require_once dirname(__FILE__) . '/DovecotLDA.php';

        $transport = new Dovecot_LDA();

        return $transport;
    }
}
