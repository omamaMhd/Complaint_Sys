<?php

namespace App\Repositories;

use App\Models\ComplaintAttachment;

class AttachmentRepository
{
    public function create(array $data): ComplaintAttachment
    {
        return ComplaintAttachment::create($data);
    }

    public function find(int $id): ?ComplaintAttachment
    {
        return ComplaintAttachment::find($id);
    }

    public function listByComplaint(int $complaintId)
    {
        return ComplaintAttachment::where('complaint_id', $complaintId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function delete(int $id): bool
    {
        $attachment = ComplaintAttachment::find($id);

        if (!$attachment) {
            return false;
        }

        return $attachment->delete();
    }
}
