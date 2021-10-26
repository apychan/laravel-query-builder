<?php

namespace Spatie\QueryBuilder\QuickSearches;

use Illuminate\Database\Eloquent\Builder;

class QuickSearchesCallback implements QuickSearch
{
    /**
     * @var callable a PHP callback of the following signature:
     * `function (\Illuminate\Database\Eloquent\Builder $builder, mixed $value, string $property)`
     */
    private $callback;

    public function __construct($callback)
    {
        $this->callback = $callback;
    }

    /** {@inheritdoc} */
    public function __invoke(Builder $query, $value, string $property)
    {
        return call_user_func($this->callback, $query, $value, $property);
    }
}
