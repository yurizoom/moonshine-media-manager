@props([
    'showLabels' => false,
])

<div class="mm-toolbar flex items-center justify-between gap-3 flex-wrap mb-4">
    <div class="flex items-center gap-2">
        <x-moonshine::link-button
            @click.prevent="refresh()"
            class="btn-warning"
            title="{{ __('moonshine-media-manager::media-manager.tooltip.refresh') }}"
        >
            <x-moonshine::icon icon="arrow-path"/>
        </x-moonshine::link-button>

        <x-moonshine::link-button
            @click.prevent="openUploadModal()"
            class="btn-success"
            title="{{ __('moonshine-media-manager::media-manager.tooltip.upload') }}"
        >
            <x-moonshine::icon icon="cloud-arrow-up"/>
            @if($showLabels)
                {{ __('moonshine-media-manager::media-manager.upload') }}
            @endif
        </x-moonshine::link-button>

        <x-moonshine::link-button
            @click.prevent="openNewFolderModal()"
            class="btn-secondary"
            title="{{ __('moonshine-media-manager::media-manager.tooltip.new_folder') }}"
        >
            <x-moonshine::icon icon="folder-plus"/>
            @if($showLabels)
                {{ __('moonshine-media-manager::media-manager.new_folder') }}
            @endif
        </x-moonshine::link-button>

        <x-moonshine::link-button
            @click.prevent="switchView('table')"
            title="{{ __('moonshine-media-manager::media-manager.tooltip.view_table') }}"
        >
            <x-moonshine::icon icon="list-bullet" x-bind:class="view === 'table' ? 'text-primary' : ''"/>
        </x-moonshine::link-button>

        <x-moonshine::link-button
            @click.prevent="switchView('list')"
            title="{{ __('moonshine-media-manager::media-manager.tooltip.view_grid') }}"
        >
            <x-moonshine::icon icon="squares-2x2" x-bind:class="view === 'list' ? 'text-primary' : ''"/>
        </x-moonshine::link-button>

        @foreach($mmToolbarActions as $actionName => $action)
            <x-moonshine::link-button
                @click.prevent="{{ $action['click'] }}"
                class="{{ $action['class'] ?? '' }}"
                title="{{ $action['label'] ?? $actionName }}"
            >
                @if(!empty($action['icon']))
                    <x-moonshine::icon icon="{{ $action['icon'] }}"/>
                @endif
                @if(!empty($action['showLabel']) || ($showLabels && !empty($action['label'])))
                    {{ $action['label'] }}
                @endif
            </x-moonshine::link-button>
        @endforeach
    </div>

    <div class="flex">
        <x-moonshine::form.input
            x-model="jumpPath"
            @keydown.enter.prevent="quickJump()"
            placeholder="Path"
            class="w-48"
        />
        <x-moonshine::link-button
            @click.prevent="quickJump()"
            title="{{ __('moonshine-media-manager::media-manager.tooltip.quick_jump') }}"
        >
            <x-moonshine::icon icon="arrow-small-right"/>
        </x-moonshine::link-button>
    </div>
</div>

<div class="mm-filters">
    <div class="mm-search">
        <x-moonshine::icon icon="magnifying-glass" class="mm-search-icon"/>
        <input type="text"
               x-model.debounce.300ms="searchQuery"
               placeholder="{{ __('moonshine-media-manager::media-manager.search_placeholder') }}"
               class="mm-search-input"
        />
        <button type="button"
                x-show="searchQuery"
                @click.prevent="searchQuery = ''"
                class="mm-search-clear"
                title="{{ __('moonshine-media-manager::media-manager.clear') }}"
        >×</button>
    </div>

    <div class="mm-filter-chips">
        <button type="button"
                @click.prevent="typeFilter = 'all'"
                :class="typeFilter === 'all' ? 'mm-chip mm-chip--active' : 'mm-chip'"
        >{{ __('moonshine-media-manager::media-manager.filter_all') }}</button>
        <button type="button"
                @click.prevent="typeFilter = 'images'"
                :class="typeFilter === 'images' ? 'mm-chip mm-chip--active' : 'mm-chip'"
        >{{ __('moonshine-media-manager::media-manager.filter_images') }}</button>
        <button type="button"
                @click.prevent="typeFilter = 'documents'"
                :class="typeFilter === 'documents' ? 'mm-chip mm-chip--active' : 'mm-chip'"
        >{{ __('moonshine-media-manager::media-manager.filter_documents') }}</button>
        <button type="button"
                @click.prevent="typeFilter = 'video'"
                :class="typeFilter === 'video' ? 'mm-chip mm-chip--active' : 'mm-chip'"
        >{{ __('moonshine-media-manager::media-manager.filter_video') }}</button>
        <button type="button"
                @click.prevent="typeFilter = 'audio'"
                :class="typeFilter === 'audio' ? 'mm-chip mm-chip--active' : 'mm-chip'"
        >{{ __('moonshine-media-manager::media-manager.filter_audio') }}</button>
        <button type="button"
                @click.prevent="typeFilter = 'archives'"
                :class="typeFilter === 'archives' ? 'mm-chip mm-chip--active' : 'mm-chip'"
        >{{ __('moonshine-media-manager::media-manager.filter_archives') }}</button>
    </div>

    <div class="mm-sort">
        <select x-model="sortField" class="mm-sort-select">
            <option value="name">{{ __('moonshine-media-manager::media-manager.sort_name') }}</option>
            <option value="date">{{ __('moonshine-media-manager::media-manager.sort_date') }}</option>
            <option value="size">{{ __('moonshine-media-manager::media-manager.sort_size') }}</option>
        </select>
        <button type="button"
                @click.prevent="sortDir = sortDir === 'asc' ? 'desc' : 'asc'"
                class="mm-sort-toggle"
                x-bind:title="sortDir === 'asc' ? '{{ __('moonshine-media-manager::media-manager.sort_asc') }}' : '{{ __('moonshine-media-manager::media-manager.sort_desc') }}'"
        >
            <x-moonshine::icon icon="arrow-up" x-show="sortDir === 'asc'"/>
            <x-moonshine::icon icon="arrow-down" x-show="sortDir === 'desc'"/>
        </button>
    </div>
</div>
