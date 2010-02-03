<?php
/**
 * This is a utility class, every method is static.
 *
 * Copyright 1999-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @package  Horde_Ldap
 */
class Horde_Ldap
{
    /**
     * Return a boolean expression using the specified operator.
     *
     * @param string $lhs    The attribute to test.
     * @param string $op     The operator.
     * @param string $rhs    The comparison value.
     * @param array $params  Any additional parameters for the operator.
     *
     * @return string  The LDAP search fragment.
     */
    static public function buildClause($lhs, $op, $rhs, $params = array())
    {
        switch ($op) {
        case 'LIKE':
            if (empty($rhs)) {
                return '(' . $lhs . '=*)';
            } elseif (!empty($params['begin'])) {
                return sprintf('(|(%s=%s*)(%s=* %s*))', $lhs, self::quote($rhs), $lhs, self::quote($rhs));
            } elseif (!empty($params['approximate'])) {
                return sprintf('(%s=~%s)', $lhs, self::quote($rhs));
            }
            return sprintf('(%s=*%s*)', $lhs, self::quote($rhs));

        default:
            return sprintf('(%s%s%s)', $lhs, $op, self::quote($rhs));
        }
    }

    /**
     * Escape characters with special meaning in LDAP searches.
     *
     * @param string $clause  The string to escape.
     *
     * @return string  The escaped string.
     */
    static public function quote($clause)
    {
        return str_replace(array('\\',   '(',  ')',  '*',  "\0"),
                           array('\\5c', '\(', '\)', '\*', "\\00"),
                           $clause);
    }

    /**
     * Take an array of DN elements and properly quote it according to RFC
     * 1485.
     *
     * @param array $parts  An array of tuples containing the attribute
     *                      name and that attribute's value which make
     *                      up the DN. Example:
     * <pre>
     * $parts = array(
     *     0 => array('cn', 'John Smith'),
     *     1 => array('dc', 'example'),
     *     2 => array('dc', 'com')
     * );
     * </pre>
     *
     * @return string  The properly quoted string DN.
     */
    static public function quoteDN($parts)
    {
        $dn = '';

        for ($i = 0, $cnt = count($parts); $i < $cnt; ++$i) {
            if ($i > 0) {
                $dn .= ',';
            }
            $dn .= $parts[$i][0] . '=';

            // See if we need to quote the value.
            if (preg_match('/^\s|\s$|\s\s|[,+="\r\n<>#;]/', $parts[$i][1])) {
                $dn .= '"' . str_replace('"', '\\"', $parts[$i][1]) . '"';
            } else {
                $dn .= $parts[$i][1];
            }
        }

        return $dn;
    }

}
