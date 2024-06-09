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
            @foreach($items as $row)
                {{ $row->render() }}
            @endforeach
        </x-slot:tbody>
    </x-moonshine::table>
