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
 * @package   Mime
 */
/**
 * This class represents the Thread-Topic header value (RFC 5322).
 *
 * @author    Klaus Leithoff <mail@leithoff.net>
 * @category  Horde
 * @copyright 2014-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Mime
 * @since     2.5.0
 */
class Horde_Mime_Headers_ThreadTopic
extends Horde_Mime_Headers_Element_Single
{
    /**
     */
    public function __construct($name, $value)
    {
        parent::__construct('Thread-Topic', $value);
    }
    /**
     */
    protected function _sendEncode($opts)
    {
        return array(Horde_Mime::encode($this->value, $opts['charset']));
    }
    /**
     */
    public static function getHandles()
    {
        return array(
            // Mail: RFC 5322
            'thread-topic'
        );
    }
}
