<?php


namespace Spatie\QueryBuilder\Exceptions;

use Illuminate\Http\Response;
use Illuminate\Support\Collection;

class InvalidQuickSearchQuery extends InvalidQuery
{
    /** @var \Illuminate\Support\Collection */
    public $unknownQuickSearches;

    /** @var \Illuminate\Support\Collection */
    public $allowedQuickSearches;

    public function __construct(Collection $unknownQuickSearches, Collection $allowedQuickSearches)
    {
        $this->unknownQuickSearches = $unknownQuickSearches;
        $this->allowedQuickSearches = $allowedQuickSearches;

        $unknownQuickSearches = $this->unknownQuickSearches->implode(', ');
        $allowedQuickSearches = $this->allowedQuickSearches->implode(', ');
        $message = "Requested quickSearch(es) `{$unknownQuickSearches}` are not allowed. Allowed quickSearch(es) are `{$allowedQuickSearches}`.";

        parent::__construct(Response::HTTP_BAD_REQUEST, $message);
    }

    public static function quickSearchesNotAllowed(Collection $unknownQuickSearches, Collection $allowedQuickSearches)
    {
        return new static(...func_get_args());
    }
}
