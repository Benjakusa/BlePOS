@props([
    'type' => 'text',
    'name' => null,
    'id' => null,
    'label' => null,
    'value' => null,
    'options' => null,
    'selected' => null,
    'checked' => false,
    'required' => false,
    'disabled' => false,
    'readonly' => false,
    'autofocus' => false,
    'autocomplete' => null,
    'maxlength' => null,
    'min' => null,
    'max' => null,
    'step' => null,
    'pattern' => null,
    'inputmode' => null,
    'leadingIcon' => null,
    'trailingIcon' => null,
    'helper' => null,
    'error' => null,
    'success' => false,
    'loading' => false,
    'compact' => false,
    'align' => null,
    'accept' => null,
    'rows' => 3,
    'placeholder' => null,
    'wrapperClass' => '',
])

@php
    $normalizedType = match ($type) {
        'switch' => 'toggle',
        'phone' => 'tel',
        default => $type,
    };

    $errorKey = $name ? trim(str_replace(['[', ']'], ['.', ''], $name), '.') : null;
    $resolvedError = $error ?? ($errorKey && isset($errors) && $errors->has($errorKey) ? $errors->first($errorKey) : null);

    $baseId = $id ?? ($name ? preg_replace('/[^a-zA-Z0-9_-]/', '_', $name) : 'ui_' . \Illuminate\Support\Str::random(6));
    $resolvedId = ($normalizedType === 'radio' && $id === null)
        ? $baseId . '_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', (string) ($value ?? \Illuminate\Support\Str::random(4)))
        : $baseId;

    $helperId = $resolvedId . '_help';

    $isChoice = in_array($normalizedType, ['checkbox', 'radio', 'toggle'], true);
    $isSelect = $normalizedType === 'select';
    $isTextarea = $normalizedType === 'textarea';
    $isFile = $normalizedType === 'file';
    $isDateTime = in_array($normalizedType, ['date', 'time', 'datetime-local'], true);
    $isPassword = $normalizedType === 'password';
    $isSearch = $normalizedType === 'search';
    $isMoney = $normalizedType === 'money';
    $isNumber = $normalizedType === 'number';
    $isTel = $normalizedType === 'tel';
    $isEmail = $normalizedType === 'email';

    if ($isChoice) {
        if ($normalizedType === 'radio') {
            $oldVal = $errorKey ? old($errorKey) : null;
            $isChecked = $oldVal !== null ? ((string) $oldVal === (string) $value) : (bool) $checked;
        } else {
            $isChecked = $errorKey && old('_token') !== null ? (bool) old($errorKey) : (bool) $checked;
        }
        $resolvedValue = $value ?? '1';
    } elseif ($isPassword || $isFile) {
        $resolvedValue = '';
    } else {
        $rawVal = $value ?? $selected;
        $resolvedValue = $errorKey ? old($errorKey, $rawVal) : $rawVal;
    }

    // Default icons per type if not explicitly provided
    if ($isSearch && !$leadingIcon) {
        $leadingIcon = 'bi-search';
    }
    if ($isDateTime && !$trailingIcon) {
        $trailingIcon = $normalizedType === 'time' ? 'bi-clock' : 'bi-calendar3';
    }

    $hasValue = $resolvedValue !== null && $resolvedValue !== '';
    $alwaysFloat = $isSelect || $isDateTime;

    $htmlInputType = match ($normalizedType) {
        'money' => 'text',
        'number' => 'number',
        default => $normalizedType,
    };

    $resolvedInputmode = $inputmode ?? match (true) {
        $isMoney => 'decimal',
        $isNumber => 'decimal',
        $isTel => 'tel',
        $isEmail => 'email',
        default => null,
    };

    $resolvedAlign = $align ?? ($isMoney ? 'right' : null);

    $hasTrailingAction = $isPassword || $isSearch || $isSelect || $isDateTime || $trailingIcon || $success || $loading;
@endphp

