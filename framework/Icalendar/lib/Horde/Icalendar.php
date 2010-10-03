<?php
/**
 * @category Horde
 * @package  Icalendar
 */

/**
 * Class representing iCalendar files.
 *
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Mike Cochrane <mike@graftonhall.co.nz>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Icalendar
 */
class Horde_Icalendar
{
    /**
     * The component type of this class.
     *
     * @var string
     */
    public $type = 'vcalendar';

    /**
     * The parent (containing) iCalendar object.
     *
     * @var Horde_Icalendar
     */
    protected $_container = false;

    /**
     * The name/value pairs of attributes for this object (UID,
     * DTSTART, etc.). Which are present depends on the object and on
     * what kind of component it is.
     *
     * @var array
     */
    protected $_attributes = array();

    /**
     * Any children (contained) iCalendar components of this object.
     *
     * @var array
     */
    protected $_components = array();

    /**
     * According to RFC 2425, we should always use CRLF-terminated lines.
     *
     * @var string
     */
    protected $_newline = "\r\n";

    /**
     * iCalendar format version (different behavior for 1.0 and 2.0 especially
     * with recurring events).
     *
     * @var string
     */
    protected $version;

    /**
     * Whether entry is vcalendar 1.0, vcard 2.1 or vnote 1.1.
     *
     * These 'old' formats are defined by www.imc.org. The 'new' (non-old)
     * formats icalendar 2.0 and vcard 3.0 are defined in rfc2426 and rfc2445
     * respectively.
     */
    protected $oldFormat = true;

    /**
     * Constructor.
     *
     * @var string $version  Version.
     */
    public function __construct($version = '2.0')
    {
        $this->setAttribute('VERSION', $version);
    }

    /**
     * Return a reference to a new component.
     *
     * @param string $type                The type of component to return
     * @param Horde_Icalendar $container  A container that this component
     *                                    will be associated with.
     *
     * @return object  Reference to a Horde_Icalendar_* object as specified.
     */
    static public function newComponent($type, $container)
    {
        $type = Horde_String::lower($type);
        $class = __CLASS__ . '_' . Horde_String::ucfirst($type);

        if (class_exists($class)) {
            $component = new $class();
            if ($container !== false) {
                $component->_container = $container;
                // Use version of container, not default set by component
                // constructor.
                $component->setVersion($container->version);
            }
        } else {
            // Should return an dummy x-unknown type class here.
            $component = false;
        }

        return $component;
    }

    /**
     * Sets the version of this component.
     *
     * @see $version
     * @see $oldFormat
     *
     * @param string $version  A float-like version string.
     */
    public function setVersion($version)
    {
        $this->oldFormat = $version < 2;
        $this->version = $version;
    }

    /**
     * Sets the value of an attribute.
     *
     * @param string $name     The name of the attribute.
     * @param string $value    The value of the attribute.
     * @param array $params    Array containing any addition parameters for
     *                         this attribute.
     * @param boolean $append  True to append the attribute, False to replace
     *                         the first matching attribute found.
     * @param array $values    Array representation of $value.  For
     *                         comma/semicolon seperated lists of values.  If
     *                         not set use $value as single array element.
     */
    public function setAttribute($name, $value, $params = array(),
                                 $append = true, $values = false)
    {
        // Make sure we update the internal format version if
        // setAttribute('VERSION', ...) is called.
        if ($name == 'VERSION') {
            $this->setVersion($value);
            if ($this->_container !== false) {
                $this->_container->setVersion($value);
            }
        }

        if (!$values) {
            $values = array($value);
        }
        $found = false;

        if (!$append) {
            foreach (array_keys($this->_attributes) as $key) {
                if ($this->_attributes[$key]['name'] == Horde_String::upper($name)) {
                    $this->_attributes[$key]['params'] = $params;
                    $this->_attributes[$key]['value'] = $value;
                    $this->_attributes[$key]['values'] = $values;
                    $found = true;
                    break;
                }
            }
        }

        if ($append || !$found) {
            $this->_attributes[] = array(
                'name'      => Horde_String::upper($name),
                'params'    => $params,
                'value'     => $value,
                'values'    => $values
            );
        }
    }

    /**
     * Sets parameter(s) for an (already existing) attribute.  The
     * parameter set is merged into the existing set.
     *
     * @param string $name   The name of the attribute.
     * @param array $params  Array containing any additional parameters for
     *                       this attribute.
     *
     * @return boolean  True on success, false if no attribute $name exists.
     */
    public function setParameter($name, $params = array())
    {
        $keys = array_keys($this->_attributes);
        foreach ($keys as $key) {
            if ($this->_attributes[$key]['name'] == $name) {
                $this->_attributes[$key]['params'] = array_merge($this->_attributes[$key]['params'], $params);
                return true;
            }
        }

        return false;
    }

    /**
     * Get the value of an attribute.
     *
     * @param string $name     The name of the attribute.
     * @param boolean $params  Return the parameters for this attribute instead
     *                         of its value.
     *
     * @return mixed (string)  The value of the attribute.
     *               (array)   The parameters for the attribute or
     *                         multiple values for an attribute.
     * @throws Horde_Icalendar_Exception
     */
    public function getAttribute($name, $params = false)
    {
        $result = array();
        foreach ($this->_attributes as $attribute) {
            if ($attribute['name'] == $name) {
                $result[] = $params
                    ? $attribute['params']
                    : $attribute['value'];
            }
        }

        if (!count($result)) {
            throw new Horde_Icalendar_Exception('Attribute "' . $name . '" Not Found');
        } elseif (count($result) == 1 && !$params) {
            return $result[0];
        }

        return $result;
    }

