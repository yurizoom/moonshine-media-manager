@props([
    'showCheckboxes' => false,
    'showUrlButton' => false,
    'showSelection' => false,
    'idPrefix' => 'file-',
])

<div x-show="view === 'list' && !loading">
    <div class="mm-list-grid">
        <template x-for="file in files" :key="file.path">
            <div class="mm-list-item"
                 :class="{
                     'mm-list-item--dir': file.isDir,
                     'mm-list-item--highlight': highlightPath === file.path,
                     @if($showSelection)
                     'mm-list-item--selected': !file.isDir && $store.mm.isSelected(file.path) && highlightPath !== file.path
                     @endif
                 }"
                 :id="'{{ $idPrefix }}' + file.path.replace(/[^a-zA-Z0-9]/g, '_')"
                 @if($showSelection)
                     @click.prevent="file.isDir ? navigate(file) : $store.mm.toggleFile(file)"
                 @else
                     @click.prevent="file.isDir && navigate(file)"
                 @endif
            >
                @if($showCheckboxes)
                    <template x-if="!file.isDir">
                        <input type="checkbox"
                               :checked="$store.mm.isSelected(file.path)"
                               @click.stop
                               @change="$store.mm.toggleFile(file)"
                               class="mm-list-checkbox"
                        />
                    </template>
                @endif

                <template x-if="file.isDir">
                    <div class="mm-list-preview mm-list-preview--folder">
                        <x-moonshine::icon icon="folder"/>
                    </div>
                </template>
                <template x-if="!file.isDir && file.type === 'image'">
                    <div class="mm-list-preview">
                        <img :src="file.url" :alt="basename(file.path)" />
                    </div>
                </template>
                <template x-if="!file.isDir && file.type !== 'image'">
                    <div class="mm-list-preview mm-list-preview--document">
                        <x-moonshine::icon icon="document"/>
                    </div>
                </template>

                <a href="#"
                   @click.prevent="file.isDir ? navigate(file) : null"
                   class="mm-list-name"
                   x-text="basename(file.path)"
                ></a>

                <div class="mm-list-actions" @click.stop>
                    <x-moonshine::dropdown>
                        <div class="dropdown-menu mm-list-dropdown-menu">
                            <x-moonshine::link-button
                                    x-show="!file.isDir"
                                    @click.stop.prevent="download(file)"
                                    class="mm-list-dropdown-btn"
                            >
                                <x-moonshine::icon icon="cloud-arrow-down" class="mm-list-dropdown-icon"/>
                                {{ __('moonshine-media-manager::media-manager.download') }}
                            </x-moonshine::link-button>
                            @if($showUrlButton)
                                <x-moonshine::link-button
                                        @click.stop.prevent="openUrlModal(file)"
                                        class="mm-list-dropdown-btn"
                                >
                                    <x-moonshine::icon icon="globe-alt" class="mm-list-dropdown-icon"/>
                                    {{ __('moonshine-media-manager::media-manager.url') }}
                                </x-moonshine::link-button>
                            @endif
                            @foreach($mmFileActions as $actionName => $action)
                                <x-moonshine::link-button
                                        x-show="{{ $action['x-show'] ?? 'true' }}"
                                        @click.stop.prevent="{{ $action['click'] }}"
                                        class="mm-list-dropdown-btn"
                                >
                                    @if(!empty($action['icon']))
                                        <x-moonshine::icon icon="{{ $action['icon'] }}" class="mm-list-dropdown-icon"/>
                                    @endif
                                    {{ $action['label'] ?? $actionName }}
                                </x-moonshine::link-button>
                            @endforeach
                            <x-moonshine::link-button
                                    @click.stop.prevent="openRenameModal(file)"
                                    class="mm-list-dropdown-btn"
                            >
                                <x-moonshine::icon icon="pencil" class="mm-list-dropdown-icon"/>
                                {{ __('moonshine-media-manager::media-manager.rename') }}
                            </x-moonshine::link-button>
                            <x-moonshine::link-button
                                    @click.stop.prevent="openDeleteModal(file)"
                                    class="mm-list-dropdown-btn"
                            >
                                <x-moonshine::icon icon="trash" class="mm-list-dropdown-icon"/>
                                {{ __('moonshine-media-manager::media-manager.delete') }}
                            </x-moonshine::link-button>
                        </div>
                        <x-slot:toggler>
                            <x-moonshine::icon icon="ellipsis-horizontal" class="mm-list-dropdown-icon mm-list-dropdown-icon--muted"/>
                        </x-slot:toggler>
                    </x-moonshine::dropdown>
                </div>
            </div>
        </template>
    </div>
</div>
