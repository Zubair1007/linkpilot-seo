<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RoleBasedAccessControlTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $specialist;
    protected Project $adminProject;
    protected Project $specialistProject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin_test@linkpilot.io',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->specialist = User::create([
            'name' => 'SEO Specialist User',
            'email' => 'specialist_test@linkpilot.io',
            'password' => bcrypt('password'),
            'role' => 'seo_specialist',
            'status' => 'active',
        ]);

        $this->adminProject = Project::create([
            'user_id' => $this->admin->id,
            'name' => 'Admin Owned Project',
            'slug' => 'admin-project',
            'target_domain' => 'adminportal.com',
        ]);

        $this->specialistProject = Project::create([
            'user_id' => $this->specialist->id,
            'name' => 'Specialist Assigned Project',
            'slug' => 'specialist-project',
            'target_domain' => 'clientdomain.com',
        ]);
    }

    public function test_admin_can_access_users_management(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/v1/admin/users');
        $response->assertStatus(200);
    }

    public function test_seo_specialist_is_denied_from_users_management(): void
    {
        Sanctum::actingAs($this->specialist);

        $response = $this->getJson('/api/v1/admin/users');
        $response->assertStatus(403)
            ->assertJson(['message' => 'Admin authorization required.']);
    }

    public function test_admin_can_create_and_delete_users(): void
    {
        Sanctum::actingAs($this->admin);

        // 1. Create user
        $createRes = $this->postJson('/api/v1/admin/users', [
            'name' => 'Junior Analyst',
            'email' => 'junior@linkpilot.io',
            'password' => 'secret12345',
            'role' => 'seo_specialist',
        ]);
        $createRes->assertStatus(201);
        $userId = $createRes->json('id');

        // 2. Delete user
        $deleteRes = $this->deleteJson("/api/v1/admin/users/{$userId}");
        $deleteRes->assertStatus(200);
    }

    public function test_seo_specialist_is_denied_from_creating_or_deleting_users(): void
    {
        Sanctum::actingAs($this->specialist);

        $createRes = $this->postJson('/api/v1/admin/users', [
            'name' => 'Hacker Account',
            'email' => 'hacker@linkpilot.io',
            'password' => 'secret12345',
            'role' => 'admin',
        ]);
        $createRes->assertStatus(403);

        $deleteRes = $this->deleteJson("/api/v1/admin/users/{$this->admin->id}");
        $deleteRes->assertStatus(403);
    }

    public function test_admin_can_access_system_health(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/v1/admin/health');
        $response->assertStatus(200);
    }

    public function test_seo_specialist_is_denied_from_system_health(): void
    {
        Sanctum::actingAs($this->specialist);

        $response = $this->getJson('/api/v1/admin/health');
        $response->assertStatus(403);
    }

    public function test_admin_can_manage_api_keys(): void
    {
        Sanctum::actingAs($this->admin);

        $createRes = $this->postJson('/api/v1/auth/tokens', [
            'name' => 'Admin External Token',
        ]);
        $createRes->assertStatus(201);

        $listRes = $this->getJson('/api/v1/auth/tokens');
        $listRes->assertStatus(200);
    }

    public function test_seo_specialist_is_denied_from_managing_or_viewing_api_keys(): void
    {
        Sanctum::actingAs($this->specialist);

        $createRes = $this->postJson('/api/v1/auth/tokens', [
            'name' => 'Unauthorized Token',
        ]);
        $createRes->assertStatus(403);

        $listRes = $this->getJson('/api/v1/auth/tokens');
        $listRes->assertStatus(403);
    }

    public function test_seo_specialist_is_denied_from_search_engine_credentials_management(): void
    {
        Sanctum::actingAs($this->specialist);

        $connectRes = $this->postJson('/api/v1/search-engines/connect', [
            'provider' => 'indexnow',
            'property_url' => 'https://example.com',
            'api_key' => 'fake_secret_key',
        ]);
        $connectRes->assertStatus(403);

        $listRes = $this->getJson('/api/v1/search-engines/properties');
        $listRes->assertStatus(403);
    }

    public function test_seo_specialist_has_full_access_to_operational_seo_features(): void
    {
        Sanctum::actingAs($this->specialist);

        // 1. Dashboard
        $dashRes = $this->getJson('/api/v1/dashboard');
        $dashRes->assertStatus(200);

        // 2. Backlinks
        $backlinksRes = $this->getJson('/api/v1/backlinks');
        $backlinksRes->assertStatus(200);

        // 3. Campaigns
        $campRes = $this->getJson('/api/v1/campaigns');
        $campRes->assertStatus(200);

        // 4. Reports
        $repRes = $this->getJson('/api/v1/reports');
        $repRes->assertStatus(200);
    }

    public function test_project_segmentation_enforces_specialist_isolation(): void
    {
        // Specialist only sees projects owned by or assigned to them
        Sanctum::actingAs($this->specialist);
        $res = $this->getJson('/api/v1/projects');
        $res->assertStatus(200);
        $projectIds = collect($res->json('data'))->pluck('id')->all();

        $this->assertContains($this->specialistProject->id, $projectIds);
        $this->assertNotContains($this->adminProject->id, $projectIds);

        // Admin sees all projects across the platform
        Sanctum::actingAs($this->admin);
        $adminRes = $this->getJson('/api/v1/projects');
        $adminRes->assertStatus(200);
        $adminProjectIds = collect($adminRes->json('data'))->pluck('id')->all();

        $this->assertContains($this->specialistProject->id, $adminProjectIds);
        $this->assertContains($this->adminProject->id, $adminProjectIds);
    }
}
