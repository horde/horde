<?php
/**
 * This is a utility class, every method is static.
 *
 * Copyright 1999-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Jan Schneider <jan@horde.org>
 * @package SQL
 */
class Horde_Sql
{
    /**
     * Returns a boolean expression using the specified operator. Uses
     * database-specific casting, if necessary.
     *
     * @param DB $dbh        The PEAR::DB database object.
     * @param string $lhs    The column or expression to test.
     * @param string $op     The operator.
     * @param string $rhs    The comparison value.
     * @param boolean $bind  If true, the method returns the query and a list
     *                       of values suitable for binding as an array.
     * @param array $params  Any additional parameters for the operator.
     *
     * @return mixed  The SQL test fragment, or an array containing the query
     *                and a list of values if $bind is true.
     */
    static public function buildClause($dbh, $lhs, $op, $rhs, $bind = false, $params = array())
    {
        $type = $dbh instanceof Horde_Db_Adapter ? Horde_String::lower($dbh->adapterName()) : $dbh->phptype;

        switch ($op) {
        case '|':
        case '&':
            switch ($type) {
            case 'pgsql':
            case 'pdo_postgresql':
                // Only PgSQL 7.3+ understands SQL99 'SIMILAR TO'; use
                // ~ for greater backwards compatibility.
                $query = 'CASE WHEN CAST(%s AS VARCHAR) ~ \'^-?[0-9]+$\' THEN (CAST(%s AS INTEGER) %s %s) <> 0 ELSE FALSE END';
                if ($bind) {
                    return array(sprintf(self::escapePrepare($query),
                                         self::escapePrepare($lhs),
                                         self::escapePrepare($lhs),
                                         self::escapePrepare($op),
                                         '?'),
                                 array((int)$rhs));
                } else {
                    return sprintf($query, $lhs, $lhs, $op, (int)$rhs);
                }

            case 'oci8':
                // Oracle uses & for variables. We need to use the bitand
                // function that is available, but may be unsupported.
                $query = 'bitand(%s, %s) = %s';
                if ($bind) {
                    return array(sprintf(self::escapePrepare($query),
                                         self::escapePrepare($lhs), '?', '?'),
                                 array((int)$rhs, (int)$rhs));
                } else {
                    return sprintf($query, $lhs, (int)$rhs, (int)$rhs);
                }

            case 'mssql':
                // MSSQL must have a valid boolean expression
                $query = '(CASE WHEN ISNUMERIC(%s) = 1 THEN (%s & %s) ELSE %s END) = %s';
                if ($bind) {
                    return array(sprintf(self::escapePrepare($query),
                                         self::escapePrepare($lhs),
                                         self::escapePrepare($lhs), '?', '?', '?'),
                                 array((int)$rhs, (int)$rhs - 1, (int)$rhs));
                } else {
                    return sprintf($query, $lhs, $lhs, (int)$rhs, (int)$rhs - 1, (int)$rhs);
                }

            case 'odbc':
                // ODBC must have a valid boolean expression
                $query = '(%s & %s) = %s';
                if ($bind) {
                    return array(sprintf(self::escapePrepare($query),
                                         self::escapePrepare($lhs), '?', '?'),
                                 array((int)$rhs, (int)$rhs));
                } else {
                    return sprintf($query, $lhs, (int)$rhs, (int)$rhs);
                }

            default:
                if ($bind) {
                    return array($lhs . ' ' . self::escapePrepare($op) . ' ?',
                                 array((int)$rhs));
                } else {
                    return $lhs . ' ' . $op . ' ' . (int)$rhs;
                }
            }

        case '~':
            if ($type == 'mysql' || $type == 'mysqli' || $type == 'pdo_mysql') {
                $op = 'REGEXP';
            }
            if ($bind) {
                return array($lhs . ' ' . $op . ' ?', array($rhs));
            } else {
                return $lhs . ' ' . $op . ' ' . $rhs;
            }

        case 'IN':
            if ($bind) {
                if (is_array($rhs)) {
                    return array($lhs . ' IN (?' . str_repeat(', ?', count($rhs) - 1) . ')', $rhs);
                } else {
                    // We need to bind each member of the IN clause
                    // separately to ensure proper quoting.
                    if (substr($rhs, 0, 1) == '(') {
                        $rhs = substr($rhs, 1);
                    }
                    if (substr($rhs, -1) == ')') {
                        $rhs = substr($rhs, 0, -1);
                    }

                    $ids = preg_split('/\s*,\s*/', $rhs);

                    return array($lhs . ' IN (?' . str_repeat(', ?', count($ids) - 1) . ')', $ids);
                }
            } else {
                if (is_array($rhs)) {
                    return $lhs . ' IN ' . implode(', ', $rhs);
                } else {
                    return $lhs . ' IN ' . $rhs;
                }
            }

        case 'LIKE':
            if ($type == 'pgsql' || $type == 'pdo_pgsql') {
                $query = '%s ILIKE %s';
            } else {
                $query = 'LOWER(%s) LIKE LOWER(%s)';
            }
            if ($bind) {
                if (empty($params['begin'])) {
                    return array(sprintf($query,
                                         self::escapePrepare($lhs),
                                         '?'),
                                 array('%' . $rhs . '%'));
                } else {
                    return array(sprintf('(' . $query . ' OR ' . $query . ')',
                                         self::escapePrepare($lhs),
                                         '?',
                                         self::escapePrepare($lhs),
                                         '?'),
                                 array($rhs . '%', '% ' . $rhs . '%'));
                }
            } else {
                if (empty($params['begin'])) {
                    return sprintf($query,
                                   $lhs,
                                   $dbh->quote('%' . $rhs . '%'));
                } else {
                    return sprintf('(' . $query . ' OR ' . $query . ')',
                                   $lhs,
                                   $dbh->quote($rhs . '%'),
                                   $lhs,
                                   $dbh->quote('% ' . $rhs . '%'));
                }
            }

        default:
            if ($bind) {
                return array($lhs . ' ' . self::escapePrepare($op) . ' ?', array($rhs));
            } else {
                return $lhs . ' ' . $op . ' ' . $dbh->quote($rhs);
            }
        }
    }

    /**
     * Escapes all characters in a string that are placeholders for the
     * prepare/execute methods of the DB package.
     *
     * @param string $query  A string to escape.
     *
     * @return string  The correctly escaped string.
     */
    static public function escapePrepare($query)
    {
        return preg_replace('/[?!&]/', '\\\\$0', $query);
    }
}
