<?php
/**
 * Horde_Form_Action_ConditionalEnable is a Horde_Form_Action that
 * enables or disables an element based on the value of another element
 *
 * Format of the $params passed to the constructor:
 * <pre>
 *  $params = array(
 *      'target'  => '[name of element this is conditional on]',
 *      'enabled' => 'true' | 'false',
 *      'values'  => array([target values to check])
 *  );
 * </pre>
 *
 * So $params = array('foo', 'true', array(1, 2)) will enable the field this
 * action is attached to if the value of 'foo' is 1 or 2, and disable it
 * otherwise.
 *
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author  Matt Kynaston <matt@kynx.org>
 * @package Form
 */
class Horde_Form_Action_ConditionalEnable extends Horde_Form_Action_conditional_enable {
}
