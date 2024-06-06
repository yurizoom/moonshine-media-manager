@include('moonshine-media-manager::snippets.menu')

<x-moonshine::divider/>

<x-moonshine::box>
    @include('moonshine-media-manager::snippets.breadcrumbs')

    @yield('media-manager-content')
</x-moonshine::box>

