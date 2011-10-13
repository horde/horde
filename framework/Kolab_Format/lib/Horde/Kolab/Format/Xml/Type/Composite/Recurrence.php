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
                'value'   => Horde_Kolab_Format_Xml::VALUE_NOT_EMPTY,
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
                'type'    => 'Horde_Kolab_Format_Xml_Type_Range',
                'value'   => Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING,
            ),
            'exclusion' => array(
                'type'    => Horde_Kolab_Format_Xml::TYPE_MULTIPLE,
                'value'   => Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING,
                'array'   => array(
                    'type' => Horde_Kolab_Format_Xml::TYPE_DATE,
                    'value' => Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING,
                ),
            ),
            'complete' => array(
                'type'    => Horde_Kolab_Format_Xml::TYPE_MULTIPLE,
                'value'   => Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING,
                'array'   => array(
                    'type' => Horde_Kolab_Format_Xml::TYPE_DATE,
                    'value' => Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING,
                ),
            ),
        ),
    );

    /**
     * Load the node value from the Kolab object.
     *
     * @param string  $name        The name of the the attribute
     *                             to be fetched.
     * @param array   &$attributes The data array that holds all
     *                             attribute values.
     * @param DOMNode $parent_node The parent node of the node to be loaded.
     * @param array   $params      The parameters for this parse operation.
     *
     * @return DOMNode|boolean The named DOMNode or false if no node value was
     *                         found.
     */
    public function load($name, &$attributes, $parent_node, $params = array())
    {
        $result = parent::load($name, $attributes, $parent_node, $params);

        if ($node = $params['helper']->findNodeRelativeTo('./' . $name, $parent_node)) {
            // Get the cycle type (must be present)
            $attributes['recurrence']['cycle'] = $node->getAttribute('cycle');
            // Get the sub type (may be present)
            $attributes['recurrence']['type'] = $node->getAttribute('type');
        }
        if (empty($attributes['recurrence'])) {
            return $result;
        }

        $recurrence = $attributes['recurrence'];

        if ($recurrence['interval'] < 0) {
            throw new Horde_Kolab_Format_Exception_ParseError(
                sprintf(
                    'Recurrence: interval cannot be below zero [Value: %s]!',
                    $recurrence['interval']
                )
            );
        }

        if (empty($recurrence['cycle'])) {
              throw new Horde_Kolab_Format_Exception_ParseError('Recurrence: "cycle" attribute missing!');
        }

        if ($recurrence['cycle'] == 'weekly') {
            // Check for <day>
            if (!isset($recurrence['day']) || count($recurrence['day']) == 0) {
                throw new Horde_Kolab_Format_Exception_ParseError(
                    'Recurrence: day tag missing for weekly recurrence!'
                );
            }
        }

        // The code below is only for monthly or yearly recurrences
        if ($recurrence['cycle'] == 'monthly'
            || $recurrence['cycle'] == 'yearly') {
            if (!isset($recurrence['type'])) {
                throw new Horde_Kolab_Format_Exception_ParseError(
                    'Recurrence: type attribute missing!'
                );
            }

            if (!isset($recurrence['daynumber'])) {
                throw new Horde_Kolab_Format_Exception_ParseError(
                    'Recurrence: daynumber tag missing!'
                );
            }
            $daynumber = $recurrence['daynumber'];
            if ($daynumber < 0) {
                throw new Horde_Kolab_Format_Exception_ParseError(
                    sprintf(
                        'Recurrence: daynumber cannot be below zero ["%s"]!',
                        $daynumber
                    )
                );
            }

            if ($recurrence['type'] == 'daynumber') {
                if ($recurrence['cycle'] == 'yearly' && $daynumber > 366) {
                    throw new Horde_Kolab_Format_Exception_ParseError(
                        sprintf(
                            'Recurrence: daynumber cannot be larger than 366 for yearly recurrences ["%s"]!',
                            $daynumber
                        )
                    );
                } else if ($recurrence['cycle'] == 'monthly' && $daynumber > 31) {
                    throw new Horde_Kolab_Format_Exception_ParseError(
                        sprintf(
                            'Recurrence: daynumber cannot be larger than 31 for monthly recurrences ["%s"]!',
                            $daynumber
                        )
                    );
                }
            } else if ($recurrence['type'] == 'weekday') {
                // daynumber is the week of the month
                if ($daynumber > 5) {
                    throw new Horde_Kolab_Format_Exception_ParseError(
                        sprintf(
                            'Recurrence: daynumber cannot be larger than 5 for type weekday ["%s"]!',
                            $daynumber
                        )
                    );
                }

                // Check for <day>
                if (!isset($recurrence['day']) || count($recurrence['day']) == 0) {
                    throw new Horde_Kolab_Format_Exception_ParseError(
                        'Recurrence: day tag missing for type weekday!'
                    );
                }
            }

            if (($recurrence['type'] == 'monthday' || $recurrence['type'] == 'yearday')
                && $recurrence['cycle'] == 'monthly') {
                throw new Horde_Kolab_Format_Exception_ParseError(
                    'Recurrence: type monthday/yearday is only allowed for yearly recurrences'
                );
            }

            if ($recurrence['cycle'] == 'yearly') {
                if ($recurrence['type'] == 'monthday') {
                    // daynumber and month
                    if (!isset($recurrence['month'])) {
                        throw new Horde_Kolab_Format_Exception_ParseError(
                            'Recurrence: month tag missing for type monthday'
                        );
                    }
                    if ($daynumber > 31) {
                        throw new Horde_Kolab_Format_Exception_ParseError(
                            sprintf(
                                'Recurrence: daynumber cannot be larger than 31 for type monthday ["%s"]!',
                                $daynumber
                            )
                        );
                    }
                } else if ($recurrence['type'] == 'yearday') {
                    if ($daynumber > 366) {
                        throw new Horde_Kolab_Format_Exception_ParseError(
                            sprintf(
                                'Recurrence: daynumber cannot be larger than 366 for type yearday ["%s"]!',
                                $daynumber
                            )
                        );
                    }
                }
            }

        }

        return $result;
    }

    /**
     * Update the specified attribute.
     *
     * @param string       $name        The name of the the attribute
     *                                  to be updated.
     * @param mixed        $value       The value to store.
     * @param DOMNode      $parent_node The parent node of the node that
     *                                  should be updated.
     * @param array        $params      The parameters for this write operation.
     * @param DOMNode|NULL $old_node    The previous value (or null if
     *                                  there is none).
     *
     * @return DOMNode|boolean The new/updated child node or false if this
     *                         failed.
     *
     * @throws Horde_Kolab_Format_Exception If converting the data to XML failed.
     */
    public function saveNodeValue(
        $name,
        $value,
        $parent_node,
        $params,
        $old_node = false
    ) {
        $node = parent::saveNodeValue($name, $value, $parent_node, $params, $old_node);
        // Add attributes
        $node->setAttribute('cycle', $value['cycle']);
        if (isset($value['type'])) {
            $node->setAttribute('type', $value['type']);
        }
        return $node;
    }
}
