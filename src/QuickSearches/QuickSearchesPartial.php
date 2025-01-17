<?php

namespace Spatie\QueryBuilder\QuickSearches;

use Illuminate\Database\Eloquent\Builder;

class QuickSearchesPartial extends QuickSearchesExact implements QuickSearch
{
    public function __invoke(Builder $query, $value, string $property)
    {
        if ($this->addRelationConstraint) {
            if ($this->isRelationProperty($query, $property)) {
                $this->withRelationConstraint($query, $value, $property);

                return;
            }
        }

        $wrappedProperty = $query->getQuery()->getGrammar()->wrap($query->qualifyColumn($property));

        $sql = "LOWER({$wrappedProperty}) LIKE ?";

        if (is_array($value)) {
            if (count(array_filter($value, 'strlen')) === 0) {
                return $query;
            }

            $query->orWhere(function (Builder $query) use ($value, $sql) {
                foreach (array_filter($value, 'strlen') as $partialValue) {
                    $partialValue = mb_strtolower($partialValue, 'UTF8');

                    $query->orWhereRaw($sql, ["%{$partialValue}%"]);
                }
            });

            return;
        }

        $value = mb_strtolower($value, 'UTF8');

        if ($this->isRelationColumn($query, $property)) {
            $query->whereRaw($sql, ["%{$value}%"]);
        } else {
            $query->orWhereRaw($sql, ["%{$value}%"]);
        }
    }
}
