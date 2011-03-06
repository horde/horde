<?php
/**
 * The Turba_Driver:: class provides a common abstracted interface to the
 * various directory search drivers.  It includes functions for searching,
 * adding, removing, and modifying directory entries.
 *
 * Copyright 2000-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Jon Parise <jon@csh.rit.edu>
 * @category Horde
 * @license  http://www.horde.org/licenses/asl.php ASL
 * @package  Turba
 */
class Turba_Driver implements Countable
{
    /**
     * The symbolic title of this source.
     *
     * @var string
     */
    public $title;

    /**
     * Hash describing the mapping between Turba attributes and
     * driver-specific fields.
     *
     * @var array
     */
    public $map = array();

    /**
     * Hash with all tabs and their fields.
     *
     * @var array
     */
    public $tabs = array();

    /**
     * List of all fields that can be accessed in the backend (excludes
     * composite attributes, etc.).
     *
     * @var array
     */
    public $fields = array();

    /**
     * Array of fields that must match exactly.
     *
     * @var array
     */
    public $strict = array();

    /**
     * Array of fields to search "approximately" (@see
     * config/backends.php).
     *
     * @var array
     */
    public $approximate = array();

    /**
     * The name of a field to store contact list names in if not the default.
     *
     * @var string
     */
    public $listNameField = null;

    /**
     * The name of a field to use as an alternative to the name field if that
     * one is empty.
     *
     * @var string
     */
    public $alternativeName = null;

    /**
     * The internal name of this source.
     *
     * @var string
     */
    protected $_name;

    /**
     * Hash holding the driver's additional parameters.
     *
     * @var array
     */
    protected $_params = array();

    /**
     * What can this backend do?
     *
     * @var array
     */
    protected $_capabilities = array();

    /**
     * Number of contacts in this source.
     *
     * @var integer
     */
    protected $_count = null;

    /**
     * Hold the value for the owner of this address book.
     *
     * @var string
     */
    protected $_contact_owner = '';

    /**
     * Constructs a new Turba_Driver object.
     *
     * @param string $name   Source name
     * @param array $params  Hash containing additional configuration
     *                       parameters.
     */
    public function __construct($name = '', array $params = array())
    {
        $this->_name = $name;
        $this->_params = $params;
    }

    /**
     * Returns the current driver's additional parameters.
     *
     * @return array  Hash containing the driver's additional parameters.
     */
    public function getParams()
    {
        return $this->_params;
    }

    /**
     * Checks if this backend has a certain capability.
     *
     * @param string $capability  The capability to check for.
     *
     * @return boolean  Supported or not.
     */
    public function hasCapability($capability)
    {
        return !empty($this->_capabilities[$capability]);
    }

    /**
     * Returns the attributes that are blob types.
     *
     * @return array  List of blob attributes in the array keys.
     */
    public function getBlobs()
    {
        global $attributes;

        $blobs = array();
        foreach (array_keys($this->fields) as $attribute) {
            if (isset($attributes[$attribute]) &&
                $attributes[$attribute]['type'] == 'image') {
                $blobs[$attribute] = true;
            }
        }

        return $blobs;
    }

    /**
     * Translates the keys of the first hash from the generalized Turba
     * attributes to the driver-specific fields. The translation is based on
     * the contents of $this->map.
     *
     * @param array $hash  Hash using Turba keys.
     *
     * @return array  Translated version of $hash.
     */
    public function toDriverKeys(array $hash)
    {
        /* Handle category. */
        if (!empty($hash['category'])) {
            if (!empty($hash['category']['new'])) {
                $cManager = new Horde_Prefs_CategoryManager();
                $cManager->add($hash['category']['value']);
            }
            $hash['category'] = $hash['category']['value'];
        }

        // Add composite fields to $hash if at least one field part exists
        // and the composite field will be saved to storage.
        // Otherwise composite fields won't be computed during an import.
        foreach ($this->map as $key => $val) {
            if (!is_array($val) ||
                empty($this->map[$key]['attribute']) ||
                array_key_exists($key, $hash)) {
                continue;
            }

            foreach ($this->map[$key]['fields'] as $mapfields) {
                if (isset($hash[$mapfields])) {
                    // Add composite field
                    $hash[$key] = null;
                    break;
                }
            }
        }

        if (!empty($hash['name']) &&
            !empty($this->listNameField) &&
            !empty($hash['__type']) &&
            is_array($this->map['name']) &&
            ($hash['__type'] == 'Group')) {
            $hash[$this->listNameField] = $hash['name'];
            unset($hash['name']);
        }

        $fields = array();
        foreach ($hash as $key => $val) {
            if (isset($this->map[$key])) {
                if (!is_array($this->map[$key])) {
                    $fields[$this->map[$key]] = $val;
                } elseif (!empty($this->map[$key]['attribute'])) {
                    $fieldarray = array();
                    foreach ($this->map[$key]['fields'] as $mapfields) {
                        $fieldarray[] = isset($hash[$mapfields])
                            ? $hash[$mapfields]
                            : '';
                    }
                    $fields[$this->map[$key]['attribute']] = preg_replace('/\s+/', ' ', trim(vsprintf($this->map[$key]['format'], $fieldarray), " \t\n\r\0\x0B,"));
                } else {
                    // If 'parse' is not specified, use 'format' and 'fields'.
                    if (!isset($this->map[$key]['parse'])) {
                        $this->map[$key]['parse'] = array(
                            array(
                                'format' => $this->map[$key]['format'],
                                'fields' => $this->map[$key]['fields']
                            )
                        );
                    }
                    foreach ($this->map[$key]['parse'] as $parse) {
                        $splitval = sscanf($val, $parse['format']);
                        $count = 0;
                        $tmp_fields = array();
                        foreach ($parse['fields'] as $mapfield) {
                            if (isset($hash[$mapfield])) {
                                // If the compositing fields are set
                                // individually, then don't set them at all.
                                break 2;
                            }
                            $tmp_fields[$this->map[$mapfield]] = $splitval[$count++];
                        }
                        // Exit if we found the best match.
                        if ($splitval[$count - 1] !== null) {
                            break;
                        }
                    }
                    $fields = array_merge($fields, $tmp_fields);
                }
            }
        }

        return $fields;
    }

    /**
     * Takes a hash of Turba key => search value and return a (possibly
     * nested) array, using backend attribute names, that can be turned into a
     * search by the driver. The translation is based on the contents of
     * $this->map, and includes nested OR searches for composite fields.
     *
     * @param array  $criteria      Hash of criteria using Turba keys.
     * @param string $search_type   OR search or AND search?
     * @param array  $strict        Fields that must be matched exactly.
     * @param boolean $match_begin  Whether to match only at beginning of
     *                              words.
     *
     * @return array  An array of search criteria.
     */
    public function makeSearch($criteria, $search_type, array $strict,
                               $match_begin = false)
    {
        $search = $search_terms = $subsearch = $strict_search = array();
        $glue = $temp = '';
        $lastChar = '\"';

        foreach ($criteria as $key => $val) {
            if (isset($this->map[$key])) {
                if (is_array($this->map[$key])) {
                    /* Composite field, break out the search terms. */
                    $parts = explode(' ', $val);
                    if (count($parts) > 1) {
                        /* Only parse if there was more than 1 search term and
                         * 'AND' the cumulative subsearches. */
                        for ($i = 0; $i < count($parts); ++$i) {
                            $term = $parts[$i];
                            $firstChar = substr($term, 0, 1);
                            if ($firstChar == '"') {
                                $temp = substr($term, 1, strlen($term) - 1);
                                $done = false;
                                while (!$done && $i < count($parts) - 1) {
                                    $lastChar = substr($parts[$i + 1], -1);
                                    if ($lastChar == '"') {
                                        $temp .= ' ' . substr($parts[$i + 1], 0, -1);
                                        $done = true;
                                    } else {
                                        $temp .= ' ' . $parts[$i + 1];
                                    }
                                    ++$i;
                                }
                                $search_terms[] = $temp;
                            } else {
                                $search_terms[] = $term;
                            }
                        }
                        $glue = 'AND';
                    } else {
                        /* If only one search term, use original input and
                           'OR' the searces since we're only looking for 1
                           term in any of the composite fields. */
                        $search_terms[0] = $val;
                        $glue = 'OR';
                    }

                    foreach ($this->map[$key]['fields'] as $field) {
                        $field = $this->toDriver($field);
                        if (!empty($strict[$field])) {
                            /* For strict matches, use the original search
                             * vals. */
                            $strict_search[] = array(
                                'field' => $field,
                                'op' => '=',
                                'test' => $val,
                            );
                        } else {
                            /* Create a subsearch for each individual search
                             * term. */
                            if (count($search_terms) > 1) {
                                /* Build the 'OR' search for each search term
                                 * on this field. */
                                $atomsearch = array();
                                for ($i = 0; $i < count($search_terms); ++$i) {
                                    $atomsearch[] = array(
                                        'field' => $field,
                                        'op' => 'LIKE',
                                        'test' => $search_terms[$i],
                                        'begin' => $match_begin,
                                        'approximate' => !empty($this->approximate[$field]),
                                    );
                                }
                                $atomsearch[] = array(
                                    'field' => $field,
                                    'op' => '=',
                                    'test' => '',
                                    'begin' => $match_begin,
                                    'approximate' => !empty($this->approximate[$field])
                                );

                                $subsearch[] = array('OR' => $atomsearch);
                                unset($atomsearch);
                                $glue = 'AND';
                            } else {
                                /* $parts may have more than one element, but
                                 * if they are all quoted we will only have 1
                                 * $subsearch. */
                                $subsearch[] = array(
                                    'field' => $field,
                                    'op' => 'LIKE',
                                    'test' => $search_terms[0],
                                    'begin' => $match_begin,
                                    'approximate' => !empty($this->approximate[$field]),
                                );
                                $glue = 'OR';
                            }
                        }
                    }
                    if (count($subsearch)) {
                        $search[] = array($glue => $subsearch);
                    }
                } else {
                    /* Not a composite field. */
                    if (!empty($strict[$this->map[$key]])) {
                        $strict_search[] = array(
                            'field' => $this->map[$key],
                            'op' => '=',
                            'test' => $val,
                        );
                    } else {
                        $search[] = array(
                            'field' => $this->map[$key],
                            'op' => 'LIKE',
                            'test' => $val,
                            'begin' => $match_begin,
                            'approximate' => !empty($this->approximate[$this->map[$key]]),
                        );
                    }
                }
            }
        }

        if (count($strict_search) && count($search)) {
            return array(
                'AND' => array(
                    $search_type => $strict_search,
                    array(
                        $search_type => $search
                    )
                )
            );
        } elseif (count($strict_search)) {
            return array(
                'AND' => $strict_search
            );
        } elseif (count($search)) {
            return array(
                $search_type => $search
            );
        }

        return array();
    }

    /**
     * Translates a single Turba attribute to the driver-specific
     * counterpart. The translation is based on the contents of
     * $this->map. This ignores composite fields.
     *
     * @param string $attribute  The Turba attribute to translate.
     *
     * @return string  The driver name for this attribute.
     */
    public function toDriver($attribute)
    {
        if (!isset($this->map[$attribute])) {
            return null;
        }

        return is_array($this->map[$attribute])
            ? $this->map[$attribute]['fields']
            : $this->map[$attribute];
    }

