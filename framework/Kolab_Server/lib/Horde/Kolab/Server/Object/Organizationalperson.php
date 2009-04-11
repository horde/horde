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
            self::ATTRIBUTE_SN => array(
                'base' => self::ATTRIBUTE_POSTALADDRESS,
                'order' => 0,
            ),
            self::ATTRIBUTE_STREET => array(
                'base' => self::ATTRIBUTE_POSTALADDRESS,
                'order' => 1,
            ),
            self::ATTRIBUTE_POSTOFFICEBOX => array(
                'base' => self::ATTRIBUTE_POSTALADDRESS,
                'order' => 2,
            ),
            self::ATTRIBUTE_POSTALCODE => array(
                'base' => self::ATTRIBUTE_POSTALADDRESS,
                'order' => 3,
            ),
            self::ATTRIBUTE_CITY => array(
                'base' => self::ATTRIBUTE_POSTALADDRESS,
                'order' => 4,
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
     * Derive an attribute value.
     *
     * @param string $attr The attribute to derive.
     *
     * @return mixed The value of the attribute.
     */
    protected function derive($attr)
    {
        switch ($attr) {
        case self::ATTRIBUTE_POSTALADDRESS:
            return $this->_get(self::ATTRIBUTE_POSTALADDRESS, true);
        default:
            return parent::derive($attr);
        }
    }

    /**
     * Collapse derived values back into the main attributes.
     *
     * @param string $key        The attribute to collapse into.
     * @param array  $attributes The attribute to collapse.
     * @param array  &$info      The information currently working on.
     * @param string $separator  Separate the fields using this character.
     *
     * @return NULL.
     */
    protected function collapse($key, $attributes, &$info, $separator = '$')
    {
        switch ($key) {
        case self::ATTRIBUTE_POSTALADDRESS:
            $empty      = true;
            $postalData = array();
            foreach ($attributes as $attribute) {
                if (!empty($info[$attribute])) {
                    if (is_array($info[$attribute])) {
                        $new = $info[$attribute][0];
                    } else {
                        $new = $info[$attribute];
                    }
                    $postalData[$attribute] = $this->quote($new);
                    $empty                  = false;
                } else {
                    $old = $this->_get($attribute, true);
                    if (!empty($old)) {
                        $postalData[$attribute] = $this->quote($old);
                        $empty                  = false;
                    } else {
                        $postalData[$attribute] = '';
                    }
                }
            }

            if ($empty === true) {
                return;
            }

            if (!empty($postalData[self::ATTRIBUTE_STREET])) {
                $postalData['street_segment'] = $postalData[self::ATTRIBUTE_STREET];
            } else {
                $postalData['street_segment'] = $postalData[self::ATTRIBUTE_POSTOFFICEBOX];
            }

            $info[self::ATTRIBUTE_POSTALADDRESS] = sprintf('%s$%s$%s %s',
                                                           $this->quote($postalData[self::ATTRIBUTE_SN]),
                                                           $this->quote($postalData['street_segment']),
                                                           $this->quote($postalData[self::ATTRIBUTE_POSTALCODE]),
                                                           $this->quote($postalData[self::ATTRIBUTE_CITY]));
            return;
        default:
            return parent::collapse($key, $attributes, &$info, $separator);
        }
    }
}