    /**
     * Gets the values of an attribute as an array.  Multiple values
     * are possible due to:
     *
     *  a) multiple occurences of 'name'
     *  b) (unsecapd) comma seperated lists.
     *
     * So for a vcard like "KEY:a,b\nKEY:c" getAttributesValues('KEY')
     * will return array('a', 'b', 'c').
     *
     * @param string $name  The name of the attribute.
     *
     * @return array  Multiple values for an attribute.
     * @throws Horde_Icalendar_Exception
     */
    public function getAttributeValues($name)
    {
        $result = array();
        foreach ($this->_attributes as $attribute) {
            if ($attribute['name'] == $name) {
                $result = array_merge($attribute['values'], $result);
            }
        }

        if (!count($result)) {
            throw new Horde_Icalendar_Exception('Attribute "' . $name . '" Not Found');
        }

        return $result;
    }

    /**
     * Returns the value of an attribute, or a specified default value
     * if the attribute does not exist.
     *
     * @param string $name    The name of the attribute.
     * @param mixed $default  What to return if the attribute specified by
     *                        $name does not exist.
     *
     * @return mixed (string) The value of $name.
     *               (mixed)  $default if $name does not exist.
     */
    public function getAttributeDefault($name, $default = '')
    {
        try {
            return $this->getAttribute($name);
        } catch (Horde_Icalendar_Exception $e) {
            return $default;
        }
    }

    /**
     * Remove all occurences of an attribute.
     *
     * @param string $name  The name of the attribute.
     */
    public function removeAttribute($name)
    {
        foreach (array_keys($this->_attributes) as $key) {
            if ($this->_attributes[$key]['name'] == $name) {
                unset($this->_attributes[$key]);
            }
        }
    }

    /**
     * Get attributes for all tags or for a given tag.
     *
     * @param string $tag  Return attributes for this tag, or all attributes
     *                     if not given.
     *
     * @return array  An array containing all the attributes and their types.
     */
    public function getAllAttributes($tag = false)
    {
        if ($tag === false) {
            return $this->_attributes;
        }

        $result = array();
        foreach ($this->_attributes as $attribute) {
            if ($attribute['name'] == $tag) {
                $result[] = $attribute;
            }
        }

        return $result;
    }

    /**
     * Add a vCalendar component (eg vEvent, vTimezone, etc.).
     *
     * @param mixed  Either a Horde_Icalendar component (subclass) or an array
     *               of them.
     */
    public function addComponent($components)
    {
        if (!is_array($components)) {
            $components = array($components);
        }

        foreach ($components as $component) {
            if ($component instanceof Horde_Icalendar) {
                $component->_container = $this;
                $this->_components[] = $component;
            }
        }
    }

    /**
     * Retrieve all the components.
     *
     * @return array  Array of Horde_Icalendar objects.
     */
    public function getComponents()
    {
        return $this->_components;
    }

    /**
     * TODO
     *
     * @return TODO
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Return the classes (entry types) we have.
     *
     * @return array  Hash with class names Horde_Icalendar_xxx as keys
     *                and number of components of this class as value.
     */
    public function getComponentClasses()
    {
        $r = array();

        foreach ($this->_components as $c) {
            $cn = strtolower(get_class($c));
            if (empty($r[$cn])) {
                $r[$cn] = 1;
            } else {
                ++$r[$cn];
            }
        }

        return $r;
    }

    /**
     * Number of components in this container.
     *
     * @return integer  Number of components in this container.
     */
    public function getComponentCount()
    {
        return count($this->_components);
    }

    /**
     * Retrieve a specific component.
     *
     * @param integer $idx  The index of the object to retrieve.
     *
     * @return mixed  (boolean) False if the index does not exist.
     *                (Horde_Icalendar_*) The requested component.
     */
    public function getComponent($idx)
    {
        return isset($this->_components[$idx])
            ? $this->_components[$idx]
            : false;
    }

    /**
     * Locates the first child component of the specified class, and returns a
     * reference to it.
     *
     * @param string $type  The type of component to find.
     *
     * @return boolean|Horde_Icalendar_*  False if no subcomponent of the
     *                                    specified class exists or the
     *                                    requested component.
     */
    public function findComponent($childclass)
    {
        $childclass = __CLASS__ . '_' . Horde_String::lower($childclass);

        foreach (array_keys($this->_components) as $key) {
            if ($this->_components[$key] instanceof $childclass) {
                return $this->_components[$key];
            }
        }

        return false;
    }

    /**
     * Locates the first matching child component of the specified class, and
     * returns a reference to it.
     *
     * @param string $childclass  The type of component to find.
     * @param string $attribute   This attribute must be set in the component
     *                            for it to match.
     * @param string $value       Optional value that $attribute must match.
     *
     * @return boolean|Horde_Icalendar_*  False if no matching subcomponent
     *                                    of the specified class exists, or
     *                                    the requested component.
     */
    public function findComponentByAttribute($childclass, $attribute,
                                             $value = null)
    {
        $childclass = __CLASS__ . '_' . Horde_String::lower($childclass);

        foreach (array_keys($this->_components) as $key) {
            if ($this->_components[$key] instanceof $childclass) {
                try {
                    $attr = $this->_components[$key]->getAttribute($attribute);
                } catch (Horde_Icalendar_Exception $e) {
                    continue;
                }

                if (is_null($value) || $value == $attr) {
                    return $this->_components[$key];
                }
            }
        }

        return false;
    }

