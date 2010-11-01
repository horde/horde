<?php
/**
 * @category Horde
 * @package  Scribe
 */

/**
 * @category Horde
 * @package  Scribe
 */
class Horde_Scribe_Client implements Horde_Scribe
{
    /**
     * @var TFramedTransport
     */
    private $_transport;

    /**
     * @var scribeClient
     */
    private $_client;

    public function connect($host = 'localhost', $port = 1463)
    {
        $socket = new TSocket($host, $port, true);
        $this->_transport = new TFramedTransport($socket);
        $protocol = new TBinaryProtocol($this->_transport, false, false);
        $this->_client = new scribeClient($protocol, $protocol);
    }

    public function log($category, $message)
    {
        $this->logMulti(array($this->makeEntry($category, $message)));
    }

    public function logMulti(array $messages)
    {
        $this->_transport->open();
        $this->_client->Log($messages);
        $this->_transport->close();
    }

    public function makeEntry($category, $message)
    {
        return new LogEntry(array('category' => $category, 'message' => $message));
    }
}
