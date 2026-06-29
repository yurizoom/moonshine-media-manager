@props([
    'urls' => [],
])

<div x-data="mmBrowser({{ Js::from($urls) }}, 'mmp-')"
     :class="{ 'mm-drag-active': isDragOver }"
>

    @include('moonshine-media-manager::partials.browser-toolbar', ['showLabels' => true])

    @include('moonshine-media-manager::partials.browser-breadcrumbs')

    @include('moonshine-media-manager::partials.browser-loading')

    @include('moonshine-media-manager::partials.browser-table', ['showUrlButton' => true])

    @include('moonshine-media-manager::partials.browser-list', ['showUrlButton' => true, 'idPrefix' => 'file-'])

    @include('moonshine-media-manager::partials.browser-empty-state')

    @include('moonshine-media-manager::partials.browser-modals', ['modalPrefix' => 'mmp-', 'showUrlModal' => true])

</div>