    /**
     * Clears the iCalendar object (resets the components and attributes
     * arrays).
     */
    public function clear()
    {
        $this->_attributes = $this->_components = array();
    }

    /**
     * Export as vCalendar format.
     *
     * @return TODO
     */
    public function exportvCalendar()
    {
        // Default values.
        // TODO: HORDE_VERSION does not exist.
        $requiredAttributes['PRODID'] = '-//The Horde Project//Horde iCalendar Library' . (defined('HORDE_VERSION') ? ', Horde ' . constant('HORDE_VERSION') : '') . '//EN';
        $requiredAttributes['METHOD'] = 'PUBLISH';

        foreach ($requiredAttributes as $name => $default_value) {
            try {
                $this->getAttribute($name);
            } catch (Horde_Icalendar_Exception $e) {
                $this->setAttribute($name, $default_value);
            }
        }

        return $this->_exportvData('VCALENDAR');
    }

    /**
     * Export this entry as a hash array with tag names as keys.
     *
     * @param boolean $paramsInKeys  If false, the operation can be quite
     *                               lossy as the parameters are ignored when
     *                               building the array keys.
     *                               So if you export a vcard with
     *                               LABEL;TYPE=WORK:foo
     *                               LABEL;TYPE=HOME:bar
     *                               the resulting hash contains only one
     *                               label field!
     *                               If set to true, array keys look like
     *                               'LABEL;TYPE=WORK'
     *
     * @return array  A hash array with tag names as keys.
     */
    public function toHash($paramsInKeys = false)
    {
        $hash = array();

        foreach ($this->_attributes as $a)  {
            $k = $a['name'];
            if ($paramsInKeys && is_array($a['params'])) {
                foreach ($a['params'] as $p => $v) {
                    $k .= ";$p=$v";
                }
            }
            $hash[$k] = $a['value'];
        }

        return $hash;
    }

