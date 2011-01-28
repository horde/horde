<?php
/**
 * The Horde_Reflection_Wiki class renders method documention in the Text_Wiki
 * format.
 *
 * Copyright 2008-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Duck <duck@obala.net>
 * @author  Jan Schneider <jan@horde.org>
 * @package Horde_Reflection
 */
class Horde_Reflection_Wiki extends Horde_Reflection {

    /**
     * Returns a signature of the method.
     *
     * @return string  Method signature.
     */
    private function _getSignature()
    {
        $name = $this->_name;
        $returnType = $this->_returns;

        $title = substr($this->_name, strpos($name, '_', 2) + 1);
        $desc = substr($this->_help, 0, 20) . '...';

        $result = "++$title - $desc\n";
        $result .= "##gray|($returnType)## ";
        $result .= "##660000|$title##";
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
            $result .= "##gray|($type) ##";
            $result .= "##336600|$name##";
        }
        reset($this->_parameters);
        if ($nbr > $this->_numberOfRequiredParameters) {
            $result .= " ] ";
        }
        $result .= ")";
        return $result;
    }

    /**
     * Returns a complete wiki description of the method.
     *
     * @return string  A wiki snippet with the method documentation.
     */
    public function autoDocument()
    {
        $signature = $this->_getSignature();
        $id = md5($this->_name);
        $help = trim(strip_tags($this->_help));

        $html = "$signature\n";
        if ($help) {
            $html .= "\nDescription : \n<code>\n$help\n</code>\n";
        }

        if (count($this->_parameters)>0) {
            $html .= "Parameters: \n";
            if (count($this->_parameters)>0) {

                $html .= "||~ Type||~ Name||~ Documentation||\n";
                while (list($name, $parameter) = each($this->_parameters)) {
                    $type = $parameter['type'];
                    if (is_array($type)) {
                        $type = implode(' | ', $type);
                    }
                    if (isset($parameter['doc'])) {
                        $doc = htmlentities($parameter['doc']);
                    } else {
                        $doc = '';
                        echo 'Missing doc for ' . $this->_name . '<br />';
                    }
                    $html .= "||$type||$name||$doc||\n";
                }
                reset($this->_parameters);
            }
        }

        return $html;
    }

}
