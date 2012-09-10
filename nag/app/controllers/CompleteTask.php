<?php
class Nag_CompleteTask_Controller extends Horde_Controller_Base
{
    public function processRequest(Horde_Controller_Request $request, Horde_Controller_Response $response)
    {
        /* Toggle the task's completion status if we're provided with a
         * valid task ID. */
        $requestVars = $request->getRequestVars();
        if (isset($requestVars['task']) && isset($requestVars['tasklist'])) {
            $nag_task = new Nag_CompleteTask();
            $result = $nag_task->result($requestVars['task'], $requestVars['tasklist']);
        } else {
            $result = array('error' => 'missing parameters');
        }

        $requestVars = $request->getGetVars();
        if (!empty($requestVars['format']) &&
            $requestVars['format'] == 'json') {
            $response->setContentType('application/json');
            $response->setBody(json_encode($result));
        } elseif ($requestVars['url']) {
            $response->setRedirectUrl($requestVars['url']);
        }
    }
}
