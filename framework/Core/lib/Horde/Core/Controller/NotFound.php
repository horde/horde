<?php
/**
 * @category Horde
 * @package  Core
 */
class Horde_Core_Controller_NotFound implements Horde_Controller
{
    /**
     */
    public function processRequest(Horde_Controller_Request $request,
                                   Horde_Controller_Response $response)
    {
        $response->setHeader('HTTP/1.0 404 ', 'Not Found');
        $response->setBody('<h1>404 File Not Found</h1>');
    }
}
