<?php

namespace Spatie\QueryBuilder\QuickSearches;

use Illuminate\Database\Eloquent\Builder;

interface QuickSearch
{
    public function __invoke(Builder $query, $value, string $property);
}
