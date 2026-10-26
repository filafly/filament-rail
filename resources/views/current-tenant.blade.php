@php
    use Filament\Facades\Filament;
    use Filament\Models\Contracts\HasCurrentTenantLabel;

    $tenant = Filament::getTenant();
    $isSidebarCollapsibleOnDesktop = $isInSidebar && Filament::isSidebarCollapsibleOnDesktop();
@endphp

{{-- The current tenant in the brand's place. Shown on desktop only, beside the rail; see rail.css. --}}
<a
    href="{{ Filament::getUrl($tenant) }}"
    @if ($isSidebarCollapsibleOnDesktop)
        x-show="$store.sidebar.isOpen"
    @endif
    class="fi-rail-current-tenant"
>
    <x-filament-panels::avatar.tenant :tenant="$tenant" loading="lazy" />

    <span class="fi-rail-current-tenant-text">
        @if ($tenant instanceof HasCurrentTenantLabel)
            <span class="fi-rail-current-tenant-label">
                {{ $tenant->getCurrentTenantLabel() }}
            </span>
        @endif

        <span class="fi-rail-current-tenant-name">
            {{ Filament::getTenantName($tenant) }}
        </span>
    </span>
</a>
