<?php
/**
 * The Turba_List:: class provides an interface for dealing with a
 * list of Turba_Objects.
 *
 * Copyright 2000-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you did
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Jon Parise <jon@csh.rit.edu>
 * @category Horde
 * @license  http://www.horde.org/licenses/asl.php ASL
 * @package  Turba
 */
class Turba_List implements Countable
{
    /**
     * The array containing the Turba_Objects represented in this list.
     *
     * @var array
     */
    public $objects = array();

    /**
     * Cached attributes array.
     *
     * @var array
     */
    protected $_attributes = null;

    /**
     * The field to compare objects by.
     *
     * @var string
     */
    protected $_usortCriteria;

    /**
     * Constructor.
     */
    public function __construct($ids = array())
    {
        foreach ($ids as $value) {
            list($source, $key) = explode(':', $value);
            $driver = Turba_Driver::singleton($source);
            if ($driver instanceof Turba_Driver) {
                $this->insert($driver->getObject($key));
            }
        }
    }

    /**
     * Inserts a new object into the list.
     *
     * @param Turba_Object $object  The object to insert.
     */
    public function insert($object)
    {
        if ($object instanceof Turba_Object) {
            $key = $object->getSource() . ':' . $object->getValue('__key');
            if (!isset($this->objects[$key])) {
                $this->objects[$key] = $object;
            }
        }
    }

    /**
     * Resets our internal pointer to the beginning of the list. Use this to
     * hide the internal storage (array, list, etc.) from client objects.
     *
     * @return Turba_Object  The next object in the list.
     */
    public function reset()
    {
        return reset($this->objects);
    }

    /**
     * Returns the next Turba_Object in the list. Use this to hide internal
     * implementation details from client objects.
     *
     * @return Turba_Object  The next object in the list.
     */
    public function next()
    {
        list(,$tmp) = each($this->objects);
        return $tmp;
    }

    /**
     * Filters/Sorts the list based on the specified sort routine.
     * The default sort order is by last name, ascending.
     *
     * @param array $order  Array of hashes describing sort fields.  Each
     *                      hash has the following fields:
     * <pre>
     * ascending - (boolean) Sort direction.
     * field - (string) Sort field.
     * </pre>
     */
    public function sort($order = null)
    {
        if (!$order) {
            $order = array(
                array(
                    'ascending' => true,
                    'field' => 'lastname'
                )
            );
        }

        $need_lastname = false;
        $last_first = ($GLOBALS['prefs']->getValue('name_format') == 'last_first');
        foreach ($order as &$field) {
            if ($last_first && ($field['field'] == 'name')) {
                $field['field'] = 'lastname';
            }

            if ($field['field'] == 'lastname') {
                $field['field'] = '__lastname';
                $need_lastname = true;
                break;
            }
        }

        if (!$need_lastname) {
            $sorted_objects = $this->objects;
        } else {
            $sorted_objects = array();
            foreach ($this->objects as $key => $object) {
                $lastname = $object->getValue('lastname');
                if (!$lastname) {
                    $lastname = Turba::guessLastname($object->getValue('name'));
                }
                $object->setValue('__lastname', $lastname);
                $sorted_objects[$key] = $object;
            }
        }

        $this->_usortCriteria = $order;
        usort($sorted_objects, array($this, '_cmp'));
        $this->objects = $sorted_objects;
    }

    /**
     * Usort helper function.
     *
     * Compares two Turba_Objects based on the member variable
     * $_usortCriteria, taking care to sort numerically if it is an integer
     * field.
     *
     * @param Turba_Object $a  The first Turba_Object to compare.
     * @param Turba_Object $b  The second Turba_Object to compare.
     *
     * @return integer  Comparison of the two field values.
     */
    protected function _cmp($a, $b)
    {
        if (is_null($this->_attributes)) {
            $this->_attributes = Horde::loadConfiguration('attributes.php', 'attributes', 'turba');
        }

        foreach ($this->_usortCriteria as $field) {
            // Set the comparison type based on the type of attribute we're
            // sorting by.
            $sortmethod = 'text';
            if (isset($this->_attributes[$field['field']])) {
                $f = $this->_attributes[$field['field']];

                if (!empty($f['cmptype'])) {
                    $sortmethod = $f['cmptype'];
                } elseif (in_array($f['type'], array('int', 'intlist', 'number'))) {
                    $sortmethod = 'int';
                }
            }

            $field = $field['field'];
            switch ($sortmethod) {
            case 'int':
                $result = ($a->getValue($field) > $b->getValue($field)) ? 1 : -1;
                break;

            case 'text':
                if (!isset($a->sortValue[$field])) {
                    $a->sortValue[$field] = Horde_String::lower($a->getValue($field), true);
                }
                if (!isset($b->sortValue[$field])) {
                    $b->sortValue[$field] = Horde_String::lower($b->getValue($field), true);
                }

                // Use strcoll for locale-safe comparisons.
                $result = strcoll($a->sortValue[$field], $b->sortValue[$field]);
                break;
            }

            if (!$field['ascending']) {
                $result = -$result;
            }
            if ($result != 0) {
                return $result;
            }
        }

        return 0;
    }

    /* Countable methods. */

    public function count()
    {
        return count($this->objects);
    }

}
