@props([
    'urls' => [],
])

<div x-data="mmBrowser({{ Js::from($urls) }}, 'mmp-')">

    {{-- Toolbar --}}
    <div class="flex items-center justify-between gap-3 flex-wrap mb-4">
        <div class="flex items-center gap-2">
            <x-moonshine::link-button @click.prevent="refresh()" class="btn-warning">
                <x-moonshine::icon icon="arrow-path"/>
            </x-moonshine::link-button>

            <x-moonshine::link-button @click.prevent="openUploadModal()" class="btn-success">
                <x-moonshine::icon icon="cloud-arrow-up"/>
                {{ __('moonshine-media-manager::media-manager.upload') }}
            </x-moonshine::link-button>

            <x-moonshine::link-button @click.prevent="openNewFolderModal()" class="btn-secondary">
                <x-moonshine::icon icon="folder-plus"/>
                {{ __('moonshine-media-manager::media-manager.new_folder') }}
            </x-moonshine::link-button>

            <x-moonshine::link-button @click.prevent="switchView('table')">
                <x-moonshine::icon icon="list-bullet" x-bind:class="view === 'table' ? 'text-primary' : ''"/>
            </x-moonshine::link-button>

            <x-moonshine::link-button @click.prevent="switchView('list')">
                <x-moonshine::icon icon="squares-2x2" x-bind:class="view === 'list' ? 'text-primary' : ''"/>
            </x-moonshine::link-button>
        </div>

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
                                <span x-text="basename(file.path)"></span>
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
                                    @click.prevent="openUrlModal(file)"
                                    class="btn-sm"
                                >
                                    <x-moonshine::icon icon="globe-alt"/>
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
                     :id="'file-' + file.path.replace(/[^a-zA-Z0-9]/g, '_')"
                     :style="Object.assign({position:'relative'}, highlightPath === file.path ? {borderColor:'#f59e0b',background:'rgba(245,158,11,0.12)',boxShadow:'0 0 0 3px rgba(245,158,11,0.4)'} : {})"
                     @click.prevent="file.isDir && navigate(file)"
                     @mouseenter="$el.style.borderColor = file.isDir ? 'var(--color-gray-300, #d1d5db)' : 'var(--color-primary, #3b82f6)'"
                     @mouseleave="$el.style.borderColor = highlightPath === file.path ? '#f59e0b' : 'transparent'"
                >
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
                    <a href="#"
                       @click.prevent="file.isDir ? navigate(file) : null"
                       style="margin-top:6px;font-size:11px;font-weight:500;color:var(--color-gray-600, #4b5563);width:100%;text-align:center;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;display:block;"
                       x-text="basename(file.path)"
                    ></a>

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
                                    @click.stop.prevent="openUrlModal(file)"
                                    style="width:100%;font-size:12px;padding:4px 8px;"
                                >
                                    <x-moonshine::icon icon="globe-alt" style="width:14px;height:14px;"/>
                                    {{ __('moonshine-media-manager::media-manager.url') }}
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

    {{-- Page modals (prefixed mmp- to avoid conflict with offcanvas mm- modals) --}}

    <x-moonshine::modal name="mmp-upload" title="{{ __('moonshine-media-manager::media-manager.upload') }}" :closeOutside="true">
        <form @submit.prevent="submitUpload()">
            <div class="flex flex-col gap-4">
                <input type="file" id="mmp-upload-input" name="files[]" multiple required class="file-input" />
                <x-moonshine::form.button type="submit">
                    {{ __('moonshine-media-manager::media-manager.submit') }}
                </x-moonshine::form.button>
            </div>
        </form>
    </x-moonshine::modal>

    <x-moonshine::modal name="mmp-rename" title="{{ __('moonshine-media-manager::media-manager.rename') }}" :closeOutside="true">
        <form @submit.prevent="submitRename()">
            <div class="flex flex-col gap-4">
                <x-moonshine::form.input x-model="renameNew" placeholder="{{ __('moonshine-media-manager::media-manager.new_path') }}" />
                <x-moonshine::form.button type="submit">
                    {{ __('moonshine-media-manager::media-manager.submit') }}
                </x-moonshine::form.button>
            </div>
        </form>
    </x-moonshine::modal>

    <x-moonshine::modal name="mmp-new-folder" title="{{ __('moonshine-media-manager::media-manager.new_folder') }}" :closeOutside="true">
        <form @submit.prevent="submitNewFolder()">
            <div class="flex flex-col gap-4">
                <x-moonshine::form.input x-model="newFolderName" placeholder="{{ __('moonshine-media-manager::media-manager.name') }}" />
                <x-moonshine::form.button type="submit">
                    {{ __('moonshine-media-manager::media-manager.submit') }}
                </x-moonshine::form.button>
            </div>
        </form>
    </x-moonshine::modal>

    <x-moonshine::modal name="mmp-delete" title="{{ __('moonshine-media-manager::media-manager.delete') }}" :closeOutside="true">
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

    <x-moonshine::modal name="mmp-url" title="{{ __('moonshine-media-manager::media-manager.url') }}" :closeOutside="true">
        <div class="flex flex-col gap-4">
            <div class="break-all select-all text-sm" x-text="urlToShow"></div>
            <div class="flex justify-end">
                <x-moonshine::form.button @click.prevent="window.MoonShine?.ui?.toggleModal(modalPrefix + 'url')">
                    {{ __('moonshine-media-manager::media-manager.close') }}
                </x-moonshine::form.button>
            </div>
        </div>
    </x-moonshine::modal>

</div>