    /**
     * Translates a hash from being keyed on driver-specific fields to being
     * keyed on the generalized Turba attributes. The translation is based on
     * the contents of $this->map.
     *
     * @param array $entry  A hash using driver-specific keys.
     *
     * @return array  Translated version of $entry.
     */
    public function toTurbaKeys(array $entry)
    {
        $new_entry = array();
        foreach ($this->map as $key => $val) {
            if (!is_array($val)) {
                $new_entry[$key] = (isset($entry[$val]) && strlen($entry[$val]))
                    ? trim($entry[$val])
                    : null;
            }
        }

        return $new_entry;
    }

    /**
     * Searches the source based on the provided criteria.
     *
     * @todo Allow $criteria to contain the comparison operator (<, =, >,
     *       'like') and modify the drivers accordingly.
     *
     * @param array $search_criteria  Hash containing the search criteria.
     * @param string $sort_order      The requested sort order which is passed
     *                                to Turba_List::sort().
     * @param string $search_type     Do an AND or an OR search (defaults to
     *                                AND).
     * @param array $return_fields    A list of fields to return; defaults to
     *                                all fields.
     * @param array $custom_strict    A list of fields that must match exactly.
     * @param boolean $match_begin    Whether to match only at beginning of
     *                                words.
     *
     * @return Turba_List  The sorted, filtered list of search results.
     * @throws Turba_Exception
     */
    public function search(array $search_criteria, $sort_order = null,
                           $search_type = 'AND', array $return_fields = array(),
                           array $custom_strict = array(), $match_begin = false)
    {
        /* If we are not using Horde_Share, enforce the requirement that the
         * current user must be the owner of the addressbook. */
        $search_criteria['__owner'] = $this->getContactOwner();
        $strict_fields = array($this->toDriver('__owner') => true);

        /* Add any fields that must match exactly for this source to the
         * $strict_fields array. */
        foreach ($this->strict as $strict_field) {
            $strict_fields[$strict_field] = true;
        }
        foreach ($custom_strict as $strict_field) {
            $strict_fields[$this->map[$strict_field]] = true;
        }

        /* Translate the Turba attributes to driver-specific attributes. */
        $fields = $this->makeSearch($search_criteria, $search_type,
                                    $strict_fields, $match_begin);

        if (count($return_fields)) {
            $return_fields_pre = array_unique(array_merge(array('__key', '__type', '__owner', 'name'), $return_fields));
            $return_fields = array();
            foreach ($return_fields_pre as $field) {
                $result = $this->toDriver($field);
                if (is_array($result)) {
                    foreach ($result as $composite_field) {
                        $composite_result = $this->toDriver($composite_field);
                        if ($composite_result) {
                            $return_fields[] = $composite_result;
                        }
                    }
                } elseif ($result) {
                    $return_fields[] = $result;
                }
            }
        } else {
            /* Need to force the array to be re-keyed for the (fringe) case
             * where we might have 1 DB field mapped to 2 or more Turba
             * fields */
            $return_fields = array_values(
                array_unique(array_values($this->fields)));
        }

        /* Retrieve the search results from the driver. */
        $objects = $this->_search($fields, $return_fields, $this->toDriverKeys($this->getBlobs()));

        return $this->_toTurbaObjects($objects, $sort_order);
    }

    /**
     * Searches the current address book for duplicate entries.
     *
     * Duplicates are determined by comparing email and name or last name and
     * first name values.
     *
     * @return array  A hash with the following format:
     * <code>
     * array('name' => array('John Doe' => Turba_List, ...), ...)
     * </code>
     * @throws Turba_Exception
     */
    public function searchDuplicates()
    {
        return array();
    }

    /**
     * Takes an array of object hashes and returns a Turba_List
     * containing the correct Turba_Objects
     *
     * @param array $objects     An array of object hashes (keyed to backend).
     * @param array $sort_order  Array of hashes describing sort fields.  Each
     *                           hash has the following fields:
     * <pre>
     * ascending - (boolean) Indicating sort direction.
     * field - (string) Sort field.
     * </pre>
     *
     * @return Turba_List  A list object.
     */
    protected function _toTurbaObjects(array $objects, array $sort_order = null)
    {
        $list = new Turba_List();

        foreach ($objects as $object) {
            /* Translate the driver-specific fields in the result back to the
             * more generalized common Turba attributes using the map. */
            $object = $this->toTurbaKeys($object);

            $done = false;
            if (!empty($object['__type']) &&
                ucwords($object['__type']) != 'Object') {
                $class = 'Turba_Object_' . ucwords($object['__type']);
                if (class_exists($class)) {
                    $list->insert(new $class($this, $object));
                    $done = true;
                }
            }
            if (!$done) {
                $list->insert(new Turba_Object($this, $object));
            }
        }

        $list->sort($sort_order);

        /* Return the filtered (sorted) results. */
        return $list;
    }

    /**
     * Returns a list of birthday or anniversary hashes from this source for a
     * certain period.
     *
     * @param Horde_Date $start  The start date of the valid period.
     * @param Horde_Date $end    The end date of the valid period.
     * @param string $category   The timeObjects category to return.
     *
     * @return array  A list of timeObject hashes.
     * @throws Turba Exception
     */
    public function listTimeObjects(Horde_Date $start, Horde_Date $end, $category)
    {
        try {
            $res = $this->getTimeObjectTurbaList($start, $end, $category);
        } catch (Turba_Exception $e) {
            /* Try the default implementation before returning an error */
            $res = $this->_getTimeObjectTurbaListFallback($start, $end, $category);
        }

        $t_objects = array();
        while ($ob = $res->next()) {
            $t_object = $ob->getValue($category);
            if (empty($t_object) ||
                $t_object == '0000-00-00' ||
                !preg_match('/(\d{4})-(\d{2})-(\d{2})/', $t_object, $match)) {
                continue;
            }

            $t_object = new Horde_Date(array(
                'mday' => $match[3],
                'month' => $match[2],
                'year' => $match[1]
            ));
            if ($t_object->compareDate($end) > 0) {
                continue;
            }

            $t_object_end = new Horde_Date($t_object);
            ++$t_object_end->mday;
            $key = $ob->getValue('__key');

            // Calculate the age of the time object
            if ($start->year == $end->year) {
                $age = $start->year - $t_object->year;
            } elseif ($t_object->month <= $end->month) {
                // t_object must be in later year
                $age = $end->year - $t_object->year;
            } else {
                // t_object must be in earlier year
                $age = $start->year - $t_object->year;
            }

            $title = sprintf(_("%d. %s of %s"),
                             $age,
                             $GLOBALS['attributes'][$category]['label'],
                             $ob->getValue('name'));

            $t_objects[] = array(
                'id' => $key,
                'title' => $title,
                'start' => sprintf('%d-%02d-%02dT00:00:00',
                                   $t_object->year,
                                   $t_object->month,
                                   $t_object->mday),
                'end' => sprintf('%d-%02d-%02dT00:00:00',
                                 $t_object_end->year,
                                 $t_object_end->month,
                                 $t_object_end->mday),
                'category' => $ob->getValue('category'),
                'recurrence' => array('type' => Horde_Date_Recurrence::RECUR_YEARLY_DATE,
                                      'interval' => 1),
                'params' => array('source' => $this->_name, 'key' => $key),
                'link' => Horde::url('contact.php', true)->add(array('source' => $this->_name, 'key' => $key))->setRaw(true));
        }

        return $t_objects;
    }

    /**
     * Default implementation for obtaining a Turba_List to get TimeObjects
     * out of.
     *
     * @param Horde_Date $start  The starting date.
     * @param Horde_Date $end    The ending date.
     * @param string $field      The address book field containing the
     *                           timeObject information (birthday,
     *                           anniversary).
     *
     * @return Turba_List  A list of objects.
     * @throws Turba_Exception
     */
    public function getTimeObjectTurbaList(Horde_Date $start, Horde_Date $end, $field)
    {
        return $this->_getTimeObjectTurbaListFallback($start, $end, $field);
    }

    /**
     * Default implementation for obtaining a Turba_List to get TimeObjects
     * out of.
     *
     * @param Horde_Date $start  The starting date.
     * @param Horde_Date $end    The ending date.
     * @param string $field      The address book field containing the
     *                           timeObject information (birthday,
     *                           anniversary).
     *
     * @return Turba_List  A list of objects.
     * @throws Turba_Exception
     */
    protected function _getTimeObjectTurbaListFallback(Horde_Date $start, Horde_Date $end, $field)
    {
        return $this->search(array(), null, 'AND', array('name', $field, 'category'));
    }

    /**
     * Retrieves a set of objects from the source.
     *
     * @param array $objectIds  The unique ids of the objects to retrieve.
     *
     * @return array  The array of retrieved objects (Turba_Objects).
     * @throws Turba_Exception
     */
    public function getObjects(array $objectIds)
    {
        $objects = $this->_read($this->map['__key'], $objectIds,
                                $this->getContactOwner(),
                                array_values($this->fields),
                                $this->toDriverKeys($this->getBlobs()));
        if (!is_array($objects)) {
            throw new Turba_Exception(_("Requested object not found."));
        }

        $results = array();
        foreach ($objects as $object) {
            $object = $this->toTurbaKeys($object);
            $done = false;
            if (!empty($object['__type']) &&
                ucwords($object['__type']) != 'Object') {
                $class = 'Turba_Object_' . ucwords($object['__type']);
                if (class_exists($class)) {
                    $results[] = new $class($this, $object);
                    $done = true;
                }
            }
            if (!$done) {
                $results[] = new Turba_Object($this, $object);
            }
        }

        return $results;
    }

    /**
     * Retrieves one object from the source.
     *
     * @param string $objectId  The unique id of the object to retrieve.
     *
     * @return Turba_Object  The retrieved object.
     * @throws Turba_Exception
     */
    public function getObject($objectId)
    {
        $result = $this->getObjects(array($objectId));

        if (empty($result[0])) {
            throw new Turba_Exception('No results');
        }

        $result = $result[0];
        if (!isset($this->map['__owner'])) {
            $result->attributes['__owner'] = $this->getContactOwner();
        }

        return $result;
    }

    /**
     * Adds a new entry to the contact source.
     *
     * @param array $attributes  The attributes of the new object to add.
     *
     * @return string  The new __key value on success.
     * @throws Turba_Exception
     */
    public function add(array $attributes)
    {
        /* Only set __type and __owner if they are not already set. */
        if (!isset($attributes['__type'])) {
            $attributes['__type'] = 'Object';
        }
        if (isset($this->map['__owner']) && !isset($attributes['__owner'])) {
            $attributes['__owner'] = $this->getContactOwner();
        }

        if (!isset($attributes['__uid'])) {
            $attributes['__uid'] = strval(new Horde_Support_Guid());
        }

        $key = $attributes['__key'] = $this->_makeKey($this->toDriverKeys($attributes));
        $uid = $attributes['__uid'];

        $attributes = $this->toDriverKeys($attributes);

        $this->_add($attributes, $this->toDriverKeys($this->getBlobs()));

        /* Log the creation of this item in the history log. */
        try {
            $GLOBALS['injector']->getInstance('Horde_History')
                ->log('turba:' . $this->getName() . ':' . $uid,
                      array('action' => 'add'), true);
        } catch (Exception $e) {
            Horde::logMessage($e, 'ERR');
        }

        return $key;
    }

