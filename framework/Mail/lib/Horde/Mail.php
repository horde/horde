<?php
/**
 * Horde's Mail interface.
 *
 * LICENSE:
 *
 * Copyright (c) 2002-2007, Richard Heyes
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 * o Redistributions of source code must retain the above copyright
 *   notice, this list of conditions and the following disclaimer.
 * o Redistributions in binary form must reproduce the above copyright
 *   notice, this list of conditions and the following disclaimer in the
 *   documentation and/or other materials provided with the distribution.
 * o The names of the authors may not be used to endorse or promote
 *   products derived from this software without specific prior written
 *   permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @category  Horde
 * @package   Mail
 * @author    Chuck Hagenbuch <chuck@horde.org>
 * @author    Michael Slusarz <slusarz@horde.org>
 * @copyright 1997-2010 Chuck Hagenbuch
 * @copyright 2010 Michael Slusarz
 * @license   http://opensource.org/licenses/bsd-license.php New BSD License
 */

/**
 * The Mail interface.
 *
 * @category Horde
 * @package  Mail
 */
class Horde_Mail
{
    /**
     * Returns a Horde_Mail_Transport:: object.
     *
     * @param string $transport  The transport to instantiate.
     * @param array $params      The parameters to pass to the transport.
     *
     * @return Horde_Mail_Transport  The transport instance.
     * @throws Horde_Mail_Exception
     * @deprecated
     */
    static public function factory($transport, $params = array())
    {
        $class = 'Horde_Mail_Transport_' . ucfirst($transport);
        if (class_exists($class)) {
            return new $class($params);
        }

        throw new Horde_Mail_Exception('Unable to find class for transport ' . $transport);
    }
}
