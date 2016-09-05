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

    public function interval($quantity, $amount)
    {
        $what = null;
        switch ($quantity) {
            case self::SECOND: $what = 'second'; break;
            case self::MINUTE: $what = 'minute'; break;
            case self::HOUR: $what = 'hour'; break;
            case self::DAY: $what = 'day'; break;
            case self::WEEK: $what = 'week'; break;
            case self::MONTH: $what = 'month'; break;
            case self::YEAR: $what = 'year'; break;
        }
        return sprintf("'%d %s'::interval", $amount, $what);
    }

    public function lastInsertId($object = null)
    {
        return parent::lastInsertId("{$object}_id_seq");
    }

    public function random()
    {
        return 'RANDOM()';
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
}