    /**
     * Returns ability of the backend to add new contacts.
     *
     * @return boolean  Can backend add?
     */
    public function canAdd()
    {
        return $this->_canAdd();
    }

    /**
     * Returns ability of the backend to add new contacts.
     *
     * @return boolean  Can backend add?
     */
    protected function _canAdd()
    {
        return false;
    }

    /**
     * Deletes the specified entry from the contact source.
     *
     * @param string $object_id  The ID of the object to delete.
     *
     * @throws Turba_Exception
     */
    public function delete($object_id)
    {
        $object = $this->getObject($object_id);

        if (!$object->hasPermission(Horde_Perms::DELETE)) {
            throw new Turba_Exception(_("Permission denied"));
        }

        $this->_delete($this->toDriver('__key'), $object_id);

        $own_contact = $GLOBALS['prefs']->getValue('own_contact');
        if (!empty($own_contact)) {
            @list($source, $id) = explode(';', $own_contact);
            if ($id == $object_id) {
                $GLOBALS['prefs']->setValue('own_contact', '');
            }
        }

        /* Log the deletion of this item in the history log. */
        if ($object->getValue('__uid')) {
            try {
                $GLOBALS['injector']->getInstance('Horde_History')->log($object->getGuid(),
                                                array('action' => 'delete'),
                                                true);
            } catch (Exception $e) {
                Horde::logMessage($e, 'ERR');
            }
        }

        return true;
    }

    /**
     * Deletes all contacts from an address book.
     *
     * @param string $sourceName  The identifier of the address book to
     *                            delete.  If omitted, will clear the current
     *                            user's 'default' address book for this
     *                            source type.
     *
     * @throws Turba_Exception
     */
    public function deleteAll($sourceName = null)
    {
        if (!$this->hasCapability('delete_all')) {
            throw new Turba_Exception('Not supported');
        }

        $this->_deleteAll($sourceName);
    }

    /**
     * TODO
     */
    protected function _deleteAll()
    {
    }

    /**
     * Modifies an existing entry in the contact source.
     *
     * @param Turba_Object $object  The object to update.
     *
     * @return string  The object id, possibly updated.
     * @throws Turba_Exception
     */
    public function save(Turba_Object $object)
    {
        $object_id = $this->_save($object);

        /* Log the modification of this item in the history log. */
        if ($object->getValue('__uid')) {
            try {
                $GLOBALS['injector']->getInstance('Horde_History')->log($object->getGuid(),
                                                array('action' => 'modify'),
                                                true);
            } catch (Exception $e) {
                Horde::logMessage($e, 'ERR');
            }
        }

        return $object_id;
    }

    /**
     * Returns the criteria available for this source except '__key'.
     *
     * @return array  An array containing the criteria.
     */
    public function getCriteria()
    {
        $criteria = $this->map;
        unset($criteria['__key']);

        return $criteria;
    }

    /**
     * Returns all non-composite fields for this source. Useful for importing
     * and exporting data, etc.
     *
     * @return array  The field list.
     */
    public function getFields()
    {
        return array_flip($this->fields);
    }

