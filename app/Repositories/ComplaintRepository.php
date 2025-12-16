<?php
namespace App\Repositories;

use App\Models\Complaint;

class ComplaintRepository
{
    public function create(array $data): Complaint
    {
        return Complaint::create($data);
    }

    public function find(int $id): ?Complaint
    {
        return Complaint::find($id);
    }
    public function update(Complaint $complaint, string $status): Complaint
    {
        $complaint->status = $status;
        $complaint->save();

        return $complaint->fresh();
    }

    // public function findByReference(string $ref): ?Complaint
    // {
    //     return Complaint::where('reference_number', $ref)->first();
    // }

    // public function update(Complaint $c, array $data): Complaint
    // {
    //     $c->update($data);
    //     return $c->fresh();
    // }
//عرض كل شكاوي مواطن معين
   public function listByCitizen(int $citizenId)
{
    return Complaint::where('citizen_id', $citizenId) ->with('attachments','histories')
        ->orderBy('created_at','desc')
        ->get();
}
//عرض الشكوى حسب الرقم المرجعي مع المرفقات
public function findByReferenceWithAttachments(string $ref)
{
    return Complaint::where('reference_number', $ref)->with('attachments', 'histories')

        ->first();
}
//عرض كل شكاوي جهة معينة
public function listForDepartment(string $department)
{
    return Complaint::where('responsible_party', $department)
        ->with('attachments')
        ->orderBy('created_at', 'desc')
        ->get();
}



}
