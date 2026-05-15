@props([
    'urls' => [],
])

<x-moonshine::off-canvas
    name="media-manager"
    title="{{ __('Media manager') }}"
    :wide="true"
>
    <div x-data="mmBrowser({{ Js::from($urls) }})">

        {{-- Toolbar --}}
        <div class="flex items-center justify-between gap-3 flex-wrap mb-4">
            <div class="flex items-center gap-2">
                <x-moonshine::link-button @click.prevent="refresh()" class="btn-warning">
                    <x-moonshine::icon icon="arrow-path"/>
                </x-moonshine::link-button>

                <x-moonshine::link-button @click.prevent="openUploadModal()" class="btn-success">
                    <x-moonshine::icon icon="cloud-arrow-up"/>
                </x-moonshine::link-button>

                <x-moonshine::link-button @click.prevent="openNewFolderModal()" class="btn-secondary">
                    <x-moonshine::icon icon="folder-plus"/>
                </x-moonshine::link-button>

                <x-moonshine::link-button @click.prevent="switchView('table')">
                    <x-moonshine::icon icon="list-bullet" x-bind:class="view === 'table' ? 'text-primary' : ''"/>
                </x-moonshine::link-button>

                <x-moonshine::link-button @click.prevent="switchView('list')">
                    <x-moonshine::icon icon="squares-2x2" x-bind:class="view === 'list' ? 'text-primary' : ''"/>
                </x-moonshine::link-button>
            </div>

            {{-- Quick Jump --}}
            <div class="flex">
                <x-moonshine::form.input
                    x-model="jumpPath"
                    @keydown.enter.prevent="quickJump()"
                    placeholder="Path"
                    class="w-48"
                />
                <x-moonshine::link-button @click.prevent="quickJump()">
                    <x-moonshine::icon icon="arrow-small-right"/>
                </x-moonshine::link-button>
            </div>
        </div>

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
                        <template x-if="isImageUrl(file.url)">
                            <div style="width:64px;height:64px;border-radius:6px;overflow:hidden;position:relative;">
                                <img :src="file.url"
                                     style="width:100%;height:100%;object-fit:cover;display:block;"
                                     :alt="file.path.split('/').pop()"
                                     @@error="$el.parentElement.innerHTML='<div style=&quot;width:64px;height:64px;border-radius:6px;border:1px dashed #ef4444;display:flex;align-items:center;justify-content:center;background:rgba(239,68,68,0.06);&quot;><svg style=&quot;width:24px;height:24px;color:#ef4444;&quot; fill=&quot;none&quot; viewBox=&quot;0 0 24 24&quot; stroke-width=&quot;1.5&quot; stroke=&quot;currentColor&quot;><path stroke-linecap=&quot;round&quot; stroke-linejoin=&quot;round&quot; d=&quot;M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z&quot;/></svg></div>'"
                                >
                            </div>
                        </template>
                        <template x-if="!isImageUrl(file.url)">
                            <div style="width:64px;height:64px;border-radius:6px;border:1px solid var(--color-gray-200, #e5e7eb);display:flex;align-items:center;justify-content:center;background:var(--color-gray-50, #f9fafb);">
                                <x-moonshine::icon icon="document" style="width:28px;height:28px;color:#9ca3af;display:block;margin:auto;"/>
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

        {{-- Breadcrumbs --}}
        <nav class="flex items-center gap-2 flex-wrap mb-4 text-sm">
            <template x-for="([url, label], index) in Object.entries(navigation)" :key="url">
                <div class="flex items-center gap-1">
                    <a href="#" @click.prevent="loadFiles(extractPathFromUrl(url))" x-text="label"
                       class="hover:text-primary transition-colors"></a>
                    <span x-show="index < Object.entries(navigation).length - 1" class="text-gray-400">/</span>
                </div>
            </template>
        </nav>

        {{-- Loading --}}
        <div x-show="loading" x-cloak class="flex justify-center py-12">
            <x-moonshine::loader />
        </div>

        {{-- Table View --}}
        <div x-show="view === 'table' && !loading">
            <x-moonshine::table>
                <x-slot:thead>
                    <tr>
                        <th class="w-8"></th>
                        <th>{{ __('moonshine-media-manager::media-manager.name') }}</th>
                        <th>{{ __('moonshine-media-manager::media-manager.time') }}</th>
                        <th>{{ __('moonshine-media-manager::media-manager.size') }}</th>
                        <th></th>
                    </tr>
                </x-slot:thead>
                <x-slot:tbody>
                    <template x-for="file in files" :key="file.path">
                        <tr :style="highlightPath === file.path ? 'background:rgba(245,158,11,0.12);box-shadow:inset 3px 0 0 #f59e0b;' : ''"
                            :class="highlightPath !== file.path ? 'hover:bg-gray-50 dark:hover:bg-gray-800/50' : ''">
                            <td>
                                <template x-if="!file.isDir">
                                    <input type="checkbox"
                                           :checked="$store.mm.isSelected(file.path)"
                                           @change="$store.mm.toggleFile(file)"
                                           class="checkbox checkbox-sm"
                                    />
                                </template>
                            </td>
                            <td>
                                <a href="#"
                                   @click.prevent="file.isDir && navigate(file)"
                                   class="flex items-center gap-2"
                                >
                                    <template x-if="file.isDir">
                                        <x-moonshine::icon icon="folder" class="size-5 text-yellow-500"/>
                                    </template>
                                    <template x-if="!file.isDir && file.type === 'image'">
                                        <div style="width:32px;height:32px;border-radius:6px;overflow:hidden;flex-shrink:0;">
                                            <img :src="file.url" style="width:100%;height:100%;object-fit:cover;display:block;" alt=""/>
                                        </div>
                                    </template>
                                    <template x-if="!file.isDir && file.type !== 'image'">
                                        <x-moonshine::icon icon="document" class="size-5 text-gray-400"/>
                                    </template>
                                    <span x-text="basename(file.path)" class="truncate max-w-xs"></span>
                                </a>
                            </td>
                            <td x-text="file.time" class="whitespace-nowrap text-sm text-gray-500"></td>
                            <td x-text="file.size" class="whitespace-nowrap text-sm text-gray-500"></td>
                            <td>
                                <div class="flex justify-end gap-1">
                                    <x-moonshine::link-button
                                        x-show="!file.isDir"
                                        @click.prevent="download(file)"
                                        class="btn-sm btn-success"
                                    >
                                        <x-moonshine::icon icon="cloud-arrow-down"/>
                                    </x-moonshine::link-button>

                                    <x-moonshine::link-button
                                        @click.prevent="openRenameModal(file)"
                                        class="btn-sm btn-primary"
                                    >
                                        <x-moonshine::icon icon="pencil"/>
                                    </x-moonshine::link-button>

                                    <x-moonshine::link-button
                                        @click.prevent="openDeleteModal(file)"
                                        class="btn-sm btn-error"
                                    >
                                        <x-moonshine::icon icon="trash"/>
                                    </x-moonshine::link-button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </x-slot:tbody>
            </x-moonshine::table>
        </div>

        {{-- List View --}}
        <div x-show="view === 'list' && !loading">
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:12px;">
                <template x-for="file in files" :key="file.path">
                    <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;padding:8px;border-radius:10px;border:1px solid transparent;cursor:pointer;transition:all .15s;text-align:center;"
                         :id="'oc-file-' + file.path.replace(/[^a-zA-Z0-9]/g, '_')"
                         :style="Object.assign({position:'relative'}, highlightPath === file.path ? {borderColor:'#f59e0b',background:'rgba(245,158,11,0.12)',boxShadow:'0 0 0 3px rgba(245,158,11,0.4)'} : (!file.isDir && $store.mm.isSelected(file.path) ? {borderColor:'var(--color-primary, #3b82f6)',background:'rgba(59,130,246,0.06)'} : {}))"
                         @click.prevent="file.isDir ? navigate(file) : $store.mm.toggleFile(file)"
                         @mouseenter="$el.style.borderColor = file.isDir ? 'var(--color-gray-300, #d1d5db)' : 'var(--color-primary, #3b82f6)'"
                         @mouseleave="$el.style.borderColor = (!file.isDir && $store.mm.isSelected(file.path)) ? 'var(--color-primary, #3b82f6)' : (highlightPath === file.path ? '#f59e0b' : 'transparent')"
                    >
                        {{-- Checkbox --}}
                        <template x-if="!file.isDir">
                            <input type="checkbox"
                                   :checked="$store.mm.isSelected(file.path)"
                                   @click.stop
                                   @change="$store.mm.toggleFile(file)"
                                   style="position:absolute;top:6px;left:6px;width:16px;height:16px;accent-color:var(--color-primary, #3b82f6);cursor:pointer;"
                            />
                        </template>

                        {{-- Preview centered --}}
                        <template x-if="file.isDir">
                            <div style="width:64px;height:64px;display:flex;align-items:center;justify-content:center;margin:0 auto;">
                                <x-moonshine::icon icon="folder" style="width:48px;height:48px;color:#eab308;display:block;margin:auto;"/>
                            </div>
                        </template>
                        <template x-if="!file.isDir && file.type === 'image'">
                            <div style="width:64px;height:64px;border-radius:8px;overflow:hidden;margin:0 auto;">
                                <img :src="file.url" style="width:100%;height:100%;object-fit:cover;display:block;" :alt="basename(file.path)" />
                            </div>
                        </template>
                        <template x-if="!file.isDir && file.type !== 'image'">
                            <div style="width:64px;height:64px;display:flex;align-items:center;justify-content:center;margin:0 auto;">
                                <x-moonshine::icon icon="document" style="width:40px;height:40px;color:#9ca3af;display:block;margin:auto;"/>
                            </div>
                        </template>

                        {{-- Name --}}
                        <span style="margin-top:6px;font-size:11px;font-weight:500;color:var(--color-gray-600, #4b5563);width:100%;text-align:center;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                              x-text="basename(file.path)"></span>

                        {{-- Actions --}}
                        <div style="position:absolute;top:4px;right:4px;" @click.stop>
                            <x-moonshine::dropdown>
                                <div class="dropdown-menu" style="min-width:160px;">
                                    <x-moonshine::link-button
                                            x-show="!file.isDir"
                                            @click.stop.prevent="download(file)"
                                            style="width:100%;font-size:12px;padding:4px 8px;"
                                    >
                                        <x-moonshine::icon icon="cloud-arrow-down" style="width:14px;height:14px;"/>
                                        {{ __('moonshine-media-manager::media-manager.download') }}
                                    </x-moonshine::link-button>
                                    <x-moonshine::link-button
                                            @click.stop.prevent="openRenameModal(file)"
                                            style="width:100%;font-size:12px;padding:4px 8px;"
                                    >
                                        <x-moonshine::icon icon="pencil" style="width:14px;height:14px;"/>
                                        {{ __('moonshine-media-manager::media-manager.rename') }}
                                    </x-moonshine::link-button>
                                    <x-moonshine::link-button
                                            @click.stop.prevent="openDeleteModal(file)"
                                            style="width:100%;font-size:12px;padding:4px 8px;"
                                    >
                                        <x-moonshine::icon icon="trash" style="width:14px;height:14px;"/>
                                        {{ __('moonshine-media-manager::media-manager.delete') }}
                                    </x-moonshine::link-button>
                                </div>
                                <x-slot:toggler>
                                    <x-moonshine::icon icon="ellipsis-horizontal" style="width:16px;height:16px;color:#9ca3af;"/>
                                </x-slot:toggler>
                            </x-moonshine::dropdown>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        {{-- Empty state --}}
        <div x-show="!loading && files.length === 0" class="text-center py-12 text-gray-400">
            {{ __('moonshine-media-manager::media-manager.empty_directory') }}
        </div>

        {{-- Modals --}}
        <x-moonshine::modal name="mm-upload" title="{{ __('moonshine-media-manager::media-manager.upload') }}" :closeOutside="true">
            <form @submit.prevent="submitUpload()">
                <div class="flex flex-col gap-4">
                    <input type="file" id="mm-upload-input" name="files[]" multiple required class="file-input" />
                    <x-moonshine::form.button type="submit">
                        {{ __('moonshine-media-manager::media-manager.submit') }}
                    </x-moonshine::form.button>
                </div>
            </form>
        </x-moonshine::modal>

        <x-moonshine::modal name="mm-rename" title="{{ __('moonshine-media-manager::media-manager.rename') }}" :closeOutside="true">
            <form @submit.prevent="submitRename()">
                <div class="flex flex-col gap-4">
                    <x-moonshine::form.input x-model="renameNew" placeholder="{{ __('moonshine-media-manager::media-manager.new_path') }}" />
                    <x-moonshine::form.button type="submit">
                        {{ __('moonshine-media-manager::media-manager.submit') }}
                    </x-moonshine::form.button>
                </div>
            </form>
        </x-moonshine::modal>

        <x-moonshine::modal name="mm-new-folder" title="{{ __('moonshine-media-manager::media-manager.new_folder') }}" :closeOutside="true">
            <form @submit.prevent="submitNewFolder()">
                <div class="flex flex-col gap-4">
                    <x-moonshine::form.input x-model="newFolderName" placeholder="{{ __('moonshine-media-manager::media-manager.name') }}" />
                    <x-moonshine::form.button type="submit">
                        {{ __('moonshine-media-manager::media-manager.submit') }}
                    </x-moonshine::form.button>
                </div>
            </form>
        </x-moonshine::modal>

        <x-moonshine::modal name="mm-delete" title="{{ __('moonshine-media-manager::media-manager.delete') }}" :closeOutside="true">
            <div class="flex flex-col gap-4">
                <p>{{ __('moonshine-media-manager::media-manager.confirm_message') }}</p>
                <div class="flex gap-2 justify-end">
                    <x-moonshine::form.button @click.prevent="window.MoonShine?.ui?.toggleModal(modalPrefix + 'delete')" class="btn-secondary">
                        {{ __('moonshine-media-manager::media-manager.close') }}
                    </x-moonshine::form.button>
                    <x-moonshine::form.button @click.prevent="submitDelete()" class="btn-error">
                        {{ __('moonshine-media-manager::media-manager.delete') }}
                    </x-moonshine::form.button>
                </div>
            </div>
        </x-moonshine::modal>

    </div>
</x-moonshine::off-canvas>
