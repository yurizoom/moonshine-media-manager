@include('moonshine-media-manager::menu')

<x-moonshine::divider/>

<x-moonshine::box>
    @include('moonshine-media-manager::breadcrumbs')

    @yield('media-manager-content')
</x-moonshine::box>

