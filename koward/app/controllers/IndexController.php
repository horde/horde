<?php
/**
 * @package Koward
 */

// @TODO Clean up
require_once dirname(__FILE__) . '/ApplicationController.php';

/**
 * @package Koward
 */
class IndexController extends Koward_ApplicationController
{
    protected $welcome;

    public function index()
    {
        $this->title = _("Index");
        $this->welcome = _("Welcome to the Koward administration interface");
    }
}