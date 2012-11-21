<?php
/**
 * Implementation for configuration objects in the Kolab XML format (KEP:9 and KEP:16)
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Format
 * @author   Aleksander Machniak <machniak@kolabsys.com>
 * @author   Thomas Bruederli <bruederli@kolabsys.com>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://www.horde.org/libraries/Horde_Kolab_Format
 */

/**
 * Kolab XML handler for client preferences.
 *
 * Copyright (C) 2012, Kolab Systems AG <contact@kolabsys.com>
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_Format
 * @author   Aleksander Machniak <machniak@kolabsys.com>
 * @author   Thomas Bruederli <bruederli@kolabsys.com>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://www.horde.org/libraries/Horde_Kolab_Format
 */
class Horde_Kolab_Format_Xml_Configuration extends Horde_Kolab_Format_Xml
{
    /**
     * The name of the root element.
     *
     * @var string
     */
    protected $_root_name = 'configuration';

    /**
     * Specific data fields for the prefs object
     *
     * @var Kolab
     */
    protected $_fields_specific = array(
        'application' => 'Horde_Kolab_Format_Xml_Type_String_MaybeMissing',
        'type' => 'Horde_Kolab_Format_Xml_Type_String',
    );

    function __construct(
        Horde_Kolab_Format_Xml_Parser $parser,
        Horde_Kolab_Format_Factory $factory,
        $params = null
    )
    {
        // Dictionary fields
        if (!empty($params['subtype']) && preg_match('/^dictionary.*/', $params['subtype'])) {
            $this->_fields_specific += array(
                'language' => 'Horde_Kolab_Format_Xml_Type_String',
                'e' => 'Horde_Kolab_Format_Xml_Type_Multiple_String',
            );
        }

        parent::__construct($parser, $factory, $params);
    }
}
