@php
    $items = filament()->auth()->check() ? $rail->getItems() : [];
@endphp

{{-- The rail's links, repeated in the sidebar for screens where the rail is hidden. --}}
@if (filled($items))
    <ul class="fi-sidebar-nav-groups fi-rail-sidebar-items">
        <li class="fi-sidebar-group">
            <ul class="fi-sidebar-group-items">
                @foreach ($items as $item)
                    <x-filament-panels::sidebar.item
                        :active="$item->isActive()"
                        :active-icon="$item->getActiveIcon()"
                        :badge="$item->getBadge()"
                        :badge-color="$item->getBadgeColor()"
                        :icon="$item->getIcon()"
                        :should-open-url-in-new-tab="$item->shouldOpenUrlInNewTab()"
                        :url="$item->getUrl()"
                    >
                        {{ $item->getLabel() }}
                    </x-filament-panels::sidebar.item>
                @endforeach
            </ul>
        </li>
    </ul>
@endif
