<?php
/**
 * The Horde_Reflection_Html class renders method documention in the HTML
 * format.
 *
 * Based on the PEAR XML_RPC2_Server_Method class by Sergio Carvalho
 *
 * Copyright 2004-2006 Sergio Gonalves Carvalho
 *                     (<sergio.carvalho@portugalmail.com>)
 * Copyright 2008-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Sergio Carvalho <sergio.carvalho@portugalmail.com>
 * @author  Duck <duck@obala.net>
 * @author  Jan Schneider <jan@horde.org>
 * @package Horde_Reflection
 */
class Horde_Reflection_Html extends Horde_Reflection {

    /**
     * Returns a signature of the method.
     *
     * @return string  Method signature.
     */
    private function _getSignature()
    {
        $name = $this->_name;
        $returnType = $this->_returns;
        $result  = "<span class=\"type\">($returnType)</span> ";
        $result .= "<span class=\"name\">$name</span>";
        $result .= "<span class=\"other\">(</span>";
        $first = true;
        $nbr = 0;
        while (list($name, $parameter) = each($this->_parameters)) {
            $nbr++;
            if ($nbr == $this->_numberOfRequiredParameters + 1) {
                $result .= "<span class=\"other\">[</span>";
            }
            if ($first) {
                $first = false;
            } else {
                $result .= ', ';
            }
            $type = $parameter['type'];
            $result .= "<span class=\"paratype\">($type) </span>";
            $result .= "<span class=\"paraname\">$name</span>";
        }
        reset($this->_parameters);
        if ($nbr > $this->_numberOfRequiredParameters) {
            $result .= "<span class=\"other\">]</span>";
        }
        $result .= "<span class=\"other\">)</span>";
        return $result;
    }

    /**
     * Returns a complete HTML description of the method.
     *
     * @return string  A HTML snippet with the method documentation.
     */
    public function autoDocument()
    {
        $signature = $this->_getSignature();
        $id = md5($this->_name);
        $help = nl2br(htmlentities($this->_help));
        $html = $this->_header();
        $html .= "  <h3><a name=\"$id\">$signature</a></h3>\n";
        $html .= "      <p><b>Description :</b></p>\n";
        $html .= "      <div class=\"description\">\n";
        $html .= "        $help\n";
        $html .= "      </div>\n";
        if (count($this->_parameters)>0) {
            $html .= "      <p><b>Parameters : </b></p>\n";
            if (count($this->_parameters)>0) {
                $html .= "      <table>\n";
                $html .= "        <tr><td><b>Type</b></td><td><b>Name</b></td><td><b>Documentation</b></td></tr>\n";
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
                    $html .= "        <tr><td>$type</td><td>$name</td><td>$doc</td></tr>\n";
                }
                reset($this->_parameters);
                $html .= "      </table>\n";
            }
        }
        $html .= $this->_footer();

        return $html;
    }

    private function _header()
    {
        $html = '
        <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
        <html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
        <head>
            <meta http-equiv="Content-Type" content="text/HTML; charset=UTF-8"  />
            <title>Available XMLRPC methods for this server</title>
            <style type="text/css">
            li,p { font-size: 10pt; font-family: Arial,Helvetia,sans-serif; }
            a:link { background-color: white; color: blue; text-decoration: underline; font-weight: bold; }
            a:visited { background-color: white; color: blue; text-decoration: underline; font-weight: bold; }
            table { border-collapse:collapse; width: 100% }
            table,td { padding: 5px; border: 1px solid black; }
            div.bloc { border: 1px dashed gray; padding: 10px; margin-bottom: 20px; }
            div.description { border: 1px solid black; padding: 10px; }
            span.type { background-color: white; color: gray; font-weight: normal; }
            span.paratype { background-color: white; color: gray; font-weight: normal; }
            span.name { background-color: white; color: #660000; }
            span.paraname { background-color: white; color: #336600; }
            img { border: 0px; }
            li { font-size: 12pt; }
            </style>
        </head>
        <body>
        ';

        return $html;
    }

    private function _footer()
    {
        return '  </body></html>';
    }

}

