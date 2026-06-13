<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Correspondant;
use App\Models\Courrier;
use App\Models\Mention;
use App\Models\User;
use App\Notifications\CommentPostedNotification;
use App\Notifications\MentionNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CommentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function makeCourrier(User $user): Courrier
    {
        $correspondant = Correspondant::query()->create(['nom_structure' => 'Test Org']);

        return Courrier::query()->create([
            'type'                   => 'Entrant',
            'reference'              => 'REF-COMMENT-001',
            'date_reception_envoi'   => now()->toDateString(),
            'objet'                  => 'Test commentaire',
            'priorite'               => 'Normale',
            'statut'                 => 'Nouveau',
            'niveau_confidentialite' => 'Standard',
            'correspondant_id'       => $correspondant->id,
            'user_id'                => $user->id,
        ]);
    }

    public function test_comment_can_be_posted_on_a_courrier(): void
    {
        $author  = User::factory()->create(['name' => 'Alice']);
        $courrier = $this->makeCourrier($author);

        $comment = $courrier->comments()->create([
            'user_id'     => $author->id,
            'body'        => 'Ceci est un commentaire de test.',
            'is_internal' => false,
        ]);

        $this->assertDatabaseHas('comments', [
            'id'               => $comment->id,
            'commentable_type' => Courrier::class,
            'commentable_id'   => $courrier->id,
            'user_id'          => $author->id,
        ]);
    }

    public function test_at_mention_creates_mention_record_and_sends_notification(): void
    {
        Notification::fake();

        $author   = User::factory()->create(['name' => 'Alice']);
        $mentioned = User::factory()->create(['name' => 'Bob']);
        $courrier  = $this->makeCourrier($author);

        // Creating the comment triggers the booted() hook which parses @Bob
        $comment = Comment::query()->create([
            'commentable_type' => Courrier::class,
            'commentable_id'   => $courrier->id,
            'user_id'          => $author->id,
            'body'             => 'Bonjour @Bob, merci de traiter ce dossier.',
            'is_internal'      => false,
        ]);

        $this->assertDatabaseHas('mentions', [
            'comment_id'        => $comment->id,
            'mentioned_user_id' => $mentioned->id,
        ]);

        Notification::assertSentTo($mentioned, MentionNotification::class);
    }

    public function test_no_mention_notification_when_no_at_sign(): void
    {
        Notification::fake();

        $author  = User::factory()->create(['name' => 'Alice']);
        $courrier = $this->makeCourrier($author);

        Comment::query()->create([
            'commentable_type' => Courrier::class,
            'commentable_id'   => $courrier->id,
            'user_id'          => $author->id,
            'body'             => 'Simple commentaire sans mention.',
            'is_internal'      => false,
        ]);

        Notification::assertNothingSent();
        $this->assertDatabaseCount('mentions', 0);
    }

    public function test_internal_comment_flag_is_persisted(): void
    {
        $author  = User::factory()->create(['name' => 'Alice']);
        $courrier = $this->makeCourrier($author);

        $comment = $courrier->comments()->create([
            'user_id'     => $author->id,
            'body'        => 'Note interne.',
            'is_internal' => true,
        ]);

        $this->assertTrue((bool) $comment->fresh()->is_internal);
    }

    public function test_parse_mentioned_users_returns_matching_users(): void
    {
        User::factory()->create(['name' => 'Alice']);
        $bob    = User::factory()->create(['name' => 'Bob']);
        $author = User::factory()->create(['name' => 'Charlie']);

        $comment = new Comment(['body' => 'Salut @Bob et @Unknown, comment ça va ?']);

        $mentioned = $comment->parseMentionedUsers();

        $this->assertCount(1, $mentioned);
        $this->assertEquals($bob->id, $mentioned->first()->id);
    }

    public function test_annotation_fields_are_persisted_on_comment(): void
    {
        $author   = User::factory()->create(['name' => 'Alice']);
        $courrier = $this->makeCourrier($author);

        $comment = $courrier->comments()->create([
            'user_id' => $author->id,
            'body' => 'Annotation sur une zone précise.',
            'kind' => Comment::KIND_ANNOTATION,
            'annotation_data' => [
                'page' => 2,
                'selection' => 'Bloc signature',
            ],
            'is_internal' => false,
        ]);

        $this->assertSame(Comment::KIND_ANNOTATION, $comment->fresh()->kind);
        $this->assertSame(2, (int) data_get($comment->fresh(), 'annotation_data.page'));
        $this->assertSame('Bloc signature', data_get($comment->fresh(), 'annotation_data.selection'));
    }

    public function test_new_comment_notifies_courrier_initiator(): void
    {
        Notification::fake();

        $owner  = User::factory()->create(['name' => 'Owner']);
        $author = User::factory()->create(['name' => 'Alice']);

        $courrier = $this->makeCourrier($owner);

        $courrier->comments()->create([
            'user_id' => $author->id,
            'body' => 'Nouveau commentaire sans mention.',
            'kind' => Comment::KIND_COMMENT,
            'is_internal' => false,
        ]);

        Notification::assertSentTo($owner, CommentPostedNotification::class);
    }

    public function test_mentioned_user_only_receives_mention_notification(): void
    {
        Notification::fake();

        $owner     = User::factory()->create(['name' => 'Owner']);
        $mentioned = User::factory()->create(['name' => 'Bob']);
        $author    = User::factory()->create(['name' => 'Alice']);

        $courrier = $this->makeCourrier($owner);

        $courrier->comments()->create([
            'user_id' => $author->id,
            'body' => 'Bonjour @Bob, peux-tu valider ?',
            'kind' => Comment::KIND_COMMENT,
            'is_internal' => false,
        ]);

        Notification::assertSentTo($mentioned, MentionNotification::class);
        Notification::assertNotSentTo($mentioned, CommentPostedNotification::class);
    }
}
