<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Attachment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

trait HandlesAttachments
{
    /**
     * Valide et stocke une pièce jointe rattachée polymorphiquement au modèle.
     * Images (jpg/png) ou PDF, 5 Mo max. Stockée sur le disque public.
     */
    protected function storeAttachment(Model $attachable, Request $request): Attachment
    {
        $request->validate([
            'file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $file = $request->file('file');
        $path = $file->store('attachments', 'public');

        /** @var Attachment $attachment */
        $attachment = $attachable->attachments()->create([
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'uploaded_by' => auth()->id(),
        ]);

        return $attachment;
    }
}
