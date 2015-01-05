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
 * @package   Core
 */

/**
 * Hack to prevent the need to access the theme cache on every server access.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014-2015 Horde LLC
 * @internal
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Core
 * @todo      Refactor Horde_Core_Alarm_Handler_Desktop to avoid this
 */
class Horde_Core_Alarm_Handler_Desktop_Icon
{
    /**
     * Icon path.
     *
     * @var string
     */
    protected $_path;

    /**
     * Constructor.
     *
     * @param string $path  Icon path.
     */
    public function __construct($path)
    {
        $this->_path = $path;
    }

    /**
     */
    public function __toString()
    {
        return strval(Horde_Themes::img($this->_path)->fulluri);
    }

}
