<?php
/**
 * A base decorator definition.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Format
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://www.horde.org/libraries/Horde_Kolab_Format
 */

/**
 * A base decorator definition.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_Format
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://www.horde.org/libraries/Horde_Kolab_Format
 */
abstract class Horde_Kolab_Format_Decorator_Base
implements Horde_Kolab_Format
{
    /**
     * The decorated Kolab format handler.
     *
     * @var Horde_Kolab_Format
     */
    private $_handler;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_Format $handler The handler to be decorated.
     */
    public function __construct(Horde_Kolab_Format $handler)
    {
        $this->_handler = $handler;
    }

    /**
     * Return the decorated handler.
     *
     * @return Horde_Kolab_Format The handler.
     */
    public function getHandler()
    {
        return $this->_handler;
    }
}