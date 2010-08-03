<?php
/**
 * Class representing vCard entries.
 *
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Karsten Fourmont <karsten@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Icalendar
 */
class Horde_Icalendar_Vcard extends Horde_Icalendar
{
    // The following were shamelessly yoinked from Contact_Vcard_Build
    // Part numbers for N components.
    const N_FAMILY = 0;
    const N_GIVEN = 1;
    const N_ADDL = 2;
    const N_PREFIX = 3;
    const N_SUFFIX = 4;

    // Part numbers for ADR components.
    const ADR_POB = 0;
    const ADR_EXTEND = 1;
    const ADR_STREET = 2;
    const ADR_LOCALITY = 3;
    const ADR_REGION = 4;
    const ADR_POSTCODE = 5;
    const ADR_COUNTRY = 6;

    // Part numbers for GEO components.
    const GEO_LAT = 0;
    const GEO_LON = 1;

    /**
     * The component type of this class.
     *
     * @var string
     */
    public $type = 'vcard';

    /**
     * Constructor.
     */
    public function __construct($version = '2.1')
    {
        parent::__construct($version);
    }

    /**
     * Sets the version of this component.
     *
     * @see $version
     * @see $oldFormat
     *
     * @param string  A float-like version string.
     */
    public function setVersion($version)
    {
        $this->oldFormat = $version < 3;
        $this->version = $version;
    }

    /**
     * Unlike vevent and vtodo, a vcard is normally not enclosed in an
     * iCalendar container. (BEGIN..END)
     *
     * @return TODO
     */
    public function exportvCalendar()
    {
        $requiredAttributes['VERSION'] = $this->version;
        $requiredAttributes['N'] = ';;;;;;';
        if ($this->version == '3.0') {
            $requiredAttributes['FN'] = '';
        }

        foreach ($requiredAttributes as $name => $default_value) {
            try {
                $this->getAttribute($name);
            } catch (Horde_Icalendar_Exception $e) {
                $this->setAttribute($name, $default_value);
            }
        }

        return $this->_exportvData('VCARD');
    }

    /**
     * Returns the contents of the "N" tag as a printable Name:
     * i.e. converts:
     *
     *   N:Duck;Dagobert;T;Professor;Sen.
     * to
     *   "Professor Dagobert T Duck Sen"
     *
     * @return string  Full name of vcard "N" tag or null if no N tag.
     */
    public function printableName()
    {
        try {
            $name_parts = $this->getAttributeValues('N');
        } catch (Horde_Icalendar_Exception $e) {
            return null;
        }

        $name_arr = array();

        foreach (array(self::N_PREFIX, self::N_GIVEN, self::N_ADDL, self::N_FAMILY, self::N_SUFFIX) as $val) {
            if (!empty($name_parts[$val])) {
                $name_arr[] = $name_parts[$val];
            }
        }

        return implode(' ', $name_arr);
    }

    /**
     * Static function to make a given email address rfc822 compliant.
     *
     * @param string $address  An email address.
     *
     * @return string  The RFC822-formatted email address.
     */
    static function getBareEmail($address)
    {
        return Horde_Mime_Address::bareAddress($address);
    }

}
