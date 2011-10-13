<?php
/**
 * Twitter as recipient.
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
 * Twitter as recipient.
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
class Horde_Push_Recipient_Twitter
implements Horde_Push_Recipient
{
    /**
     * The twitter client.
     *
     * @var Horde_Service_Twitter
     */
    private $_twitter;

    /**
     * Constructor.
     *
     * @param Horde_Service_Twitter $twitter The twitter client.
     */
    public function __construct(Horde_Service_Twitter $twitter)
    {
        $this->_twitter = $twitter;
    }

    /**
     * Push content to the recipient.
     *
     * @param Horde_Push $content The content element.
     *
     * @return NULL
     */
    public function push(Horde_Push $content)
    {
        //@todo This is the trivial implementation. There may be no summary, it may be too long, etc.
        $this->_twitter->statuses->update($content->getSummary());
    }
}
