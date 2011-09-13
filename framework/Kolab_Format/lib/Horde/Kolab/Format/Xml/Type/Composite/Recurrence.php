<?php
/**
 * Handles recurrence data.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Format
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL
 * @link     http://www.horde.org/libraries/Horde_Kolab_Format
 */

/**
 * Handles recurrence data.
 *
 * Copyright 2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html.
 *
 * @since Horde_Kolab_Format 1.1.0
 *
 * @category Kolab
 * @package  Kolab_Format
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL
 * @link     http://www.horde.org/libraries/Horde_Kolab_Format
 */
class Horde_Kolab_Format_Xml_Type_Composite_Recurrence
extends Horde_Kolab_Format_Xml_Type_Composite_Predefined
{
    /** Override in extending classes to set predefined parameters. */
    protected $_predefined_parameters = array(
        'value'   => Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING,
        'array'   => array(
            'interval' => array(
                'type'    => Horde_Kolab_Format_Xml::TYPE_INTEGER,
                'value'   => Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING,
            ),
            'day' => array(
                'type'    => Horde_Kolab_Format_Xml::TYPE_MULTIPLE,
                'value'   => Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING,
                'array'   => array(
                    'type' => Horde_Kolab_Format_Xml::TYPE_STRING,
                    'value' => Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING,
                ),
            ),
            'daynumber' => array(
                'type'    => Horde_Kolab_Format_Xml::TYPE_INTEGER,
                'value'   => Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING,
            ),
            'month' => array(
                'type'    => Horde_Kolab_Format_Xml::TYPE_STRING,
                'value'   => Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING,
            ),
            'range' => array(
                'type'    => Horde_Kolab_Format_Xml::TYPE_STRING,
                'value'   => Horde_Kolab_Format_Xml::VALUE_DEFAULT,
                'default' => '',
            ),
            'exclusion' => array(
                'type'    => Horde_Kolab_Format_Xml::TYPE_MULTIPLE,
                'value'   => Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING,
                'array'   => array(
                    'type' => Horde_Kolab_Format_Xml::TYPE_STRING,
                    'value' => Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING,
                ),
            ),
            'complete' => array(
                'type'    => Horde_Kolab_Format_Xml::TYPE_MULTIPLE,
                'value'   => Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING,
                'array'   => array(
                    'type' => Horde_Kolab_Format_Xml::TYPE_STRING,
                    'value' => Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING,
                ),
            ),
        ),
    );


    /**
     * Load recurrence information.
     *
     * @param DOMNode $node    The original node if set.
     * @param boolean $missing Has the node been missing?
     *
     * @return array The recurrence information.
     *
     * @throws Horde_Kolab_Format_Exception If converting the data from XML failed.
     */
    protected function _loadRecurrence($node, $missing)
    {
        if ($missing) {
            return null;
        }

        // Collect all child nodes
        $children = $node->childNodes;

        $recurrence = $this->_loadArray($node, $this->_fields_recurrence);

        // Get the cycle type (must be present)
        $recurrence['cycle'] = $node->getAttribute('cycle');
        // Get the sub type (may be present)
        $recurrence['type'] = $node->getAttribute('type');

        // Exclusions.
        if (isset($recurrence['exclusion'])) {
            $exceptions = array();
            foreach ($recurrence['exclusion'] as $exclusion) {
                if (!empty($exclusion)) {
                    list($year, $month, $mday) = sscanf($exclusion, '%04d-%02d-%02d');

                    $exceptions[] = sprintf('%04d%02d%02d', $year, $month, $mday);
                }
            }
            $recurrence['exceptions'] = $exceptions;
        }

        // Completed dates.
        if (isset($recurrence['complete'])) {
            $completions = array();
            foreach ($recurrence['complete'] as $complete) {
                if (!empty($complete)) {
                    list($year, $month, $mday) = sscanf($complete, '%04d-%02d-%02d');

                    $completions[] = sprintf('%04d%02d%02d', $year, $month, $mday);
                }
            }
            $recurrence['completions'] = $completions;
        }

        // Range is special
        foreach ($children as $child) {
            if ($child->tagName == 'range') {
                $recurrence['range-type'] = $child->getAttribute('type');
            }
        }

        if (isset($recurrence['range']) && isset($recurrence['range-type'])
            && $recurrence['range-type'] == 'date') {
            $recurrence['range'] = Horde_Kolab_Format_Date::decodeDate($recurrence['range']);
        }

        // Sanity check
        $valid = $this->_validateRecurrence($recurrence);

        return $recurrence;
    }

    /**
     * Validate recurrence hash information.
     *
     * @param array &$recurrence Recurrence hash loaded from XML.
     *
     * @return boolean True on success.
     *
     * @throws Horde_Kolab_Format_Exception If the recurrence data is invalid.
     */
    protected function _validateRecurrence(&$recurrence)
    {
        if (!isset($recurrence['cycle'])) {
              throw new Horde_Kolab_Format_Exception('recurrence tag error: cycle attribute missing');
        }

        if (!isset($recurrence['interval'])) {
              throw new Horde_Kolab_Format_Exception('recurrence tag error: interval tag missing');
        }
        $interval = $recurrence['interval'];
        if ($interval < 0) {
            throw new Horde_Kolab_Format_Exception('recurrence tag error: interval cannot be below zero: '
                                      . $interval);
        }

        if ($recurrence['cycle'] == 'weekly') {
            // Check for <day>
            if (!isset($recurrence['day']) || count($recurrence['day']) == 0) {
                throw new Horde_Kolab_Format_Exception('recurrence tag error: day tag missing for weekly recurrence');
            }
        }

        // The code below is only for monthly or yearly recurrences
        if ($recurrence['cycle'] != 'monthly'
            && $recurrence['cycle'] != 'yearly') {
            return true;
        }

        if (!isset($recurrence['type'])) {
            throw new Horde_Kolab_Format_Exception('recurrence tag error: type attribute missing');
        }

        if (!isset($recurrence['daynumber'])) {
            throw new Horde_Kolab_Format_Exception('recurrence tag error: daynumber tag missing');
        }
        $daynumber = $recurrence['daynumber'];
        if ($daynumber < 0) {
            throw new Horde_Kolab_Format_Exception('recurrence tag error: daynumber cannot be below zero: '
                                      . $daynumber);
        }

        if ($recurrence['type'] == 'daynumber') {
            if ($recurrence['cycle'] == 'yearly' && $daynumber > 366) {
                throw new Horde_Kolab_Format_Exception('recurrence tag error: daynumber cannot be larger than 366 for yearly recurrences: ' . $daynumber);
            } else if ($recurrence['cycle'] == 'monthly' && $daynumber > 31) {
                throw new Horde_Kolab_Format_Exception('recurrence tag error: daynumber cannot be larger than 31 for monthly recurrences: ' . $daynumber);
            }
        } else if ($recurrence['type'] == 'weekday') {
            // daynumber is the week of the month
            if ($daynumber > 5) {
                throw new Horde_Kolab_Format_Exception('recurrence tag error: daynumber cannot be larger than 5 for type weekday: ' . $daynumber);
            }

            // Check for <day>
            if (!isset($recurrence['day']) || count($recurrence['day']) == 0) {
                throw new Horde_Kolab_Format_Exception('recurrence tag error: day tag missing for type weekday');
            }
        }

        if (($recurrence['type'] == 'monthday' || $recurrence['type'] == 'yearday')
            && $recurrence['cycle'] == 'monthly') {
            throw new Horde_Kolab_Format_Exception('recurrence tag error: type monthday/yearday is only allowed for yearly recurrences');
        }

        if ($recurrence['cycle'] == 'yearly') {
            if ($recurrence['type'] == 'monthday') {
                // daynumber and month
                if (!isset($recurrence['month'])) {
                    throw new Horde_Kolab_Format_Exception('recurrence tag error: month tag missing for type monthday');
                }
                if ($daynumber > 31) {
                    throw new Horde_Kolab_Format_Exception('recurrence tag error: daynumber cannot be larger than 31 for type monthday: ' . $daynumber);
                }
            } else if ($recurrence['type'] == 'yearday') {
                if ($daynumber > 366) {
                    throw new Horde_Kolab_Format_Exception('recurrence tag error: daynumber cannot be larger than 366 for type yearday: ' . $daynumber);
                }
            }
        }

        return true;
    }

    /**
     * Save recurrence information.
     *
     * @param DOMNode $parent_node The parent node to attach
     *                             the child to.
     * @param string  $name        The name of the node.
     * @param mixed   $value       The value to store.
     * @param boolean $missing     Has the value been missing?
     *
     * @return DOMNode The new child node.
     */
    protected function _saveRecurrence($parent_node, $name, $value, $missing)
    {
        $this->_removeNodes($parent_node, $name);

        if (empty($value)) {
            return false;
        }

        // Exclusions.
        if (isset($value['exceptions'])) {
            $exclusions = array();
            foreach ($value['exceptions'] as $exclusion) {
                if (!empty($exclusion)) {
                    list($year, $month, $mday) = sscanf($exclusion, '%04d%02d%02d');
                    $exclusions[]              = "$year-$month-$mday";
                }
            }
            $value['exclusion'] = $exclusions;
        }

        // Completed dates.
        if (isset($value['completions'])) {
            $completions = array();
            foreach ($value['completions'] as $complete) {
                if (!empty($complete)) {
                    list($year, $month, $mday) = sscanf($complete, '%04d%02d%02d');
                    $completions[]             = "$year-$month-$mday";
                }
            }
            $value['complete'] = $completions;
        }

        if (isset($value['range'])
            && isset($value['range-type']) && $value['range-type'] == 'date') {
            $value['range'] = Horde_Kolab_Format_Date::encodeDate($value['range']);
        }

        $r_node = $this->_xmldoc->createElement($name);
        $r_node = $parent_node->appendChild($r_node);

        // Save normal fields
        $this->_saveArray($r_node, $value, $this->_fields_recurrence);

        // Add attributes
        $r_node->setAttribute('cycle', $value['cycle']);
        if (isset($value['type'])) {
            $r_node->setAttribute('type', $value['type']);
        }

        $child = $this->_findNode($r_node->childNodes, 'range');
        if ($child) {
            $child->setAttribute('type', $value['range-type']);
        }

        return $r_node;
    }
}
