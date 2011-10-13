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
 * Copyright 2011 Horde LLC (http://www.horde.org/)
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
implements Horde_Push_Recipient
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
     *
     * @return NULL
     */
    public function push(Horde_Push $content)
    {
        $this->pushed[] = $content;
    }
}
