<div class="flex items-center">
    <div class="flex h-6 items-center">
        <input type="checkbox"
               class="w-4 h-4 form-checkbox {{ $class ?? '' }}"
               id="{{ $id ?? rand(1000,10000) }}"
               name="{{ $name ?? ($id ?? rand(1000,10000)) }}"
               @if($required ?? false) required @endif
               @if($model) wire:model.live.debounce.500ms="{{ $model }}" @endif>
        <div class="ml-3 text-sm leading-6">
            <label for="{{ $name ?? ($id ?? rand(1000,10000)) }}"
                   class="font-bold text-gray-900 {{ $labelClass ?? '' }}">
                {!! $label !!}
                @if($required ?? false)
                    <span class="text-red-500">*</span>
                @endif
            </label>
        </div>
    </div>
    @error($model ?? ($name ?? ($id ?? rand(1000,10000)))) <span class="text-red-500">{{ $message }}</span> @enderror
</div>
