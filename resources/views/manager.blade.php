@props([
    'urls' => [],
])

<div x-data="mmBrowser({{ Js::from($urls) }}, 'mmp-')">

    {{-- Toolbar --}}
    @include('moonshine-media-manager::partials.browser-toolbar', ['showLabels' => true])

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
                                    title="{{ __('moonshine-media-manager::media-manager.tooltip.download') }}"
                                >
                                    <x-moonshine::icon icon="cloud-arrow-down"/>
                                </x-moonshine::link-button>

                                <x-moonshine::link-button
                                    @click.prevent="openUrlModal(file)"
                                    class="btn-sm"
                                    title="{{ __('moonshine-media-manager::media-manager.tooltip.show_url') }}"
                                >
                                    <x-moonshine::icon icon="globe-alt"/>
                                </x-moonshine::link-button>

                                <x-moonshine::link-button
                                    @click.prevent="openRenameModal(file)"
                                    class="btn-sm btn-primary"
                                    title="{{ __('moonshine-media-manager::media-manager.tooltip.rename') }}"
                                >
                                    <x-moonshine::icon icon="pencil"/>
                                </x-moonshine::link-button>

                                <x-moonshine::link-button
                                    @click.prevent="openDeleteModal(file)"
                                    class="btn-sm btn-error"
                                    title="{{ __('moonshine-media-manager::media-manager.tooltip.delete') }}"
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

    {{-- Modals --}}
    @include('moonshine-media-manager::partials.browser-modals', ['modalPrefix' => 'mmp-', 'showUrlModal' => true])

</div>
