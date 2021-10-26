<?php


namespace Spatie\QueryBuilder\Concerns;


use Illuminate\Support\Str;

trait AddMustIncludeFieldsToQuery
{
    /** @var \Illuminate\Support\Collection */
    protected $mustIncludeFields;

    public function mustIncludeFields($fields): self
    {
        $fields = is_array($fields) ? $fields : func_get_args();
        $newFields = [];

        foreach ($fields as $fieldName) {
            $exploded  = explode('.', $fieldName);
            $fieldKey = null;
            $lastIndex = count($exploded) - 1;

            for ($i = 0; $i < $lastIndex; $i++) {
                $fieldKey = $fieldKey . ($i === 0 ? '' : '.') . $exploded[$i];
            }

            if (!isset($fieldKey)) {
                $fieldKey = Str::camel($this->getModel()->getTable());
            }

            $newFields[$fieldKey][] = $exploded[$lastIndex];
        }

        $this->mustIncludeFields = collect($newFields);

        return $this;
    }
}
