<?php
namespace App\Repositories\Eloquent;

use App\Repositories\Contracts\CitizenRepositoryInterface;
use App\Models\Citizen;
use App\Models\User;
class CitizenRepository implements CitizenRepositoryInterface
{
    public function create(array $data)
    {
        return Citizen::create($data);
    }

    public function findByMobile(string $mobile)
    {
        return Citizen::where('mobile', $mobile)->first();
    }
 public function findByMobile1(string $mobile)
    {
        return User::where('mobile', $mobile)->first();
    }


    public function save($citizen)
    {
        return $citizen->save();
    }

    public function findById(int $id)
    {
    return Citizen::withTrashed()->find($id);
    }




}