@if($isChoice)
    <div class="ui-choice-wrap {{ $resolvedError ? 'has-error' : '' }} {{ $disabled ? 'is-disabled' : '' }} {{ $wrapperClass }}"
         data-ui-input="{{ $normalizedType }}">
        <label class="ui-choice-label" for="{{ $resolvedId }}">
            <input
                type="{{ $normalizedType === 'toggle' ? 'checkbox' : $normalizedType }}"
                id="{{ $resolvedId }}"
                @if($name) name="{{ $name }}" @endif
                value="{{ $resolvedValue }}"
                class="ui-choice-native"
                @checked($isChecked)
                @required($required)
                @disabled($disabled)
                @if($resolvedError) aria-invalid="true" @endif
                @if($resolvedError || $helper) aria-describedby="{{ $helperId }}" @endif
                {{ $attributes->except(['class']) }}
            >
            @if($normalizedType === 'toggle')
                <span class="ui-toggle-track" aria-hidden="true">
                    <span class="ui-toggle-knob"></span>
                </span>
            @elseif($normalizedType === 'radio')
                <span class="ui-radio-box" aria-hidden="true">
                    <span class="ui-radio-dot"></span>
                </span>
            @else
                <span class="ui-check-box" aria-hidden="true">
                    <i class="bi bi-check"></i>
                </span>
            @endif
            @if($label)
                <span class="ui-choice-text">
                    {{ $label }}@if($required)<span class="ui-req" aria-hidden="true">*</span>@endif
                </span>
            @endif
        </label>
        <div class="ui-helper-slot" id="{{ $helperId }}">
            @if($resolvedError)
                <span class="ui-error-text"><i class="bi bi-exclamation-circle" aria-hidden="true"></i><span>{{ $resolvedError }}</span></span>
            @elseif($helper)
                <span class="ui-helper-text">{{ $helper }}</span>
            @endif
        </div>
    </div>

@elseif($isFile)
    <div class="ui-field ui-file-field {{ $resolvedError ? 'has-error' : '' }} {{ $disabled ? 'is-disabled' : '' }} {{ $wrapperClass }}"
         data-ui-input="file">
        <div class="ui-file-dropzone" tabindex="0" role="button" aria-label="{{ $label ?? 'Upload file' }}">
            <input
                type="file"
                id="{{ $resolvedId }}"
                @if($name) name="{{ $name }}" @endif
                @if($accept) accept="{{ $accept }}" @endif
                class="ui-file-native"
                @required($required)
                @disabled($disabled)
                @if($resolvedError) aria-invalid="true" @endif
                @if($resolvedError || $helper) aria-describedby="{{ $helperId }}" @endif
                {{ $attributes->except(['class']) }}
            >
            <div class="ui-file-empty">
                <i class="bi bi-cloud-arrow-up ui-file-icon" aria-hidden="true"></i>
                <span class="ui-file-prompt">
                    {{ $label ?? 'Drop file here or click to browse' }}@if($required)<span class="ui-req" aria-hidden="true">*</span>@endif
                </span>
            </div>
            <div class="ui-file-selected" hidden>
                <i class="bi bi-file-earmark-text ui-file-icon" aria-hidden="true"></i>
                <span class="ui-file-name"></span>
                <button type="button" class="ui-file-clear" aria-label="Remove file">
                    <i class="bi bi-x" aria-hidden="true"></i>
                </button>
            </div>
        </div>
        <div class="ui-helper-slot" id="{{ $helperId }}">
            @if($resolvedError)
                <span class="ui-error-text"><i class="bi bi-exclamation-circle" aria-hidden="true"></i><span>{{ $resolvedError }}</span></span>
            @elseif($helper)
                <span class="ui-helper-text">{{ $helper }}</span>
            @endif
        </div>
    </div>