    /**
     * Exports a given Turba_Object as an iCalendar vCard.
     *
     * @param Turba_Object $object  Turba_Object.
     * @param string $version       The vcard version to produce.
     * @param array $fields         Hash of field names and
     *                              Horde_SyncMl_Property properties with the
     *                              requested fields.
     * @param boolean $skipEmpty    Whether to skip empty fields.
     *
     * @return Horde_Icalendar_Vcard  A vcard object.
     */
    public function tovCard(Turba_Object $object, $version = '2.1',
                            array $fields = null, $skipEmpty = false)
    {
        $hash = $object->getAttributes();
        $vcard = new Horde_Icalendar_Vcard($version);
        $formattedname = false;
        $charset = ($version == '2.1')
            ? array('CHARSET' => 'UTF-8')
            : array();

        foreach ($hash as $key => $val) {
            if ($skipEmpty && !strlen($val)) {
                continue;
            }

            switch ($key) {
            case 'name':
                if ($fields && !isset($fields['FN'])) {
                    break;
                }
                $vcard->setAttribute('FN', $val, Horde_Mime::is8bit($val) ? $charset : array());
                $formattedname = true;
                break;

            case 'nickname':
            case 'alias':
                $params = Horde_Mime::is8bit($val) ? $charset : array();
                if (!$fields || isset($fields['NICKNAME'])) {
                    $vcard->setAttribute('NICKNAME', $val, $params);
                }
                if (!$fields || isset($fields['X-EPOCSECONDNAME'])) {
                    $vcard->setAttribute('X-EPOCSECONDNAME', $val, $params);
                }
                break;

            case 'homeAddress':
                if ($fields &&
                    (!isset($fields['LABEL']) ||
                     (isset($fields['LABEL']->Params['TYPE']) &&
                      !isset($fields['LABEL']->Params['TYPE']->ValEnum['HOME'])))) {
                    break;
                }
                if ($version == '2.1') {
                    $vcard->setAttribute('LABEL', $val, array('HOME' => null));
                } else {
                    $vcard->setAttribute('LABEL', $val, array('TYPE' => 'HOME'));
                }
                break;

            case 'workAddress':
                if ($fields &&
                    (!isset($fields['LABEL']) ||
                     (isset($fields['LABEL']->Params['TYPE']) &&
                      !isset($fields['LABEL']->Params['TYPE']->ValEnum['WORK'])))) {
                    break;
                }
                if ($version == '2.1') {
                    $vcard->setAttribute('LABEL', $val, array('WORK' => null));
                } else {
                    $vcard->setAttribute('LABEL', $val, array('TYPE' => 'WORK'));
                }
                break;

            case 'otherAddress':
                if ($fields && !isset($fields['LABEL'])) {
                    break;
                }
                $vcard->setAttribute('LABEL', $val);
                break;

            case 'phone':
                if ($fields && !isset($fields['TEL'])) {
                    break;
                }
                $vcard->setAttribute('TEL', $val);
                break;

            case 'homePhone':
                if ($fields &&
                    (!isset($fields['TEL']) ||
                     (isset($fields['TEL']->Params['TYPE']) &&
                      !isset($fields['TEL']->Params['TYPE']->ValEnum['HOME'])))) {
                    break;
                }
                if ($version == '2.1') {
                    $vcard->setAttribute('TEL', $val, array('HOME' => null));
                } else {
                    $vcard->setAttribute('TEL', $val, array('TYPE' => 'HOME'));
                }
                break;

            case 'workPhone':
                if ($fields &&
                    (!isset($fields['TEL']) ||
                     (isset($fields['TEL']->Params['TYPE']) &&
                      !isset($fields['TEL']->Params['TYPE']->ValEnum['WORK'])))) {
                    break;
                }
                if ($version == '2.1') {
                    $vcard->setAttribute('TEL', $val, array('WORK' => null));
                } else {
                    $vcard->setAttribute('TEL', $val, array('TYPE' => 'WORK'));
                }
                break;

            case 'cellPhone':
                if ($fields &&
                    (!isset($fields['TEL']) ||
                     (isset($fields['TEL']->Params['TYPE']) &&
                      !isset($fields['TEL']->Params['TYPE']->ValEnum['CELL'])))) {
                    break;
                }
                if ($version == '2.1') {
                    $vcard->setAttribute('TEL', $val, array('CELL' => null));
                } else {
                    $vcard->setAttribute('TEL', $val, array('TYPE' => 'CELL'));
                }
                break;

            case 'homeCellPhone':
                $parameters = array();
                if ($fields) {
                    if (!isset($fields['TEL'])) {
                        break;
                    }
                    if (!isset($fields['TEL']->Params['TYPE']) ||
                        isset($fields['TEL']->Params['TYPE']->ValEnum['CELL'])) {
                        if ($version == '2.1') {
                            $parameters['CELL'] = null;
                        } else {
                            $parameters['TYPE'] = 'CELL';
                        }
                    }
                    if (!isset($fields['TEL']->Params['TYPE']) ||
                        isset($fields['TEL']->Params['TYPE']->ValEnum['HOME'])) {
                        if ($version == '2.1') {
                            $parameters['HOME'] = null;
                        } else {
                            $parameters['TYPE'] = 'HOME';
                        }
                    }
                    if (empty($parameters)) {
                        break;
                    }
                } else {
                    if ($version == '2.1') {
                        $parameters = array('CELL' => null, 'HOME' => null);
                    } else {
                        $parameters = array('TYPE' => 'CELL', 'TYPE' => 'HOME');
                    }
                }
                $vcard->setAttribute('TEL', $val, $parameters);
                break;

            case 'workCellPhone':
                $parameters = array();
                if ($fields) {
                    if (!isset($fields['TEL'])) {
                        break;
                    }
                    if (!isset($fields['TEL']->Params['TYPE']) ||
                        isset($fields['TEL']->Params['TYPE']->ValEnum['CELL'])) {
                        if ($version == '2.1') {
                            $parameters['CELL'] = null;
                        } else {
                            $parameters['TYPE'] = 'CELL';
                        }
                    }
                    if (!isset($fields['TEL']->Params['TYPE']) ||
                        isset($fields['TEL']->Params['TYPE']->ValEnum['WORK'])) {
                        if ($version == '2.1') {
                            $parameters['WORK'] = null;
                        } else {
                            $parameters['TYPE'] = 'WORK';
                        }
                    }
                    if (empty($parameters)) {
                        break;
                    }
                } else {
                    if ($version == '2.1') {
                        $parameters = array('CELL' => null, 'WORK' => null);
                    } else {
                        $parameters = array('TYPE' => 'CELL', 'TYPE' => 'WORK');
                    }
                }
                $vcard->setAttribute('TEL', $val, $parameters);
                break;

            case 'videoCall':
                if ($fields &&
                    (!isset($fields['TEL']) ||
                     (isset($fields['TEL']->Params['TYPE']) &&
                      !isset($fields['TEL']->Params['TYPE']->ValEnum['VIDEO'])))) {
                    break;
                }
                if ($version == '2.1') {
                    $vcard->setAttribute('TEL', $val, array('VIDEO' => null));
                } else {
                    $vcard->setAttribute('TEL', $val, array('TYPE' => 'VIDEO'));
                }
                break;

            case 'homeVideoCall':
                $parameters = array();
                if ($fields) {
                    if (!isset($fields['TEL'])) {
                        break;
                    }
                    if (!isset($fields['TEL']->Params['TYPE']) ||
                        isset($fields['TEL']->Params['TYPE']->ValEnum['VIDEO'])) {
                        if ($version == '2.1') {
                            $parameters['VIDEO'] = null;
                        } else {
                            $parameters['TYPE'] = 'VIDEO';
                        }
                    }
                    if (!isset($fields['TEL']->Params['TYPE']) ||
                        isset($fields['TEL']->Params['TYPE']->ValEnum['HOME'])) {
                        if ($version == '2.1') {
                            $parameters['HOME'] = null;
                        } else {
                            $parameters['TYPE'] = 'HOME';
                        }
                    }
                    if (empty($parameters)) {
                        break;
                    }
                } else {
                    if ($version == '2.1') {
                        $parameters = array('VIDEO' => null, 'HOME' => null);
                    } else {
                        $parameters = array('TYPE' => 'VIDEO', 'TYPE' => 'HOME');
                    }
                }
                $vcard->setAttribute('TEL', $val, $parameters);
                break;

            case 'workVideoCall':
                $parameters = array();
                if ($fields) {
                    if (!isset($fields['TEL'])) {
                        break;
                    }
                    if (!isset($fields['TEL']->Params['TYPE']) ||
                        isset($fields['TEL']->Params['TYPE']->ValEnum['VIDEO'])) {
                        if ($version == '2.1') {
                            $parameters['VIDEO'] = null;
                        } else {
                            $parameters['TYPE'] = 'VIDEO';
                        }
                    }
                    if (!isset($fields['TEL']->Params['TYPE']) ||
                        isset($fields['TEL']->Params['TYPE']->ValEnum['WORK'])) {
                        if ($version == '2.1') {
                            $parameters['WORK'] = null;
                        } else {
                            $parameters['TYPE'] = 'WORK';
                        }
                    }
                    if (empty($parameters)) {
                        break;
                    }
                } else {
                    if ($version == '2.1') {
                        $parameters = array('VIDEO' => null, 'WORK' => null);
                    } else {
                        $parameters = array('TYPE' => 'VIDEO', 'TYPE' => 'WORK');
                    }
                }
                $vcard->setAttribute('TEL', $val, $parameters);
                break;

            case 'sip':
                if ($fields && !isset($fields['X-SIP'])) {
                    break;
                }
                $vcard->setAttribute('X-SIP', $val);
                break;
            case 'ptt':
                if ($fields &&
                    (!isset($fields['X-SIP']) ||
                     (isset($fields['X-SIP']->Params['TYPE']) &&
                      !isset($fields['X-SIP']->Params['TYPE']->ValEnum['POC'])))) {
                    break;
                }
                if ($version == '2.1') {
                    $vcard->setAttribute('X-SIP', $val, array('POC' => null));
                } else {
                    $vcard->setAttribute('X-SIP', $val, array('TYPE' => 'POC'));
                }
                break;

            case 'voip':
                if ($fields &&
                    (!isset($fields['X-SIP']) ||
                     (isset($fields['X-SIP']->Params['TYPE']) &&
                      !isset($fields['X-SIP']->Params['TYPE']->ValEnum['VOIP'])))) {
                    break;
                }
                if ($version == '2.1') {
                    $vcard->setAttribute('X-SIP', $val, array('VOIP' => null));
                } else {
                    $vcard->setAttribute('X-SIP', $val, array('TYPE' => 'VOIP'));
                }
                break;

            case 'shareView':
                if ($fields &&
                    (!isset($fields['X-SIP']) ||
                     (isset($fields['X-SIP']->Params['TYPE']) &&
                      !isset($fields['X-SIP']->Params['TYPE']->ValEnum['SWIS'])))) {
                    break;
                }
                if ($version == '2.1') {
                    $vcard->setAttribute('X-SIP', $val, array('SWIS' => null));
                } else {
                    $vcard->setAttribute('X-SIP', $val, array('TYPE' => 'SWIS'));
                }
                break;

            case 'instantMessenger':
                if ($fields && !isset($fields['X-WV-ID'])) {
                    break;
                }
                $vcard->setAttribute('X-WV-ID', $val);
                break;

            case 'fax':
                if ($fields &&
                    (!isset($fields['TEL']) ||
                     (isset($fields['TEL']->Params['TYPE']) &&
                      !isset($fields['TEL']->Params['TYPE']->ValEnum['FAX'])))) {
                    break;
                }
                if ($version == '2.1') {
                    $vcard->setAttribute('TEL', $val, array('FAX' => null));
                } else {
                    $vcard->setAttribute('TEL', $val, array('TYPE' => 'FAX'));
                }
                break;

            case 'homeFax':
                $parameters = array();
                if ($fields) {
                    if (!isset($fields['TEL'])) {
                        break;
                    }
                    if (!isset($fields['TEL']->Params['TYPE']) ||
                        isset($fields['TEL']->Params['TYPE']->ValEnum['FAX'])) {
                        if ($version == '2.1') {
                            $parameters['FAX'] = null;
                        } else {
                            $parameters['TYPE'] = 'FAX';
                        }
                    }
                    if (!isset($fields['TEL']->Params['TYPE']) ||
                        isset($fields['TEL']->Params['TYPE']->ValEnum['HOME'])) {
                        if ($version == '2.1') {
                            $parameters['HOME'] = null;
                        } else {
                            $parameters['TYPE'] = 'HOME';
                        }
                    }
                    if (empty($parameters)) {
                        break;
                    }
                } else {
                    if ($version == '2.1') {
                        $parameters = array('FAX' => null, 'HOME' => null);
                    } else {
                        $parameters = array('TYPE' => 'FAX', 'TYPE' => 'HOME');
                    }
                }
                $vcard->setAttribute('TEL', $val, $parameters);
                break;

            case 'workFax':
                $parameters = array();
                if ($fields) {
                    if (!isset($fields['TEL'])) {
                        break;
                    }
                    if (!isset($fields['TEL']->Params['TYPE']) ||
                        isset($fields['TEL']->Params['TYPE']->ValEnum['FAX'])) {
                        if ($version == '2.1') {
                            $parameters['FAX'] = null;
                        } else {
                            $parameters['TYPE'] = 'FAX';
                        }
                    }
                    if (!isset($fields['TEL']->Params['TYPE']) ||
                        isset($fields['TEL']->Params['TYPE']->ValEnum['WORK'])) {
                        if ($version == '2.1') {
                            $parameters['WORK'] = null;
                        } else {
                            $parameters['TYPE'] = 'WORK';
                        }
                    }
                    if (empty($parameters)) {
                        break;
                    }
                } else {
                    if ($version == '2.1') {
                        $parameters = array('FAX' => null, 'WORK' => null);
                    } else {
                        $parameters = array('TYPE' => 'FAX', 'TYPE' => 'WORK');
                    }
                }
                $vcard->setAttribute('TEL', $val, $parameters);
                if ($version == '2.1') {
                    $vcard->setAttribute('TEL', $val, array('FAX' => null, 'WORK' => null));
                } else {
                    $vcard->setAttribute('TEL', $val, array('TYPE' => 'FAX', 'TYPE' => 'WORK'));
                }
                break;

            case 'pager':
                if ($fields &&
                    (!isset($fields['TEL']) ||
                     (isset($fields['TEL']->Params['TYPE']) &&
                      !isset($fields['TEL']->Params['TYPE']->ValEnum['PAGER'])))) {
                    break;
                }
                if ($version == '2.1') {
                    $vcard->setAttribute('TEL', $val, array('PAGER' => null));
                } else {
                    $vcard->setAttribute('TEL', $val, array('TYPE' => 'PAGER'));
                }
                break;

            case 'email':
                if ($fields && !isset($fields['EMAIL'])) {
                    break;
                }
                $vcard->setAttribute('EMAIL', Horde_Icalendar_Vcard::getBareEmail($val));
                break;

            case 'homeEmail':
                if ($fields &&
                    (!isset($fields['EMAIL']) ||
                     (isset($fields['EMAIL']->Params['TYPE']) &&
                      !isset($fields['EMAIL']->Params['TYPE']->ValEnum['HOME'])))) {
                    break;
                }
                if ($version == '2.1') {
                    $vcard->setAttribute('EMAIL',
                                         Horde_Icalendar_Vcard::getBareEmail($val),
                                         array('HOME' => null));
                } else {
                    $vcard->setAttribute('EMAIL',
                                         Horde_Icalendar_Vcard::getBareEmail($val),
                                         array('TYPE' => 'HOME'));
                }
                break;

            case 'workEmail':
                if ($fields &&
                    (!isset($fields['EMAIL']) ||
                     (isset($fields['EMAIL']->Params['TYPE']) &&
                      !isset($fields['EMAIL']->Params['TYPE']->ValEnum['WORK'])))) {
                    break;
                }
                if ($version == '2.1') {
                    $vcard->setAttribute('EMAIL',
                                         Horde_Icalendar_Vcard::getBareEmail($val),
                                         array('WORK' => null));
                } else {
                    $vcard->setAttribute('EMAIL',
                                         Horde_Icalendar_Vcard::getBareEmail($val),
                                         array('TYPE' => 'WORK'));
                }
                break;

            case 'emails':
                if ($fields && !isset($fields['EMAIL'])) {
                    break;
                }
                $emails = explode(',', $val);
                foreach ($emails as $email) {
                    $vcard->setAttribute('EMAIL', Horde_Icalendar_Vcard::getBareEmail($email));
                }
                break;

            case 'title':
                if ($fields && !isset($fields['TITLE'])) {
                    break;
                }
                $vcard->setAttribute('TITLE', $val, Horde_Mime::is8bit($val) ? $charset : array());
                break;

            case 'role':
                if ($fields && !isset($fields['ROLE'])) {
                    break;
                }
                $vcard->setAttribute('ROLE', $val, Horde_Mime::is8bit($val) ? $charset : array());
                break;

            case 'notes':
                if ($fields && !isset($fields['NOTE'])) {
                    break;
                }
                $vcard->setAttribute('NOTE', $val, Horde_Mime::is8bit($val) ? $charset : array());
                break;

            case 'businessCategory':
            case 'category':
                if ($fields && !isset($fields['CATEGORIES'])) {
                    break;
                }
                $vcard->setAttribute('CATEGORIES', $val);
                break;

            case 'anniversary':
                if (!$fields || isset($fields['X-ANNIVERSARY'])) {
                    $vcard->setAttribute('X-ANNIVERSARY', $val);
                }
                break;

            case 'spouse':
                if (!$fields || isset($fields['X-SPOUSE'])) {
                    $vcard->setAttribute('X-SPOUSE', $val);
                }
                break;

            case 'children':
                if (!$fields || isset($fields['X-CHILDREN'])) {
                    $vcard->setAttribute('X-CHILDREN', $val);
                }
                break;

            case 'website':
                if ($fields && !isset($fields['URL'])) {
                    break;
                }
                $vcard->setAttribute('URL', $val);
                break;

            case 'homeWebsite':
                if ($fields &&
                    (!isset($fields['URL']) ||
                     (isset($fields['URL']->Params['TYPE']) &&
                      !isset($fields['URL']->Params['TYPE']->ValEnum['HOME'])))) {
                    break;
                }
                if ($version == '2.1') {
                    $vcard->setAttribute('URL', $val, array('HOME' => null));
                } else {
                    $vcard->setAttribute('URL', $val, array('TYPE' => 'HOME'));
                }
                break;

            case 'workWebsite':
                if ($fields &&
                    (!isset($fields['URL']) ||
                     (isset($fields['URL']->Params['TYPE']) &&
                      !isset($fields['URL']->Params['TYPE']->ValEnum['WORK'])))) {
                    break;
                }
                if ($version == '2.1') {
                    $vcard->setAttribute('URL', $val, array('WORK' => null));
                } else {
                    $vcard->setAttribute('URL', $val, array('TYPE' => 'WORK'));
                }
                break;

            case 'birthday':
                if ($fields && !isset($fields['BDAY'])) {
                    break;
                }
                $vcard->setAttribute('BDAY', $val);
                break;

            case 'timezone':
                if ($fields && !isset($fields['TZ'])) {
                    break;
                }
                $vcard->setAttribute('TZ', $val, array('VALUE' => 'text'));
                break;

            case 'latitude':
                if ($fields && !isset($fields['GEO'])) {
                    break;
                }
                if (isset($hash['longitude'])) {
                    $vcard->setAttribute('GEO',
                                         array('latitude' => $val,
                                               'longitude' => $hash['longitude']));
                }
                break;

            case 'homeLatitude':
                if ($fields &&
                    (!isset($fields['GEO']) ||
                     (isset($fields['GEO']->Params['TYPE']) &&
                      !isset($fields['GEO']->Params['TYPE']->ValEnum['HOME'])))) {
                    break;
                }
                if (isset($hash['homeLongitude'])) {
                    if ($version == '2.1') {
                        $vcard->setAttribute('GEO',
                                             array('latitude' => $val,
                                                   'longitude' => $hash['homeLongitude']),
                                             array('HOME' => null));
                   } else {
                        $vcard->setAttribute('GEO',
                                             array('latitude' => $val,
                                                   'longitude' => $hash['homeLongitude']),
                                             array('TYPE' => 'HOME'));
                   }
                }
                break;

            case 'workLatitude':
                if ($fields &&
                    (!isset($fields['GEO']) ||
                     (isset($fields['GEO']->Params['TYPE']) &&
                      !isset($fields['GEO']->Params['TYPE']->ValEnum['HOME'])))) {
                    break;
                }
                if (isset($hash['workLongitude'])) {
                    if ($version == '2.1') {
                        $vcard->setAttribute('GEO',
                                             array('latitude' => $val,
                                                   'longitude' => $hash['workLongitude']),
                                             array('WORK' => null));
                   } else {
                        $vcard->setAttribute('GEO',
                                             array('latitude' => $val,
                                                   'longitude' => $hash['workLongitude']),
                                             array('TYPE' => 'WORK'));
                   }
                }
                break;

            case 'photo':
            case 'logo':
                $name = Horde_String::upper($key);
                $params = array();
                if (strlen($val)) {
                    $params['ENCODING'] = 'b';
                }
                if (isset($hash[$key . 'type'])) {
                    $params['TYPE'] = $hash[$key . 'type'];
                }
                if ($fields &&
                    (!isset($fields[$name]) ||
                     (isset($params['TYPE']) &&
                      isset($fields[$name]->Params['TYPE']) &&
                      !isset($fields[$name]->Params['TYPE']->ValEnum[$params['TYPE']])))) {
                    break;
                }
                $vcard->setAttribute($name,
                                     base64_encode($val),
                                     $params);
                break;
            }
        }

        // No explicit firstname/lastname in data source: we have to guess.
        if (!isset($hash['lastname']) && isset($hash['name'])) {
            if (($pos = strpos($hash['name'], ',')) !== false) {
                // Assume Last, First
                $hash['lastname'] = Horde_String::substr($hash['name'], 0, $pos);
                $hash['firstname'] = trim(Horde_String::substr($hash['name'], $pos + 1));
            } elseif (($pos = Horde_String::rpos($hash['name'], ' ')) !== false) {
                // Assume everything after last space as lastname
                $hash['lastname'] = trim(Horde_String::substr($hash['name'], $pos + 1));
                $hash['firstname'] = Horde_String::substr($hash['name'], 0, $pos);
            } else {
                $hash['lastname'] = $hash['name'];
                $hash['firstname'] = '';
            }
        }

        $a = array(
            Horde_Icalendar_Vcard::N_FAMILY => isset($hash['lastname']) ? $hash['lastname'] : '',
            Horde_Icalendar_Vcard::N_GIVEN  => isset($hash['firstname']) ? $hash['firstname'] : '',
            Horde_Icalendar_Vcard::N_ADDL   => isset($hash['middlenames']) ? $hash['middlenames'] : '',
            Horde_Icalendar_Vcard::N_PREFIX => isset($hash['namePrefix']) ? $hash['namePrefix'] : '',
            Horde_Icalendar_Vcard::N_SUFFIX => isset($hash['nameSuffix']) ? $hash['nameSuffix'] : '',
        );
        $val = implode(';', $a);
        if (!$fields || isset($fields['N'])) {
            $vcard->setAttribute('N', $val, Horde_Mime::is8bit($val) ? $charset : array(), false, $a);
        }

        if (!$formattedname && (!$fields || isset($fields['FN']))) {
            if ($object->getValue('name')) {
                $val = $object->getValue('name');
            } elseif (!empty($this->alternativeName) &&
                isset($hash[$this->alternativeName])) {
                $val = $hash[$this->alternativeName];
            } else {
                $val = '';
            }
            $vcard->setAttribute('FN', $val, Horde_Mime::is8bit($val) ? $charset : array());
        }

        $org = array();
        if (!empty($hash['company']) ||
            (!$skipEmpty && array_key_exists('company', $hash))) {
            $org[] = $hash['company'];
        }
        if (!empty($hash['department']) ||
            (!$skipEmpty && array_key_exists('department', $hash))) {
            $org[] = $hash['department'];
        }
        if (count($org) && (!$fields || isset($fields['ORG']))) {
            $val = implode(';', $org);
            $vcard->setAttribute('ORG', $val, Horde_Mime::is8bit($val) ? $charset : array(), false, $org);
        }

        if ((!$fields || isset($fields['ADR'])) &&
            (!empty($hash['commonAddress']) ||
             !empty($hash['commonStreet']) ||
             !empty($hash['commonPOBox']) ||
             !empty($hash['commonExtended']) ||
             !empty($hash['commonCity']) ||
             !empty($hash['commonProvince']) ||
             !empty($hash['commonPostalCode']) ||
             !empty($hash['commonCountry']) ||
             (!$skipEmpty &&
              (array_key_exists('commonAddress', $hash) ||
               array_key_exists('commonStreet', $hash) ||
               array_key_exists('commonPOBox', $hash) ||
               array_key_exists('commonExtended', $hash) ||
               array_key_exists('commonCity', $hash) ||
               array_key_exists('commonProvince', $hash) ||
               array_key_exists('commonPostalCode', $hash) ||
               array_key_exists('commonCountry', $hash))))) {
            /* We can't know if this particular Turba source uses a single
             * address field or multiple for
             * street/city/province/postcode/country. Try to deal with
             * both. */
            if (isset($hash['commonAddress']) &&
                !isset($hash['commonStreet'])) {
                $hash['commonStreet'] = $hash['commonAddress'];
            }
            $a = array(
                Horde_Icalendar_Vcard::ADR_POB      => isset($hash['commonPOBox'])
                    ? $hash['commonPOBox'] : '',
                Horde_Icalendar_Vcard::ADR_EXTEND   => isset($hash['commonExtended'])
                    ? $hash['commonExtended'] : '',
                Horde_Icalendar_Vcard::ADR_STREET   => isset($hash['commonStreet'])
                    ? $hash['commonStreet'] : '',
                Horde_Icalendar_Vcard::ADR_LOCALITY => isset($hash['commonCity'])
                    ? $hash['commonCity'] : '',
                Horde_Icalendar_Vcard::ADR_REGION   => isset($hash['commonProvince'])
                    ? $hash['commonProvince'] : '',
                Horde_Icalendar_Vcard::ADR_POSTCODE => isset($hash['commonPostalCode'])
                    ? $hash['commonPostalCode'] : '',
                Horde_Icalendar_Vcard::ADR_COUNTRY  => isset($hash['commonCountry'])
                    ? Horde_Nls::getCountryISO($hash['commonCountry']) : '',
            );

            $val = implode(';', $a);
            if ($version == '2.1') {
                $params = array();
                if (Horde_Mime::is8bit($val)) {
                    $params['CHARSET'] = 'UTF-8';
                }
            } else {
                $params = array('TYPE' => '');
            }
            $vcard->setAttribute('ADR', $val, $params, true, $a);
        }

        if ((!$fields ||
             (isset($fields['ADR']) &&
              (!isset($fields['ADR']->Params['TYPE']) ||
               isset($fields['ADR']->Params['TYPE']->ValEnum['HOME'])))) &&
            (!empty($hash['homeAddress']) ||
             !empty($hash['homeStreet']) ||
             !empty($hash['homePOBox']) ||
             !empty($hash['homeExtended']) ||
             !empty($hash['homeCity']) ||
             !empty($hash['homeProvince']) ||
             !empty($hash['homePostalCode']) ||
             !empty($hash['homeCountry']) ||
             (!$skipEmpty &&
              (array_key_exists('homeAddress', $hash) ||
               array_key_exists('homeStreet', $hash) ||
               array_key_exists('homePOBox', $hash) ||
               array_key_exists('homeExtended', $hash) ||
               array_key_exists('homeCity', $hash) ||
               array_key_exists('homeProvince', $hash) ||
               array_key_exists('homePostalCode', $hash) ||
               array_key_exists('homeCountry', $hash))))) {
            if (isset($hash['homeAddress']) && !isset($hash['homeStreet'])) {
                $hash['homeStreet'] = $hash['homeAddress'];
            }
            $a = array(
                Horde_Icalendar_Vcard::ADR_POB      => isset($hash['homePOBox'])
                    ? $hash['homePOBox'] : '',
                Horde_Icalendar_Vcard::ADR_EXTEND   => isset($hash['homeExtended'])
                    ? $hash['homeExtended'] : '',
                Horde_Icalendar_Vcard::ADR_STREET   => isset($hash['homeStreet'])
                    ? $hash['homeStreet'] : '',
                Horde_Icalendar_Vcard::ADR_LOCALITY => isset($hash['homeCity'])
                    ? $hash['homeCity'] : '',
                Horde_Icalendar_Vcard::ADR_REGION   => isset($hash['homeProvince'])
                    ? $hash['homeProvince'] : '',
                Horde_Icalendar_Vcard::ADR_POSTCODE => isset($hash['homePostalCode'])
                    ? $hash['homePostalCode'] : '',
                Horde_Icalendar_Vcard::ADR_COUNTRY  => isset($hash['homeCountry'])
                    ? Horde_Nls::getCountryISO($hash['homeCountry']) : '',
            );

            $val = implode(';', $a);
            if ($version == '2.1') {
                $params = array('HOME' => null);
                if (Horde_Mime::is8bit($val)) {
                    $params['CHARSET'] = 'UTF-8';
                }
            } else {
                $params = array('TYPE' => 'HOME');
            }
            $vcard->setAttribute('ADR', $val, $params, true, $a);
        }

        if ((!$fields ||
             (isset($fields['ADR']) &&
              (!isset($fields['ADR']->Params['TYPE']) ||
               isset($fields['ADR']->Params['TYPE']->ValEnum['WORK'])))) &&
            (!empty($hash['workAddress']) ||
             !empty($hash['workStreet']) ||
             !empty($hash['workPOBox']) ||
             !empty($hash['workExtended']) ||
             !empty($hash['workCity']) ||
             !empty($hash['workProvince']) ||
             !empty($hash['workPostalCode']) ||
             !empty($hash['workCountry']) ||
             (!$skipEmpty &&
              (array_key_exists('workAddress', $hash) ||
               array_key_exists('workStreet', $hash) ||
               array_key_exists('workPOBox', $hash) ||
               array_key_exists('workExtended', $hash) ||
               array_key_exists('workCity', $hash) ||
               array_key_exists('workProvince', $hash) ||
               array_key_exists('workPostalCode', $hash) ||
               array_key_exists('workCountry', $hash))))) {
            if (isset($hash['workAddress']) && !isset($hash['workStreet'])) {
                $hash['workStreet'] = $hash['workAddress'];
            }
            $a = array(
                Horde_Icalendar_Vcard::ADR_POB      => isset($hash['workPOBox'])
                    ? $hash['workPOBox'] : '',
                Horde_Icalendar_Vcard::ADR_EXTEND   => isset($hash['workExtended'])
                    ? $hash['workExtended'] : '',
                Horde_Icalendar_Vcard::ADR_STREET   => isset($hash['workStreet'])
                    ? $hash['workStreet'] : '',
                Horde_Icalendar_Vcard::ADR_LOCALITY => isset($hash['workCity'])
                    ? $hash['workCity'] : '',
                Horde_Icalendar_Vcard::ADR_REGION   => isset($hash['workProvince'])
                    ? $hash['workProvince'] : '',
                Horde_Icalendar_Vcard::ADR_POSTCODE => isset($hash['workPostalCode'])
                    ? $hash['workPostalCode'] : '',
                Horde_Icalendar_Vcard::ADR_COUNTRY  => isset($hash['workCountry'])
                    ? Horde_Nls::getCountryISO($hash['workCountry']) : '',
            );

            $val = implode(';', $a);
            if ($version == '2.1') {
                $params = array('WORK' => null);
                if (Horde_Mime::is8bit($val)) {
                    $params['CHARSET'] = 'UTF-8';
                }
            } else {
                $params = array('TYPE' => 'WORK');
            }
            $vcard->setAttribute('ADR', $val, $params, true, $a);
        }

        return $vcard;
    }

