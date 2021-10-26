<?php

namespace Spatie\QueryBuilder\Concerns;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\QueryBuilder\Exceptions\AllowedFieldsMustBeCalledBeforeAllowedIncludes;
use Spatie\QueryBuilder\Exceptions\InvalidFieldQuery;
use Spatie\QueryBuilder\Exceptions\UnknownIncludedFieldsQuery;

trait AddsFieldsToQuery
{
    /** @var \Illuminate\Support\Collection */
    protected $allowedFields;

    public function allowedFields($fields): self
    {
        if ($this->allowedIncludes instanceof Collection) {
            throw new AllowedFieldsMustBeCalledBeforeAllowedIncludes();
        }

        $fields = is_array($fields) ? $fields : func_get_args();

        $this->allowedFields = collect($fields)
            ->map(function (string $fieldName) {
                return $this->prependField($fieldName);
            });

        $this->ensureAllFieldsExist();

        $this->addRequestedModelFieldsToQuery();

        return $this;
    }

    protected function addRequestedModelFieldsToQuery()
    {
        $modelTableName = $this->getModel()->getTable();
        $camelModelTableName = Str::camel($modelTableName);

        $modelFields = $this->request->fields()->get($camelModelTableName);

        $mustIncludeFields = $this->mustIncludeFields ?? collect([]);

        $modelFields = array_unique(array_merge($modelFields ?? [],  $mustIncludeFields->get($camelModelTableName) ?? []));

        if (empty($modelFields)) {
            throw InvalidFieldQuery::fieldsNotAllowed(collect(), $this->allowedFields);
        }

        $prependedFields = $this->prependFieldsWithTableName($modelFields, $modelTableName);

        $this->select($prependedFields);
    }

    public function getRequestedFieldsForRelatedTable(string $relation): array
    {
        $table = Str::plural(Str::snake($relation)); // TODO: make this configurable

        $fields = $this->request->fields()->mapWithKeys(function ($fields, $relation) {
            return [$relation => $fields];
        })->get($relation);

        $mustIncludeFields = $this->mustIncludeFields ?? collect([]);

        $fields = array_unique(array_merge($fields ?? [], $mustIncludeFields->get($relation) ?? []));

        if (!$fields) {
            throw InvalidFieldQuery::fieldsNotAllowed(collect([$relation]), $this->allowedFields);
        }

        if (!$this->allowedFields instanceof Collection) {
            // We have requested fields but no allowed fields (yet?)

            throw new UnknownIncludedFieldsQuery($fields);
        }

        return $fields;
    }

    protected function ensureAllFieldsExist()
    {
        $requestedFields = $this->request->fields()
            ->map(function ($fields, $model) {
                $tableName = $model;

                return $this->prependFieldsWithTableName($fields, $tableName);
            })
            ->flatten()
            ->unique();

        $unknownFields = $requestedFields->diff($this->allowedFields);

        if ($unknownFields->isNotEmpty()) {
            throw InvalidFieldQuery::fieldsNotAllowed($unknownFields, $this->allowedFields);
        }
    }

    protected function prependFieldsWithTableName(array $fields, string $tableName): array
    {
        return array_map(function ($field) use ($tableName) {
            return $this->prependField($field, $tableName);
        }, $fields);
    }

    protected function prependField(string $field, ?string $table = null): string
    {
        if (!$table) {
            $table = Str::camel($this->getModel()->getTable());
        }

        if (Str::contains($field, '.')) {
            // Already prepended

            return $field;
        }

        return "{$table}.{$field}";
    }
}
