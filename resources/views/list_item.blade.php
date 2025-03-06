<div class="file">
    <div class="file-info">
        <x-moonshine-media-manager-preview isDir="{{ $items['isDir'] }}" type="{{ $items['type'] ?? '' }}" preview="{!! $items['preview'] !!}" class="size-10"/>

        <a @if(!$items['isDir'])target="_blank" @endif href="{{ $items['link'] }}" class="file-name"
           title="{{ $items['path'] }}">
            {{ basename($items['path']) }}
        </a>
        <span class="file-size">{{ $items['size'] }}</span>
    </div>

    <x-moonshine::dropdown>
        <div class="dropdown-menu">
            @foreach($actions as $button)
                @if($button->isSee([]))
                    {{ $button->customAttributes(['class' => 'w-full'])->render() }}
                @endif
            @endforeach
        </div>
        <x-slot:toggler>
            <x-moonshine::icon icon="ellipsis-horizontal"/>
        </x-slot:toggler>
    </x-moonshine::dropdown>
</div>
