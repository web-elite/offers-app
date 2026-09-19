@php
    $value = old($field['name'], data_get($item, $field['name']));
    if (($field['type'] ?? 'text') === 'checkbox') {
        $value = old($field['name'], (bool) data_get($item, $field['name']));
    }
    $isMultiselect = in_array($field['type'] ?? 'text', ['multiselect', 'checkboxgroup'], true);
    $selected = $isMultiselect ? (array) $value : [];
    $col = $field['col'] ?? 12;
@endphp
<div class="field field--col-{{ $col }}">
    @if(($field['type'] ?? 'text') === 'checkbox')
        <label class="field__check">
            <input type="hidden" name="{{ $field['name'] }}" value="0">
            <input type="checkbox" name="{{ $field['name'] }}" value="1" @checked($value)>
            <span>{{ $field['label'] }}</span>
        </label>
    @else
        <label class="field__label" for="f-{{ $field['name'] }}">{{ $field['label'] }}</label>

        @switch($field['type'] ?? 'text')
            @case('textarea')
                <textarea id="f-{{ $field['name'] }}" name="{{ $field['name'] }}" rows="4" dir="auto">{{ $value }}</textarea>
                @break

            @case('select')
                <select id="f-{{ $field['name'] }}" name="{{ $field['name'] }}">
                    @if(!empty($field['empty']))
                        <option value="">{{ $field['empty'] }}</option>
                    @endif
                    @foreach(($field['options'] ?? []) as $optionValue => $optionLabel)
                        <option value="{{ $optionValue }}" @selected((string) $value === (string) $optionValue)>{{ $optionLabel }}</option>
                    @endforeach
                </select>
                @break

            @case('multiselect')
                <select id="f-{{ $field['name'] }}" name="{{ $field['name'] }}[]" multiple size="6">
                    @foreach(($field['options'] ?? []) as $optionValue => $optionLabel)
                        <option value="{{ $optionValue }}" @selected(in_array((string) $optionValue, array_map('strval', $selected), true))>{{ $optionLabel }}</option>
                    @endforeach
                </select>
                @break

            @case('checkboxgroup')
                <div class="field__checks">
                    @foreach(($field['options'] ?? []) as $optionValue => $optionLabel)
                        <label class="field__check">
                            <input type="checkbox" name="{{ $field['name'] }}[]" value="{{ $optionValue }}"
                                   @checked(in_array((string) $optionValue, array_map('strval', $selected), true))>
                            <span>{{ $optionLabel }}</span>
                        </label>
                    @endforeach
                </div>
                @break

            @case('datetime')
                <input id="f-{{ $field['name'] }}" type="datetime-local" name="{{ $field['name'] }}"
                       value="{{ $value ? \Illuminate\Support\Carbon::parse($value)->format('Y-m-d\TH:i') : '' }}">
                @break

            @case('password')
                <input id="f-{{ $field['name'] }}" type="password" name="{{ $field['name'] }}" autocomplete="new-password">
                @break

            @case('number')
                <input id="f-{{ $field['name'] }}" type="number" name="{{ $field['name'] }}" value="{{ $value }}" min="0">
                @break

            @case('file')
                <input id="f-{{ $field['name'] }}" type="file" name="{{ $field['name'] }}" accept="image/*">
                @break

            @default
                <input id="f-{{ $field['name'] }}" type="{{ $field['type'] === 'url' ? 'url' : ($field['type'] === 'email' ? 'email' : 'text') }}"
                       name="{{ $field['name'] }}" value="{{ $value }}" dir="{{ $field['type'] === 'text' ? 'auto' : 'ltr' }}">
        @endswitch

        @if(!empty($field['help']))
            <p class="field__help">{{ $field['help'] }}</p>
        @endif
    @endif
</div>