@else
    <div class="ui-field
                {{ $compact ? 'is-compact' : '' }}
                {{ $isTextarea ? 'is-textarea' : '' }}
                {{ $isSelect ? 'is-select' : '' }}
                {{ $alwaysFloat ? 'always-float' : '' }}
                {{ $hasValue ? 'has-value' : '' }}
                {{ $leadingIcon ? 'has-leading' : '' }}
                {{ $hasTrailingAction ? 'has-trailing' : '' }}
                {{ $resolvedAlign === 'right' ? 'is-right' : '' }}
                {{ $resolvedError ? 'has-error' : '' }}
                {{ $success ? 'has-success' : '' }}
                {{ $loading ? 'is-loading' : '' }}
                {{ $disabled ? 'is-disabled' : '' }}
                {{ $readonly ? 'is-readonly' : '' }}
                {{ $wrapperClass }}"
         data-ui-input="{{ $normalizedType }}"
         @if($label) data-label="{{ $label }}" @endif>

        <div class="ui-control">
            @if($leadingIcon)
                <i class="bi {{ $leadingIcon }} ui-leading-icon" aria-hidden="true"></i>
            @endif

            @if($isSelect)
                <select
                    id="{{ $resolvedId }}"
                    @if($name) name="{{ $name }}" @endif
                    class="ui-select-native"
                    @required($required)
                    @disabled($disabled)
                    @if($resolvedError) aria-invalid="true" @endif
                    @if($resolvedError || $helper) aria-describedby="{{ $helperId }}" @endif
                    {{ $attributes->except(['class']) }}
                >
                    @if(is_array($options))
                        @foreach($options as $optKey => $optItem)
                            @php
                                $optVal = is_array($optItem) ? ($optItem['value'] ?? $optKey) : $optKey;
                                $optLabel = is_array($optItem) ? ($optItem['label'] ?? $optVal) : $optItem;
                                $isOptSelected = (string) $resolvedValue === (string) $optVal;
                            @endphp
                            <option value="{{ $optVal }}" @selected($isOptSelected)>{{ $optLabel }}</option>
                        @endforeach
                    @else
                        {{ $slot }}
                    @endif
                </select>

                <button
                    type="button"
                    class="ui-select-trigger"
                    aria-haspopup="listbox"
                    aria-expanded="false"
                    @disabled($disabled)
                >
                    <span class="ui-select-value"></span>
                </button>

                <div class="ui-select-menu" role="listbox" hidden>
                    <div class="ui-select-search-wrap" hidden>
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <input type="text" class="ui-select-search" placeholder="Search..." aria-label="Search options">
                    </div>
                    <div class="ui-select-options"></div>
                </div>

            @elseif($isTextarea)
                <textarea
                    id="{{ $resolvedId }}"
                    @if($name) name="{{ $name }}" @endif
                    rows="{{ $rows }}"
                    placeholder=" "
                    @if($maxlength) maxlength="{{ $maxlength }}" @endif
                    @required($required)
                    @disabled($disabled)
                    @readonly($readonly)
                    @if($autofocus) autofocus @endif
                    @if($resolvedError) aria-invalid="true" @endif
                    @if($resolvedError || $helper) aria-describedby="{{ $helperId }}" @endif
                    {{ $attributes->except(['class']) }}
                >{{ $resolvedValue }}</textarea>

            @else
                <input
                    type="{{ $htmlInputType }}"
                    id="{{ $resolvedId }}"
                    @if($name) name="{{ $name }}" @endif
                    value="{{ $resolvedValue }}"
                    placeholder=" "
                    @if($autocomplete) autocomplete="{{ $autocomplete }}" @endif
                    @if($resolvedInputmode) inputmode="{{ $resolvedInputmode }}" @endif
                    @if($isEmail) autocapitalize="off" spellcheck="false" @endif
                    @if($isMoney) data-ui-money="true" @endif
                    @if($isNumber) data-ui-number="true" @endif
                    @if($isTel) data-ui-tel="true" @endif
                    @if($maxlength) maxlength="{{ $maxlength }}" @endif
                    @if($min !== null) min="{{ $min }}" @endif
                    @if($max !== null) max="{{ $max }}" @endif
                    @if($step !== null) step="{{ $step }}" @endif
                    @if($pattern) pattern="{{ $pattern }}" @endif
                    @required($required)
                    @disabled($disabled)
                    @readonly($readonly)
                    @if($autofocus) autofocus @endif
                    @if($resolvedError) aria-invalid="true" @endif
                    @if($resolvedError || $helper) aria-describedby="{{ $helperId }}" @endif
                    {{ $attributes->except(['class']) }}
                >
            @endif

            @if($label)
                <label class="ui-label" for="{{ $resolvedId }}">
                    <span class="ui-label-text">{{ $label }}</span>@if($required)<span class="ui-req" aria-hidden="true">*</span>@endif
                </label>
            @endif

            @if($isPassword)
                <button type="button" class="ui-trailing-btn ui-pw-toggle" aria-label="Show password">
                    <i class="bi bi-eye" aria-hidden="true"></i>
                </button>
            @elseif($isSearch)
                <button type="button" class="ui-trailing-btn ui-search-clear" aria-label="Clear search" hidden>
                    <i class="bi bi-x-circle" aria-hidden="true"></i>
                </button>
            @elseif($isSelect)
                <i class="bi bi-chevron-down ui-trailing-icon ui-select-chevron" aria-hidden="true"></i>
            @elseif($loading)
                <span class="ui-trailing-icon ui-spinner" aria-hidden="true"></span>
            @elseif($success)
                <i class="bi bi-check-circle ui-trailing-icon ui-success-icon" aria-hidden="true"></i>
            @elseif($trailingIcon)
                <i class="bi {{ $trailingIcon }} ui-trailing-icon" aria-hidden="true"></i>
            @endif
        </div>

        <div class="ui-meta-row">
            <div class="ui-helper-slot" id="{{ $helperId }}">
                @if($resolvedError)
                    <span class="ui-error-text"><i class="bi bi-exclamation-circle" aria-hidden="true"></i><span>{{ $resolvedError }}</span></span>
                @elseif($helper)
                    <span class="ui-helper-text">{{ $helper }}</span>
                @endif
            </div>
            @if($isTextarea && $maxlength)
                <span class="ui-char-count" data-max="{{ $maxlength }}">0 / {{ $maxlength }}</span>
            @endif
        </div>
    </div>
@endif
