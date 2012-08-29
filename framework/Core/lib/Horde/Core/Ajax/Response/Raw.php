<?php
/**
 * A response object that directly outputs the data.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Core
 */
class Horde_Core_Ajax_Response_Raw extends Horde_Core_Ajax_Response
{
    /**
     * Charset of the data (if of type text/*).
     *
     * @var string
     */
    public $charset;

    /**
     * Content-type of the data.
     *
     * @var string
     */
    public $type;

    /**
     * @param string $type     Content-type of the data.
     * @param string $charset  Charset of the data (if of type text/*).
     */
    public function __construct($data = null, $type = 'text/plain',
                                $charset = 'UTF-8')
    {
        parent::__construct($data);

        $this->_charset = $charset;
        $this->_type = $type;
    }

    /**
     */
    public function send()
    {
        $type = trim($this->type);
        if (stripos($type, 'text/') === 0) {
            $type .= '; charset=' . $this->charset;
        }

        header('Content-Type: ' . $type);
        echo $this->data;
    }

}
