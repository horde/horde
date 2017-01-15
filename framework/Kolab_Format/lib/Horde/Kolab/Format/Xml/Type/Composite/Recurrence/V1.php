<?php
/**
 * Handles  api version 1 of recurrence data.
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
 * Handles  api version 1 of recurrence data.
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
class Horde_Kolab_Format_Xml_Type_Composite_Recurrence_V1
extends Horde_Kolab_Format_Xml_Type_Composite_Recurrence
{
    protected $elements = array(
        'interval'  => 'Horde_Kolab_Format_Xml_Type_RecurrenceInterval',
        'day'       => 'Horde_Kolab_Format_Xml_Type_Multiple_String',
        'daynumber' => 'Horde_Kolab_Format_Xml_Type_Integer',
        'month'     => 'Horde_Kolab_Format_Xml_Type_String_MaybeMissing',
        'range'     => 'Horde_Kolab_Format_Xml_Type_RecurrenceRange',
        'exclusion' => 'Horde_Kolab_Format_Xml_Type_Multiple_Date',
        'complete'  => 'Horde_Kolab_Format_Xml_Type_Multiple_Date',
    );
}
