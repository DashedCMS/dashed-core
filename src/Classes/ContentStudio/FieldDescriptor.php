<?php

namespace Dashed\DashedCore\Classes\ContentStudio;

class FieldDescriptor
{
    /**
     * @param  array<string, string>|null  $options
     * @param  array<int, FieldDescriptor>|null  $of  Geneste velden (voor repeaters).
     */
    public function __construct(
        public string $name,
        public string $type,
        public string $label,
        public ?array $options = null,
        public ?array $of = null,
    ) {
    }

    public function toArray(): array
    {
        $data = [
            'name' => $this->name,
            'type' => $this->type,
            'label' => $this->label,
        ];

        if ($this->options !== null) {
            $data['options'] = $this->options;
        }

        if ($this->of !== null) {
            $data['of'] = array_map(fn (FieldDescriptor $field) => $field->toArray(), $this->of);
        }

        return $data;
    }
}
