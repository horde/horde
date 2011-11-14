<?php
/**
 * Facebook as recipient.
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
 * Facebook as recipient.
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
class Horde_Push_Recipient_Facebook
extends Horde_Push_Recipient_Base
{
    /**
     * The facebook client.
     *
     * @var Horde_Service_Facebook
     */
    private $_facebook;

    /**
     * Constructor.
     *
     * @param Horde_Service_Facebook $facebook The facebook client.
     */
    public function __construct(Horde_Service_Facebook $facebook)
    {
        $this->_facebook = $facebook;
    }

    /**
     * Push content to the recipient.
     *
     * @param Horde_Push $content The content element.
     * @param array      $options Additional options.
     *
     * @return string The result description.
     */
    public function push(Horde_Push $content, $options = array())
    {
        $text = $content->getSummary();
        if (empty($options['pretend'])) {
            $streams = new Horde_Service_Facebook_Streams($this->_facebook);
            $streams->publish($text);
            return 'Pushed to facebook stream.';
        } else {
            return sprintf(
                'Would push "%s" to the facebook stream.', $text
            );
        }
    }
}
