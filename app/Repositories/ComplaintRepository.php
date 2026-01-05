<?php
namespace App\Repositories;

use App\Models\Complaint;
use App\Models\User;
use App\Models\Citizen;
use Illuminate\Support\Facades\DB;
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
        ->with('attachments', 'histories')
        ->orderBy('created_at', 'desc')
        ->get();
}



// محاولة قفل الشكوى للمعالجة من قبل موظف معين
public function tryLock(int $complaintId, int $employeeId): bool
{
    $affected = DB::update(
        "UPDATE complaints
         SET locked_by = ?, locked_at = NOW()
         WHERE id = ?
           AND (
                locked_by IS NULL
                OR locked_at < NOW() - INTERVAL 2 MINUTE
           )",
        [$employeeId, $complaintId]
    );

    return $affected === 1;
}
//////////////////
public function unlock(int $complaintId, int $employeeId): void
{
    DB::update(
        "UPDATE complaints
         SET locked_by = NULL, locked_at = NULL
         WHERE id = ? AND locked_by = ?",
        [$complaintId, $employeeId]
    );
}

//تجربة القفل
// public function tryLock(int $complaintId, int $employeeId): bool
// {
//     $ttl = 120; // مدة القفل بالثواني (دقيقتين)

//     $affected = DB::update(
//         "UPDATE complaints
//          SET locked_by = ?, locked_at = NOW()
//          WHERE id = ?
//            AND (
//                  locked_by IS NULL
//                  OR locked_at < DATE_SUB(NOW(), INTERVAL ? SECOND)
//             )",
//         [$employeeId, $complaintId, $ttl]
//     );

//     return $affected === 1;
// }

public function getCitizenNameById(int $citizenId): string
{
    // $citizen = $this->model->find($citizenId);
    // return $citizen ? $citizen->name : 'Unknown';
    return Citizen::where('id', $citizenId)->value('username');
}

public function findNameById(int $id): ?string
    {
        return User::where('id', $id)->value('username');
        // أو value('name') حسب عمودك
    }
    public function getDepartmentByUserId(int $id): string
{
     return User::where('id', $id)
        ->value('responsible_party');
}


}
