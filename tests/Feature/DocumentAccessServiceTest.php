<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentAccessRule;
use App\Models\User;
use App\Services\DocumentAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DocumentAccessServiceTest extends TestCase
{
    use RefreshDatabase;

    private DocumentAccessService $service;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(DocumentAccessService::class);
        $this->user    = User::factory()->create();

        // Ensure permissions exist
        Permission::findOrCreate('ged.documents.view',   'web');
        Permission::findOrCreate('ged.documents.update', 'web');
        Permission::findOrCreate('ged.documents.delete', 'web');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private static int $seq = 0;

    private function makeDocument(array $overrides = []): Document
    {
        self::$seq++;
        return Document::create(array_merge([
            'reference_doc'         => 'ACL-' . self::$seq,
            'titre'                 => 'ACL test doc ' . self::$seq,
            'type_document'         => 'Document',
            'etat_cycle_vie'        => 'Brouillon',
            'auteur_id'             => $this->user->id,
            'confidentiality_level' => 'Standard',
        ], $overrides));
    }

    private function addRule(Document $doc, User $user, array $flags): DocumentAccessRule
    {
        return DocumentAccessRule::create(array_merge([
            'document_id'  => $doc->id,
            'user_id'      => $user->id,
            'role_id'      => null,
            'can_view'     => false,
            'can_download' => false,
            'can_edit'     => false,
            'can_share'    => false,
        ], $flags));
    }

    // ─── canView() ────────────────────────────────────────────────────────────

    public function test_user_with_global_perm_can_view_doc_with_no_rules(): void
    {
        $this->user->givePermissionTo('ged.documents.view');
        $doc = $this->makeDocument();

        $this->assertTrue($this->service->canView($this->user, $doc));
    }

    public function test_user_without_perm_cannot_view_doc_with_no_rules(): void
    {
        $doc = $this->makeDocument(); // no rules, no perm

        $this->assertFalse($this->service->canView($this->user, $doc));
    }

    public function test_user_without_global_perm_but_with_explicit_rule_can_view(): void
    {
        $doc = $this->makeDocument();
        $this->addRule($doc, $this->user, ['can_view' => true]);

        $this->assertTrue($this->service->canView($this->user, $doc));
    }

    public function test_user_with_global_perm_blocked_when_doc_has_rules_they_dont_match(): void
    {
        $this->user->givePermissionTo('ged.documents.view');
        $doc = $this->makeDocument();

        $other = User::factory()->create();
        $this->addRule($doc, $other, ['can_view' => true]); // rule for someone else

        $this->assertFalse($this->service->canView($this->user, $doc));
    }

    public function test_user_with_global_perm_and_matching_rule_can_view(): void
    {
        $this->user->givePermissionTo('ged.documents.view');
        $doc = $this->makeDocument();

        $this->addRule($doc, $this->user, ['can_view' => true]);

        $this->assertTrue($this->service->canView($this->user, $doc));
    }

    public function test_role_based_rule_grants_view_access(): void
    {
        $doc  = $this->makeDocument();
        $role = Role::findOrCreate('gestionnaire', 'web');
        $this->user->assignRole($role);

        DocumentAccessRule::create([
            'document_id'  => $doc->id,
            'user_id'      => null,
            'role_id'      => $role->id,
            'can_view'     => true,
            'can_download' => false,
            'can_edit'     => false,
            'can_share'    => false,
        ]);

        $this->assertTrue($this->service->canView($this->user, $doc));
    }

    // ─── canEdit() ───────────────────────────────────────────────────────────

    public function test_can_edit_requires_global_perm_and_matching_edit_rule(): void
    {
        $this->user->givePermissionTo('ged.documents.update');
        $doc = $this->makeDocument();
        $this->addRule($doc, $this->user, ['can_edit' => true]);

        $this->assertTrue($this->service->canEdit($this->user, $doc));
    }

    public function test_can_edit_blocked_when_doc_has_edit_rules_user_not_in(): void
    {
        $this->user->givePermissionTo('ged.documents.update');
        $doc   = $this->makeDocument();
        $other = User::factory()->create();
        $this->addRule($doc, $other, ['can_edit' => true]); // only 'other' can edit

        $this->assertFalse($this->service->canEdit($this->user, $doc));
    }

    // ─── canDownload() ────────────────────────────────────────────────────────

    public function test_can_download_requires_view_perm_and_no_download_rule_restrictions(): void
    {
        $this->user->givePermissionTo('ged.documents.view');
        $doc = $this->makeDocument(); // no rules

        $this->assertTrue($this->service->canDownload($this->user, $doc));
    }

    public function test_can_download_explicit_rule_grants_without_global_perm(): void
    {
        $doc = $this->makeDocument();
        $this->addRule($doc, $this->user, ['can_download' => true]);

        $this->assertTrue($this->service->canDownload($this->user, $doc));
    }

    // ─── canDelete() ─────────────────────────────────────────────────────────

    public function test_can_delete_requires_global_delete_permission(): void
    {
        $doc = $this->makeDocument();

        $this->assertFalse($this->service->canDelete($this->user, $doc));

        $this->user->givePermissionTo('ged.documents.delete');
        $this->assertTrue($this->service->canDelete($this->user, $doc));
    }
}
