<?php
/**
 * Handles the address type.
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
 * Handles the address type.
 *
 * Copyright 2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html.
 *
 * @since Horde_Kolab_Format 1.1.0
 *
 * @category Kolab
 * @package  Kolab_Format
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL
 * @link     http://www.horde.org/libraries/Horde_Kolab_Format
 */
class Horde_Kolab_Format_Xml_Type_AddressType
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
    protected $default = 'home';

    /**
     * Address types
     *
     * @todo Check enum possibilities
     *
     * @var array
     */
    private $_address_types = array(
        'business',
        'home',
        'other',
    );

}
