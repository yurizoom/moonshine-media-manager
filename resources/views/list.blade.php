@extends('moonshine-media-manager::layouts.layout')

<style>
    .files {
        display: flex;
        gap: 10px;
        margin-top: 1rem;
        #list-style: none;
        #margin: 0;
        #padding: 0;
    }

    .files > .file {
        #float: left;
        width: 150px;
        border-radius: 1rem;
        border-width: 1px;
        border-color: rgba(var(--dark-100), var(--tw-bg-opacity));
        margin-bottom: 10px;
        #position: relative;
        padding: .2rem .5rem;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: space-between;
    }

    .files > .file > .file-select {
        #position: absolute;
        #top: -4px;
        #left: -1px;
    }

    .file-checkbox {
        align-self: start;
    }

    .file-preview {
        height: 2rem;
        width: 2rem
    }

    .file-info {
        display: flex;
        flex-direction: column;
        align-items: center;
        #padding: 10px;
        width: 100%;
        #background: #f4f4f4;
    }

    .file-name {
        font-weight: bold;
        color: #666;
        display: block;
        width: 100%;
        text-align: center;
        overflow: hidden !important;
        white-space: nowrap !important;
        text-overflow: ellipsis !important;
    }

    .file-size {
        color: #999;
        font-size: 12px;
        display: block;
    }

</style>

@section('media-manager-content')
    <div class="files">
        @if (empty($list))
            <div style="height: 200px;border: none;"></div>
        @else
            @foreach($list as $item)
                <div class="file">
                    <div class="file-info">
                        <x-moonshine::form.label class="file-checkbox">
                            <x-moonshine::form.input
                                type="checkbox"
                                value="$item['name']"
                            />
                        </x-moonshine::form.label>

                        @if($item['isDir'])
                            <x-moonshine::icon icon="heroicons.folder" class="file-preview"/>
                        @else
                            @switch($item['type'])
                                @case('image')
                                    @if($item['preview'])
                                        {!! $item['preview'] !!}
                                    @else
                                        <x-moonshine::icon icon="heroicons.photo" class="file-preview"/>
                                    @endif
                                    @break
                                @case('zip')
                                    <x-moonshine::icon icon="heroicons.archive-box" class="file-preview"/>
                                    @break
                                @case('word')
                                    <x-moonshine::icon icon="heroicons.newspaper" class="file-preview"/>
                                    @break
                                @case('ppt')
                                    <x-moonshine::icon icon="heroicons.presentation-chart-bar" class="file-preview"/>
                                    @break
                                @case('xls')
                                    <x-moonshine::icon icon="heroicons.table-cells" class="file-preview"/>
                                    @break
                                @case('txt')
                                    <x-moonshine::icon icon="heroicons.document-text" class="file-preview"/>
                                    @break
                                @case('code')
                                    <x-moonshine::icon icon="heroicons.code-bracket" class="file-preview"/>
                                    @break
                                @default
                                    <x-moonshine::icon icon="heroicons.document" class="file-preview"/>
                            @endswitch
                        @endif

                        <a @if(!$item['isDir'])target="_blank" @endif href="{{ $item['link'] }}" class="file-name"
                           title="{{ $item['name'] }}">
                            {{ basename($item['name']) }}
                        </a>
                        <span class="file-size">
                        {{ $item['size'] }}
                        </span>
                    </div>

                    <x-moonshine::dropdown>
                        <div class="dropdown-menu">
                            <x-moonshine::modal title="Rename & Move">
                                <x-moonshine::form raw>
                                    <x-moonshine::form.label name="path"> Path</x-moonshine::form.label>
                                    <x-moonshine::form.input name="new" value=""/>
                                    <x-moonshine::form.input type="hidden" name="path"/>
                                    <div>
                                        <x-moonshine::form.button @click.prevent="toggleModal">Close
                                        </x-moonshine::form.button>
                                        <x-moonshine::form.button class="btn-primary">Submit
                                        </x-moonshine::form.button>
                                    </div>
                                </x-moonshine::form>
                                <x-slot name="outerHtml">
                                    <x-moonshine::link-button @click.prevent="toggleModal"
                                                              icon="heroicons.outline.pencil-square">
                                    </x-moonshine::link-button>
                                </x-slot>
                            </x-moonshine::modal>
                            <x-moonshine::link-button href="#"
                                                      icon="heroicons.outline.trash"
                            >
                            </x-moonshine::link-button>
                            @unless($item['isDir'])
                                <x-moonshine::link-button href="{{ $item['download'] }}"
                                                          icon="heroicons.outline.cloud-arrow-down"
                                                          target="_blank"
                                >
                                </x-moonshine::link-button>
                            @endunless
                            <x-moonshine::modal :closeOutside="false" title="Url">
                                <div>
                                    <x-moonshine::form.input/>
                                    <x-moonshine::form.button @click.prevent="toggleModal">Close
                                    </x-moonshine::form.button>
                                </div>
                                <x-slot name="outerHtml">
                                    <x-moonshine::link-button @click.prevent="toggleModal"
                                                              icon="heroicons.outline.globe-alt">
                                    </x-moonshine::link-button>
                                </x-slot>
                            </x-moonshine::modal>
                        </div>
                        <x-slot:toggler>
                            <x-moonshine::icon icon="heroicons.ellipsis-horizontal"/>
                        </x-slot:toggler>
                    </x-moonshine::dropdown>
                </div>
            @endforeach
        @endif
    </div>
@endsection