    /**
     * Function to convert a Horde_Icalendar_Vcard object into a Turba
     * Object Hash with Turba attributes suitable as a parameter for add().
     *
     * @see add()
     *
     * @param Horde_Icalendar_Vcard $vcard  The Horde_Icalendar_Vcard object
     *                                      to parse.
     *
     * @return array  A Turba attribute hash.
     */
    public function toHash(Horde_Icalendar_Vcard $vcard)
    {
        $hash = array();
        $attr = $vcard->getAllAttributes();
        foreach ($attr as $item) {
            switch ($item['name']) {
            case 'FN':
                $hash['name'] = $item['value'];
                break;

            case 'N':
                $name = $item['values'];
                if (!empty($name[Horde_Icalendar_Vcard::N_FAMILY])) {
                    $hash['lastname'] = $name[Horde_Icalendar_Vcard::N_FAMILY];
                }
                if (!empty($name[Horde_Icalendar_Vcard::N_GIVEN])) {
                    $hash['firstname'] = $name[Horde_Icalendar_Vcard::N_GIVEN];
                }
                if (!empty($name[Horde_Icalendar_Vcard::N_ADDL])) {
                    $hash['middlenames'] = $name[Horde_Icalendar_Vcard::N_ADDL];
                }
                if (!empty($name[Horde_Icalendar_Vcard::N_PREFIX])) {
                    $hash['namePrefix'] = $name[Horde_Icalendar_Vcard::N_PREFIX];
                }
                if (!empty($name[Horde_Icalendar_Vcard::N_SUFFIX])) {
                    $hash['nameSuffix'] = $name[Horde_Icalendar_Vcard::N_SUFFIX];
                }
                break;

            case 'NICKNAME':
            case 'X-EPOCSECONDNAME':
                $hash['nickname'] = $item['value'];
                $hash['alias'] = $item['value'];
                break;

            // We use LABEL but also support ADR.
            case 'LABEL':
                if (isset($item['params']['HOME']) && !isset($hash['homeAddress'])) {
                    $hash['homeAddress'] = $item['value'];
                } elseif (isset($item['params']['WORK']) && !isset($hash['workAddress'])) {
                    $hash['workAddress'] = $item['value'];
                } elseif (!isset($hash['commonAddress'])) {
                    $hash['commonAddress'] = $item['value'];
                }
                break;

            case 'ADR':
                if (isset($item['params']['TYPE'])) {
                    if (!is_array($item['params']['TYPE'])) {
                        $item['params']['TYPE'] = array($item['params']['TYPE']);
                    }
                } else {
                    $item['params']['TYPE'] = array();
                    if (isset($item['params']['WORK'])) {
                        $item['params']['TYPE'][] = 'WORK';
                    }
                    if (isset($item['params']['HOME'])) {
                        $item['params']['TYPE'][] = 'HOME';
                    }
                    if (count($item['params']['TYPE']) == 0) {
                        $item['params']['TYPE'][] = 'COMMON';
                    }
                }

                $address = $item['values'];
                foreach ($item['params']['TYPE'] as $adr) {
                    switch (Horde_String::upper($adr)) {
                    case 'HOME':
                        $prefix = 'home';
                        break;

                    case 'WORK':
                        $prefix = 'work';
                        break;

                    default:
                        $prefix = 'common';
                    }

                    if (isset($hash[$prefix . 'Address'])) {
                        continue;
                    }

                    $hash[$prefix . 'Address'] = '';

                    if (!empty($address[Horde_Icalendar_Vcard::ADR_STREET])) {
                        $hash[$prefix . 'Street'] = $address[Horde_Icalendar_Vcard::ADR_STREET];
                        $hash[$prefix . 'Address'] .= $hash[$prefix . 'Street'] . "\n";
                    }
                    if (!empty($address[Horde_Icalendar_Vcard::ADR_EXTEND])) {
                        $hash[$prefix . 'Extended'] = $address[Horde_Icalendar_Vcard::ADR_EXTEND];
                        $hash[$prefix . 'Address'] .= $hash[$prefix . 'Extended'] . "\n";
                    }
                    if (!empty($address[Horde_Icalendar_Vcard::ADR_POB])) {
                        $hash[$prefix . 'POBox'] = $address[Horde_Icalendar_Vcard::ADR_POB];
                        $hash[$prefix . 'Address'] .= $hash[$prefix . 'POBox'] . "\n";
                    }
                    if (!empty($address[Horde_Icalendar_Vcard::ADR_LOCALITY])) {
                        $hash[$prefix . 'City'] = $address[Horde_Icalendar_Vcard::ADR_LOCALITY];
                        $hash[$prefix . 'Address'] .= $hash[$prefix . 'City'];
                    }
                    if (!empty($address[Horde_Icalendar_Vcard::ADR_REGION])) {
                        $hash[$prefix . 'Province'] = $address[Horde_Icalendar_Vcard::ADR_REGION];
                        $hash[$prefix . 'Address'] .= ', ' . $hash[$prefix . 'Province'];
                    }
                    if (!empty($address[Horde_Icalendar_Vcard::ADR_POSTCODE])) {
                        $hash[$prefix . 'PostalCode'] = $address[Horde_Icalendar_Vcard::ADR_POSTCODE];
                        $hash[$prefix . 'Address'] .= ' ' . $hash[$prefix . 'PostalCode'];
                    }
                    if (!empty($address[Horde_Icalendar_Vcard::ADR_COUNTRY])) {
                        include 'Horde/Nls/Countries.php';
                        $country = array_search($address[Horde_Icalendar_Vcard::ADR_COUNTRY], $countries);
                        if ($country === false) {
                            $country = $address[Horde_Icalendar_Vcard::ADR_COUNTRY];
                        }
                        $hash[$prefix . 'Country'] = $country;
                        $hash[$prefix . 'Address'] .= "\n" . $address[Horde_Icalendar_Vcard::ADR_COUNTRY];
                    }

                    $hash[$prefix . 'Address'] = trim($hash[$prefix . 'Address']);
                }
                break;

            case 'TZ':
                // We only support textual timezones.
                if (!isset($item['params']['VALUE']) ||
                    Horde_String::lower($item['params']['VALUE']) != 'text') {
                    break;
                }
                $timezones = explode(';', $item['value']);
                $available_timezones = Horde_Nls::getTimezones();
                foreach ($timezones as $timezone) {
                    $timezone = trim($timezone);
                    if (isset($available_timezones[$timezone])) {
                        $hash['timezone'] = $timezone;
                        break 2;
                    }
                }
                break;

            case 'GEO':
                if (isset($item['params']['HOME'])) {
                    $hash['homeLatitude'] = $item['value']['latitude'];
                    $hash['homeLongitude'] = $item['value']['longitude'];
                } elseif (isset($item['params']['WORK'])) {
                    $hash['workLatitude'] = $item['value']['latitude'];
                    $hash['workLongitude'] = $item['value']['longitude'];
                } else {
                    $hash['latitude'] = $item['value']['latitude'];
                    $hash['longitude'] = $item['value']['longitude'];
                }
                break;

            case 'TEL':
                if (isset($item['params']['FAX'])) {
                    if (isset($item['params']['WORK']) &&
                        !isset($hash['workFax'])) {
                        $hash['workFax'] = $item['value'];
                    } elseif (isset($item['params']['HOME']) &&
                              !isset($hash['homeFax'])) {
                        $hash['homeFax'] = $item['value'];
                    } elseif (!isset($hash['fax'])) {
                        $hash['fax'] = $item['value'];
                    }
                } elseif (isset($item['params']['PAGER']) &&
                          !isset($hash['pager'])) {
                    $hash['pager'] = $item['value'];
                } elseif (isset($item['params']['TYPE'])) {
                    if (!is_array($item['params']['TYPE'])) {
                        $item['params']['TYPE'] = array($item['params']['TYPE']);
                    }
                    // For vCard 3.0.
                    if (in_array('CELL', $item['params']['TYPE'])) {
                        if (in_array('HOME', $item['params']['TYPE']) &&
                            !isset($hash['homeCellPhone'])) {
                            $hash['homeCellPhone'] = $item['value'];
                        } elseif (in_array('WORK', $item['params']['TYPE']) &&
                                  !isset($hash['workCellPhone'])) {
                            $hash['workCellPhone'] = $item['value'];
                        } elseif (!isset($hash['cellPhone'])) {
                            $hash['cellPhone'] = $item['value'];
                        }
                    } elseif (in_array('FAX', $item['params']['TYPE'])) {
                        if (in_array('HOME', $item['params']['TYPE']) &&
                            !isset($hash['homeFax'])) {
                            $hash['homeFax'] = $item['value'];
                        } elseif (in_array('WORK', $item['params']['TYPE']) &&
                                  !isset($hash['workFax'])) {
                            $hash['workFax'] = $item['value'];
                        } elseif (!isset($hash['fax'])) {
                            $hash['fax'] = $item['value'];
                        }
                    } elseif (in_array('VIDEO', $item['params']['TYPE'])) {
                        if (in_array('HOME', $item['params']['TYPE']) &&
                            !isset($hash['homeVideoCall'])) {
                            $hash['homeVideoCall'] = $item['value'];
                        } elseif (in_array('WORK', $item['params']['TYPE']) &&
                                  !isset($hash['workVideoCall'])) {
                            $hash['workVideoCall'] = $item['value'];
                        } elseif (!isset($hash['videoCall'])) {
                            $hash['videoCall'] = $item['value'];
                        }
                    } elseif (in_array('PAGER', $item['params']['TYPE']) &&
                              !isset($hash['pager'])) {
                        $hash['pager'] = $item['value'];
                    } elseif (in_array('WORK', $item['params']['TYPE']) &&
                              !isset($hash['workPhone'])) {
                        $hash['workPhone'] = $item['value'];
                    } elseif (in_array('HOME', $item['params']['TYPE']) &&
                              !isset($hash['homePhone'])) {
                        $hash['homePhone'] = $item['value'];
                    }
                } elseif (isset($item['params']['CELL'])) {
                    if (isset($item['params']['WORK']) &&
                        !isset($hash['workCellPhone'])) {
                        $hash['workCellPhone'] = $item['value'];
                    } elseif (isset($item['params']['HOME']) &&
                              !isset($hash['homeCellPhone'])) {
                        $hash['homeCellPhone'] = $item['value'];
                    } elseif (!isset($hash['cellPhone'])) {
                        $hash['cellPhone'] = $item['value'];
                    }
                } elseif (isset($item['params']['VIDEO'])) {
                    if (isset($item['params']['WORK']) &&
                        !isset($hash['workVideoCall'])) {
                        $hash['workVideoCall'] = $item['value'];
                    } elseif (isset($item['params']['HOME']) &&
                              !isset($hash['homeVideoCall'])) {
                        $hash['homeVideoCall'] = $item['value'];
                    } elseif (!isset($hash['homeVideoCall'])) {
                        $hash['videoCall'] = $item['value'];
                    }
                } elseif (count($item['params']) <= 1 ||
                          isset($item['params']['VOICE'])) {
                    // There might be e.g. SAT;WORK which must not overwrite
                    // WORK.
                    if (isset($item['params']['WORK']) &&
                        !isset($hash['workPhone'])) {
                        $hash['workPhone'] = $item['value'];
                    } elseif (isset($item['params']['HOME']) &&
                              !isset($hash['homePhone'])) {
                        $hash['homePhone'] = $item['value'];
                    } elseif ((count($item['params']) == 0 ||
                               (count($item['params']) == 1 &&
                                isset($item['params']['VOICE']))) &&
                              !isset($hash['phone'])) {
                        $hash['phone'] = $item['value'];
                    }
                }
                break;

            case 'EMAIL':
                $email_set = false;
                if (isset($item['params']['HOME']) &&
                    (!isset($hash['homeEmail']) ||
                     isset($item['params']['PREF']))) {
                    $hash['homeEmail'] = Horde_Icalendar_Vcard::getBareEmail($item['value']);
                    $email_set = true;
                } elseif (isset($item['params']['WORK']) &&
                          (!isset($hash['workEmail']) ||
                           isset($item['params']['PREF']))) {
                    $hash['workEmail'] = Horde_Icalendar_Vcard::getBareEmail($item['value']);
                    $email_set = true;
                } elseif (isset($item['params']['TYPE'])) {
                    if (!is_array($item['params']['TYPE'])) {
                        $item['params']['TYPE'] = array($item['params']['TYPE']);
                    }
                    if (in_array('HOME', $item['params']['TYPE']) &&
                        (!isset($hash['homeEmail']) ||
                         in_array('PREF', $item['params']['TYPE']))) {
                        $hash['homeEmail'] = Horde_Icalendar_Vcard::getBareEmail($item['value']);
                        $email_set = true;
                    } elseif (in_array('WORK', $item['params']['TYPE']) &&
                              (!isset($hash['workEmail']) ||
                         in_array('PREF', $item['params']['TYPE']))) {
                        $hash['workEmail'] = Horde_Icalendar_Vcard::getBareEmail($item['value']);
                        $email_set = true;
                    }
                }
                if (!$email_set &&
                    (!isset($hash['email']) ||
                     isset($item['params']['PREF']))) {
                    $hash['email'] = Horde_Icalendar_Vcard::getBareEmail($item['value']);
                }

                if (!isset($hash['emails'])) {
                    $hash['emails'] = Horde_Icalendar_Vcard::getBareEmail($item['value']);
                } else {
                    $hash['emails'] .= ', ' . Horde_Icalendar_Vcard::getBareEmail($item['value']);
                }
                break;

            case 'TITLE':
                $hash['title'] = $item['value'];
                break;

            case 'ROLE':
                $hash['role'] = $item['value'];
                break;

            case 'ORG':
                // The VCARD 2.1 specification requires the presence of two
                // SEMI-COLON separated fields: Organizational Name and
                // Organizational Unit. Additional fields are optional.
                $hash['company'] = !empty($item['values'][0]) ? $item['values'][0] : '';
                $hash['department'] = !empty($item['values'][1]) ? $item['values'][1] : '';
                break;

            case 'NOTE':
                $hash['notes'] = $item['value'];
                break;

            case 'CATEGORIES':
                $hash['businessCategory'] = $hash['category'] = str_replace('\; ', ';', $item['value']);
                break;

            case 'URL':
                if (isset($item['params']['HOME']) &&
                    !isset($hash['homeWebsite'])) {
                    $hash['homeWebsite'] = $item['value'];
                } elseif (isset($item['params']['WORK']) &&
                          !isset($hash['workWebsite'])) {
                    $hash['workWebsite'] = $item['value'];
                } elseif (!isset($hash['website'])) {
                    $hash['website'] = $item['value'];
                }
                break;

            case 'BDAY':
                if (empty($item['value'])) {
                    $hash['birthday'] = '';
                } else {
                    $hash['birthday'] = $item['value']['year'] . '-' . $item['value']['month'] . '-' .  $item['value']['mday'];
                }
                break;

            case 'PHOTO':
            case 'LOGO':
                if (isset($item['params']['VALUE']) &&
                    Horde_String::lower($item['params']['VALUE']) == 'uri') {
                    // No support for URIs yet.
                    break;
                }
                if (!isset($item['params']['ENCODING']) ||
                    (Horde_String::lower($item['params']['ENCODING']) != 'b' &&
                     Horde_String::upper($item['params']['ENCODING']) != 'BASE64')) {
                    // Invalid property.
                    break;
                }
                $type = Horde_String::lower($item['name']);
                $hash[$type] = base64_decode($item['value']);
                if (isset($item['params']['TYPE'])) {
                    $hash[$type . 'type'] = $item['params']['TYPE'];
                }
                break;

            case 'X-SIP':
                if (isset($item['params']['POC']) &&
                    !isset($hash['ptt'])) {
                    $hash['ptt'] = $item['value'];
                } elseif (isset($item['params']['VOIP']) &&
                          !isset($hash['voip'])) {
                    $hash['voip'] = $item['value'];
                } elseif (isset($item['params']['SWIS']) &&
                          !isset($hash['shareView'])) {
                    $hash['shareView'] = $item['value'];
                } elseif (!isset($hash['sip'])) {
                    $hash['sip'] = $item['value'];
                }
                break;

            case 'X-WV-ID':
                $hash['instantMessenger'] = $item['value'];
                break;

            case 'X-ANNIVERSARY':
                $hash['anniversary'] = $item['value']['year'] . '-' . $item['value']['month'] . '-' . $item['value']['mday'];
                break;

            case 'X-CHILDREN':
                $hash['children'] = $item['value'];
                break;

            case 'X-SPOUSE':
                $hash['spouse'] = $item['value'];
                break;
            }
        }

        /* Ensure we have a valid name field. */
        if (empty($hash['name'])) {
            /* If name is a composite field, it won't be present in the
             * $this->fields array, so check for that as well. */
            if (isset($this->map['name']) &&
                is_array($this->map['name']) &&
                !empty($this->map['name']['attribute'])) {
                $fieldarray = array();
                foreach ($this->map['name']['fields'] as $mapfields) {
                    $fieldarray[] = isset($hash[$mapfields]) ?
                        $hash[$mapfields] : '';
                }
                $hash['name'] = trim(vsprintf($this->map['name']['format'], $fieldarray),
                                     " \t\n\r\0\x0B,");
            } else {
                $hash['name'] = isset($hash['firstname']) ? $hash['firstname'] : '';
                if (!empty($hash['lastname'])) {
                    $hash['name'] .= ' ' . $hash['lastname'];
                }
                $hash['name'] = trim($hash['name']);
            }
        }

        return $hash;
    }

