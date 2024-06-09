<tr>
    <td>
        <x-moonshine::link-native href="{{ $items['link'] }}"
                                  title="{{ $items['path'] }}"
                                  class="flex gap-2"
        >
            <x-moonshine-media-manager-preview isDir="{{ $items['isDir'] }}" type="{{ $items['type'] ?? '' }}" preview="{!! $items['preview'] !!}" class=""/>
            <div>{{ $items['icon'] }} {{ basename($items['path']) }}</div>
        </x-moonshine::link-native>
    </td>
    <td>{{ $items['time'] }}</td>
    <td>{{ $items['size'] }}</td>
    <td>
        <div class="flex justify-end gap-2">
            @foreach($actions as $button)
                @if($button->isSee([]))
                    {{ $button->render() }}
                @endif
            @endforeach
        </div>

    </td>
</tr>
