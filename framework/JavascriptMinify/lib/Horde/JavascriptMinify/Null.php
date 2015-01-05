<?php
/**
 * Copyright 2014-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2014-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   JavascriptMinify
 */

/**
 * Javascript minification driver that does nothing (returns the unaltered
 * javascript).
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   JavascriptMinify
 */
class Horde_JavascriptMinify_Null extends Horde_JavascriptMinify
{
    /**
     */
    public function minify()
    {
        if (is_string($this->_data)) {
            return $this->_data;
        }

        $out = '';
        foreach ($this->_data as $val) {
            if (!is_readable($val)) {
                throw new Horde_JavascriptMinify_Exception(
                    sprintf('%s does not exist or is not readable.', $val)
                );
            }

            $out .= file_get_contents($val) . "\n";
        }

        return $out;
    }

}