    /**
     * Parses a string containing vCalendar data.
     *
     * @todo This method doesn't work well at all, if $base is VCARD.
     *
     * @param string $text     The data to parse.
     * @param string $base     The type of the base object.
     * @param string $charset  The encoding charset for $text. Defaults to
     *                         utf-8 for new format, iso-8859-1 for old format.
     * @param boolean $clear   If true clears the iCal object before parsing.
     *
     * @return boolean  True on successful import, false otherwise.
     * @throws Horde_Icalendar_Exception
     */
    public function parsevCalendar($text, $base = 'VCALENDAR',
                                   $charset = null, $clear = true)
    {
        if ($clear) {
            $this->clear();
        }

        if (preg_match('/^BEGIN:' . $base . '(.*)^END:' . $base . '/ism', $text, $matches)) {
            $container = true;
            $vCal = $matches[1];
        } else {
            // Text isn't enclosed in BEGIN:VCALENDAR
            // .. END:VCALENDAR. We'll try to parse it anyway.
            $container = false;
            $vCal = $text;
        }
        $vCal = trim($vCal);

        // All subcomponents.
        $matches = null;
        if (preg_match_all('/^BEGIN:(.*)(\r\n|\r|\n)(.*)^END:\1/Uims', $vCal, $matches)) {
            // vTimezone components are processed first. They are
            // needed to process vEvents that may use a TZID.
            foreach ($matches[0] as $key => $data) {
                $type = trim($matches[1][$key]);
                if ($type != 'VTIMEZONE') {
                    continue;
                }
                $component = $this->newComponent($type, $this);
                if ($component === false) {
                    throw new Horde_Icalendar_Exception('Unable to create object for type ' . $type);
                }
                $component->parsevCalendar($data, $type, $charset);

                $this->addComponent($component);

                // Remove from the vCalendar data.
                $vCal = str_replace($data, '', $vCal);
            }

            // Now process the non-vTimezone components.
            foreach ($matches[0] as $key => $data) {
                $type = trim($matches[1][$key]);
                if ($type == 'VTIMEZONE') {
                    continue;
                }
                $component = $this->newComponent($type, $this);
                if ($component === false) {
                    throw new Horde_Icalendar_Exception('Unable to create object for type ' . $type);
                }
                $component->parsevCalendar($data, $type, $charset);

                $this->addComponent($component);

                // Remove from the vCalendar data.
                $vCal = str_replace($data, '', $vCal);
            }
        } elseif (!$container) {
            return false;
        }

        // Unfold "quoted printable" folded lines like:
        //  BODY;ENCODING=QUOTED-PRINTABLE:=
        //  another=20line=
        //  last=20line
        while (preg_match_all('/^([^:]+;\s*(ENCODING=)?QUOTED-PRINTABLE(.*=\r?\n)+(.*[^=])?\r?\n)/mU', $vCal, $matches)) {
            foreach ($matches[1] as $s) {
                $r = preg_replace('/=\r?\n/', '', $s);
                $vCal = str_replace($s, $r, $vCal);
            }
        }

        // Unfold any folded lines.
        $vCal = preg_replace('/[\r\n]+[ \t]/', '', $vCal);

        // Parse the remaining attributes.
        if (preg_match_all('/^((?:[^":]+|(?:"[^"]*")+)*):([^\r\n]*)\r?$/m', $vCal, $matches)) {
            foreach ($matches[0] as $attribute) {
                preg_match('/([^;^:]*)((;(?:[^":]+|(?:"[^"]*")+)*)?):([^\r\n]*)[\r\n]*/', $attribute, $parts);
                $tag = trim(Horde_String::upper($parts[1]));
                $value = $parts[4];
                $params = array();

                // Parse parameters.
                if (!empty($parts[2])) {
                    preg_match_all('/;(([^;=]*)(=("[^"]*"|[^;]*))?)/', $parts[2], $param_parts);
                    foreach ($param_parts[2] as $key => $paramName) {
                        $paramName = Horde_String::upper($paramName);
                        $paramValue = $param_parts[4][$key];
                        if ($paramName == 'TYPE') {
                            $paramValue = preg_split('/(?<!\\\\),/', $paramValue);
                            if (count($paramValue) == 1) {
                                $paramValue = $paramValue[0];
                            }
                        }
                        if (is_string($paramValue)) {
                            if (preg_match('/"([^"]*)"/', $paramValue, $parts)) {
                                $paramValue = $parts[1];
                            }
                        } else {
                            foreach ($paramValue as $k => $tmp) {
                                if (preg_match('/"([^"]*)"/', $tmp, $parts)) {
                                    $paramValue[$k] = $parts[1];
                                }
                            }
                        }
                        $params[$paramName] = $paramValue;
                    }
                }

                // Charset and encoding handling.
                if ((isset($params['ENCODING']) &&
                     Horde_String::upper($params['ENCODING']) == 'QUOTED-PRINTABLE') ||
                    isset($params['QUOTED-PRINTABLE'])) {

                    $value = quoted_printable_decode($value);
                    if (isset($params['CHARSET'])) {
                        $value = Horde_String::convertCharset($value, $params['CHARSET']);
                    } else {
                        $value = Horde_String::convertCharset($value, empty($charset) ? ($this->oldFormat ? 'iso-8859-1' : 'utf-8') : $charset);
                    }
                } elseif (isset($params['CHARSET'])) {
                    $value = Horde_String::convertCharset($value, $params['CHARSET']);
                } else {
                    // As per RFC 2279, assume UTF8 if we don't have an
                    // explicit charset parameter.
                    $value = Horde_String::convertCharset($value, empty($charset) ? ($this->oldFormat ? 'iso-8859-1' : 'utf-8') : $charset);
                }

                // Get timezone info for date fields from $params.
                $tzid = isset($params['TZID']) ? trim($params['TZID'], '\"') : false;

                switch ($tag) {
                // Date fields.
                case 'COMPLETED':
                case 'CREATED':
                case 'LAST-MODIFIED':
                case 'X-MOZ-LASTACK':
                case 'X-MOZ-SNOOZE-TIME':
                    $this->setAttribute($tag, $this->_parseDateTime($value, $tzid), $params);
                    break;

                case 'BDAY':
                case 'X-ANNIVERSARY':
                    $this->setAttribute($tag, $this->_parseDate($value), $params);
                    break;

                case 'DTEND':
                case 'DTSTART':
                case 'DTSTAMP':
                case 'DUE':
                case 'AALARM':
                case 'RECURRENCE-ID':
                    // types like AALARM may contain additional data after a ;
                    // ignore these.
                    $ts = explode(';', $value);
                    if (isset($params['VALUE']) && $params['VALUE'] == 'DATE') {
                        $this->setAttribute($tag, $this->_parseDate($ts[0]), $params);
                    } else {
                        $this->setAttribute($tag, $this->_parseDateTime($ts[0], $tzid), $params);
                    }
                    break;

                case 'TRIGGER':
                    if (isset($params['VALUE']) &&
                        $params['VALUE'] == 'DATE-TIME') {
                            $this->setAttribute($tag, $this->_parseDateTime($value, $tzid), $params);
                    } else {
                        $this->setAttribute($tag, $this->_parseDuration($value), $params);
                    }
                    break;

                // Comma seperated dates.
                case 'EXDATE':
                case 'RDATE':
                    $dates = array();
                    preg_match_all('/,([^,]*)/', ',' . $value, $values);

                    foreach ($values[1] as $value) {
                        $stamp = $this->_parseDateTime($value);
                        $dates[] = array('year' => date('Y', $stamp),
                                         'month' => date('m', $stamp),
                                         'mday' => date('d', $stamp));
                    }
                    $this->setAttribute($tag, isset($dates[0]) ? $dates[0] : null, $params, true, $dates);
                    break;

                // Duration fields.
                case 'DURATION':
                    $this->setAttribute($tag, $this->_parseDuration($value), $params);
                    break;

                // Period of time fields.
                case 'FREEBUSY':
                    $periods = array();
                    preg_match_all('/,([^,]*)/', ',' . $value, $values);
                    foreach ($values[1] as $value) {
                        $periods[] = $this->_parsePeriod($value);
                    }

                    $this->setAttribute($tag, isset($periods[0]) ? $periods[0] : null, $params, true, $periods);
                    break;

                // UTC offset fields.
                case 'TZOFFSETFROM':
                case 'TZOFFSETTO':
                    $this->setAttribute($tag, $this->_parseUtcOffset($value), $params);
                    break;

                // Integer fields.
                case 'PERCENT-COMPLETE':
                case 'PRIORITY':
                case 'REPEAT':
                case 'SEQUENCE':
                    $this->setAttribute($tag, intval($value), $params);
                    break;

                // Geo fields.
                case 'GEO':
                    if ($this->oldFormat) {
                        $floats = explode(',', $value);
                        $value = array('latitude' => floatval($floats[1]),
                                       'longitude' => floatval($floats[0]));
                    } else {
                        $floats = explode(';', $value);
                        $value = array('latitude' => floatval($floats[0]),
                                       'longitude' => floatval($floats[1]));
                    }
                    $this->setAttribute($tag, $value, $params);
                    break;

                // Recursion fields.
                case 'EXRULE':
                case 'RRULE':
                    $this->setAttribute($tag, trim($value), $params);
                    break;

                // ADR, ORG and N are lists seperated by unescaped semicolons
                // with a specific number of slots.
                case 'ADR':
                case 'N':
                case 'ORG':
                    $value = trim($value);
                    // As of rfc 2426 2.4.2 semicolon, comma, and colon must
                    // be escaped (comma is unescaped after splitting below).
                    $value = str_replace(array('\\n', '\\N', '\\;', '\\:'),
                                         array($this->_newline, $this->_newline, ';', ':'),
                                         $value);

                    // Split by unescaped semicolons:
                    $values = preg_split('/(?<!\\\\);/', $value);
                    $value = str_replace('\\;', ';', $value);
                    $values = str_replace('\\;', ';', $values);
                    $this->setAttribute($tag, trim($value), $params, true, $values);
                    break;

                // String fields.
                default:
                    if ($this->oldFormat) {
                        // vCalendar 1.0 and vCard 2.1 only escape semicolons
                        // and use unescaped semicolons to create lists.
                        $value = trim($value);
                        // Split by unescaped semicolons:
                        $values = preg_split('/(?<!\\\\);/', $value);
                        $value = str_replace('\\;', ';', $value);
                        $values = str_replace('\\;', ';', $values);
                        $this->setAttribute($tag, trim($value), $params, true, $values);
                    } else {
                        $value = trim($value);
                        // As of rfc 2426 2.4.2 semicolon, comma, and colon
                        // must be escaped (comma is unescaped after splitting
                        // below).
                        $value = str_replace(array('\\n', '\\N', '\\;', '\\:', '\\\\'),
                                             array($this->_newline, $this->_newline, ';', ':', '\\'),
                                             $value);

                        // Split by unescaped commas.
                        $values = preg_split('/(?<!\\\\),/', $value);
                        $value = str_replace('\\,', ',', $value);
                        $values = str_replace('\\,', ',', $values);

                        $this->setAttribute($tag, trim($value), $params, true, $values);
                    }
                    break;
                }
            }
        }

        return true;
    }

