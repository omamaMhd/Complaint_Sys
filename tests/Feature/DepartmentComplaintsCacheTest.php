<?php
namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Services\ComplaintService;
use App\Repositories\ComplaintRepository;
use App\Repositories\ComplaintHistoryRepository;
use App\Repositories\AttachmentRepository;
use App\Models\Citizen;
use App\Models\Complaint;

class DepartmentComplaintsCacheTest extends TestCase
{
    protected ComplaintService $service;
    protected $repo;

    protected function setUp(): void
    {
        parent::setUp();

        // تفريغ الكاش والـ query log قبل كل تيست
        Cache::flush();
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->repo = app(ComplaintRepository::class);
        $this->service = new ComplaintService(
            $this->repo,
            app(ComplaintHistoryRepository::class),
            app(AttachmentRepository::class)
        );
    }



public function test_department_complaints_cache_hits_and_clears_with_duration()
{
    $department = 'وزارة الداخلية';
    $cacheKey = "department_complaints_{$department}";

    // إنشاء مواطن وشكوى أولية للقسم
    $citizen = Citizen::create([
        'username' => 'test_user',
        'mobile' => '0999999999',
        'password' => bcrypt('password')
    ]);

    $complaint1 = Complaint::create([
        'citizen_id' => $citizen->id,
        'type' => 'service',
        'description' => 'Initial complaint',
        'status' => 'new',
        'responsible_party' => $department,
        'reference_number' => 'REF001'
    ]);

    // --- أول طلب --- يجب أن يملأ الكاش
    $startFirst = microtime(true);
    $first = $this->service->getDepartmentComplaints($department);
    $durationFirst = microtime(true) - $startFirst;
    $queriesAfterFirst = count(DB::getQueryLog());
    $this->assertTrue(Cache::has($cacheKey), "Cache should exist after first fetch");
    $this->assertGreaterThan(0, $queriesAfterFirst, "Database should be queried first time");

    // --- ثاني طلب --- يجب أن يأتي من الكاش
    DB::flushQueryLog();
    $startSecond = microtime(true);
    $second = $this->service->getDepartmentComplaints($department);
    $durationSecond = microtime(true) - $startSecond;
    $queriesAfterSecond = count(DB::getQueryLog());
    $this->assertEquals($first, $second, "Data should be identical from cache");
    $this->assertEquals(0, $queriesAfterSecond, "Database should NOT be queried second time (cache hit)");

    // --- إضافة شكوى جديدة للقسم --- يجب أن يمسح الكاش
    $complaint2 = Complaint::create([
        'citizen_id' => $citizen->id,
        'type' => 'service',
        'description' => 'New complaint',
        'status' => 'new',
        'responsible_party' => $department,
        'reference_number' => 'REF002'
    ]);

    // كسر الكاش يدويًا كما يفعل Service
    Cache::forget($cacheKey);
    $this->assertFalse(Cache::has($cacheKey), "Cache should be cleared after new complaint");

    // --- طلب جديد بعد إضافة الشكوى الجديدة --- يجب أن يستعلم من DB
    DB::flushQueryLog();
    $startThird = microtime(true);
    $third = $this->service->getDepartmentComplaints($department);
    $durationThird = microtime(true) - $startThird;
    $queriesAfterThird = count(DB::getQueryLog());

    $this->assertTrue($queriesAfterThird > 0, "Database should be queried again after cache was cleared");
    $this->assertTrue($durationThird > $durationSecond, "Request after cache clear should take longer than cache hit");

    // --- طباعة النتائج ---
    echo "First request duration (from DB): {$durationFirst}s, Queries: {$queriesAfterFirst}\n";
    echo "Second request duration (from Cache): {$durationSecond}s, Queries: {$queriesAfterSecond}\n";
    echo "Third request duration (after new complaint, from DB): {$durationThird}s, Queries: {$queriesAfterThird}\n";
}
}