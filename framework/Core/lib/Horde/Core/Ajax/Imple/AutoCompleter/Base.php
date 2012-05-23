<?php
/**
 * Base class for configuring a javascript autocompleter.
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
abstract class Horde_Core_Ajax_Imple_AutoCompleter_Base
{
    /**
     * Configuration parameters.
     *
     * @var array
     */
    public $params = array();

    /**
     * Constructor.
     *
     * @param array $params  Configuration options.
     */
    public function __construct(array $params = array())
    {
        $this->params = $params;
    }

    /**
     * Generate the javascript necessary to instantiate the autocompleter
     * object.
     *
     * @param Horde_Core_Ajax_Imple_AutoCompleter $ac  The underlying imple
     *                                                 object.
     */
    abstract function generate(Horde_Core_Ajax_Imple_AutoCompleter $ac);

}
