<?php
/**
 * @package Koward
 */

/**
 * @package Koward
 */
class IndexController extends Koward_Controller_Application
{
    protected $welcome;

    public function index()
    {
        $this->title = _("Index");
        $this->welcome = _("Welcome to the Koward administration interface");
    }
}