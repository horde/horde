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
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * http://www.horde.org/licenses/lgpl21.
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
extends Horde_Kolab_Format_Xml_Type_Composite
{
    protected $elements = array(
        'interval'  => 'Horde_Kolab_Format_Xml_Type_RecurrenceInterval',
        'day'       => 'Horde_Kolab_Format_Xml_Type_Multiple_String',
        'daynumber' => 'Horde_Kolab_Format_Xml_Type_Integer',
        'month'     => 'Horde_Kolab_Format_Xml_Type_String_MaybeMissing',
        'range'     => 'Horde_Kolab_Format_Xml_Type_RecurrenceRange',
        'exclusion' => 'Horde_Kolab_Format_Xml_Type_Multiple_Date',
        'complete'  => 'Horde_Kolab_Format_Xml_Type_Multiple_Date',
    );

    /**
     * Load the node value from the Kolab object.
     *
     * @param string                        $name        The name of the the
     *                                                   attribute to be fetched.
     * @param array                         &$attributes The data array that
     *                                                   holds all attribute
     *                                                   values.
     * @param DOMNode                       $parent_node The parent node of the
     *                                                   node to be loaded.
     * @param Horde_Kolab_Format_Xml_Helper $helper      A XML helper instance.
     * @param array                         $params      Additiona parameters for
     *                                                   this parse operation.
     *
     * @return DOMNode|boolean The named DOMNode or false if no node value was
     *                         found.
     */
    public function load(
        $name,
        &$attributes,
        $parent_node,
        Horde_Kolab_Format_Xml_Helper $helper,
        $params = array()
    )
    {
        $result = parent::load($name, $attributes, $parent_node, $helper, $params);

        if ($node = $helper->findNodeRelativeTo('./' . $name, $parent_node)) {
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
     * @param string                        $name        The name of the attribute
     *                                                   to be updated.
     * @param mixed                         $value       The value to store.
     * @param DOMNode                       $parent_node The parent node of the
     *                                                   node that should be
     *                                                   updated.
     * @param Horde_Kolab_Format_Xml_Helper $helper      A XML helper instance.
     * @param array                         $params      The parameters for this
     *                                                   write operation.
     * @param DOMNode|NULL                  $old_node    The previous value (or
     *                                                   null if there is none).
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
        Horde_Kolab_Format_Xml_Helper $helper,
        $params = array(),
        $old_node = false
    )
    {
        $node = parent::saveNodeValue($name, $value, $parent_node, $helper, $params, $old_node);
        // Add attributes
        $node->setAttribute('cycle', $value['cycle']);
        if (isset($value['type'])) {
            $node->setAttribute('type', $value['type']);
        }
        return $node;
    }
}