    /**
     * Export this component in vCal format.
     *
     * @param string $base  The type of the base object.
     *
     * @return string  vCal format data.
     */
    protected function _exportvData($base = 'VCALENDAR')
    {
        $result = 'BEGIN:' . Horde_String::upper($base) . $this->_newline;

        // VERSION is not allowed for entries enclosed in VCALENDAR/ICALENDAR,
        // as it is part of the enclosing VCALENDAR/ICALENDAR. See rfc2445
        if ($base !== 'VEVENT' && $base !== 'VTODO' && $base !== 'VALARM' &&
            $base !== 'VJOURNAL' && $base !== 'VFREEBUSY') {
            // Ensure that version is the first attribute.
            $result .= 'VERSION:' . $this->version . $this->_newline;
        }
        foreach ($this->_attributes as $attribute) {
            $name = $attribute['name'];
            if ($name == 'VERSION') {
                // Already done.
                continue;
            }

            $params_str = '';
            $params = $attribute['params'];
            if ($params) {
                foreach ($params as $param_name => $param_value) {
                    /* Skip CHARSET for iCalendar 2.0 data, not allowed. */
                    if ($param_name == 'CHARSET' && !$this->oldFormat) {
                        continue;
                    }
                    /* Skip VALUE=DATE for vCalendar 1.0 data, not allowed. */
                    if ($this->oldFormat &&
                        $param_name == 'VALUE' && $param_value == 'DATE') {
                        continue;
                    }

                    if ($param_value === null) {
                        $params_str .= ";$param_name";
                    } else {
                        $len = strlen($param_value);
                        $safe_value = '';
                        $quote = false;
                        for ($i = 0; $i < $len; ++$i) {
                            $ord = ord($param_value[$i]);
                            // Accept only valid characters.
                            if ($ord == 9 || $ord == 32 || $ord == 33 ||
                                ($ord >= 35 && $ord <= 126) ||
                                $ord >= 128) {
                                $safe_value .= $param_value[$i];
                                // Characters above 128 do not need to be
                                // quoted as per RFC2445 but Outlook requires
                                // this.
                                if ($ord == 44 || $ord == 58 || $ord == 59 ||
                                    $ord >= 128) {
                                    $quote = true;
                                }
                            }
                        }
                        if ($quote) {
                            $safe_value = '"' . $safe_value . '"';
                        }
                        $params_str .= ";$param_name=$safe_value";
                    }
                }
            }

            $value = $attribute['value'];
            switch ($name) {
            // Date fields.
            case 'COMPLETED':
            case 'CREATED':
            case 'DCREATED':
            case 'LAST-MODIFIED':
            case 'X-MOZ-LASTACK':
            case 'X-MOZ-SNOOZE-TIME':
                $value = $this->_exportDateTime($value);
                break;

            case 'DTEND':
            case 'DTSTART':
            case 'DTSTAMP':
            case 'DUE':
            case 'AALARM':
            case 'RECURRENCE-ID':
                if (isset($params['VALUE'])) {
                    if ($params['VALUE'] == 'DATE') {
                        // VCALENDAR 1.0 uses T000000 - T235959 for all day events:
                        if ($this->oldFormat && $name == 'DTEND') {
                            $d = new Horde_Date($value);
                            $value = new Horde_Date(array(
                                'year' => $d->year,
                                'month' => $d->month,
                                'mday' => $d->mday - 1));
                            $value->correct();
                            $value = $this->_exportDate($value, '235959');
                        } else {
                            $value = $this->_exportDate($value, '000000');
                        }
                    } else {
                        $value = $this->_exportDateTime($value);
                    }
                } else {
                    $value = $this->_exportDateTime($value);
                }
                break;

            // Comma seperated dates.
            case 'EXDATE':
            case 'RDATE':
                $dates = array();
                foreach ($value as $date) {
                    if (isset($params['VALUE'])) {
                        if ($params['VALUE'] == 'DATE') {
                            $dates[] = $this->_exportDate($date, '000000');
                        } elseif ($params['VALUE'] == 'PERIOD') {
                            $dates[] = $this->_exportPeriod($date);
                        } else {
                            $dates[] = $this->_exportDateTime($date);
                        }
                    } else {
                        $dates[] = $this->_exportDateTime($date);
                    }
                }
                $value = implode(',', $dates);
                break;

            case 'TRIGGER':
                if (isset($params['VALUE'])) {
                    if ($params['VALUE'] == 'DATE-TIME') {
                        $value = $this->_exportDateTime($value);
                    } elseif ($params['VALUE'] == 'DURATION') {
                        $value = $this->_exportDuration($value);
                    }
                } else {
                    $value = $this->_exportDuration($value);
                }
                break;

            // Duration fields.
            case 'DURATION':
                $value = $this->_exportDuration($value);
                break;

            // Period of time fields.
            case 'FREEBUSY':
                $value_str = '';
                foreach ($value as $period) {
                    $value_str .= empty($value_str) ? '' : ',';
                    $value_str .= $this->_exportPeriod($period);
                }
                $value = $value_str;
                break;

            // UTC offset fields.
            case 'TZOFFSETFROM':
            case 'TZOFFSETTO':
                $value = $this->_exportUtcOffset($value);
                break;

            // Integer fields.
            case 'PERCENT-COMPLETE':
            case 'PRIORITY':
            case 'REPEAT':
            case 'SEQUENCE':
                $value = "$value";
                break;

            // Geo fields.
            case 'GEO':
                if ($this->oldFormat) {
                    $value = $value['longitude'] . ',' . $value['latitude'];
                } else {
                    $value = $value['latitude'] . ';' . $value['longitude'];
                }
                break;

            // Recurrence fields.
            case 'EXRULE':
            case 'RRULE':
                break;

            default:
                if ($this->oldFormat) {
                    if (is_array($attribute['values']) &&
                        count($attribute['values']) > 1) {
                        $values = $attribute['values'];
                        if ($name == 'N' || $name == 'ADR' || $name == 'ORG') {
                            $glue = ';';
                        } else {
                            $glue = ',';
                        }
                        $values = str_replace(';', '\\;', $values);
                        $value = implode($glue, $values);
                    } else {
                        /* vcard 2.1 and vcalendar 1.0 escape only
                         * semicolons */
                        $value = str_replace(';', '\\;', $value);
                    }
                    // Text containing newlines or ASCII >= 127 must be BASE64
                    // or QUOTED-PRINTABLE encoded. Currently we use
                    // QUOTED-PRINTABLE as default.
                    if (preg_match("/[^\x20-\x7F]/", $value) &&
                        empty($params['ENCODING']))  {
                        $params['ENCODING'] = 'QUOTED-PRINTABLE';
                        $params_str .= ';ENCODING=QUOTED-PRINTABLE';
                        // Add CHARSET as well. At least the synthesis client
                        // gets confused otherwise
                        if (empty($params['CHARSET'])) {
                            $params['CHARSET'] = 'UTF-8';
                            $params_str .= ';CHARSET=' . $params['CHARSET'];
                        }
                    }
                } else {
                    if (is_array($attribute['values']) &&
                        count($attribute['values'])) {
                        $values = $attribute['values'];
                        if ($name == 'N' || $name == 'ADR' || $name == 'ORG') {
                            $glue = ';';
                        } else {
                            $glue = ',';
                        }
                        // As of rfc 2426 2.5 semicolon and comma must be
                        // escaped.
                        $values = str_replace(array('\\', ';', ','),
                                              array('\\\\', '\\;', '\\,'),
                                              $values);
                        $value = implode($glue, $values);
                    } else {
                        // As of rfc 2426 2.5 semicolon and comma must be
                        // escaped.
                        $value = str_replace(array('\\', ';', ','),
                                             array('\\\\', '\\;', '\\,'),
                                             $value);
                    }
                    $value = preg_replace('/\r?\n/', '\n', $value);
                }
                break;
            }

            $value = str_replace("\r", '', $value);
            if (!empty($params['ENCODING']) &&
                $params['ENCODING'] == 'QUOTED-PRINTABLE' &&
                strlen(trim($value))) {
                $result .= $name . $params_str . ':'
                    . str_replace('=0A', '=0D=0A',
                                  Horde_Mime::quotedPrintableEncode($value))
                    . $this->_newline;
            } else {
                $attr_string = $name . $params_str . ':' . $value;
                if (!$this->oldFormat) {
                    $attr_string = Horde_String::wordwrap($attr_string, 75, $this->_newline . ' ',
                                                    true, 'utf-8', true);
                }
                $result .= $attr_string . $this->_newline;
            }
        }

        foreach ($this->_components as $component) {
            $result .= $component->exportvCalendar();
        }

        return $result . 'END:' . $base . $this->_newline;
    }

