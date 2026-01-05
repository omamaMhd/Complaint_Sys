<?php

namespace App\Repositories;

use App\Models\User;
use App\Models\Complaint;
use App\Models\Citizen;

class AdminRepository
{

public function create(array $data)
{
    return User::create($data);
}
public function getAllComplaints()
    {
    return Complaint::with('handler:id,username,responsible_party') // تجيب id + username + الجهة
        ->select([
            'id','type','location', 'responsible_party','description','status',
            'citizen_id','user_id','created_at'
        ])
        ->orderBy('created_at', 'desc')
        ->get();
}
public function getComplaintDetails(int $complaintId)
{
    return Complaint::with([
        'citizen:id,username,mobile,created_at,updated_at,deleted_at',
        'handler:id,username,mobile,responsible_party',
        'lockedBy:id,username',
        'attachments:id,complaint_id,path,original_name,created_at,updated_at',
        'histories' => function($query) {
            $query->orderBy('created_at', 'asc')
                  ->with(['performedBy:id,username']);
        }
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
