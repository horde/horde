<?php
/**
 * Implementation for horde user preferences in the Kolab XML format.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Format
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */

/**
 * Kolab XML handler for client preferences.
 *
 * Copyright 2007-2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_Format
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */
class Horde_Kolab_Format_Xml_Hprefs extends Horde_Kolab_Format_Xml
{
    /**
     * Specific data fields for the prefs object
     *
     * @var Kolab
     */
    protected $_fields_specific;

    /**
     * Automatically create categories if they are missing?
     *
     * @var boolean
     */
    protected $_create_categories = false;

    /**
     * Constructor
     */
    public function __construct($parser, $params = array())
    {
        $this->_root_name = 'h-prefs';

        /** Specific preferences fields, in kolab format specification order
         */
        $this->_fields_specific = array(
            'application' => 'Horde_Kolab_Format_Xml_Type_PrefsApplication',
            'pref' => array(
                'type'    => self::TYPE_MULTIPLE,
                'value'   => self::VALUE_MAYBE_MISSING,
                'array'   => array(
                    'type' => self::TYPE_STRING,
                    'value' => self::VALUE_MAYBE_MISSING,
                ),
            ),
        );

        parent::__construct($parser, $params);
    }
}
