<?php
/**
 * Whups Hooks configuration file.
 *
 * THE HOOKS PROVIDED IN THIS FILE ARE EXAMPLES ONLY.  DO NOT ENABLE THEM
 * BLINDLY IF YOU DO NOT KNOW WHAT YOU ARE DOING.  YOU HAVE TO CUSTOMIZE THEM
 * TO MATCH YOUR SPECIFIC NEEDS AND SYSTEM ENVIRONMENT.
 *
 * For more information please see the horde/config/hooks.php.dist file.
 */
class Whups_Hooks
{
    /**
     * Customize the order and grouping of ticket fields.
     *
     * @param string $type   A ticket type.
     * @param array $fields  A list of field names.
     *
     * @return array  A one-dimensional array with field names, or a
     *                two-dimensional array with header labels as keys and
     *                field names as values.
     */
//    public function group_fields($type, $fields)
//    {
//        // Example #1: Split all fields into two groups, one for custom
//        //             attribute fields and one for the regular fields.
//        $common_fields = $attributes = array();
//        foreach ($fields as $field) {
//            if (substr($field, 0, 10) == 'attribute_') {
//                $attributes[] = $field;
//            } else {
//                $common_fields[] = $field;
//            }
//        }
//        return array('Common Fields' => $common_fields,
//                     'Attributes' => $attributes);
//
//        // Example #2: Move the 'queue' field at the top of the regular
//        //             fields list.
//        $new_fields = array();
//        foreach ($fields as $field) {
//            if ($field == 'queue') {
//                array_unshift($new_fields, $field);
//            } else {
//                array_push($new_fields, $field);
//            }
//        }
//        return $new_fields;
//    }

    /**
     * Intercept ticket changes.
     *
     * @param Whups_Ticket $ticket  A ticket object.
     * @param array $changes        A list of change hashes.
     *
     * @return array  The modified changes.
     */
//    public function ticket_update($ticket, $changes)
//    {
//        // Example #1: If a comment has been added to a closed ticket,
//        //             re-open the ticket and set the state to "assigned".
//        //             You might want to use numeric ids for the 'to' item in
//        //             a real life hook.
//        /* We only want to change the ticket state if it is closed, a comment
//         * has been added, and the state hasn't been changed already. */
//        if (!empty($changes['comment']) &&
//            empty($changes['state']) &&
//            $ticket->get('state_category') == 'resolved') {
//            /* Pick the first state from the state category 'assigned'. */
//            $states = $GLOBALS['whups_driver']->getStates($ticket->get('type'),
//                                                          'assigned');
//            /* These three item have to exist in a change set. */
//            $changes['state'] = array(
//                'to' => key($states),
//                'from' => $ticket->get('state'),
//                'from_name' => $ticket->get('state_name'));
//        }
// 
//        // Example #2: Simple spam check.
//        if (isset($changes['comment']['to']) &&
//            stripos($changes['comment']['to'], 'some spam text') !== false) {
//            throw new Whups_Exception('Spammer!');
//        }
// 
//        return $changes;
//    }
}
