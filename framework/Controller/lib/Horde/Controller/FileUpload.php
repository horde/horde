<?php
/**
 * Copyright 2007 Maintainable Software, LLC
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php
 * @category   Horde
 * @package    Horde_Controller
 */

/**
 * A file upload from multipart form
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php
 * @category   Horde
 * @package    Horde_Controller
 */
class Horde_Controller_FileUpload
{
    public $originalFilename = null;
    public $length           = null;
    public $contentType      = null;
    public $path             = null;

    public function __construct($options)
    {
        $this->originalFilename = isset($options['name'])     ? $options['name']     : null;
        $this->length           = isset($options['size'])     ? $options['size']     : null;
        $this->contentType      = isset($options['type'])     ? $options['type']     : null;
        $this->path             = isset($options['tmp_name']) ? $options['tmp_name'] : null;
    }

}
