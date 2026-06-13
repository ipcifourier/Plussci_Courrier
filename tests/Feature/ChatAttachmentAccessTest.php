<?php

namespace Tests\Feature;

use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ChatAttachmentAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_sender_can_open_chat_attachment(): void
    {
        Storage::fake('public');

        $sender = User::factory()->create();
        $recipient = User::factory()->create();

        $message = ChatMessage::query()->create([
            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'body' => ChatMessage::ATTACHMENT_PLACEHOLDER,
        ]);

        $message
            ->addMediaFromString('%PDF-1.4 test payload')
            ->usingFileName('note.pdf')
            ->usingName('note')
            ->toMediaCollection('attachments');

        $attachment = $message->getFirstMedia('attachments');

        $response = $this
            ->actingAs($sender)
            ->get(route('chat.attachments.show', ['chatMessage' => $message, 'media' => $attachment]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_unrelated_user_cannot_open_chat_attachment(): void
    {
        Storage::fake('public');

        $sender = User::factory()->create();
        $recipient = User::factory()->create();
        $outsider = User::factory()->create();

        $message = ChatMessage::query()->create([
            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'body' => ChatMessage::ATTACHMENT_PLACEHOLDER,
        ]);

        $message
            ->addMediaFromString('spreadsheet payload')
            ->usingFileName('plan.xlsx')
            ->usingName('plan')
            ->toMediaCollection('attachments');

        $attachment = $message->getFirstMedia('attachments');

        $response = $this
            ->actingAs($outsider)
            ->get(route('chat.attachments.show', ['chatMessage' => $message, 'media' => $attachment]));

        $response->assertForbidden();
    }
}