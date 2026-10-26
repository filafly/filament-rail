<?php

namespace Filafly\Rail;

use Closure;
use Filament\Actions\Action;
use Filament\Auth\Pages\EditProfile;
use Filament\Contracts\Plugin;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationItem;
use Filament\Pages\SimplePage;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Filament\View\PanelsRenderHook;
use Illuminate\Database\Eloquent\Model;

class RailPlugin implements Plugin
{
    use EvaluatesClosures;

    protected bool $isVisibleOnAuthPages = false;

    protected bool $hasTenants = true;

    protected bool $hasTenantMenuInSidebar = false;

    protected bool $hasNotifications = true;

    protected bool $hasBrand = true;

    protected bool $hasCurrentTenant = false;

    /**
     * @var array<NavigationItem> | Closure
     */
    protected array|Closure $items = [];

    protected string|Closure|null $logo = null;

    protected string|Closure|null $color = null;

    protected ?Closure $tenantStatus = null;

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }

    public function getId(): string
    {
        return 'filafly-rail';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->renderHook(
                PanelsRenderHook::BODY_START,
                fn (array $scopes) => $this->shouldRender($scopes)
                    ? view('filafly-rail::rail', ['rail' => $this])
                    : null,
            )
            // The rail is desktop only, so its links also join the sidebar,
            // where they show below the desktop breakpoint.
            ->renderHook(
                PanelsRenderHook::SIDEBAR_NAV_END,
                fn () => view('filafly-rail::sidebar-items', ['rail' => $this]),
            )
            ->renderHook(
                PanelsRenderHook::SIDEBAR_LOGO_AFTER,
                fn () => $this->hasCurrentTenant()
                    ? view('filafly-rail::current-tenant', ['rail' => $this, 'isInSidebar' => true])
                    : null,
            )
            ->renderHook(
                PanelsRenderHook::TOPBAR_LOGO_AFTER,
                fn () => $this->hasCurrentTenant()
                    ? view('filafly-rail::current-tenant', ['rail' => $this, 'isInSidebar' => false])
                    : null,
            );
    }

    public function boot(Panel $panel): void {}

    /**
     * Show the rail on auth pages too (login, registration, password reset,
     * email verification and a simple profile page). Hidden by default, since
     * those pages have no sidebar for the rail to sit beside.
     */
    public function onAuthPages(bool $condition = true): static
    {
        $this->isVisibleOnAuthPages = $condition;

        return $this;
    }

    /**
     * List the user's tenants in the rail when the panel has tenancy. On by
     * default; the sidebar's tenant menu then only shows below desktop.
     */
    public function tenants(bool $condition = true): static
    {
        $this->hasTenants = $condition;

        return $this;
    }

    /**
     * Keep Filament's tenant menu in the sidebar on desktop as well.
     */
    public function tenantMenuInSidebar(bool $condition = true): static
    {
        $this->hasTenantMenuInSidebar = $condition;

        return $this;
    }

    /**
     * A status dot on each tenant tile. The closure receives the tenant as
     * `$tenant` and returns a panel color name (`success`, `warning`,
     * `danger`, `gray`, ...) or null for no dot.
     */
    public function tenantStatus(?Closure $callback): static
    {
        $this->tenantStatus = $callback;

        return $this;
    }

    /**
     * Keep the panel's brand (logo or name) in the sidebar header, and in the
     * topbar when the panel has one. On by default; turn it off to let the
     * rail's logo tile carry the app's identity on desktop. Below desktop the
     * rail is hidden, so the brand always shows there.
     */
    public function sidebarBrand(bool $condition = true): static
    {
        $this->hasBrand = $condition;

        return $this;
    }

    /**
     * Show the current tenant (avatar and name) where the brand sits, on
     * desktop. It takes the brand's place, so the brand is hidden there.
     */
    public function sidebarTenant(bool $condition = true): static
    {
        $this->hasCurrentTenant = $condition;

        return $this;
    }

    /**
     * Show the database notifications bell when the panel has them.
     */
    public function notifications(bool $condition = true): static
    {
        $this->hasNotifications = $condition;

        return $this;
    }

    /**
     * Links pinned to the bottom of the rail, above the notifications bell.
     *
     * @param  array<NavigationItem> | Closure  $items
     */
    public function items(array|Closure $items): static
    {
        $this->items = $items;

        return $this;
    }

    /**
     * The image in the rail's logo tile. Defaults to the panel favicon, then
     * to the brand name's initial.
     */
    public function logo(string|Closure|null $url): static
    {
        $this->logo = $url;

        return $this;
    }

    /**
     * Tint the rail with a color (any CSS color). The closure receives the
     * current tenant as `$tenant`, so each tenant can carry its brand.
     */
    public function color(string|Closure|null $color): static
    {
        $this->color = $color;

        return $this;
    }

    /**
     * @return array<NavigationItem>
     */
    public function getItems(): array
    {
        return array_values(array_filter(
            $this->evaluate($this->items),
            fn (NavigationItem $item): bool => $item->isVisible(),
        ));
    }

    public function getLogo(): ?string
    {
        return $this->evaluate($this->logo) ?? Filament::getFavicon();
    }

    public function getColor(): ?string
    {
        $tenant = Filament::getTenant();

        return $this->evaluate($this->color, ['tenant' => $tenant], $tenant ? [Model::class => $tenant, $tenant::class => $tenant] : []);
    }

    public function hasNotifications(): bool
    {
        return $this->hasNotifications
            && Filament::auth()->check()
            && Filament::hasDatabaseNotifications();
    }

    public function hasTenants(): bool
    {
        return $this->hasTenants
            && Filament::hasTenancy()
            && Filament::auth()->check()
            && filled(Filament::getTenant());
    }

    public function hasCurrentTenant(): bool
    {
        return $this->hasCurrentTenant && $this->hasTenants();
    }

    /**
     * Whether the brand beside the rail is hidden on desktop, either turned
     * off or replaced by the current tenant.
     */
    public function hidesBrand(): bool
    {
        return (! $this->hasBrand) || $this->hasCurrentTenant();
    }

    public function hasTenantMenuInSidebar(): bool
    {
        return $this->hasTenantMenuInSidebar;
    }

    /**
     * @return array<Model>
     */
    public function getTenants(): array
    {
        return Filament::getUserTenants(Filament::auth()->user());
    }

    public function getTenantStatusColor(Model $tenant): ?string
    {
        return $this->evaluate($this->tenantStatus, ['tenant' => $tenant], [Model::class => $tenant, $tenant::class => $tenant]);
    }

    /**
     * Filament's tenant menu items that are plain links, split into the
     * "register a tenant" link (shown as a tile after the tenants) and the
     * rest (profile, billing and custom items, shown at the bottom). Items
     * that run an action instead of linking somewhere are left out.
     *
     * @return array{register: ?Action, links: array<Action>}
     */
    public function getTenantMenuLinks(): array
    {
        $items = collect(Filament::getCurrentPanel()?->getTenantMenuItems() ?? [])
            ->filter(fn (Action $item): bool => filled($item->getUrl()));

        return [
            'register' => $items->first(fn (Action $item): bool => $item->getName() === 'register'),
            'links' => $items->reject(fn (Action $item): bool => $item->getName() === 'register')->values()->all(),
        ];
    }

    /**
     * @param  array<string>  $scopes
     */
    protected function shouldRender(array $scopes): bool
    {
        if ($this->isVisibleOnAuthPages) {
            return true;
        }

        foreach ($scopes as $scope) {
            if ($this->isAuthPage($scope)) {
                return false;
            }
        }

        return true;
    }

    protected function isAuthPage(string $page): bool
    {
        if (is_a($page, SimplePage::class, allow_string: true)) {
            return true;
        }

        return is_a($page, EditProfile::class, allow_string: true) && $page::isSimple();
    }
}
