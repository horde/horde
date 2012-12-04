<?php
/**
 * Implementation for events in the Kolab XML format.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Format
 * @author   Thomas Jarosch <thomas.jarosch@intra2net.com>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://www.horde.org/libraries/Horde_Kolab_Format
 */

/**
 * Kolab XML handler for event groupware objects.
 *
 * Copyright 2007-2009 Klarälvdalens Datakonsult AB
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_Format
 * @author   Thomas Jarosch <thomas.jarosch@intra2net.com>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://www.horde.org/libraries/Horde_Kolab_Format
 */
class Horde_Kolab_Format_Xml_Event extends Horde_Kolab_Format_Xml
{
    /**
     * The name of the root element.
     *
     * @var string
     */
    protected $_root_name = 'event';

    /**
     * Specific data fields for the prefs object
     *
     * @var Kolab
     */
    protected $_fields_specific = array(
        'summary'      => 'Horde_Kolab_Format_Xml_Type_String_MaybeMissing',
        'location'     => 'Horde_Kolab_Format_Xml_Type_String_MaybeMissing',
        'organizer'    => 'Horde_Kolab_Format_Xml_Type_Composite_SimplePerson',
        'start-date'   => 'Horde_Kolab_Format_Xml_Type_EventDateTime',
        'alarm'        => 'Horde_Kolab_Format_Xml_Type_Integer',
        'recurrence'   => 'Horde_Kolab_Format_Xml_Type_Composite_Recurrence',
        'attendee'     => 'Horde_Kolab_Format_Xml_Type_Multiple_Attendee',
        'show-time-as' => 'Horde_Kolab_Format_Xml_Type_String_MaybeMissing',
        'color-label'  => 'Horde_Kolab_Format_Xml_Type_String_MaybeMissing',
        'end-date'     => 'Horde_Kolab_Format_Xml_Type_EventDateTime',
    );

    /**
     * Convert the data to a XML stream. Strings contained in the data array may
     * only be provided as UTF-8 data.
     *
     * @param array $object  The data array representing the object.
     * @param array $options Additional options when writing the XML.
     * <pre>
     * - previous: The previous XML text (default: empty string)
     * - relaxed: Relaxed error checking (default: false)
     * </pre>
     *
     * @return resource The data as XML stream.
     *
     * @throws Horde_Kolab_Format_Exception If converting the data to XML failed.
     */
    public function save($object, $options = array())
    {
        if (!empty($object['_is_all_day'])) {
            $this->_fields_specific['start-date'] = 'Horde_Kolab_Format_Xml_Type_EventDate';
            $this->_fields_specific['end-date'] = 'Horde_Kolab_Format_Xml_Type_EventDate';
        }
        return parent::save($object, $options);
    }
}
