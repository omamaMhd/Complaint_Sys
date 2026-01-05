<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ComplaintService;
use App\Repositories\ComplaintRepository;
use App\Models\Complaint;
use Illuminate\Support\Facades\Auth;

class ComplaintController extends Controller
{
     protected $service;

    public function __construct(ComplaintService $service)
    {
        $this->service = $service;

        $this->middleware(function ($request, $next) {
            $user = Auth::user();
            if (!$user->hasRole('admin') && !$user->hasRole('employee')) {
           return response()->json(['message' => 'Unauthorized. Admin or Employee access only.'], 403);}        

           return $next($request);
        }) ->except(['store', 'track', 'myComplaints','addAttachment','myComplaintNotes']);}

//تقديم شكوى 
public function store(Request $request)
{
    $validated = $request->validate([
        'type' => 'required|string|max:100',
        'location' => 'nullable|string|max:255',
        'responsible_party' => 'nullable|string|max:255',
        'description' => 'required|string|min:5',
        'attachments' => 'nullable|array|max:5',
        'attachments.*' => 'file|mimes:jpeg,png,jpg,pdf|max:5120'
    ]);

    $citizenId = auth()->id();

    $complaint = $this->service->createComplaint(
        $validated,
        $request->file('attachments', []),
        $citizenId
    );

    // 🔥 سطر واحد مهم للاختبار
    \Log::info('Complaint stored', [
        'complaint_id' => $complaint->id,
        'server_id' =>  config('app.server_id'),
    ]);

    return response()->json([
        'message' => 'Complaint created successfully',
        'reference' => $complaint->reference_number,
        'id' => $complaint->id,
        'server_id' => config('app.server_id'), // اختياري
    ], 201);
}

    // public function store(Request $request)
    // {
    //     $validated = $request->validate([
    //         'type' => 'required|string|max:100',
    //         'location' => 'nullable|string|max:255',
    //         'responsible_party'=>'nullable|string|max:255',
    //         'description' => 'required|string|min:5',
    //          'attachments' => 'nullable|array|max:5',
    //          'attachments.*' => 'file|mimes:jpeg,png,jpg,pdf|max:5120'
    //     ]);


    //      $citizenId = auth()->id();

    //     $complaint = $this->service->createComplaint(
    //         $validated,
    //         $request->file('attachments', []),
    //         $citizenId
    //     );
        

    //     return response()->json([
    //         'message' => 'Complaint created successfully',
    //         'reference' => $complaint->reference_number,
    //         'id' => $complaint->id
    //     ], 201);
    // }
//عرض كل مواطن الشكاوي التي قدمها 
   public function myComplaints()
{
    $citizenId = auth()->id();
    $list = $this->service->listUserComplaints($citizenId);
    return response()->json($list);
}
//عرض ملاحظات وطلبات معلومات اضافية على شكوى معينة من قبل المواطن
public function myComplaintNotes($complaintId)
{
    $citizenId = auth()->id();

    $notes = $this->service->getCitizenComplaintNotes($complaintId, $citizenId);

    return response()->json([
    
        'data' => $notes
    ]);
}


//     public function Change_Status(Request $request, $id)
//     {
//         $request->validate(['status' => 'required|in:new,in_progress,completed,rejected']);
// $byUser = auth()->id();
//         $complaint = $this->service->changeStatus($id, $request->status, auth()->id(),$byUser);

//         return response()->json([
//             'message' => 'Status updated',
//             'complaint' => $complaint
//         ]);
//     }
      //  $complaint = $this->service->changeStatus($id, $request->status, auth()->id());

public function Change_Status(Request $request, int $id)
{
    $request->validate([
        'status' => 'required|in:new,in_progress,completed,rejected',
    ]);

    $byUser = auth()->id();

    if (!$byUser) {
        return response()->json(['message' => 'Unauthorized'], 401);
    }

    $updatedComplaint = $this->service->changeStatus(
        $id,
        $request->status,
        $byUser
    );

    return response()->json([
        'message' => 'Status updated successfully',
        'complaint' => $updatedComplaint
    ]);
}



    public function history($id)
    {
        $history = $this->service->getHistory($id);
        return response()->json($history);
    }
// اضافة مرفقات للشكوى 
    public function addAttachment(Request $request, $id)
{
    $request->validate([
        'file' => 'required|file|mimes:jpeg,png,jpg,pdf|max:2048', 
    ]);
     $uploadedBy = auth('sanctum')->id() ;
     //auth('citizen')->id(); // أو auth('sanctum')->id() للموظف/أدمن

    if (!$uploadedBy) {
        return response()->json(['message' => 'Unauthenticated'], 401);
    }
    

    $attachment = $this->service->addAttachment(
        $id,
        $request->file('file'),
        auth()->id()
    );

    return response()->json([
        'message' => 'Attachment uploaded successfully',
        'attachment' => $attachment
    ], 201);
}

public function track($reference)
{
    $complaint = $this->service->trackComplaint($reference);

    if (!$complaint) {
        return response()->json(['message' => 'Complaint not found'], 404);
    }
 return response()->json([$complaint

    ]);
}
//عرض كل شكاوي جهة معينة
public function Department_Complaints()
{
    $department = auth()->user()->responsible_party;

    $list = $this->service->getDepartmentComplaints($department);

    return response()->json($list);

}
// اضافة ملاحظة على الشكوى وطلب معلومات اضافية من المواطن
public function Add_Note(Request $request, $id)
{
    $request->validate([
        'note' => 'required|string|min:3'
    ]);

    try {
        $note = $this->service->addNote(
            $id,
            $request->note,
        );

        return response()->json([
            'message' => 'Note added successfully',
            'note' => $note
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'message' => $e->getMessage()
        ], $e instanceof \Illuminate\Http\Exceptions\HttpResponseException ? $e->getResponse()->status() : 500);
    }
}

}



