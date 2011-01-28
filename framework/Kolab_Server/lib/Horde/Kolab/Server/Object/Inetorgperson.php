<?php
/**
 * A bsaic object representation.
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
 * This class provides basic methods common to all Kolab server objects.
 *
 * Copyright 2008-2011 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_Server_Object_Inetorgperson extends Horde_Kolab_Server_Object_Organizationalperson
{
    /** The specific object class of this object type */
    const OBJECTCLASS_INETORGPERSON = 'inetOrgPerson';

    /**
     * The attributes defined for this class.
     *
     * @var array
     */
    static public $attributes = array(
        'uid', 'mail','Firstnamelastname',
/*         'Organization', 'Businesscategory', 'Homephone', 'Mobile', */
/*         'Photo', 'Jpegphoto', 'Givenname', 'Middlenames', */
/*         'Homepostaladdress', 'Labeleduri', 'Lastnamefirstname', */
/*         'Usersmimecertificate' */
    );

    /**
     * A structure to initialize the attribute structure for this class.
     *
     * @var array
     */
/*     static public $init_attributes = array( */
/*         'defined' => array( */
/*             self::ATTRIBUTE_SID, */
/*             self::ATTRIBUTE_GIVENNAME, */
/*             self::ATTRIBUTE_LABELEDURI, */
/*             self::ATTRIBUTE_HOMEPOSTALADDRESS, */
/*             self::ATTRIBUTE_ORGANIZATION, */
/*             self::ATTRIBUTE_BUSINESSCATEGORY, */
/*             self::ATTRIBUTE_HOMEPHONE, */
/*             self::ATTRIBUTE_MOBILE, */
/*             self::ATTRIBUTE_PHOTO, */
/*             self::ATTRIBUTE_JPEGPHOTO, */
/*             self::ATTRIBUTE_SMIMECERTIFICATE, */
/*         ), */
/*         'derived' => array( */
/*             self::ATTRARRAY_HOMEPOSTALADDRESS => array( */
/*                 'base' => array( */
/*                     self::ATTRIBUTE_HOMEPOSTALADDRESS, */
/*                     self::ATTRIBUTE_GIVENNAME, */
/*                     self::ATTRIBUTE_SN */
/*                 ), */
/*                 'method' => 'getHomePostalAddressHash', */
/*             ), */
/*             self::ATTRARRAY_LABELEDURI => array( */
/*                 'base' => array( */
/*                     self::ATTRIBUTE_LABELEDURI, */
/*                 ), */
/*                 'method' => 'getLabeledUriHash', */
/*             ), */
/*             self::ATTRIBUTE_GIVENNAME => array( */
/*                 'base' => array( */
/*                     self::ATTRIBUTE_GIVENNAME, */
/*                 ), */
/*                 'method' => 'getField', */
/*                 'args' => array( */
/*                     self::ATTRIBUTE_GIVENNAME, */
/*                     0, */
/*                     ' ' */
/*                 ), */
/*             ), */
/*             self::ATTRIBUTE_MIDDLENAMES => array( */
/*                 'base' => array( */
/*                     self::ATTRIBUTE_GIVENNAME, */
/*                 ), */
/*                 'method' => 'getField', */
/*                 'args' => array( */
/*                     self::ATTRIBUTE_GIVENNAME, */
/*                     1, */
/*                     ' ', */
/*                     2 */
/*                 ), */
/*             ), */
/*             self::ATTRIBUTE_FNLN => array( */
/*                 'base' => array( */
/*                     self::ATTRIBUTE_GIVENNAME, */
/*                     self::ATTRIBUTE_SN */
/*                 ), */
/*                 'method' => 'getFnLn', */
/*             ), */
/*             self::ATTRIBUTE_LNFN => array( */
/*                 'base' => array( */
/*                     self::ATTRIBUTE_GIVENNAME, */
/*                     self::ATTRIBUTE_SN */
/*                 ), */
/*                 'method' => 'getLnFn', */
/*             ), */
/*         ), */
/*         'collapsed' => array( */
/*             self::ATTRIBUTE_GIVENNAME => array( */
/*                 'base' => array( */
/*                     self::ATTRIBUTE_GIVENNAME, */
/*                     self::ATTRIBUTE_MIDDLENAMES, */
/*                 ), */
/*                 'method' => 'setField', */
/*                 'args' => array( */
/*                     ' ', */
/*                 ), */
/*             ), */
/*             self::ATTRIBUTE_LABELEDURI => array( */
/*                 'base' => array( */
/*                     self::ATTRARRAY_LABELEDURI, */
/*                 ), */
/*                 'method' => 'setLabeledUriHash', */
/*             ), */
/*             self::ATTRIBUTE_HOMEPOSTALADDRESS => array( */
/*                 'base' => array( */
/*                     self::ATTRARRAY_HOMEPOSTALADDRESS, */
/*                 ), */
/*                 'method' => 'setHomePostalAddressHash', */
/*             ), */
/*         ), */
/*         'locked' => array( */
/*             self::ATTRIBUTE_MAIL, */
/*         ), */
/*         'object_classes' => array( */
/*             self::OBJECTCLASS_INETORGPERSON, */
/*         ), */
/*     ); */

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
        $criteria = array('AND' => array(array('field' => self::ATTRIBUTE_OC,
                                               'op'    => '=',
                                               'test'  => self::OBJECTCLASS_INETORGPERSON),
                          ),
        );
        return $criteria;
    }

    /**
     * Get the name of this Object as "Firstname Lastname".
     *
     * @return string The name.
     */
    protected function getFnLn()
    {
        $gn = $this->get(self::ATTRIBUTE_GIVENNAME, true);
        $sn = $this->get(self::ATTRIBUTE_SN, true);
        return sprintf('%s %s', $gn, $sn);
    }

    /**
     * Get the name of this Object as "Lastname, Firstname".
     *
     * @return string The name.
     */
    protected function getLnFn()
    {
        $gn = $this->get(self::ATTRIBUTE_GIVENNAME, true);
        $sn = $this->get(self::ATTRIBUTE_SN, true);
        return sprintf('%s, %s', $sn, $gn);
    }

    /**
     * Return a hash of URIs. The keys of the hash are the labels.
     *
     * @return array The URIs.
     */
    protected function getLabeledUriHash()
    {
        $result = array();
        $uris   = $this->get(self::ATTRIBUTE_LABELEDURI, false);
        if (empty($uris)) {
            return array();
        }
        if (!is_array($uris)) {
            $uris = array($uris);
        }
        foreach ($uris as $uri) {
            list($address, $label) = explode(' ', $uri, 2);
            if (!isset($result[$label])) {
                $result[$label] = array($address);
            } else {
                $result[$label][] = $address;
            }
        }
        return $result;
    }

    /**
     * Store a hash of URIs. The keys of the hash are the labels.
     *
     * @param string $key        The attribute to collapse into.
     * @param array  $attributes The attributes to collapse.
     * @param array  &$info      The information currently working on.
     *
     * @return NULL
     */
    protected function setLabeledUriHash($key, $attributes, &$info)
    {
        $result = array();
        $uris   = $info[self::ATTRARRAY_LABELEDURI];
        foreach ($uris as $label => $addresses) {
            if (!is_array($addresses)) {
                $addresses = array($addresses);
            }
            foreach ($addresses as $address) {
                $result[] = $address . ' ' . $label;
            }
        }
        $info[self::ATTRIBUTE_LABELEDURI] = $result;
        unset($info[self::ATTRARRAY_LABELEDURI]);
    }

    /**
     * Get home postal addresses as an array.
     *
     * @return array The home addressses.
     */
    protected function getHomePostalAddressHash()
    {
        $result    = array();
        $addresses = $this->get(self::ATTRIBUTE_HOMEPOSTALADDRESS);
        if (empty($addresses)) {
            return $addresses;
        }
        if (!is_array($addresses)) {
            $addresses = array($addresses);
        }
        foreach ($addresses as $address) {
            list($name_segment, $street_segment,
                 $postal_address, $postal_code, $city) = sscanf('%s$%s$%s$%s %s', $address);
            if ($name_segment == "Post office box") {
                $result[] = array(
                    self::ATTRIBUTE_POSTOFFICEBOX => $street_segment,
                    self::ATTRIBUTE_POSTALADDRESS => $postal_address,
                    self::ATTRIBUTE_POSTALCODE => $postal_code,
                    self::ATTRIBUTE_CITY => $city
                );
            } else {
                $result[] = array(
                    self::ATTRIBUTE_STREET => $street_segment,
                    self::ATTRIBUTE_POSTALADDRESS => $postal_address,
                    self::ATTRIBUTE_POSTALCODE => $postal_code,
                    self::ATTRIBUTE_CITY => $city
                );
            }
        }
        return $result;
    }

    /**
     * Store home postal addresses provided as array.
     *
     * @param string $key        The attribute to collapse into.
     * @param array  $attributes The attributes to collapse.
     * @param array  &$info      The information currently working on.
     *
     * @return NULL
     */
    protected function setHomePostalAddressHash($key, $attributes, &$info)
    {
        $result         = array();
        $db_postal_data = array();
        $db_elements    = array(self::ATTRIBUTE_GIVENNAME,
                                self::ATTRIBUTE_SN);
        foreach ($db_elements as $attribute) {
            if (!empty($info[$attribute])) {
                if (is_array($info[$attribute])) {
                    $new = $info[$attribute][0];
                } else {
                    $new = $info[$attribute];
                }
                $db_postal_data[$attribute] = $this->quote($new);
            } else {
                $old = $this->_get($attribute, true);
                if (!empty($old)) {
                    $db_postal_data[$attribute] = $this->quote($old);
                } else {
                    $db_postal_data[$attribute] = '';
                }
            }
        }
        $elements = array(self::ATTRIBUTE_STREET,
                          self::ATTRIBUTE_POSTOFFICEBOX,
                          self::ATTRIBUTE_POSTALADDRESS,
                          self::ATTRIBUTE_POSTALCODE,
                          self::ATTRIBUTE_CITY);
        if (!empty($info[self::ATTRARRAY_HOMEPOSTALADDRESS])) {
            $addresses = $info[self::ATTRARRAY_HOMEPOSTALADDRESS];
        } else {
            $addresses = $this->get(self::ATTRARRAY_HOMEPOSTALADDRESS);
        }
        foreach ($addresses as $address) {
            $postal_data = array();
            foreach ($elements as $element) {
                if (isset($address[$element])) {
                    $postal_data[$element] = $this->quote($address[$element]);
                } else {
                    $postal_data[$element] = '';
                }
            }
            if (!empty($postal_data[self::ATTRIBUTE_STREET])) {
                $postal_data['street_segment'] = $postal_data[self::ATTRIBUTE_STREET];
                $postal_data['name_segment']   = $db_postal_data[self::ATTRIBUTE_GIVENNAME] . ' ' . $db_postal_data[self::ATTRIBUTE_SN];
            } else {
                $postal_data['street_segment'] = $postal_data[self::ATTRIBUTE_POSTOFFICEBOX];
                $postal_data['name_segment']   = "Post office box";
            }
            $result[] = sprintf('%s$%s$%s$%s %s',
                                $postal_data['name_segment'],
                                $postal_data['street_segment'],
                                $postal_data[self::ATTRIBUTE_POSTALADDRESS],
                                $postal_data[self::ATTRIBUTE_POSTALCODE],
                                $postal_data[self::ATTRIBUTE_CITY]);
        }
        $info[self::ATTRIBUTE_HOMEPOSTALADDRESS] = $result;
        unset($info[self::ATTRARRAY_HOMEPOSTALADDRESS]);
    }

    /**
     * Generates an ID for the given information.
     *
     * @param array $info The data of the object.
     *
     * @static
     *
     * @return string|PEAR_Error The ID.
     */
    public function generateId(array &$info)
    {
        if ($this->exists()) {
            if (!isset($info[self::ATTRIBUTE_GIVENNAME])
                && !isset($info[self::ATTRIBUTE_SN])) {
                return false;
            }
            if (!isset($info[self::ATTRIBUTE_GIVENNAME])) {
                $info[self::ATTRIBUTE_GIVENNAME] = $this->get(self::ATTRIBUTE_GIVENNAME);
            }
            if (!isset($info[self::ATTRIBUTE_SN])) {
                $info[self::ATTRIBUTE_SN] = $this->get(self::ATTRIBUTE_SN);
            }
        }

        $id_mapfields = array(self::ATTRIBUTE_GIVENNAME,
                              self::ATTRIBUTE_SN);
        $id_format    = self::ATTRIBUTE_CN . '=' . '%s %s';

        $fieldarray = array();
        foreach ($id_mapfields as $mapfield) {
            if (isset($info[$mapfield])) {
                $id = $info[$mapfield];
                if (is_array($id)) {
                    $id = $id[0];
                }
                $fieldarray[] = $this->server->structure->quoteForUid($id);
            } else {
                $fieldarray[] = '';
            }
        }

        return trim(vsprintf($id_format, $fieldarray), " \t\n\r\0\x0B,");
    }

}