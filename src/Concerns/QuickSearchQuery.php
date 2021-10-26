<?php


namespace Spatie\QueryBuilder\Concerns;

use Spatie\QueryBuilder\AllowedQuickSearch;
use Spatie\QueryBuilder\Exceptions\InvalidQuickSearchQuery;

trait QuickSearchQuery
{
    /** @var \Illuminate\Support\Collection */
    protected $allowedQuickSearches;

    public function allowedQuickSearches($quickSearches): self
    {
        $quickSearches = is_array($quickSearches) ? $quickSearches : func_get_args();

        $this->allowedQuickSearches = collect($quickSearches)->map(function ($quickSearch) {
            if ($quickSearch instanceof AllowedQuickSearch) {
                return $quickSearch;
            }

            return AllowedQuickSearch::partial($quickSearch);
        });

        //$this->ensureAllQuickSearchesExist();

        $this->addQuickSearchesToQuery();

        return $this;
    }

    protected function addQuickSearchesToQuery()
    {
        $this->allowedQuickSearches->each(function (AllowedQuickSearch $quickSearch) {
            $value = $this->request->quickSearches();

            $quickSearch->quickSearch($this, $value);

            return;
        });
    }

    protected function findQuickSearch(string $property): ?AllowedQuickSearch
    {
        return $this->allowedQuickSearches
            ->first(function (AllowedQuickSearch $quickSearch) use ($property) {
                return $quickSearch->isForQuickSearch($property);
            });
    }

    protected function isQuickSearchRequested(AllowedQuickSearch $allowedQuickSearch): bool
    {
        return $this->request->quickSearches()->has($allowedQuickSearch->getName());
    }

    protected function ensureAllQuickSearchesExist()
    {
        if (config('query-builder.disable_invalid_filter_query_exception')) {
            return;
        }

        $quickSearchNames = $this->request->quickSearches()->keys();

        $allowedQuickSearchNames = $this->allowedQuickSearches->map(function (AllowedQuickSearch $allowedQuickSearch) {
            return $allowedQuickSearch->getName();
        });

        $diff = $quickSearchNames->diff($allowedQuickSearchNames);

        if ($diff->count()) {
            throw InvalidQuickSearchQuery::quickSearchesNotAllowed($diff, $allowedQuickSearchNames);
        }
    }
}
