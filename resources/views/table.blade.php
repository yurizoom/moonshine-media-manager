@extends('moonshine-media-manager::layouts.layout')

@section('media-manager-content')
    @if (!empty($list))
        <x-moonshine::table>
            <x-slot:thead>
                <tr>
                    <th>
                        <x-moonshine::form.label>
                            <x-moonshine::form.input
                                type="checkbox"
                                value=""
                            />
                        </x-moonshine::form.label>
                    </th>
                    <th>{{ __('moonshine-media-manager::media-manager.name') }}</th>
                    <th></th>
                    <th>{{ __('moonshine-media-manager::media-manager.time') }}</th>
                    <th>{{ __('moonshine-media-manager::media-manager.size') }}</th>
                </tr>
            </x-slot:thead>
            <x-slot:tbody>
                @foreach($list as $item)
                    <tr>
                        <td>
                            <x-moonshine::form.label>
                                <x-moonshine::form.input
                                    type="checkbox"
                                    value="{{ $item['name'] }}"
                                />
                            </x-moonshine::form.label>
                        </td>
                        <td>
{{--                            {!! $item['preview'] !!}--}}

                            <x-moonshine::link-native href="{{ $item['link'] }}"
                                                      title="{{ $item['name'] }}"
                            >
                                {{ $item['icon'] }} {{ basename($item['name']) }}
                            </x-moonshine::link-native>
                        </td>

                        <td>
                            <div class="flex">
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

                        </td>
                        <td>{{ $item['time'] }}</td>
                        <td>{{ $item['size'] }}</td>
                    </tr>
                @endforeach
            </x-slot:tbody>
        </x-moonshine::table>
    @endif
@endsection
