<?php
if (version_compare(PHP_VERSION, '5.3.0', '<')) {
    /**
     * Horde base exception class that supports prior exception for PHP < 5.3.0
     *
     * Originates from
     * http://framework.zend.com/wiki/display/ZFPROP/previous+Exception+on+Zend_Exception+-+Marc+Bennewitz
     *
     * Copyright 2008-2012 Horde LLC (http://www.horde.org/)
     *
     * See the enclosed file COPYING for license information (LGPL). If you
     * did not receive this file, see http://www.horde.org/licenses/lgpl21.
     *
     * @category Horde
     * @package  Exception
     */
    class Horde_Exception extends Exception
    {
        private $_previous = null;

        /**
         * Error details that should not be part of the main exception message,
         * e.g. any additional debugging information.
         *
         * @var string
         */
        public $details;

        /**
         * Has this exception been logged?
         *
         * @var boolean
         */
        public $logged = false;

        /**
         * Construct the exception
         *
         * @param string $msg
         * @param int $code
         * @param Exception $previous
         */
        public function __construct($msg = '', $code = 0, Exception $previous = null)
        {
            parent::__construct($msg, $code);
            $this->_previous = $previous;
        }

        /**
         * Returns previous Exception
         *
         * @return Exception|null
         */
        final public function getPrevious()
        {
            return $this->_previous;
        }

        /**
         * String representation of the exception
         *
         * @return string
         */
        public function __toString()
        {
            if ($this->getPrevious()) {
                return $this->getPrevious()->__toString() . "\n\nNext " . parent::__toString();
            } else {
                return parent::__toString();
            }
        }

    }
} else {
    /**
     * Horde base exception class.
     *
     * Copyright 2008-2012 Horde LLC (http://www.horde.org/)
     *
     * See the enclosed file COPYING for license information (LGPL). If you
     * did not receive this file, see http://www.horde.org/licenses/lgpl21.
     *
     * @category Horde
     * @package  Exception
     */
    class Horde_Exception extends Exception
    {
        /**
         * Error details that should not be part of the main exception message,
         * e.g. any additional debugging information.
         *
         * @var string
         */
        public $details;

        /**
         * Has this exception been logged?
         *
         * @var boolean
         */
        public $logged = false;

    }
}
