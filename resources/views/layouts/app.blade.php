<x-layouts.app :title="$title ?? 'まつり総合管理システム'">
    {{ $slot ?? '' }}
    @yield('content')
</x-layouts.app>