    /**
     * Convert the contact to an ActiveSync contact message
     *
     * @param Turba_Object $object  The turba object to convert
     *
     * @return Horde_ActiveSync_Message_Contact
     */
    public function toASContact(Turba_Object $object)
    {
        $message = new Horde_ActiveSync_Message_Contact(array('logger' => $GLOBALS['injector']->getInstance('Horde_Log_Logger')));
        $hash = $object->getAttributes();
        foreach ($hash as $field => $value) {
            switch ($field) {
            case 'name':
                $message->fileas = $value;
                break;

            case 'lastname':
                $message->lastname = $value;
                break;

            case 'firstname':
                $message->firstname = $value;
                break;

            case 'middlenames':
                $message->middlename = $value;
                break;

            case 'namePrefix':
                $message->title = $value;
                break;

            case 'nameSuffix':
                $message->suffix = $value;
                break;

            case 'photo':
                $message->picture = base64_encode($value);
                break;

            case 'homeStreet':
                /* Address (TODO: check for a single home/workAddress field
                 * instead) */
                $message->homestreet = $hash['homeStreet'];
                break;

            case 'homeCity':
                $message->homecity = $hash['homeCity'];
                break;

            case 'homeProvince':
                $message->homestate = $hash['homeProvince'];
                break;

            case 'homePostalCode':
                $message->homepostalcode = $hash['homePostalCode'];
                break;

            case 'homeCountry':
                $message->homecountry = !empty($hash['homeCountry']) ? Horde_Nls::getCountryISO($hash['homeCountry']) : null;
                break;

            case 'workStreet':
                $message->businessstreet = $hash['workStreet'];
                break;

            case 'workCity':
                $message->businesscity = $hash['workCity'];
                break;

            case 'workProvince':
                $message->businessstate = $hash['workProvince'];
                break;

            case 'workPostalCode':
                $message->businesspostalcode = $hash['workPostalCode'];
                break;

            case 'workCountry':
                $message->businesscountry = !empty($hash['workCountry']) ? Horde_Nls::getCountryISO($hash['workCountry']) : null;

            case 'homePhone':
                /* Phone */
                $message->homephonenumber = $hash['homePhone'];
                break;

            case 'cellPhone':
                $message->mobilephonenumber = $hash['cellPhone'];
                break;
            case 'fax':
                $message->businessfaxnumber = $hash['fax'];
                break;

            case 'workPhone':
                $message->businessphonenumber = $hash['workPhone'];
                break;

            case 'pager':
                $message->pagernumber = $hash['pager'];
                break;

            case 'email':
                $message->email1address = Horde_Icalendar_Vcard::getBareEmail($value);
                break;

            case 'title':
                $message->jobtitle = $value;
                break;

            case 'company':
                $message->companyname = $value;
                break;

            case 'departnemt':
                $message->department = $value;
                break;

            case 'category':
                // Categories FROM horde are a simple string value, going BACK to horde are an array with 'value' and 'new' keys
                $message->categories = explode(';', $value);
                break;

            case 'spouse':
                $message->spouse = $value;
                break;
            case 'notes':
                /* Assume no truncation - AS server will truncate as needed */
                $message->body = $value;
                $message->bodysize = strlen($message->body);
                $message->bodytruncated = false;
                break;

            case 'website':
                $message->webpage = $value;
                break;

            case 'birthday':
            case 'anniversary':
                if (!empty($value)) {
                    $date = new Horde_Date($value);
                    $message->{$field} = $date;
                } else {
                    $message->$field = null;
                }
                break;
            }
        }

        if (empty($this->fileas)) {
            $message->fileas = Turba::formatName($object);
        }

        return $message;
    }

