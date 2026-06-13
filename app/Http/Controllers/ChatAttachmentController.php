<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChatAttachmentController extends Controller
{
    public function __invoke(Request $request, ChatMessage $chatMessage, Media $media): StreamedResponse
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        abort_if(! $user instanceof \App\Models\User, 403);

        // Only sender or recipient can download the attachment
        abort_unless(
            (int) $chatMessage->sender_id === (int) $user->id ||
            (int) $chatMessage->recipient_id === (int) $user->id ||
            $user->hasRole('Super Admin'),
            403
        );

        abort_unless((int) $media->model_id === (int) $chatMessage->id && $media->model_type === ChatMessage::class, 404);

        return response()->streamDownload(function () use ($media): void {
            $stream = fopen($media->getPath(), 'rb');
            if ($stream !== false) {
                fpassthru($stream);
                fclose($stream);
            }
        }, $media->file_name, [
            'Content-Type'   => $media->mime_type,
            'Content-Length' => $media->size,
        ]);
    }
}
