<?php

namespace Filafly\Rail\Tests;

use Filafly\Rail\Tests\Fixtures\Team;
use Illuminate\Support\Str;

class RailTest extends TestCase
{
    public function test_the_rail_lists_the_users_tenants_with_the_current_one_marked(): void
    {
        $this->signInWithTeams(['acme' => 'Acme Corp', 'globex' => 'Globex']);
        Team::query()->create(['name' => 'Initech', 'slug' => 'initech']);

        $this->get('/admin/acme')
            ->assertOk()
            ->assertSeeInOrder([
                'fi-rail fi-rail-has-tenants',
                'fi-rail-logo',
                'fi-rail-tenants',
                'href="http://localhost/admin/acme"', 'fi-rail-tenant fi-active', 'AC',
                'href="http://localhost/admin/globex"', 'fi-rail-tenant', '>G<',
            ], escape: false)
            ->assertDontSee('Initech');
    }

    public function test_tenant_statuses_and_the_tint_come_from_closures(): void
    {
        $this->rail()
            ->tenantStatus(fn (Team $tenant): ?string => $tenant->status === 'active' ? 'success' : null)
            ->color(fn (?Team $tenant): ?string => $tenant?->slug === 'acme' ? '#f97316' : null);

        $this->signInWithTeams(['acme' => 'Acme Corp']);

        $this->get('/admin/acme')
            ->assertOk()
            ->assertSee('fi-rail-tinted', escape: false)
            ->assertSee('--fi-rail-tint: #f97316', escape: false)
            ->assertSee('--fi-rail-status: var(--success-500)', escape: false);
    }

    public function test_visible_items_and_the_notifications_bell_sit_at_the_bottom(): void
    {
        $this->signInWithTeams(['acme' => 'Acme Corp']);

        $this->get('/admin/acme')
            ->assertOk()
            ->assertSeeInOrder([
                'fi-rail-items',
                'href="https://filamentphp.com/docs"', 'target="_blank"', 'Documentation',
                'fi-rail-notifications',
            ], escape: false)
            ->assertDontSee('Hidden link');
    }

    public function test_the_notifications_bell_shows_an_unread_dot(): void
    {
        $user = $this->signInWithTeams(['acme' => 'Acme Corp']);

        $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'test',
            'data' => [],
        ]);

        $this->get('/admin/acme')
            ->assertOk()
            ->assertSeeInOrder(['fi-rail-notifications', 'fi-rail-item-dot'], escape: false);
    }

    public function test_the_notifications_bell_can_be_turned_off(): void
    {
        $this->rail()->notifications(false);
        $this->signInWithTeams(['acme' => 'Acme Corp']);

        $this->get('/admin/acme')
            ->assertOk()
            ->assertDontSee('fi-rail-notifications', escape: false);
    }

    public function test_items_are_repeated_in_the_sidebar_for_small_screens(): void
    {
        $this->signInWithTeams(['acme' => 'Acme Corp']);

        $this->get('/admin/acme')
            ->assertOk()
            ->assertSeeInOrder(['fi-sidebar-nav', 'fi-rail-sidebar-items', 'Documentation'], escape: false);
    }

    public function test_tenants_can_stay_in_the_sidebar_tenant_menu(): void
    {
        $this->rail()->tenantMenuInSidebar();
        $this->signInWithTeams(['acme' => 'Acme Corp']);

        $this->get('/admin/acme')
            ->assertOk()
            ->assertSee('fi-rail-tenants', escape: false)
            ->assertDontSee('fi-rail-has-tenants', escape: false);
    }

    public function test_tenants_can_be_left_out_of_the_rail(): void
    {
        $this->rail()->tenants(false);
        $this->signInWithTeams(['acme' => 'Acme Corp']);

        $this->get('/admin/acme')
            ->assertOk()
            ->assertDontSee('fi-rail-has-tenants', escape: false)
            ->assertDontSee('fi-rail-tenant fi-active', escape: false);
    }

    public function test_the_logo_tile_falls_back_to_the_brand_initial_and_takes_a_custom_logo(): void
    {
        $this->signInWithTeams(['acme' => 'Acme Corp']);

        $this->get('/admin/acme')
            ->assertOk()
            ->assertSeeInOrder(['fi-rail-logo-tile', 'fi-rail-logo-initial'], escape: false);

        $this->rail()->logo('/images/logo.svg');

        $this->get('/admin/acme')
            ->assertOk()
            ->assertSeeInOrder(['fi-rail-logo-tile', 'src="/images/logo.svg"'], escape: false);
    }

    public function test_the_brand_stays_beside_the_rail_by_default(): void
    {
        $this->signInWithTeams(['acme' => 'Acme Corp']);

        $this->get('/admin/acme')
            ->assertOk()
            ->assertDontSee('fi-rail-hides-brand', escape: false)
            ->assertDontSee('fi-rail-current-tenant', escape: false);
    }

    public function test_the_brand_can_be_hidden(): void
    {
        $this->rail()->sidebarBrand(false);
        $this->signInWithTeams(['acme' => 'Acme Corp']);

        $this->get('/admin/acme')
            ->assertOk()
            ->assertSee('fi-rail-hides-brand', escape: false)
            ->assertDontSee('fi-rail-current-tenant', escape: false);
    }

    public function test_the_current_tenant_can_take_the_brands_place(): void
    {
        $this->rail()->sidebarTenant();
        $this->signInWithTeams(['acme' => 'Acme Corp', 'globex' => 'Globex']);

        $this->get('/admin/globex')
            ->assertOk()
            ->assertSee('fi-rail-hides-brand', escape: false)
            ->assertSeeInOrder([
                'fi-sidebar-header-logo-ctn',
                'href="http://localhost/admin/globex"', 'fi-rail-current-tenant', 'fi-rail-current-tenant-name', 'Globex',
            ], escape: false);
    }

    public function test_the_rail_is_left_off_auth_pages_by_default(): void
    {
        $this->get('/admin/login')
            ->assertOk()
            ->assertDontSee('fi-rail-logo', escape: false);
    }

    public function test_the_rail_can_be_shown_on_auth_pages(): void
    {
        $this->rail()->onAuthPages();

        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('fi-rail-logo', escape: false)
            ->assertDontSee('fi-rail-has-tenants', escape: false);
    }
}
