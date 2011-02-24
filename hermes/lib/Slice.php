<?php
/**
 * Hermes_Slice:: Lightweight wrapper around a single timeslice
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author Michael J Rubinsky <mrubinsk@horde.org>
 */
class Hermes_Slice implements ArrayAccess, IteratorAggregate
{
    /**
     * Slice properties
     *
     * @var array
     */
    protected $_properties;

    public function __construct($properties = array())
    {
        $this->_properties = $properties; //new Horde_Support_Array($properties);
    }

    /**
     * Populate object from a json object.
     *
     * @param stdClass Hash containing slice data. @see self::toJson()
     */
    public function fromJson(stdClass $json)
    {
        $this->_properties = array (
            'client' => $json->c,
            'costobject' => $json->co,
            'c_costobject_name' => $json->con,
            'date' => $json->d,
            'description' => $json->desc,
            'employee' => $json->e,
            'hours' => $json->h,
            'id' => $json->i,
            'note' => $json->n,
            'rate' => $json->r,
            'submitted' => $json->s,
            'type' => $json->t,
            '_type_name' => $json->tn,
        );
    }

    /**
     * Populate this slice from a time entry form.
     * Assumes the values are POSTed.
     */
    public function readForm()
    {
        $this->_properties['client'] = Horde_Util::getPost('client');
        $this->_properties['type'] = Horde_Util::getPost('type');
        $this->_properties['costobject'] = Horde_Util::getPost('costobject');
        $this->_properties['date'] = new Horde_Date(Horde_Util::getPost('start_date'));
        $this->_properties['hours'] = Horde_Util::getPost('hours');
        $this->_properties['description'] = Horde_Util::getPost('description');
        $this->_properties['id'] = Horde_Util::getPost('id', 0);
        $this->_properties['billable'] = Horde_Util::getPost('billable') ? 1 : 0;
    }

    /**
     * Get the json representation of this slice. The resulting json contains
     * the following properties
     *<pre>
     * c    - client id
     * cn   - client object
     * co   - costobject id
     * con  - costobject name
     * d    - date
     * desc - description
     * e    - employee
     * h    - hours
     * i    - slice id
     * n    - note
     * r    - rate
     * s    - submitted
     * t    - type id
     * tn   - type name
     * b    - billable
     *</pre>
     *
     * @return string
     */
    public function toJson()
    {
        // @TODO: DO we need the *entire* contact object?
        $cn = $GLOBALS['registry']->clients->getClients(array($this->_properties['client']));
        $json = array (
            'c' => $this->_properties['client'],
            'cn' => current($cn),
            'co' => $this->_properties['costobject'],
            'con' => $this->_properties['_costobject_name'],
            'd' => $this->_properties['date']->dateString(),
            'desc' => $this->_properties['description'],
            'e' => $this->_properties['employee'],
            'h' => $this->_properties['hours'],
            'i' => $this->_properties['id'],
            'n' => $this->_properties['note'],
            'r' => $this->_properties['rate'],
            's' => $this->_properties['submitted'],
            't' => $this->_properties['type'],
            'tn' => $this->_properties['_type_name'],
            'b'  => $this->_properties['billable']
        );

        return $json;
    }

    /**
     * ArrayAccess::offsetExists
     *
     * @param mixed $offset
     *
     * @return boolean
     */
    public function offsetExists($offset)
    {
        return isset($this->_properties[$offset]);
    }

    /**
     * ArrayAccess::offsetGet
     *
     * @param mixed $offset
     *
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return empty($this->_properties[$offset]) ? null : $this->_properties[$offset];
    }

    /**
     * ArrayAccess::offsetSet
     *
     * @param mixed $offset
     * @param mixed $value
     *
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->_properties[$offset] = $value;
    }

    /**
     * ArrayAccess::offsetUnset
     *
     * @param mixed $offset
     *
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->_properties[$offset]);
    }

    /**
     * IteratorAggregate::getIterator
     *
     */
    public function getIterator()
    {
        return new ArrayIterator($this->_properties);
    }

}