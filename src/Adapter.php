<?php

/**
 * PostgreSQL-specific database abstraction layer.
 *
 * @package Quibble\Postgresql
 * @author Marijn Ophorst <marijn@monomelodies.nl>
 * @copyright MonoMelodies 2008, 2009, 2010, 2011, 2012, 2015, 2016
 */

namespace Quibble\Postgresql;

use Quibble\Dabble;

/**
 * PostgreSQL database abstraction class.
 */
class Adapter extends Dabble\Adapter
{
    public function __construct(
        $dsn,
        $username = null,
        $password = null,
        array $options = []
    ) {
        return parent::__construct(
            "pgsql:$dsn",
            $username,
            $password,
            $options
        );
    }

    public function any($key, $values, &$bind)
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

    public function lastInsertId($object = null)
    {
        return parent::lastInsertId("{$object}_id_seq");
    }

    public function interval($unit, $amount) : Dabble\Raw
    {
        return new Dabble\Raw(sprintf("'%d %s'::interval", $amount, $unit));
    }

    public function random() : Dabble\Raw
    {
        return new Dabble\Raw('RANDOM()');
    }

    public function now() : Dabble\Raw
    {
        return new Dabble\Raw('NOW()');
    }

    /**
     * The native driver does not convert PostgreSQL arrays to PHP arrays.
     * So, this method does. :)
     *
     * @param string $string A database-field containing an array.
     * @return array A PHP array of its values.
     */
    public static function stringToArray($string)
    {
        if (!preg_match("@^{.*?}$@", $string)) {
            return $string;
        }
        $parts = explode(',', substr($string, 1, -1));
        foreach ($parts as &$part) {
            $part = self::stringToArray($part);
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
        $json = json_encode($array);
        return '{'.substr($json, 1, -1).'}';
    }
}

