<?php
/**
 * Copyright 2009-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2009-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Code for manipulating/parsing MIME header data.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2009-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Mime_Headers
{
    /**
     * Determines the priority of the message based on the headers.
     *
     * @param Horde_Mime_Headers $header  The headers object.
     *
     * @return string  'high', 'low', or 'normal'.
     */
    public function getPriority($header)
    {
        if (($xpriority = $header['X-Priority']) &&
            (preg_match('/\s*(\d+)\s*/', $xpriority, $matches))) {
            if (in_array($matches[1], array(1, 2))) {
                return 'high';
            } elseif (in_array($matches[1], array(4, 5))) {
                return 'low';
            }
        } elseif (($importance = $header['Importance']) &&
                  preg_match('/:\s*(\w+)\s*/', $importance, $matches)) {
            if (strcasecmp($matches[1], 'high') === 0) {
                return 'high';
            } elseif (strcasecmp($matches[1], 'low') === 0) {
                return 'low';
            }
        }

        return 'normal';
    }

}
