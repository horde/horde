<?php
/**
 * Copyright 2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Url
 */

/**
 * An object to handle Data URLs (RFC 2397).
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Url
 */
class Horde_Url_Data
{
    /**
     * Should data be base64 encoded?
     *
     * @var boolean
     */
    public $base64 = true;

    /**
     * Binary data.
     *
     * @var string
     */
    public $data = '';

    /**
     * The MIME type.
     *
     * @var string
     */
    public $type = 'application/octet-stream';

    /**
     * Create a new object from existing data.
     *
     * @param string $type     The MIME type of the data.
     * @param string $data     The data.
     * @param boolean $base64  Should data be base64 encoded?
     */
    static public function create($type = null, $data = null, $base64 = true)
    {
        $ob = new self();

        if (!is_null($type)) {
            $ob->type = $type;
        }

        if (!is_null($data)) {
            $ob->data = $data;
        }

        $ob->base64 = (bool)$base64;

        return $ob;
    }

    /**
     * Constructor.
     *
     * @param string $data  An RFC 2397 compliant data string.
     */
    public function __construct($data = null)
    {
        if (!is_null($data) && (strpos($data, 'data:') === 0)) {
            $fp = fopen($data, 'r');
            $this->data = stream_get_contents($fp);
            $meta = stream_get_meta_data($fp);
            $this->type = $meta['mediatype'];
        }
    }

    /**
     * Output RFC 2397 compliant data string.
     */
    public function __toString()
    {
        return 'data:' . htmlspecialchars($this->type) .
            ($this->base64
                ? ';base64,' . base64_encode($this->data)
                : ',' . rawurlencode($this->data));
    }

}
