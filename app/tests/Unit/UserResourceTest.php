<?php

namespace Tests\Unit;

use App\Filament\Resources\UserResource;
use App\Models\Organization;
use App\Models\User;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserResourceTest extends TestCase
{
    use RefreshDatabase;

    protected $superAdmin;
    protected $org;
    protected $orgUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->org = Organization::factory()->create();

        $this->superAdmin = User::factory()->create([
            'is_super_admin' => true,
        ]);

        $this->orgUser = User::factory()->create([
            'organization_id' => $this->org->id,
            'is_super_admin' => false,
        ]);
    }

    /** ────────────── Stubs for Filament v3 ────────────── */
    protected function makeHasFormsStub()
    {
        return new class implements \Filament\Forms\Contracts\HasForms {
            use \Filament\Forms\Concerns\InteractsWithForms;

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

    /** ────────────── Tests ────────────── */

    public function test_navigation_visibility()
    {
        $this->actingAs($this->superAdmin);
        $this->assertTrue(UserResource::shouldRegisterNavigation());

        $this->actingAs($this->orgUser);
        $this->assertTrue(UserResource::shouldRegisterNavigation());

        auth()->logout();
        $this->assertFalse(UserResource::shouldRegisterNavigation());
    }

    public function test_table_contains_expected_columns()
    {
        $table = UserResource::table(new Table($this->makeHasTableStub()));
        $columns = collect($table->getColumns())->map->getName();

        $this->assertContains('id', $columns);
        $this->assertContains('name', $columns);
        $this->assertContains('email', $columns);
        $this->assertContains('organization.name', $columns);
        $this->assertContains('is_super_admin', $columns);
        $this->assertContains('created_at', $columns);
    }

    public function test_query_is_filtered_for_non_super_admin()
    {
        $userFromSameOrg = User::factory()->create(['organization_id' => $this->org->id]);
        $userFromOtherOrg = User::factory()->create(['organization_id' => Organization::factory()->create()->id]);

        $this->actingAs($this->orgUser);
        $users = UserResource::getEloquentQuery()->get();

        $this->assertTrue($users->contains($userFromSameOrg));
        $this->assertFalse($users->contains($userFromOtherOrg));
    }

    public function test_super_admin_sees_all_users()
    {
        $otherOrgUser = User::factory()->create(['organization_id' => Organization::factory()->create()->id]);

        $this->actingAs($this->superAdmin);
        $users = UserResource::getEloquentQuery()->get();

        $this->assertTrue($users->contains($otherOrgUser));
    }

    public function test_permissions()
    {
        $this->actingAs($this->superAdmin);
        $this->assertTrue(UserResource::canViewAny());
        $this->assertTrue(UserResource::canCreate());
        $this->assertTrue(UserResource::canEdit($this->orgUser));
        $this->assertTrue(UserResource::canDelete($this->orgUser));

        $this->actingAs($this->orgUser);
        $this->assertTrue(UserResource::canViewAny());
        $this->assertFalse(UserResource::canCreate());
        $this->assertFalse(UserResource::canEdit($this->superAdmin));
        $this->assertFalse(UserResource::canDelete($this->superAdmin));
    }
}
