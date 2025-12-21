<?php

namespace App\Repositories;

use App\Models\User;
use App\Models\Complaint;

class AdminRepository
{

public function create(array $data)
{
    return User::create($data);
}
public function getAllComplaints()
    {
        return Complaint::select([
            'id','type','location', 'responsible_party','description','status',
            'citizen_id','user_id','locked_by','locked_until','created_at'
        ])
        ->orderBy('created_at', 'desc')
        ->get();
    }
    public function getComplaintDetails($complaintId)
    {
        return Complaint::select('id', 'citizen_id', 'user_id', 'locked_by')
        ->with([
            'citizen:id,username,mobile', // فقط الحقول المطلوبة
            'handler:id,username,mobile,responsible_party', // بدل assignedUser
            'lockedBy:id,username'
        ])->findOrFail($complaintId);
    }
        public function findByMobile(string $mobile): ?User
    {
        return User::where('mobile', $mobile)->first();
    }

    public function findById($id)
    {
        return Complaint::with(['citizen', 'assignedUser', 'lockedBy'])
                        ->findOrFail($id);
    }

/**
     * الحصول على شكوى محددة بالتفاصيل
     */
    public function getComplaintById($id)
    {
        return Complaint::with([
            'citizen:id,username,mobile,created_at',
            'handler:id,username,mobile,responsible_party',
            'lockedBy:id,username',
            'attachments:id,complaint_id,path,original_name,created_at',
            'histories' => function ($query) {
                $query->orderBy('created_at', 'desc')
                      ->with(['complaint:id,reference_number']);
            }
        ])->findOrFail($id);
    }
/**
     * الحصول على إحصائيات الشكاوى
     */
    public function getComplaintStats()
    {
        return [
            'total' => Complaint::count(),
            'new' => Complaint::where('status', 'new')->count(),
            'in_progress' => Complaint::where('status', 'in_progress')->count(),
            'completed' => Complaint::where('status', 'completed')->count(),
            'rejected' => Complaint::where('status', 'rejected')->count(),
            'by_department' => Complaint::selectRaw('responsible_party, count(*) as count')
                ->groupBy('responsible_party')
                ->get()
                ->pluck('count', 'responsible_party')
        ];
    }}

 /**
     * البحث المتقدم في الشكاوى
     */
    /*
    public function searchComplaints(array $filters)
    {
        $query = Complaint::with([
            'citizen:id,username,mobile',
            'handler:id,username',
            'lockedBy:id,username'
        ]);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['responsible_party'])) {
            $query->where('responsible_party', $filters['responsible_party']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('reference_number', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('type', 'like', "%{$search}%")
                  ->orWhereHas('citizen', function ($q) use ($search) {
                      $q->where('username', 'like', "%{$search}%")
                        ->orWhere('mobile', 'like', "%{$search}%");
                          });
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate(20);
    }

}*/