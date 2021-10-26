<?php


namespace Spatie\QueryBuilder\Exceptions;

use Exception;

class InvalidQuickSearchValue extends Exception
{
    public static function make($value)
    {
        return new static("QuickSearch value `{$value}` is invalid.");
    }
}
