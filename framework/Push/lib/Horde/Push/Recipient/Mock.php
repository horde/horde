<?php
/**
 * A mock recipient.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Push
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://www.horde.org/libraries/Horde_Push
 */

/**
 * A mock recipient.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Push
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://www.horde.org/libraries/Horde_Push
 */
class Horde_Push_Recipient_Mock
extends Horde_Push_Recipient_Base
{
    /**
     * Pushed content elements.
     *
     * @var array
     */
    public $pushed = array();

    /**
     * Push content to the recipient.
     *
     * @param Horde_Push $content The content element.
     * @param array      $options Additional options.
     *
     * @return NULL
     */
    public function push(Horde_Push $content, $options = array())
    {
        $this->pushed[] = $content;
        if (empty($options['pretend'])) {
            return sprintf('Pushed "%s".', $content->getSummary());
        } else {
            return sprintf('Would push "%s".', $content->getSummary());
        }
    }
}
