@props([
    'showCheckboxes' => false,
    'showUrlButton' => false,
    'idPrefix' => 'file-',
])

<div x-show="view === 'table' && !loading">
    <x-moonshine::table>
        <x-slot:thead>
            <tr>
                @if($showCheckboxes)
                    <th class="w-8"></th>
                @endif
                <th>{{ __('moonshine-media-manager::media-manager.name') }}</th>
                <th>{{ __('moonshine-media-manager::media-manager.time') }}</th>
                <th>{{ __('moonshine-media-manager::media-manager.size') }}</th>
                <th></th>
            </tr>
        </x-slot:thead>
        <x-slot:tbody>
            <template x-for="file in displayedFiles" :key="file.path">
                <tr :id="'{{ $idPrefix }}' + file.path.replace(/[^a-zA-Z0-9]/g, '_')"
                    :class="highlightPath === file.path ? 'mm-table-row--highlight' : 'hover:bg-gray-50 dark:hover:bg-gray-800/50'">
                    @if($showCheckboxes)
                        <td>
                            <template x-if="!file.isDir">
                                <input type="checkbox"
                                       :checked="$store.mm.isSelected(file.path)"
                                       @change="$store.mm.toggleFile(file)"
                                       class="checkbox checkbox-sm"
                                />
                            </template>
                        </td>
                    @endif
                    <td>
                        <a href="#"
                           @click.prevent="file.isDir && navigate(file)"
                           class="flex items-center gap-2"
                        >
                            <template x-if="file.isDir">
                                <x-moonshine::icon icon="folder" class="size-5 text-yellow-500"/>
                            </template>
                            <template x-if="!file.isDir && file.type === 'image'">
                                <div class="mm-table-thumb">
                                    <img :src="file.url" alt="" loading="lazy" decoding="async"/>
                                </div>
                            </template>
                            <template x-if="!file.isDir && file.type !== 'image'">
                                <x-moonshine::icon icon="document" class="size-5 text-gray-400"/>
                            </template>
                            <span x-text="basename(file.path)"@if($showCheckboxes) class="truncate max-w-xs"@endif></span>
                        </a>
                    </td>
                    <td x-text="file.time" class="whitespace-nowrap text-sm text-gray-500"></td>
                    <td x-text="file.size" class="whitespace-nowrap text-sm text-gray-500"></td>
                    <td>
                        <div class="flex justify-end gap-1">
                            <x-moonshine::link-button
                                x-show="!file.isDir && file.type === 'image'"
                                @click.prevent="openImagePreview(file)"
                                class="btn-sm"
                                title="{{ __('moonshine-media-manager::media-manager.tooltip.view_image') }}"
                            >
                                <x-moonshine::icon icon="eye"/>
                            </x-moonshine::link-button>

                            <x-moonshine::link-button
                                x-show="!file.isDir"
                                @click.prevent="download(file)"
                                class="btn-sm btn-success"
                                title="{{ __('moonshine-media-manager::media-manager.tooltip.download') }}"
                            >
                                <x-moonshine::icon icon="cloud-arrow-down"/>
                            </x-moonshine::link-button>

                            @if($showUrlButton)
                                <x-moonshine::link-button
                                    x-show="!file.isDir"
                                    @click.prevent="openUrlModal(file)"
                                    class="btn-sm"
                                    title="{{ __('moonshine-media-manager::media-manager.tooltip.show_url') }}"
                                >
                                    <x-moonshine::icon icon="globe-alt"/>
                                </x-moonshine::link-button>
                            @endif

                            @foreach($mmFileActions as $actionName => $action)
                                <x-moonshine::link-button
                                    x-show="{{ $action['x-show'] ?? 'true' }}"
                                    @click.prevent="{{ $action['click'] }}"
                                    class="btn-sm {{ $action['class'] ?? '' }}"
                                    title="{{ $action['label'] ?? $actionName }}"
                                >
                                    @if(!empty($action['icon']))
                                        <x-moonshine::icon icon="{{ $action['icon'] }}"/>
                                    @endif
                                </x-moonshine::link-button>
                            @endforeach

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
