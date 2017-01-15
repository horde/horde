<?php
/**
 * Handles a string attribute that defaults to an empty string.
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
 * Handles a string attribute that defaults to an empty string.
 *
 * Copyright 2011-2017 Horde LLC (http://www.horde.org/)
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
class Horde_Kolab_Format_Xml_Type_String_Empty
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
    protected $default = '';
}
