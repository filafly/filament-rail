@php
    use Filament\Facades\Filament;
    use Filament\Models\Contracts\HasAvatar;
    use Filament\Support\Enums\IconSize;
    use Filament\Support\Icons\Heroicon;
    use Illuminate\Contracts\Support\Htmlable;
    use Illuminate\Support\Str;

    use function Filament\Support\generate_icon_html;

    $plainText = fn (string | Htmlable | null $text): string => ($text instanceof Htmlable)
        ? trim(html_entity_decode(strip_tags($text->toHtml()), ENT_QUOTES | ENT_HTML5, 'UTF-8'))
        : (string) $text;

    $initials = fn (string $name): string => Str::of($name)
        ->squish()
        ->explode(' ')
        ->take(2)
        ->map(fn (string $word): string => Str::upper(Str::substr($word, 0, 1)))
        ->join('');

    $brandName = $plainText(Filament::getBrandName());
    $logo = $rail->getLogo();
    $color = $rail->getColor();

    $hasTenants = $rail->hasTenants();
    $currentTenant = $hasTenants ? Filament::getTenant() : null;
    $tenants = $hasTenants ? $rail->getTenants() : [];
    ['register' => $registerLink, 'links' => $tenantLinks] = $hasTenants ? $rail->getTenantMenuLinks() : ['register' => null, 'links' => []];

    $items = Filament::auth()->check() ? $rail->getItems() : [];

    $hasNotifications = $rail->hasNotifications();
    $unreadNotificationsCount = $hasNotifications
        ? Filament::auth()->user()->unreadNotifications()->count()
        : 0;
    $notificationsLabel = __('filament-panels::layout.actions.open_database_notifications.label');
@endphp

{{-- The rail is a dark surface in both themes, so everything on it is styled for one. --}}
<aside
    x-data
    aria-label="{{ $brandName }}"
    @class([
        'fi-rail',
        'fi-rail-has-tenants' => $hasTenants && (! $rail->hasTenantMenuInSidebar()),
        'fi-rail-has-notifications' => $hasNotifications,
        'fi-rail-tinted' => filled($color),
        'fi-rail-hides-brand' => $rail->hidesBrand(),
    ])
    @if (filled($color))
        style="--fi-rail-tint: {{ e($color) }}"
    @endif
>
    <a
        href="{{ Filament::getHomeUrl() ?? Filament::getUrl() }}"
        class="fi-rail-logo"
        x-tooltip="{ content: @js($brandName), placement: 'right', theme: $store.theme }"
    >
        <span class="fi-rail-logo-tile">
            @if (filled($logo))
                <img src="{{ $logo }}" alt="" aria-hidden="true" />
            @else
                <span class="fi-rail-logo-initial" aria-hidden="true">{{ Str::upper(Str::substr($brandName, 0, 1)) }}</span>
            @endif
        </span>
        <span class="fi-sr-only">{{ $brandName }}</span>
    </a>

    <nav class="fi-rail-tenants" @if ($hasTenants) aria-label="{{ __('filament-panels::layout.tenant_menu.search_field.label') }}" @endif>
        @foreach ($tenants as $tenant)
            @php
                $tenantName = $plainText(Filament::getTenantName($tenant));
                $tenantAvatar = ($tenant instanceof HasAvatar) ? $tenant->getFilamentAvatarUrl() : null;
                $isCurrentTenant = $tenant->is($currentTenant);
                $statusColor = $rail->getTenantStatusColor($tenant);
            @endphp

            <a
                href="{{ Filament::getUrl($tenant) }}"
                @if ($isCurrentTenant) aria-current="page" @endif
                @class([
                    'fi-rail-tenant',
                    'fi-active' => $isCurrentTenant,
                ])
                x-tooltip="{ content: @js($tenantName), placement: 'right', theme: $store.theme }"
            >
                @if (filled($tenantAvatar))
                    <img src="{{ $tenantAvatar }}" alt="" class="fi-rail-tenant-avatar" />
                @else
                    <span class="fi-rail-tenant-initials" aria-hidden="true">{{ $initials($tenantName) }}</span>
                @endif

                <span class="fi-sr-only">{{ $tenantName }}</span>

                @if (filled($statusColor))
                    <span
                        class="fi-rail-tenant-status"
                        style="--fi-rail-status: var(--{{ e($statusColor) }}-500)"
                        aria-hidden="true"
                    ></span>
                @endif
            </a>
        @endforeach

        @if ($registerLink)
            @php
                $registerLabel = $plainText($registerLink->getLabel());
            @endphp

            <a
                href="{{ $registerLink->getUrl() }}"
                aria-label="{{ $registerLabel }}"
                class="fi-rail-tenant fi-rail-tenant-register"
                x-tooltip="{ content: @js($registerLabel), placement: 'right', theme: $store.theme }"
            >
                {{ generate_icon_html($registerLink->getIcon() ?? Heroicon::Plus, size: IconSize::Medium) }}
            </a>
        @endif
    </nav>

    <nav class="fi-rail-items">
        @foreach ($tenantLinks as $link)
            @php
                $linkLabel = $plainText($link->getLabel());
                $isActive = url()->current() === $link->getUrl();
            @endphp

            <a
                href="{{ $link->getUrl() }}"
                @if ($link->shouldOpenUrlInNewTab()) target="_blank" rel="noopener noreferrer" @endif
                aria-label="{{ $linkLabel }}"
                @class(['fi-rail-item', 'fi-active' => $isActive])
                x-tooltip="{ content: @js($linkLabel), placement: 'right', theme: $store.theme }"
            >
                {{ generate_icon_html($link->getIcon(), size: IconSize::Large) }}
            </a>
        @endforeach

        @foreach ($items as $item)
            @php
                $label = $plainText($item->getLabel());
                $isActive = $item->isActive();
            @endphp

            <a
                href="{{ $item->getUrl() }}"
                @if ($item->shouldOpenUrlInNewTab()) target="_blank" rel="noopener noreferrer" @endif
                aria-label="{{ $label }}"
                @class(['fi-rail-item', 'fi-active' => $isActive])
                x-tooltip="{ content: @js($label), placement: 'right', theme: $store.theme }"
            >
                {{ generate_icon_html(($isActive && $item->getActiveIcon()) ? $item->getActiveIcon() : $item->getIcon(), size: IconSize::Large) }}

                @if (filled($item->getBadge()))
                    <span class="fi-rail-item-dot" aria-hidden="true"></span>
                @endif
            </a>
        @endforeach

        @if ($hasNotifications)
            <button
                type="button"
                aria-label="{{ $notificationsLabel }}"
                class="fi-rail-item fi-rail-notifications"
                x-on:click="$dispatch('open-modal', { id: 'database-notifications' })"
                x-tooltip="{ content: @js($notificationsLabel), placement: 'right', theme: $store.theme }"
            >
                {{ generate_icon_html(Heroicon::OutlinedBell, alias: \Filament\View\PanelsIconAlias::TOPBAR_OPEN_DATABASE_NOTIFICATIONS_BUTTON, size: IconSize::Large) }}

                @if ($unreadNotificationsCount)
                    <span class="fi-rail-item-dot" aria-hidden="true"></span>
                @endif
            </button>
        @endif
    </nav>
</aside>
