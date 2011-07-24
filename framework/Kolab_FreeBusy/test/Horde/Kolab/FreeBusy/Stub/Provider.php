<?php
class Horde_Kolab_FreeBusy_Stub_Provider
implements Horde_Kolab_FreeBusy_Provider
{
    public function trigger(Horde_Controller_Response $response, $params = array())
    {
        $type = isset($params->type) ? ' and retrieved data of type "' . $params->type . '"' : '';
        $response->setBody('triggered folder "' . $params->folder . '"' . $type);
    }

    public function fetch(Horde_Controller_Response $response, $params = array())
    {
        $response->setBody('fetched "' . $params->type . '" data for user "' . $params->owner . '"');
    }

    public function regenerate(Horde_Controller_Response $response, $params = array())
    {
        $response->setBody('regenerated');
    }

    public function delete(Horde_Controller_Response $response, $params = array())
    {
        $response->setBody('deleted data for user "' . $params->owner . '"');
    }
}
