<?php
/**
 * Execute FQL  queries
 *
 * Copyright 2009-2011 The Horde Project (http://www.horde.org)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package Horde_Service_Facebook
 */
class Horde_Service_Facebook_Fql extends Horde_Service_Facebook_Base
{

    /**
     * Run a FQL query, optionally including the current session_key.
     *
     * http://developers.facebook.com/documentation.php?v=1.0&doc=fql
     *
     * @param string $query             The FQL to run.
     * @param boolean $include_session  Include the session_key
     *
     * @return array of hashes containing results.
     */
    public function run($query, $include_session = true)
    {
        $params = array('query' => $query);
        if ($include_session) {
            $params['session_key'] = $this->_facebook->auth->getSessionKey();
        }

        return $this->_facebook->callMethod('facebook.fql.query', $params);
    }

}