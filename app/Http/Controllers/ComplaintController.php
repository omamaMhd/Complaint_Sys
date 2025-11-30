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
        ]);

        $data = [
            'citizen_id' => auth()->id(), // ✅ الجهة المقدمة للشكوى
            'type' => $validated['type'],
            'location' => $validated['location'] ?? null,
            'description' => $validated['description'],
            'responsible_party' => $validated['responsible_party'],
        ];
        

        $complaint = $this->service->createComplaint($data);

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


    // public function changeStatus(Request $request, $id)
    // {
    //     $request->validate(['status' => 'required|in:new,in_progress,completed,rejected']);

    //     $complaint = $this->service->changeStatus($id, $request->status, auth('admin')->id());

    //     return response()->json([
    //         'message' => 'Status updated',
    //         'complaint' => $complaint
    //     ]);
    // }

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
     $uploadedBy = auth('sanctum')->id();
     // auth('citizen')->id(); // أو auth('sanctum')->id() للموظف/أدمن

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
public function track($reference)
{
    $complaint = Complaint::where('reference_number', $reference)
        ->select('id', 'type', 'location', 'status', 'created_at')
        ->first();

    if (!$complaint) {
        return response()->json([
            'message' => 'Complaint not found'
        ], 404);
    }

    return response()->json([
        'reference' => $reference,
        'status' => $complaint->status,
        'type' => $complaint->type,
        'location' => $complaint->location,
        'submitted_at' => $complaint->created_at->toDateTimeString()
    ]);
}

}