    /**
     * Parse a UTC Offset field.
     *
     * @param $text TODO
     *
     * @return TODO
     */
    protected function _parseUtcOffset($text)
    {
        $offset = array();

        if (preg_match('/(\+|-)([0-9]{2})([0-9]{2})([0-9]{2})?/', $text, $timeParts)) {
            $offset['ahead']  = (bool)($timeParts[1] == '+');
            $offset['hour']   = intval($timeParts[2]);
            $offset['minute'] = intval($timeParts[3]);
            if (isset($timeParts[4])) {
                $offset['second'] = intval($timeParts[4]);
            }
            return $offset;
        }

        return false;
    }

    /**
     * Export a UTC Offset field.
     *
     * @param $value TODO
     *
     * @return TODO
     */
    function _exportUtcOffset($value)
    {
        $offset = ($value['ahead'] ? '+' : '-') .
            sprintf('%02d%02d', $value['hour'], $value['minute']);

        if (isset($value['second'])) {
            $offset .= sprintf('%02d', $value['second']);
        }

        return $offset;
    }

    /**
     * Parse a Time Period field.
     *
     * @param $text TODO
     *
     * @return array  TODO
     */
    protected function _parsePeriod($text)
    {
        $periodParts = explode('/', $text);
        $start = $this->_parseDateTime($periodParts[0]);

        if ($duration = $this->_parseDuration($periodParts[1])) {
            return array('start' => $start, 'duration' => $duration);
        } elseif ($end = $this->_parseDateTime($periodParts[1])) {
            return array('start' => $start, 'end' => $end);
        }
    }

