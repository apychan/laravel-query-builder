<?php


namespace Spatie\QueryBuilder\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class FiltersExactOr implements Filter
{
    protected $relationConstraints = [];

    /** @var bool */
    protected $addRelationConstraint = true;

    public function __construct(bool $addRelationConstraint = true)
    {
        $this->addRelationConstraint = $addRelationConstraint;
    }

    public function __invoke(Builder $query, $value, string $property)
    {
        if ($this->addRelationConstraint) {
            if ($this->isRelationProperty($query, $property)) {
                $this->withRelationConstraint($query, $value, $property);

                return;
            }
        }

        if (is_array($value)) {
            $query->orWhereIn($query->qualifyColumn($property), $value);

            return;
        }

        if ($this->isRelationColumn($query, $property)) {
            $query->Where($query->qualifyColumn($property), '=', $value);
        } else {
            $query->orWhere($query->qualifyColumn($property), '=', $value);
        }
    }

    protected function isRelationProperty(Builder $query, string $property): bool
    {
        if (! Str::contains($property, '.')) {
            return false;
        }

        if (in_array($property, $this->relationConstraints)) {
            return false;
        }

        $firstRelationship = explode('.', $property)[0];

        if (! method_exists($query->getModel(), $firstRelationship)) {
            return false;
        }

        return is_a($query->getModel()->{$firstRelationship}(), Relation::class);
    }

    protected function isRelationColumn(Builder $query, string $property): bool
    {
        if (! Str::contains($property, '.')) {
            return false;
        }

        if (!in_array($property, $this->relationConstraints)) {
            return false;
        }

        $exploded = explode('.', $property);
        $firstRelationship = $exploded[0];
        $column = $exploded[1] ?? null;

        if ($query->getModel()->getTable() !== $firstRelationship || is_null($column)) {
            return false;
        }

        return true;
    }

    protected function withRelationConstraint(Builder $query, $value, string $property)
    {
        [$relation, $property] = collect(explode('.', $property))
            ->pipe(function (Collection $parts) {
                return [
                    $parts->except(count($parts) - 1)->implode('.'),
                    $parts->last(),
                ];
            });

        $query->orWhereHas($relation, function (Builder $query) use ($value, $property) {
            $this->relationConstraints[] = $property = $query->qualifyColumn($property);

            $this->__invoke($query, $value, $property);
        });
    }
}
