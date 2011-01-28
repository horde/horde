<?php
/**
 * The Turba_List:: class provides an interface for dealing with a
 * list of Turba_Objects.
 *
 * Copyright 2000-2011 The Horde Project (http://www.horde.org/)
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
     * The field to compare objects by.
     *
     * @var string
     */
    protected $_usortCriteria;

    /**
     * Constructor.
     */
    public function __construct(array $ids = array())
    {
        foreach ($ids as $value) {
            list($source, $key) = explode(':', $value);
            try {
                $driver = $GLOBALS['injector']->getInstance('Turba_Factory_Driver')->create($source);
                $this->insert($driver->getObject($key));
            } catch (Turba_Exception $e) {}
        }
    }

    /**
     * Inserts a new object into the list.
     *
     * @param Turba_Object $object  The object to insert.
     */
    public function insert(Turba_Object $object)
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

        $need_lastname = $need_firstname = false;
        $name_format = $GLOBALS['prefs']->getValue('name_format');
        $name_sort = $GLOBALS['prefs']->getValue('name_sort');
        foreach ($order as &$field) {
            if ($field['field'] == 'name') {
                if ($name_sort == 'last_first') {
                    $field['field'] = 'lastname';
                } elseif ($name_sort == 'first_last') {
                    $field['field'] = 'firstname';
                }
            }

            if ($field['field'] == 'lastname') {
                $field['field'] = '__lastname';
                $need_lastname = true;
                break;
            }
            if ($field['field'] == 'firstname') {
                $field['field'] = '__firstname';
                $need_firstname = true;
                break;
            }
        }

        if ($need_firstname || $need_lastname) {
            $sorted_objects = array();
            foreach ($this->objects as $key => $object) {
                $name = $object->getValue('name');
                $firstname = $object->getValue('firstname');
                $lastname = $object->getValue('lastname');
                if (!$lastname) {
                    $lastname = Turba::guessLastname($name);
                }
                if (!$firstname) {
                    switch ($name_format) {
                    case 'last_first':
                        $firstname = preg_replace('/' . preg_quote($lastname, '/') . ',\s*/', '', $name);
                        break;
                    case 'first_last':
                        $firstname = preg_replace('/\s+' . preg_quote($lastname, '/') . '/', '', $name);
                        break;
                    default:
                        $firstname = preg_replace('/\s*' . preg_quote($lastname, '/') . '(,\s*)?/', '', $name);
                        break;
                    }
                }
                $object->setValue('__lastname', $lastname);
                $object->setValue('__firstname', $firstname);
                $sorted_objects[$key] = $object;
            }
        } else {
            $sorted_objects = $this->objects;
        }

        $this->_usortCriteria = $order;

        /* Exceptions thrown inside a sort incorrectly cause an error. See
         * Bug #9202. */
        @usort($sorted_objects, array($this, '_cmp'));

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
    protected function _cmp(Turba_Object $a, Turba_Object $b)
    {
        foreach ($this->_usortCriteria as $field) {
            // Set the comparison type based on the type of attribute we're
            // sorting by.
            $sortmethod = 'text';
            if (isset($GLOBALS['attributes'][$field['field']])) {
                $f = $GLOBALS['attributes'][$field['field']];

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
                    $a->sortValue[$field] = Horde_String::lower($a->getValue($field), true, 'UTF-8');
                }
                if (!isset($b->sortValue[$field])) {
                    $b->sortValue[$field] = Horde_String::lower($b->getValue($field), true, 'UTF-8');
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
