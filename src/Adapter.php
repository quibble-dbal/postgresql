<?php

/**
 * PostgreSQL-specific database abstraction layer.
 *
 * @package Quibble\Postgresql
 * @author Marijn Ophorst <marijn@monomelodies.nl>
 * @copyright MonoMelodies 2008, 2009, 2010, 2011, 2012, 2015, 2016, 2017
 */

namespace Quibble\Postgresql;

use Quibble\Dabble;
use DomainException;

/**
 * PostgreSQL database abstraction class.
 */
class Adapter extends Dabble\Adapter
{
    public function __construct(string $dsn, ?string $username = null, ?string $password = null, ?array $options = null)
    {
        return parent::__construct("pgsql:$dsn", $username, $password, $options);
    }

    public function any(string $key, $values, &$bind) : string
    {
        $els = [];
        if (!is_array($values)) {
            $values = [$values];
        }
        foreach ($values as $value) {
            $els[] = sprintf(
                '%s = ANY(%s)',
                implode('', $this->values([$value])),
                $key
            );
        }
        return '('.implode(' OR ', $els).')';
    }

    public function lastInsertId(?string $object = null) : string
    {
        return parent::lastInsertId("{$object}_id_seq");
    }

    public function interval(string $unit, int $amount) : string
    {
        return sprintf("'%d %s'::interval", $amount, $unit);
    }

    public function random() : string
    {
        return 'RANDOM()';
    }

    public function now() : string
    {
        return 'NOW()';
    }

    /**
     * The native driver does not convert PostgreSQL arrays to PHP arrays.
     * So, this method does. :)
     *
     * @param string $string A database-field containing an array.
     * @return array A PHP array of its values.
     * @throws DomainException if the string passed is not a PostgreSQL array
     */
    public static function stringToArray(string $string) : array
    {
        if (!preg_match("@^{.*?}$@", $string)) {
            throw new DomainException("Not array-like: $string");
        }
        $parts = json_decode('['.substr($string, 1, -1).']');
        foreach ($parts as &$part) {
            if (preg_match('@^{.*?}@', $part)) {
                $part = self::stringToArray($part);
            }
        }
        return $parts;
    }

    /**
     * The native driver does not convert PHP arrays to PostgreSQL arrays.
     * So, this method does. :)
     *
     * @param array $array An array.
     * @return string A string suitable for insert/update.
     */
    public static function arrayToString(array $array) : string
    {
        foreach ($array as &$sub) {
            if (is_array($sub)) {
                $sub = self::arrayToString($sub);
            } else {
                $sub = json_encode($sub);
            }
        }
        return '{'.implode(',', $array).'}';
    }
}

