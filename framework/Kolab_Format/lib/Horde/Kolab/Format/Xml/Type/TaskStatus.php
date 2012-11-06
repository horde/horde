<?php
/**
 * Handles the task status.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Format
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL
 * @link     http://www.horde.org/libraries/Horde_Kolab_Format
 */

/**
 * Handles the task status.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_Format
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL
 * @link     http://www.horde.org/libraries/Horde_Kolab_Format
 */
class Horde_Kolab_Format_Xml_Type_TaskStatus
extends Horde_Kolab_Format_Xml_Type_String
{
    /**
     * Indicate which value type is expected.
     *
     * @var int
     */
    protected $value = Horde_Kolab_Format_Xml::VALUE_DEFAULT;

    /**
     * A default value if required.
     *
     * @var string
     */
    protected $default = 'not-started';

    /**
     * @todo Enum possibilities
     */
}
