<?php
namespace App\Repositories\Contracts;

interface CitizenRepositoryInterface
{
    public function create(array $data);
    public function findByMobile(string $mobile);
    public function save($citizen);
}
