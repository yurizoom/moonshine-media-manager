@props([
    'pickerConfig' => [],
])

<div x-data="mmPicker({{ Js::from($pickerConfig) }})">
    <input type="hidden"
           {{ $attributes->only(['name', 'data-name', 'data-column', 'data-level']) }}
           :value="rawValue"
    />

    {{-- Single value --}}
    <template x-if="!multiple && previewUrl">
        <div class="mm-picker-card mm-picker-card--inline">
            {{-- Image: OK --}}
            <template x-if="isImageUrl(previewUrl) && !singleBroken">
                <img :src="previewUrl"
                     class="zoom-in rounded object-cover ring-1 ring-black/5 dark:ring-white/10 cursor-pointer"
                     alt=""
                     x-on:error="markBroken(0)"
                     @click.stop="$dispatch('img-popup', {open: true, src: previewUrl, wide: true, auto: true, styles: ''})"
                >
            </template>
            {{-- Image: broken --}}
            <template x-if="isImageUrl(previewUrl) && singleBroken">
                <div class="mm-picker-card--broken">
                    @include('moonshine-media-manager::partials.icon-broken')
                    <span>{{ __('moonshine-media-manager::media-manager.error.file_not_exists', ['path' => '']) }}</span>
                    <button type="button" @click.prevent="clear()" class="mm-picker-remove">×</button>
                </div>
            </template>
            {{-- Non-image file: broken --}}
            <template x-if="!isImageUrl(previewUrl) && singleBroken">
                <div class="mm-picker-card--broken">
                    @include('moonshine-media-manager::partials.icon-broken')
                    <span>{{ __('moonshine-media-manager::media-manager.error.file_not_exists', ['path' => '']) }}</span>
                    <button type="button" @click.prevent="clear()" class="mm-picker-remove">×</button>
                </div>
            </template>
            {{-- Non-image file: OK --}}
            <template x-if="!isImageUrl(previewUrl) && !singleBroken">
                <div class="mm-picker-card--document">
                    <span x-text="fileExt(rawValue).toUpperCase()" class="mm-picker-ext"></span>
                    <span x-text="rawValue.split('/').pop()" class="mm-picker-filename"></span>
                </div>
            </template>
        </div>
    </template>

    {{-- Multiple values --}}
    <template x-if="multiple && paths.length > 0">
        <div class="flex flex-wrap gap-2 mb-3">
            <template x-for="(p, idx) in paths" :key="idx">
                <div :style="{ cursor: dragIdx === idx ? 'grabbing' : 'grab', opacity: dragIdx === idx ? 0.4 : 1 }"
                     class="mm-picker-card"
                     draggable="true"
                     @dragstart="dragStart(idx, $event)"
                     @dragover.prevent=""
                     @drop="dropTo(idx)"
                     @dragend="dragEnd()"
                >
                    <button type="button"
                            @click.prevent="removeAt(idx)"
                            class="mm-picker-remove"
                            title="{{ __('moonshine-media-manager::media-manager.remove') }}"
                    >×</button>
                    {{-- Image: OK --}}
                    <template x-if="isImageUrl(baseUrl + '/' + p) && !broken.includes(idx)">
                         <img :src="baseUrl + '/' + p"
                              class="zoom-in rounded object-cover ring-1 ring-black/5 dark:ring-white/10 cursor-pointer"
                              :alt="p.split('/').pop()"
                              loading="lazy"
                              decoding="async"
                              x-on:error="markBroken(idx)"
                              @click.stop="$dispatch('img-popup', {open: true, src: baseUrl + '/' + p, wide: true, auto: true, styles: ''})"
                         >
                    </template>
                    {{-- Image: broken --}}
                    <template x-if="isImageUrl(baseUrl + '/' + p) && broken.includes(idx)">
                        <div class="mm-picker-card--broken">
                            @include('moonshine-media-manager::partials.icon-broken')
                            <span>{{ __('moonshine-media-manager::media-manager.error.file_not_exists', ['path' => '']) }}</span>
                        </div>
                    </template>
                    {{-- Non-image file: broken --}}
                    <template x-if="!isImageUrl(baseUrl + '/' + p) && broken.includes(idx)">
                        <div class="mm-picker-card--broken">
                            @include('moonshine-media-manager::partials.icon-broken')
                            <span>{{ __('moonshine-media-manager::media-manager.error.file_not_exists', ['path' => '']) }}</span>
                        </div>
                    </template>
                    {{-- Non-image file: OK --}}
                    <template x-if="!isImageUrl(baseUrl + '/' + p) && !broken.includes(idx)">
                        <div class="mm-picker-card--document">
                            <span x-text="fileExt(p).toUpperCase()" class="mm-picker-ext"></span>
                            <span x-text="p.split('/').pop()" class="mm-picker-filename"></span>
                        </div>
                    </template>
                </div>
            </template>
        </div>
    </template>

    <div class="flex items-center gap-2 mt-2">
        <x-moonshine::link-button
            @click.prevent="pick()"
            class="btn-primary"
        >
            <x-moonshine::icon icon="folder-open"/>
        </x-moonshine::link-button>

        <x-moonshine::link-button
            x-show="hasValue"
            @click.prevent="clear()"
            class="btn-error"
        >
            <x-moonshine::icon icon="trash"/>
        </x-moonshine::link-button>
    </div>
</div>
