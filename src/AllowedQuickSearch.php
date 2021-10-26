<?php


namespace Spatie\QueryBuilder;

use Illuminate\Support\Collection;
use Spatie\QueryBuilder\QuickSearches\QuickSearch;
use Spatie\QueryBuilder\QuickSearches\QuickSearchesCallback;
use Spatie\QueryBuilder\QuickSearches\QuickSearchesExact;
use Spatie\QueryBuilder\QuickSearches\QuickSearchesPartial;
use Spatie\QueryBuilder\QuickSearches\QuickSearchesScope;
use Spatie\QueryBuilder\QuickSearches\QuickSearchesTrashed;

class AllowedQuickSearch
{
    /** @var \Spatie\QueryBuilder\QuickSearches\QuickSearch */
    protected $quickSearchClass;

    /** @var string */
    protected $name;

    /** @var string */
    protected $internalName;

    /** @var \Illuminate\Support\Collection */
    protected $ignored;

    /** @var mixed */
    protected $default;

    public function __construct(string $name, QuickSearch $quickSearchClass, ?string $internalName = null)
    {
        $this->name = $name;

        $this->quickSearchClass = $quickSearchClass;

        $this->ignored = Collection::make();

        $this->internalName = $internalName ?? $name;
    }

    public function quickSearch(QueryBuilder $query, $value)
    {
        $valueToQuickSearch = $this->resolveValueForQuickSearch($value);

        if (is_null($valueToQuickSearch)) {
            return;
        }

        ($this->quickSearchClass)($query->getEloquentBuilder(), $valueToQuickSearch, $this->internalName);
    }

    public static function setQuickSearchArrayValueDelimiter(string $delimiter = null): void
    {
        if (isset($delimiter)) {
            QueryBuilderRequest::setQuickSearchArrayValueDelimiter($delimiter);
        }
    }

    public static function exact(string $name, ?string $internalName = null, bool $addRelationConstraint = true, string $arrayValueDelimiter = null): self
    {
        static::setQuickSearchArrayValueDelimiter($arrayValueDelimiter);

        return new static($name, new QuickSearchesExact($addRelationConstraint), $internalName);
    }

    public static function partial(string $name, $internalName = null, bool $addRelationConstraint = true, string $arrayValueDelimiter = null): self
    {
        static::setQuickSearchArrayValueDelimiter($arrayValueDelimiter);

        return new static($name, new QuickSearchesPartial($addRelationConstraint), $internalName);
    }

    public static function scope(string $name, $internalName = null, string $arrayValueDelimiter = null): self
    {
        static::setQuickSearchArrayValueDelimiter($arrayValueDelimiter);

        return new static($name, new QuickSearchesScope(), $internalName);
    }

    public static function callback(string $name, $callback, $internalName = null, string $arrayValueDelimiter = null): self
    {
        static::setQuickSearchArrayValueDelimiter($arrayValueDelimiter);

        return new static($name, new QuickSearchesCallback($callback), $internalName);
    }

    public static function trashed(string $name = 'trashed', $internalName = null): self
    {
        return new static($name, new QuickSearchesTrashed(), $internalName);
    }

    public static function custom(string $name, QuickSearch $quickSearchClass, $internalName = null, string $arrayValueDelimiter = null): self
    {
        static::setQuickSearchArrayValueDelimiter($arrayValueDelimiter);

        return new static($name, $quickSearchClass, $internalName);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isForQuickSearch(string $quickSearchName): bool
    {
        return $this->name === $quickSearchName;
    }

    public function ignore(...$values): self
    {
        $this->ignored = $this->ignored
            ->merge($values)
            ->flatten();

        return $this;
    }

    public function getIgnored(): array
    {
        return $this->ignored->toArray();
    }

    public function getInternalName(): string
    {
        return $this->internalName;
    }

    public function default($value): self
    {
        $this->default = $value;

        return $this;
    }

    public function getDefault()
    {
        return $this->default;
    }

    public function hasDefault(): bool
    {
        return isset($this->default);
    }

    protected function resolveValueForQuickSearch($value)
    {
        if (is_array($value)) {
            $remainingProperties = array_diff_assoc($value, $this->ignored->toArray());

            return ! empty($remainingProperties) ? $remainingProperties : null;
        }

        return ! $this->ignored->contains($value) ? $value : null;
    }
}
