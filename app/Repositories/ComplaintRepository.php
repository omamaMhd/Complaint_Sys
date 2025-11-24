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

    public function findByReference(string $ref): ?Complaint
    {
        return Complaint::where('reference_number', $ref)->first();
    }

    public function update(Complaint $c, array $data): Complaint
    {
        $c->update($data);
        return $c->fresh();
    }

   public function listByCitizen(int $citizenId)
{
    return Complaint::where('citizen_id', $citizenId)
        ->orderBy('created_at','desc')
        ->get();
}

}