    /**
     * Export a Time Period field.
     *
     * @param $value TODO
     *
     * @return TODO
     */
    protected function _exportPeriod($value)
    {
        $period = $this->_exportDateTime($value['start']) . '/';

        return isset($value['duration'])
            ? $period . $this->_exportDuration($value['duration'])
            : $period . $this->_exportDateTime($value['end']);
    }

    /**
     * Grok the TZID and return an offset in seconds from UTC for this
     * date and time.
     *
     * @param $date TODO
     * @param $time TODO
     * @param $tzid TODO
     *
     * @return TODO
     */
    protected function _parseTZID($date, $time, $tzid)
    {
        $vtimezone = $this->_container->findComponentByAttribute('vtimezone', 'TZID', $tzid);
        if (!$vtimezone) {
            return false;
        }

        $change_times = array();
        foreach ($vtimezone->getComponents() as $o) {
            $t = $vtimezone->parseChild($o, $date['year']);
            if ($t !== false) {
                $change_times[] = $t;
            }
        }

        if (!$change_times) {
            return false;
        }

        sort($change_times);

        // Time is arbitrarily based on UTC for comparison.
        $t = @gmmktime($time['hour'], $time['minute'], $time['second'],
                       $date['month'], $date['mday'], $date['year']);

        if ($t < $change_times[0]['time']) {
            return $change_times[0]['from'];
        }

        for ($i = 0, $n = count($change_times); $i < $n - 1; $i++) {
            if (($t >= $change_times[$i]['time']) &&
                ($t < $change_times[$i + 1]['time'])) {
                return $change_times[$i]['to'];
            }
        }

        if ($t >= $change_times[$n - 1]['time']) {
            return $change_times[$n - 1]['to'];
        }

        return false;
    }

