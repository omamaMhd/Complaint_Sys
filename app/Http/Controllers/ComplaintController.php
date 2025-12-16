<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ComplaintService;
use App\Repositories\ComplaintRepository;
use App\Models\Complaint;

class ComplaintController extends Controller
{
     protected $service;

    public function __construct(ComplaintService $service)
    {
       // $this->middleware('auth:sanctum');
        $this->service = $service;
    }
//تقديم شكوى 
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|string|max:100',
            'location' => 'nullable|string|max:255',
            'responsible_party'=>'nullable|string|max:255',
            'description' => 'required|string|min:5',
             'attachments' => 'nullable|array|max:5',
             'attachments.*' => 'file|mimes:jpeg,png,jpg,pdf|max:5120'
            //'file' => 'required|file|mimes:jpeg,png,jpg,pdf|max:2048', 
        ]);

        // $data = [
        //     'citizen_id' => auth()->id(), // ✅ الجهة المقدمة للشكوى
        //     'type' => $validated['type'],
        //     'location' => $validated['location'] ?? null,
        //     'description' => $validated['description'],
        //     'responsible_party' => $validated['responsible_party'],
        // ];
         $citizenId = auth()->id();

        $complaint = $this->service->createComplaint(
            $validated,
            $request->file('attachments', []),
            $citizenId
        );
        

      //  $complaint = $this->service->createComplaint($data);

        return response()->json([
            'message' => 'Complaint created successfully',
            'reference' => $complaint->reference_number,
            'id' => $complaint->id
        ], 201);
    }
//عرض كل مواطن الشكاوي التي قدمها 
   public function myComplaints()
{
    $citizenId = auth()->id();
    $list = $this->service->listUserComplaints($citizenId);
    return response()->json($list);
}


    public function changeStatus(Request $request, $id)
    {
        $request->validate(['status' => 'required|in:new,in_progress,completed,rejected']);

        $complaint = $this->service->changeStatus($id, $request->status, auth()->user()->responsible_party, auth()->id());

        return response()->json([
            'message' => 'Status updated',
            'complaint' => $complaint
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
//متابعة حالة الشكوى حسب الرقم المرجعي للشكوى
// public function track($reference)
// {
//     $complaint = Complaint::where('reference_number', $reference)
//         ->select('id', 'type', 'location', 'status', 'created_at')
//         ->first();

//     if (!$complaint) {
//         return response()->json([
//             'message' => 'Complaint not found'
//         ], 404);
//     }

//     return response()->json([
//         'reference' => $reference,
//         'status' => $complaint->status,
//         'type' => $complaint->type,
//         'location' => $complaint->location,
//         'submitted_at' => $complaint->created_at->toDateTimeString()
//     ]);
// }
public function track($reference)
{
    $complaint = $this->service->trackComplaint($reference);

    if (!$complaint) {
        return response()->json(['message' => 'Complaint not found'], 404);
    }
 return response()->json([$complaint
    // return response()->json([
    //     'reference' => $reference,
    //     'status' => $complaint->status,
    //     'type' => $complaint->type,
    //     'location' => $complaint->location,
    //     'submitted_at' => $complaint->created_at->toDateTimeString(),
        // 'attachments' => $complaint->attachments->map(function ($a) {
        //     return [
        //         'id' => $a->id,
        //         'name' => $a->original_name,
        //         'url' => asset('storage/' . $a->path)
        //     ];
        // })
    ]);
}
//عرض كل شكاوي جهة معينة
public function departmentComplaints()
{
    $department = auth()->user()->responsible_party;

    $list = $this->service->getDepartmentComplaints($department);

    return response()->json($list);

}
// اضافة ملاحظة على الشكوى وطلب معلومات اضافية من المواطن
public function addNote(Request $request, $id)
{
    $request->validate([
        'note' => 'required|string|min:3'
    ]);

    try {
        $note = $this->service->addNote(
            $id,
            $request->note,
            auth()->user()->responsible_party,
            auth()->id()
        );

        return response()->json([
            'message' => 'Note added successfully',
            'note' => $note
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'message' => $e->getMessage()
        ], $e->getMessage() === "Unauthorized" ? 403 : 404);
    }
}

// public function requestInfo(Request $request, $id)
// {
//     $request->validate([
//         'message' => 'required|string|min:3'
//     ]);

//     try {
//         $info = $this->service->requestInfo(
//             $id,
//             $request->message,
//             auth()->user()->responsible_party,
//             auth()->id()
//         );

//         return response()->json([
//             'message' => 'Information request sent successfully',
//             'info' => $info
//         ]);

//     } catch (\Exception $e) {
//         return response()->json([
//             'message' => $e->getMessage()
//         ], $e->getMessage() === "Unauthorized" ? 403 : 404);
//     }
// }


}
