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
        <div style="display:inline-block;position:relative;overflow:hidden;border-radius:8px;">
            {{-- Image: OK --}}
            <template x-if="isImageUrl(previewUrl) && !singleBroken">
                <img :src="previewUrl"
                     style="width:96px;height:96px;"
                     class="zoom-in rounded object-cover ring-1 ring-black/5 dark:ring-white/10 cursor-pointer"
                     alt=""
                     x-on:error="markBroken(0)"
                     @click.stop="$dispatch('img-popup', {open: true, src: previewUrl, wide: true, auto: true, styles: ''})"
                >
            </template>
            {{-- Image: broken --}}
            <template x-if="isImageUrl(previewUrl) && singleBroken">
                <div style="width:96px;height:96px;border-radius:8px;border:1px dashed #ef4444;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:4px;background:rgba(239,68,68,0.06);">
                    <svg style="width:24px;height:24px;color:#ef4444;" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/></svg>
                    <span style="font-size:9px;color:#ef4444;font-weight:500;">{{ __('moonshine-media-manager::media-manager.error.file_not_exists', ['path' => '']) }}</span>
                    <button type="button" @click.prevent="clear()"
                            style="position:absolute;top:4px;right:4px;z-index:2;background:rgba(239,68,68,0.9);color:#fff;border:none;cursor:pointer;padding:3px 6px;border-radius:3px;font-size:12px;line-height:1;">×</button>
                </div>
            </template>
            {{-- Non-image file --}}
            <template x-if="!isImageUrl(previewUrl)">
                <div style="width:96px;height:96px;border-radius:8px;border:1px solid var(--color-gray-200, #e5e7eb);display:flex;flex-direction:column;align-items:center;justify-content:center;gap:4px;background:var(--color-gray-50, #f9fafb);position:relative;">
                    <span x-text="fileExt(rawValue).toUpperCase()" style="font-size:11px;font-weight:700;color:var(--color-primary, #3b82f6);background:rgba(59,130,246,0.1);padding:2px 8px;border-radius:4px;"></span>
                    <span x-text="rawValue.split('/').pop()" style="font-size:9px;color:#6b7280;max-width:80px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;text-align:center;"></span>
                </div>
            </template>
        </div>
    </template>

    {{-- Multiple values --}}
    <template x-if="multiple && paths.length > 0">
        <div class="flex flex-wrap gap-2 mb-3">
            <template x-for="(p, idx) in paths" :key="idx">
                <div :style="{ position: 'relative', overflow: 'hidden', borderRadius: '8px', cursor: dragIdx === idx ? 'grabbing' : 'grab', opacity: dragIdx === idx ? 0.4 : 1 }"
                     draggable="true"
                     @dragstart="dragStart(idx, $event)"
                     @dragover.prevent=""
                     @drop="dropTo(idx)"
                     @dragend="dragEnd()"
                >
                    <button type="button"
                            @click.prevent="removeAt(idx)"
                            style="position:absolute;top:4px;right:4px;z-index:2;background:rgba(239,68,68,0.9);color:#fff;border:none;cursor:pointer;padding:3px 6px;border-radius:3px;font-size:12px;line-height:1;"
                            title="{{ __('moonshine-media-manager::media-manager.remove') }}"
                    >×</button>
                    {{-- Image: OK --}}
                    <template x-if="isImageUrl(baseUrl + '/' + p) && !broken.includes(idx)">
                        <img :src="baseUrl + '/' + p"
                             style="width:96px;height:96px;display:block;"
                             class="zoom-in rounded object-cover ring-1 ring-black/5 dark:ring-white/10 cursor-pointer"
                             :alt="p.split('/').pop()"
                             x-on:error="markBroken(idx)"
                             @click.stop="$dispatch('img-popup', {open: true, src: baseUrl + '/' + p, wide: true, auto: true, styles: ''})"
                        >
                    </template>
                    {{-- Image: broken --}}
                    <template x-if="isImageUrl(baseUrl + '/' + p) && broken.includes(idx)">
                        <div style="width:96px;height:96px;border-radius:8px;border:1px dashed #ef4444;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:4px;background:rgba(239,68,68,0.06);">
                            <svg style="width:24px;height:24px;color:#ef4444;" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/></svg>
                            <span style="font-size:9px;color:#ef4444;font-weight:500;text-align:center;padding:0 4px;">{{ __('moonshine-media-manager::media-manager.error.file_not_exists', ['path' => '']) }}</span>
                        </div>
                    </template>
                    {{-- Non-image file --}}
                    <template x-if="!isImageUrl(baseUrl + '/' + p)">
                        <div style="width:96px;height:96px;border-radius:8px;border:1px solid var(--color-gray-200, #e5e7eb);display:flex;flex-direction:column;align-items:center;justify-content:center;gap:4px;background:var(--color-gray-50, #f9fafb);">
                            <span x-text="fileExt(p).toUpperCase()" style="font-size:11px;font-weight:700;color:var(--color-primary, #3b82f6);background:rgba(59,130,246,0.1);padding:2px 8px;border-radius:4px;"></span>
                            <span x-text="p.split('/').pop()" style="font-size:9px;color:#6b7280;max-width:80px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;text-align:center;"></span>
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