    /**
     * Parses a DateTime field and returns a unix timestamp. If the
     * field cannot be parsed then the original text is returned
     * unmodified.
     *
     * @todo This function should be moved to Horde_Date and made public.
     *
     * @param $time TODO
     * @param $tzid TODO
     *
     * @return TODO
     */
    protected function _parseDateTime($text, $tzid = false)
    {
        $dateParts = explode('T', $text);
        if (count($dateParts) != 2 && !empty($text)) {
            // Not a datetime field but may be just a date field.
            if (!preg_match('/^(\d{4})-?(\d{2})-?(\d{2})$/', $text, $match)) {
                // Or not
                return $text;
            }
            $dateParts = array($text, '000000');
        }

        if (!($date = $this->_parseDate($dateParts[0])) ||
            !($time = $this->_parseTime($dateParts[1]))) {
            return $text;
        }

        // Get timezone info for date fields from $tzid and container.
        $tzoffset = ($time['zone'] == 'Local' && $tzid &&
                     ($this->_container instanceof Horde_Icalendar))
                     ? $this->_parseTZID($date, $time, $tzid)
                     : false;
        if ($time['zone'] == 'UTC' || $tzoffset !== false) {
            $result = @gmmktime($time['hour'], $time['minute'], $time['second'],
                                $date['month'], $date['mday'], $date['year']);
            if ($tzoffset) {
                $result -= $tzoffset;
            }
        } else {
            // We don't know the timezone so assume local timezone.
            // FIXME: shouldn't this be based on the user's timezone
            // preference rather than the server's timezone?
            $result = @mktime($time['hour'], $time['minute'], $time['second'],
                              $date['month'], $date['mday'], $date['year']);
        }

        return ($result !== false) ? $result : $text;
    }

    /**
     * Export a DateTime field.
     *
     * @todo A bunch of code calls this function outside this class, so it
     * needs to be marked public for now.
     *
     * @param integer|object|array $value  The time value to export (either a
     *                                     Horde_Date, array, or timestamp).
     *
     * @return string  The string representation of the datetime value.
     */
    public function _exportDateTime($value)
    {
        $date = new Horde_Date($value);
        return $date->toICalendar();
    }

    /**
     * Parses a Time field.
     *
     * @param $text  TODO
     *
     * @return TODO
     */
    protected function _parseTime($text)
    {
        if (!preg_match('/([0-9]{2})([0-9]{2})([0-9]{2})(Z)?/', $text, $timeParts)) {
            return false;
        }

        return array(
            'hour' => $timeParts[1],
            'minute' => $timeParts[2],
            'second' => $timeParts[3],
            'zone' => isset($timeParts[4]) ? 'UTC' : 'Local'
        );
    }

    /**
     * Parses a Date field.
     *
     * @param $text TODO
     *
     * @return array TODO
     */
    protected function _parseDate($text)
    {
        $parts = explode('T', $text);
        if (count($parts) == 2) {
            $text = $parts[0];
        }

        if (!preg_match('/^(\d{4})-?(\d{2})-?(\d{2})$/', $text, $match)) {
            return false;
        }

        return array(
            'year' => $match[1],
            'month' => $match[2],
            'mday' => $match[3]
        );
    }

    /**
     * Exports a date field.
     *
     * @param object|array $value  Date object or hash.
     * @param string $autoconvert  If set, use this as time part to export the
     *                             date as datetime when exporting to Vcalendar
     *                             1.0. Examples: '000000' or '235959'
     *
     * @return TODO
     */
    protected function _exportDate($value, $autoconvert = false)
    {
        if (is_object($value)) {
            $value = array('year' => $value->year, 'month' => $value->month, 'mday' => $value->mday);
        }

        return ($autoconvert !== false && $this->oldFormat)
            ? sprintf('%04d%02d%02dT%s', $value['year'], $value['month'], $value['mday'], $autoconvert)
            : sprintf('%04d%02d%02d', $value['year'], $value['month'], $value['mday']);
    }

    /**
     * Parse a Duration Value field.
     *
     * @param $text TODO
     *
     * @return TODO
     */
    protected function _parseDuration($text)
    {
        if (!preg_match('/([+]?|[-])P(([0-9]+W)|([0-9]+D)|)(T(([0-9]+H)|([0-9]+M)|([0-9]+S))+)?/', trim($text), $durvalue)) {
            return false;
        }

        // Weeks.
        $duration = 7 * 86400 * intval($durvalue[3]);

        if (count($durvalue) > 4) {
            // Days.
            $duration += 86400 * intval($durvalue[4]);
        }

        if (count($durvalue) > 5) {
            // Hours.
            $duration += 3600 * intval($durvalue[7]);

            // Mins.
            if (isset($durvalue[8])) {
                $duration += 60 * intval($durvalue[8]);
            }

            // Secs.
            if (isset($durvalue[9])) {
                $duration += intval($durvalue[9]);
            }
        }

        // Sign.
        if ($durvalue[1] == "-") {
            $duration *= -1;
        }

        return $duration;
    }

    /**
     * Export a duration value.
     *
     * @param $value TODO
     */
    protected function _exportDuration($value)
    {
        $duration = '';
        if ($value < 0) {
            $value *= -1;
            $duration .= '-';
        }
        $duration .= 'P';

        $weeks = floor($value / (7 * 86400));
        $value = $value % (7 * 86400);
        if ($weeks) {
            $duration .= $weeks . 'W';
        }

        $days = floor($value / (86400));
        $value = $value % (86400);
        if ($days) {
            $duration .= $days . 'D';
        }

        if ($value) {
            $duration .= 'T';

            $hours = floor($value / 3600);
            $value = $value % 3600;
            if ($hours) {
                $duration .= $hours . 'H';
            }

            $mins = floor($value / 60);
            $value = $value % 60;
            if ($mins) {
                $duration .= $mins . 'M';
            }

            if ($value) {
                $duration .= $value . 'S';
            }
        }

        return $duration;
    }

}
