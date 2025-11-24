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
}
