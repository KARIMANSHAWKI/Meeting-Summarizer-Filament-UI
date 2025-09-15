<?php

namespace Tests\Unit;

use App\Filament\Resources\OrganizationResource;
use App\Models\Organization;
use App\Models\User;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationResourceTest extends TestCase
{
    use RefreshDatabase;

    protected $superAdmin;
    protected $org;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->create([
            'is_super_admin' => true,
        ]);

        $this->org = Organization::factory()->create();

        $this->user = User::factory()->create([
            'organization_id' => $this->org->id,
            'is_super_admin' => false,
        ]);
    }

    /** ✅ Helper: minimal HasForms stub */
    protected function makeHasFormsStub()
    {
        return new class implements \Filament\Forms\Contracts\HasForms {
            use \Filament\Forms\Concerns\InteractsWithForms;

            public function makeFilamentTranslatableContentDriver(): \Filament\Support\Contracts\TranslatableContentDriver
            {
                // Return a dummy driver that won't be used in tests
                return new class implements \Filament\Support\Contracts\TranslatableContentDriver {
                    public function getDefaultLocale(): string { return 'en'; }
                    public function getLocales(): array { return ['en']; }
                    public function translate($content, string $locale = null) { return $content; }
                };
            }
        };
    }

    /** ✅ Helper: minimal HasTable stub */
    protected function makeHasTableStub()
    {
        return new class implements \Filament\Tables\Contracts\HasTable {
            use \Filament\Tables\Concerns\InteractsWithTable;

            public function makeFilamentTranslatableContentDriver(): \Filament\Support\Contracts\TranslatableContentDriver
            {
                return new class implements \Filament\Support\Contracts\TranslatableContentDriver {
                    public function getDefaultLocale(): string { return 'en'; }
                    public function getLocales(): array { return ['en']; }
                    public function translate($content, string $locale = null) { return $content; }
                };
            }
        };
    }

    public function test_navigation_only_registers_for_super_admin()
    {
        $this->actingAs($this->superAdmin);
        $this->assertTrue(OrganizationResource::shouldRegisterNavigation());

        $this->actingAs($this->user);
        $this->assertFalse(OrganizationResource::shouldRegisterNavigation());

        auth()->logout();
        $this->assertFalse(OrganizationResource::shouldRegisterNavigation());
    }

    public function test_form_contains_name_and_slug_fields()
    {
        $form = OrganizationResource::form(new Form($this->makeHasFormsStub()));
        $schema = collect($form->getComponents())->map->getName();

        $this->assertContains('name', $schema);
        $this->assertContains('slug', $schema);
    }

    public function test_table_contains_expected_columns()
    {
        $table = OrganizationResource::table(new Table($this->makeHasTableStub()));
        $columns = collect($table->getColumns())->map->getName();

        $this->assertContains('id', $columns);
        $this->assertContains('name', $columns);
        $this->assertContains('slug', $columns);
        $this->assertContains('created_at', $columns);
    }

    public function test_only_super_admin_can_view_create_delete()
    {
        $this->actingAs($this->superAdmin);
        $this->assertTrue(OrganizationResource::canViewAny());
        $this->assertTrue(OrganizationResource::canCreate());
        $this->assertTrue(OrganizationResource::canDelete($this->org));

        $this->actingAs($this->user);
        $this->assertFalse(OrganizationResource::canViewAny());
        $this->assertFalse(OrganizationResource::canCreate());
        $this->assertFalse(OrganizationResource::canDelete($this->org));
    }

    public function test_can_edit_for_super_admin_or_own_org()
    {
        $this->actingAs($this->superAdmin);
        $this->assertTrue(OrganizationResource::canEdit($this->org));

        $this->actingAs($this->user);
        $this->assertTrue(OrganizationResource::canEdit($this->org));

        $otherOrg = Organization::factory()->create();
        $this->assertFalse(OrganizationResource::canEdit($otherOrg));
    }
}
