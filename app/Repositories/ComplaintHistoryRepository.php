<?php

namespace App\Repositories;

use App\Models\ComplaintHistory;

class ComplaintHistoryRepository
{
    public function create(array $data): ComplaintHistory
    {
        return ComplaintHistory::create($data);
    }

    public function forComplaint(int $complaintId)
    {
        return ComplaintHistory::where('complaint_id', $complaintId)
            ->orderBy('created_at', 'desc')
            ->get();
    }
    public function getNotesForCitizenComplaint(int $complaintId)
{
    return ComplaintHistory:: where('complaint_id', $complaintId)
        ->where('action', 'note_added')
        ->orderBy('created_at', 'asc')
        ->get()
        ->map(function ($row) {
            return [
                'note' => $row->data['note'] ?? null,
                'by' => $row->performed_by_name ?? 'Unknown',
                'created_at' => $row->created_at->toDateTimeString(),
            ];
        });
}

}
