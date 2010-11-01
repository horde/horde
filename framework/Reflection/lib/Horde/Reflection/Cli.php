<?php
/**
 * The Horde_Reflection_Cli class renders method documention on the command line.
 *
 * Based on the PEAR XML_RPC2_Server_Method class by Sergio Carvalho
 *
 * Copyright 2004-2006 Sergio Gonalves Carvalho
 *                     (<sergio.carvalho@portugalmail.com>)
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Sergio Carvalho <sergio.carvalho@portugalmail.com>
 * @author  Duck <duck@obala.net>
 * @package Horde_Reflection
 */
class Horde_Reflection_CLI extends Horde_Reflection {

    /**
     * Cli inteface
     */
    private $_cli;

    /**
     * Constructor.
     *
     * @param ReflectionMethod $method  The PHP method to introspect.
     */
    public function __construct(ReflectionFunction $method)
    {
        $this->_cli = Horde_Cli::init();

        parent::__construct($method);
    }

    /**
     * Returns a signature of the method.
     *
     * @return string  Method signature.
     */
    private function _getSignature()
    {
        $name = $this->_name;
        $returnType = $this->_returns;

        $title = substr($name, strpos($name, '_', 2) + 1);

        $result = $this->_cli->yellow($title) . '  ' .  $this->_help . "\n";
        $result .= $this->_cli->blue($returnType) . ' ';
        $result .=  $this->_cli->green($title) . ' ';
        $result .= "(";
        $first = true;
        $nbr = 0;

        while (list($name, $parameter) = each($this->_parameters)) {
            $nbr++;
            if ($nbr == $this->_numberOfRequiredParameters + 1) {
                $result .= " [ ";
            }
            if ($first) {
                $first = false;
            } else {
                $result .= ', ';
            }
            $type = $parameter['type'];
            $result .= $this->_cli->red($type) . ' ';
            $result .= $this->_cli->blue($name);
        }
        reset($this->_parameters);
        if ($nbr > $this->_numberOfRequiredParameters) {
            $result .= " ] ";
        }
        $result .= ")";
        return $result;
    }

    /**
     * Returns a complete description of the method.
     *
     * @return string  A snippet with the method documentation.
     */
    public function autoDocument()
    {
        $this->_cli->writeln();
        $this->_cli->writeln($this->_getSignature());

        if (count($this->_parameters) > 0) {
            $out = $this->_cli->indent("Type\tName\tDocumentation\n");
            while (list($name, $parameter) = each($this->_parameters)) {
                $type = $parameter['type'];
                if (is_array($type)) {
                    $type = implode(' | ', $type);
                }
                if (isset($parameter['doc'])) {
                    $doc = trim($parameter['doc']);
                } else {
                    $doc = '';
                }
                $out .= $this->_cli->indent("$type\t$name\t$doc\n");
            }
            $this->_cli->writeln($out);

            reset($this->_parameters);
        }
    }

}