    /**
     * Convert an ActiveSync contact message into a hash suitable for
     * importing via self::add().
     *
     * @param Horde_ActiveSync_Message_Contact $message  The contact message
     *                                                   object.
     *
     * @return array  A contact hash.
     */
    public function fromASContact(Horde_ActiveSync_Message_Contact $message)
    {
        $hash = array();
        $formattedname = false;

        $textMap = array(
            'fileas' => 'name',
            'lastname' => 'lastname',
            'firstname' => 'firstname',
            'middlename' => 'middlenames',
            'title' => 'namePrefix',
            'suffix' => 'nameSuffix',
            'homestreet' => 'homeStreet',
            'homecity' => 'homeCity',
            'homestate' => 'homeProvince',
            'homepostalcode' => 'homePostalCode',
            'businessstreet' => 'workStreet',
            'businesscity' => 'workCity',
            'businessstate' => 'workProvince',
            'businesspostalcode' => 'workPostalCode',
            'jobtitle' => 'title',
            'companyname' => 'company',
            'department' => 'department',
            'spouse' => 'spouse',
            'body' => 'notes',
            'webpage' => 'website',
            'assistantname' => 'assistant'
        );
        foreach ($textMap as $asField => $turbaField) {
            if (!$message->isGhosted($asField)) {
                $hash[$turbaField] = $message->{$asField};
            }
        }

        $nonTextMap = array(
            'homephonenumber' => 'homePhone',
            'businessphonenumber' => 'workPhone',
            'businessfaxnumber' => 'fax',
            'pagernumber' => 'pager',
            'mobilephonenumber' => 'cellPhone'
        );
        foreach ($nonTextMap as $asField => $turbaField) {
            if (!$message->isGhosted($asField)) {
                $hash[$turbaField] = $message->{$asField};
            }
        }

        /* Requires special handling */

        // picture ($message->picture *should* already be base64 encdoed)
        if (!$message->isGhosted('picture')) {
            $hash['photo'] = base64_decode($message->picture);
        }

        /* Email addresses */
        if (!$message->isGhosted('email1address')) {
            $hash['email'] = Horde_Icalendar_Vcard::getBareEmail($message->email1address);
        }

        /* Categories */
        if (count($message->categories)) {
            $hash['category'] = implode('|', $message->categories);
        } elseif (!$message->isGhosted('categories')) {
            $hash['category'] = '';
        }

        /* Birthday and Anniversary */
        if (!empty($message->birthday)) {
            $bday = new Horde_Date($message->birthday);
            $hash['birthday'] = $bday->format('Y-m-d');
        } elseif (!$message->isGhosted('birthday')) {
            $hash['birthday'] = null;
        }
        if (!empty($message->anniversary)) {
            $anniversary = new Horde_Date($message->anniversary);
            $hash['anniversary'] = $anniversary->format('Y-m-d');
        } elseif (!$message->isGhosted('anniversary')) {
            $hash['anniversary'] = null;
        }

        /* Countries */
        include 'Horde/Nls/Countries.php';
        if (!empty($message->homecountry)) {
            $country = array_search($message->homecountry, $countries);
            if ($country === false) {
                $country = $message->homecountry;
            }
            $hash['homeCountry'] = $country;
        } elseif (!$message->isGhosted('homecountry')) {
            $hash['homeCountry'] = null;
        }

        if (!empty($message->businesscountry)) {
            $country = array_search($message->businesscountry, $countries);
            if ($country === false) {
                $country = $message->businesscountry;
            }
            $hash['workCountry'] = $country;
        } elseif (!$message->isGhosted('businesscountry')) {
            $hash['workCountry'] = null;
        }

        return $hash;
    }

