<?php
/**
 * An organizational person (objectclass 2.5.6.7).
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */

/**
 * This class provides methods for the organizationalPerson objectclass.
 *
 * Copyright 2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */
class Horde_Kolab_Server_Object_Organizationalperson extends Horde_Kolab_Server_Object_Person
{
    /** Define attributes specific to this object type */

    /** The postal address */
    const ATTRIBUTE_POSTALADDRESS = 'postalAddress';

    /** The raw postal address as stored in the database */
    const ATTRIBUTE_POSTALADDRESSRAW = 'postalAddressRaw';

    /** The job title */
    const ATTRIBUTE_JOBTITLE = 'title';

    /** The street address */
    const ATTRIBUTE_STREET = 'street';

    /** The post office box */
    const ATTRIBUTE_POSTOFFICEBOX = 'postOfficeBox';

    /** The postal code */
    const ATTRIBUTE_POSTALCODE = 'postalCode';

    /** The city */
    const ATTRIBUTE_CITY = 'l';

    /** The fax number */
    const ATTRIBUTE_FAX = 'facsimileTelephoneNumber';

    /** The specific object class of this object type */
    const OBJECTCLASS_ORGANIZATIONALPERSON = 'organizationalPerson';

    /**
     * A structure to initialize the attribute structure for this class.
     *
     * @var array
     */
    static public $init_attributes = array(
        'defined' => array(
            self::ATTRIBUTE_JOBTITLE,
            self::ATTRIBUTE_STREET,
            self::ATTRIBUTE_POSTOFFICEBOX,
            self::ATTRIBUTE_POSTALCODE,
            self::ATTRIBUTE_CITY,
            self::ATTRIBUTE_FAX,
            self::ATTRIBUTE_POSTALADDRESS,
        ),
        'derived' => array(
            self::ATTRIBUTE_POSTALADDRESS => array(
                'base' => array(
                    self::ATTRIBUTE_POSTALADDRESS,
                ),
                'method' => 'getPostalAddress',
            ),
            self::ATTRIBUTE_POSTALADDRESSRAW => array(
                'base' => array(
                    self::ATTRIBUTE_POSTALADDRESS,
                ),
                'method' => '_get',
                'args' => array(
                    self::ATTRIBUTE_POSTALADDRESS,
                ),
            ),
        ),
        'collapsed' => array(
            self::ATTRIBUTE_POSTALADDRESS => array(
                'base' => array(
                    self::ATTRIBUTE_SN,
                    self::ATTRIBUTE_STREET,
                    self::ATTRIBUTE_POSTOFFICEBOX,
                    self::ATTRIBUTE_POSTALADDRESS,
                    self::ATTRIBUTE_POSTALCODE,
                    self::ATTRIBUTE_CITY,
                ),
                'method' => 'setPostalAddress',
            ),
        ),
        'object_classes' => array(
            self::OBJECTCLASS_ORGANIZATIONALPERSON,
        ),
    );

    /**
     * Return the filter string to retrieve this object type.
     *
     * @static
     *
     * @return string The filter to retrieve this object type from the server
     *                database.
     */
    public static function getFilter()
    {
        return '(&(' . self::ATTRIBUTE_OC . '=' . self::OBJECTCLASS_ORGANIZATIONALPERSON . '))';
    }

    /**
     * Get the value for the postal address. This is not the complete postal
     * address but just an additional section you may set to complete the postal
     * address (things like "c/o John Doe").
     *
     * @return string The postal address.
     */
    protected function getPostalAddress()
    {
        $postal = $this->_get(self::ATTRIBUTE_POSTALADDRESS, true);
        if (empty($postal)) {
            return $postal;
        }
        $postal_parts = explode('$', $postal);
        if (isset($postal_parts[1])) {
            return $this->unquote($postal_parts[1]);
        } else {
            return '';
        }
    }

    /**
     * Set the complete postal address.
     *
     * @param string $key        The attribute to collapse into.
     * @param array  $attributes The attributes to collapse.
     * @param array  &$info      The information currently working on.
     *
     * @return NULL.
     */
    protected function setPostalAddress($key, $attributes, &$info)
    {
        $empty       = true;
        $postal_data = array();
        foreach ($attributes as $attribute) {
            if (isset($info[$attribute])) {
                if (is_array($info[$attribute])) {
                    $new = $info[$attribute][0];
                } else {
                    $new = $info[$attribute];
                }
                $postal_data[$attribute] = $this->quote($new);
                $empty                   = false;
            } else {
                $old = $this->get($attribute, true);
                if (!empty($old)) {
                    $postal_data[$attribute] = $old;
                    $empty                   = false;
                } else {
                    $postal_data[$attribute] = '';
                }
            }
        }

        if ($empty === true) {
            return;
        }

        if (!empty($postal_data[self::ATTRIBUTE_POSTOFFICEBOX])) {
            $postal_data['street_segment'] = $postal_data[self::ATTRIBUTE_POSTOFFICEBOX];
        } else {
            $postal_data['street_segment'] = $postal_data[self::ATTRIBUTE_STREET];
        }

        $info[$key] = sprintf('%s$%s$%s$%s %s',
                              $postal_data[self::ATTRIBUTE_SN],
                              $postal_data[self::ATTRIBUTE_POSTALADDRESS],
                              $postal_data['street_segment'],
                              $postal_data[self::ATTRIBUTE_POSTALCODE],
                              $postal_data[self::ATTRIBUTE_CITY]);
    }

}