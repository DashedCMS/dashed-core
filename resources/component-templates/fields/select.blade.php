<div>
    @if($label ?? false)
        <label class="block text-sm font-bold text-gray-700 {{ $labelClass ?? '' }}">
            {{ $label }}
            @if($required ?? false)
                <span class="text-red-500">*</span>
            @endif
        </label>
        <div class="mt-1">
            @endif
            <select class="custom-form-input {{ $class ?? '' }}"
                   id="{{ $id ?? rand(1000,10000) }}"
                   name="{{ $name ?? ($id ?? rand(1000,10000)) }}"
                   @if($required ?? false) required @endif
                   @if($model) wire:model.live.debounce.500ms="{{ $model }}" @endif>
                {{ $slot }}
            </select>
            @error($model ?? ($name ?? ($id ?? rand(1000,10000)))) <span class="text-red-500">{{ $message }}</span> @enderror
            @if($helperText ?? false) <span class="text-xs">{{ $helperText }}</span> @endif
            @if($label ?? false)
        </div>
    @endif
</div>