    /**
     * Checks if the current user has the requested permissions on this
     * address book.
     *
     * @param integer $perm  The permission to check for.
     *
     * @return boolean  True if the user has permission, otherwise false.
     */
    public function hasPermission($perm)
    {
        $perms = $GLOBALS['injector']->getInstance('Horde_Perms');
        return $perms->exists('turba:sources:' . $this->_name)
            ? $perms->hasPermission('turba:sources:' . $this->_name, $GLOBALS['registry']->getAuth(), $perm)
            // Assume we have permissions if they're not explicitly set.
            : true;
    }

    /**
     * Return the name of this address book.
     * (This is the key into the cfgSources array)
     *
     * @string Address book name
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * Return the owner to use when searching or creating contacts in
     * this address book.
     *
     * @return string  Contact owner.
     */
    public function getContactOwner()
    {
        return empty($this->_contact_owner)
            ? $this->_getContactOwner()
            : $this->_contact_owner;
    }

    /**
     * Return the owner to use when searching or creating contacts in
     * this address book.
     *
     * @return string  Contact owner.
     */
    protected function _getContactOwner()
    {
        return $GLOBALS['registry']->getAuth();
    }

    /**
     * Creates a new Horde_Share for this source type.
     *
     * @param string $share_name  The share name
     * @param array  $params      The params for the share.
     *
     * @return Horde_Share  The share object.
     */
    public function createShare($share_name, array $params)
    {
        // If the raw address book name is not set, use the share name
        if (empty($params['params']['name'])) {
            $params['params']['name'] = $share_name;
        }

        return Turba::createShare($share_name, $params);
    }

    /**
     * Creates an object key for a new object.
     *
     * @param array $attributes  The attributes (in driver keys) of the
     *                           object being added.
     *
     * @return string  A unique ID for the new object.
     */
    protected function _makeKey(array $attributes)
    {
        return hash('md5', mt_rand());
    }

    /**
     * Static method to construct Turba_Driver objects.
     *
     * @param string $name   String containing the internal name of this
     *                       source.
     * @param array $config  Array containing the configuration information for
     *                       this source.
     *
     * @return Turba_Driver  The concrete driver object.
     * @throws Turba_Exception
     */
    static public function factory($name, array $config)
    {
        $class = __CLASS__ . '_' . ucfirst(basename($config['type']));

        if (class_exists($class)) {
            $driver = new $class($config['params']);
        } else {
            throw new Turba_Exception(sprintf(_("Unable to load the definition of %s."), $class));
        }

        /* Store name and title. */
        $driver->_name = $name;
        $driver->title = $config['title'];

        /* Initialize */
        $driver->_init();

        /* Store and translate the map at the Source level. */
        $driver->map = $config['map'];
        foreach ($driver->map as $key => $val) {
            if (!is_array($val)) {
                $driver->fields[$key] = $val;
            }
        }

        /* Store tabs. */
        if (isset($config['tabs'])) {
            $driver->tabs = $config['tabs'];
        }

        /* Store remaining fields. */
        if (isset($config['strict'])) {
            $driver->strict = $config['strict'];
        }
        if (isset($config['approximate'])) {
            $driver->approximate = $config['approximate'];
        }
        if (isset($config['list_name_field'])) {
            $driver->listNameField = $config['list_name_field'];
        }
        if (isset($config['alternative_name'])) {
            $driver->alternativeName = $config['alternative_name'];
        }

        return $driver;
    }

    /**
     * Initialize the driver.
     *
     * @throws Turba_Exception
     */
    protected function _init()
    {
    }

    /**
     * Searches the address book with the given criteria and returns a
     * filtered list of results. If the criteria parameter is an empty array,
     * all records will be returned.
     *
     * @param array $criteria    Array containing the search criteria.
     * @param array $fields      List of fields to return.
     * @param array $blobFields  Array of fields containing binary data.
     *
     * @return array  Hash containing the search results.
     * @throws Turba_Exception
     */
    protected function _search(array $criteria, array $fields, array $blobFields = array())
    {
        throw new Turba_Exception(_("Searching is not available."));
    }

    /**
     * Reads the given data from the address book and returns the results.
     *
     * @param string $key    The primary key field to use.
     * @param mixed $ids     The ids of the contacts to load.
     * @param string $owner  Only return contacts owned by this user.
     * @param array $fields  List of fields to return.
     * @param array $blobFields  Array of fields containing binary data.
     *
     * @return array  Hash containing the search results.
     * @throws Turba_Exception
     */
    protected function _read($key, $ids, $owner, array $fields, array $blobFields = array())
    {
        throw new Turba_Exception(_("Reading contacts is not available."));
    }

    /**
     * Adds the specified contact to the SQL database.
     *
     * @param array $attributes  TODO
     *
     * @throws Turba_Exception
     */
    protected function _add(array $attributes)
    {
        throw new Turba_Exception(_("Adding contacts is not available."));
    }

    /**
     * Deletes the specified contact from the SQL database.
     *
     * @param string $object_key TODO
     * @param string $object_id  TODO
     *
     * @throws Turba_Exception
     */
    protected function _delete($object_key, $object_id)
    {
        throw new Turba_Exception(_("Deleting contacts is not available."));
    }

    /**
     * Saves the specified object in the SQL database.
     *
     * @param Turba_Object $object  The object to save
     *
     * @return string  The object id, possibly updated.
     * @throws Turba_Exception
     */
    protected function _save(Turba_Object $object)
    {
        throw new Turba_Exception(_("Saving contacts is not available."));
    }

    /**
     * Remove all entries owned by the specified user.
     *
     * @param string $user  The user's data to remove.
     *
     * @throws Turba_Exception
     */
    public function removeUserData($user)
    {
        throw new Turba_Exception(_("Removing user data is not supported in the current address book storage driver."));
    }

    /**
     * Check if the passed in share is the default share for this source.
     *
     * @param Horde_Share_Object $share  The share object.
     * @param array $srcconfig           The cfgSource entry for the share.
     *
     * @return boolean TODO
     */
    public function checkDefaultShare(Horde_Share_Object $share, array $srcconfig)
    {
        $params = @unserialize($share->get('params'));
        if (!isset($params['default'])) {
            $params['default'] = ($params['name'] == $GLOBALS['registry']->getAuth());
            $share->set('params', serialize($params));
            $share->save();
        }

        return $params['default'];
    }

    /* Countable methods. */

    /**
     * Returns the number of contacts of the current user in this address book.
     *
     * @return integer  The number of contacts that the user owns.
     * @throws Turba_Exception
     */
    public function count()
    {
        if (is_null($this->_count)) {
            $this->_count = count(
                $this->_search(array('AND' => array(
                                   array('field' => $this->toDriver('__owner'),
                                         'op' => '=',
                                         'test' => $this->getContactOwner()))),
                               array($this->toDriver('__key')))
            );
        }

        return $this->_count;
    }

}
