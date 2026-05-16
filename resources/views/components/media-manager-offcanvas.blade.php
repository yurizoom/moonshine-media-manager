@props([
    'urls' => [],
])

<x-moonshine::off-canvas
    name="media-manager"
    title="{{ __('Media manager') }}"
    :wide="true"
>
    <div x-data="mmBrowser({{ Js::from($urls) }})">

        @include('moonshine-media-manager::partials.browser-toolbar')

        {{-- Selected files bar --}}
        <div x-show="$store.mm.hasSelection && !loading" x-cloak class="mb-4">
            <div class="text-sm font-medium mb-2 text-gray-500">{{ __('moonshine-media-manager::media-manager.selected_files') }}</div>
            <div style="display:flex;flex-wrap:wrap;gap:8px;padding:12px;background:rgba(var(--color-primary-rgb,59,130,246),0.1);border-radius:8px;">
                <template x-for="(file, idx) in $store.mm.selected" :key="file.path">
                    <div :style="{ position: 'relative', display: 'flex', flexDirection: 'column', alignItems: 'center', width: '72px', paddingTop: '4px', cursor: selectedDragIdx === idx ? 'grabbing' : 'grab', opacity: selectedDragIdx === idx ? 0.4 : 1 }"
                         draggable="true"
                         @dragstart="dragSelectedStart(idx, $event)"
                         @dragover.prevent=""
                         @drop="dropSelectedTo(idx)"
                         @dragend="dragSelectedEnd()"
                    >
                        <div style="position:absolute;top:3px;right:3px;display:flex;gap:2px;z-index:2;">
                            <button type="button"
                                    @click.prevent="navigateToFile(file.path)"
                                    style="background:rgba(59,130,246,0.9);color:#fff;border:none;cursor:pointer;padding:3px 5px;border-radius:3px;font-size:0;line-height:1;display:flex;align-items:center;justify-content:center;"
                                    title="{{ __('moonshine-media-manager::media-manager.go_to_folder') }}"
                            >
                                <x-moonshine::icon icon="folder" style="width:10px;height:10px;"/>
                            </button>
                            <button type="button"
                                    @click.prevent="$store.mm.selected.splice(idx, 1)"
                                    style="background:rgba(239,68,68,0.9);color:#fff;border:none;cursor:pointer;padding:3px 5px;border-radius:3px;font-size:12px;line-height:1;display:flex;align-items:center;justify-content:center;"
                                    title="{{ __('moonshine-media-manager::media-manager.remove') }}"
                            >×</button>
                        </div>
                        <template x-if="isImageUrl(file.url) && !brokenSelectedPaths.includes(file.path)">
                            <div style="width:64px;height:64px;border-radius:6px;overflow:hidden;position:relative;">
                                <img :src="file.url"
                                     style="width:100%;height:100%;object-fit:cover;display:block;"
                                     :alt="file.path.split('/').pop()"
                                     @@error="if(!brokenSelectedPaths.includes(file.path)) brokenSelectedPaths.push(file.path)"
                                >
                            </div>
                        </template>
                        <template x-if="isImageUrl(file.url) && brokenSelectedPaths.includes(file.path)">
                            <div style="width:64px;height:64px;border-radius:6px;border:1px dashed #ef4444;display:flex;align-items:center;justify-content:center;background:rgba(239,68,68,0.06);">
                                <svg style="width:24px;height:24px;color:#ef4444;" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/></svg>
                            </div>
                        </template>
                        <template x-if="!isImageUrl(file.url) && !brokenSelectedPaths.includes(file.path)">
                            <div style="width:64px;height:64px;border-radius:6px;border:1px solid var(--color-gray-200, #e5e7eb);display:flex;align-items:center;justify-content:center;background:var(--color-gray-50, #f9fafb);">
                                <x-moonshine::icon icon="document" style="width:28px;height:28px;color:#9ca3af;display:block;margin:auto;"/>
                            </div>
                        </template>
                        <template x-if="!isImageUrl(file.url) && brokenSelectedPaths.includes(file.path)">
                            <div style="width:64px;height:64px;border-radius:6px;border:1px dashed #ef4444;display:flex;align-items:center;justify-content:center;background:rgba(239,68,68,0.06);">
                                <svg style="width:24px;height:24px;color:#ef4444;" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/></svg>
                            </div>
                        </template>
                        <span style="font-size:10px;color:#888;width:64px;text-align:center;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;margin-top:2px;"
                              x-text="file.path.split('/').pop()"></span>
                    </div>
                </template>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:8px;">
                <x-moonshine::form.button @click.prevent="$store.mm.confirm()" class="btn-primary btn-sm">
                    {{ __('moonshine-media-manager::media-manager.pick') }}
                </x-moonshine::form.button>
                <x-moonshine::form.button @click.prevent="$store.mm.close()" class="btn-secondary btn-sm">
                    {{ __('moonshine-media-manager::media-manager.close') }}
                </x-moonshine::form.button>
            </div>
        </div>

        @include('moonshine-media-manager::partials.browser-breadcrumbs')

        @include('moonshine-media-manager::partials.browser-loading')

        @include('moonshine-media-manager::partials.browser-table', ['showCheckboxes' => true])

        @include('moonshine-media-manager::partials.browser-list', ['showCheckboxes' => true, 'showSelection' => true, 'idPrefix' => 'oc-file-'])

        @include('moonshine-media-manager::partials.browser-empty-state')

        @include('moonshine-media-manager::partials.browser-modals')

    </div>
</x-moonshine::off-canvas>
