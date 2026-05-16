@props([
    'showCheckboxes' => false,
    'showUrlButton' => false,
    'showSelection' => false,
    'idPrefix' => 'file-',
])

<div x-show="view === 'list' && !loading">
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:12px;">
        <template x-for="file in files" :key="file.path">
            <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;padding:8px;border-radius:10px;border:1px solid transparent;cursor:pointer;transition:all .15s;text-align:center;"
                 :id="'{{ $idPrefix }}' + file.path.replace(/[^a-zA-Z0-9]/g, '_')"
                 @if($showSelection)
                     :style="Object.assign({position:'relative'}, highlightPath === file.path ? {borderColor:'#f59e0b',background:'rgba(245,158,11,0.12)',boxShadow:'0 0 0 3px rgba(245,158,11,0.4)'} : (!file.isDir && $store.mm.isSelected(file.path) ? {borderColor:'var(--color-primary, #3b82f6)',background:'rgba(59,130,246,0.06)'} : {}))"
                     @click.prevent="file.isDir ? navigate(file) : $store.mm.toggleFile(file)"
                     @mouseenter="$el.style.borderColor = file.isDir ? 'var(--color-gray-300, #d1d5db)' : 'var(--color-primary, #3b82f6)'"
                     @mouseleave="$el.style.borderColor = (!file.isDir && $store.mm.isSelected(file.path)) ? 'var(--color-primary, #3b82f6)' : (highlightPath === file.path ? '#f59e0b' : 'transparent')"
                 @else
                     :style="Object.assign({position:'relative'}, highlightPath === file.path ? {borderColor:'#f59e0b',background:'rgba(245,158,11,0.12)',boxShadow:'0 0 0 3px rgba(245,158,11,0.4)'} : {})"
                     @click.prevent="file.isDir && navigate(file)"
                     @mouseenter="$el.style.borderColor = file.isDir ? 'var(--color-gray-300, #d1d5db)' : 'var(--color-primary, #3b82f6)'"
                     @mouseleave="$el.style.borderColor = highlightPath === file.path ? '#f59e0b' : 'transparent'"
                 @endif
            >
                @if($showCheckboxes)
                    <template x-if="!file.isDir">
                        <input type="checkbox"
                               :checked="$store.mm.isSelected(file.path)"
                               @click.stop
                               @change="$store.mm.toggleFile(file)"
                               style="position:absolute;top:6px;left:6px;width:16px;height:16px;accent-color:var(--color-primary, #3b82f6);cursor:pointer;"
                        />
                    </template>
                @endif

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
                            @if($showUrlButton)
                                <x-moonshine::link-button
                                        @click.stop.prevent="openUrlModal(file)"
                                        style="width:100%;font-size:12px;padding:4px 8px;"
                                >
                                    <x-moonshine::icon icon="globe-alt" style="width:14px;height:14px;"/>
                                    {{ __('moonshine-media-manager::media-manager.url') }}
                                </x-moonshine::link-button>
                            @endif
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
