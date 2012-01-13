<?php
/**
 * Handles name attributes.
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
 * Handles name attributes.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * http://www.horde.org/licenses/lgpl21.
 *
 * @since Horde_Kolab_Format 1.1.0
 *
 * @category Kolab
 * @package  Kolab_Format
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL
 * @link     http://www.horde.org/libraries/Horde_Kolab_Format
 */
class Horde_Kolab_Format_Xml_Type_Composite_Name
extends Horde_Kolab_Format_Xml_Type_Composite
{
    protected $elements = array(
        'given-name'   => 'Horde_Kolab_Format_Xml_Type_String_MaybeMissing',
        'middle-names' => 'Horde_Kolab_Format_Xml_Type_String_MaybeMissing',
        'last-name'    => 'Horde_Kolab_Format_Xml_Type_String_MaybeMissing',
        'full-name'    => 'Horde_Kolab_Format_Xml_Type_String_MaybeMissing',
        'initials'     => 'Horde_Kolab_Format_Xml_Type_String_MaybeMissing',
        'prefix'       => 'Horde_Kolab_Format_Xml_Type_String_MaybeMissing',
        'suffix'       => 'Horde_Kolab_Format_Xml_Type_String_MaybeMissing',
    );
}
