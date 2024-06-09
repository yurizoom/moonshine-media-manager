@props([
    'isDir',
    'type',
    'preview',
    'class' => ''
])

<style>
    .file-preview {
        height: 2rem;
        width: 2rem
    }
    .file-preview.size-10 {
        height: 5rem;
        width: 5rem;
    }
    .file-preview img {
        width: 100%;
        height: 100%;
        object-position: center;
        object-fit: contain;
    }
</style>


@if($isDir)
    <x-moonshine::icon icon="heroicons.folder" class="file-preview {{ $class }}"/>
@else
    @switch($type)
        @case('image')
            @if($preview)
                <div class="file-preview {{ $class }}">{!! $preview !!}</div>
            @else
                <x-moonshine::icon icon="heroicons.photo" class="file-preview {{ $class }}"/>
            @endif
            @break
        @case('zip')
            <x-moonshine::icon icon="heroicons.archive-box" class="file-preview {{ $class }}"/>
            @break
        @case('word')
            <x-moonshine::icon icon="heroicons.newspaper" class="file-preview {{ $class }}"/>
            @break
        @case('ppt')
            <x-moonshine::icon icon="heroicons.presentation-chart-bar" class="file-preview {{ $class }}"/>
            @break
        @case('xls')
            <x-moonshine::icon icon="heroicons.table-cells" class="file-preview {{ $class }}"/>
            @break
        @case('txt')
            <x-moonshine::icon icon="heroicons.document-text" class="file-preview {{ $class }}"/>
            @break
        @case('code')
            <x-moonshine::icon icon="heroicons.code-bracket" class="file-preview {{ $class }}"/>
            @break
        @default
            <x-moonshine::icon icon="heroicons.document" class="file-preview {{ $class }}"/>
    @endswitch
@endif
