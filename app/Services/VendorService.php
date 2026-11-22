<?php

namespace App\Services;

use App\DTOs\VendorFilterDTO;
use App\Models\Vendor;
use App\Repositories\Contracts\VendorRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class VendorService
{
    public function __construct(
        protected VendorRepositoryInterface $vendorRepository
    ) {}

    /**
     * ページネーション一覧を取得
     */
    public function paginateVendors(VendorFilterDTO $filter): LengthAwarePaginator
    {
        return $this->vendorRepository->paginate($filter);
    }

    /**
     * 出展・出店者を登録
     *
     * @param  array<string, mixed>  $data
     */
    public function createVendor(array $data): Vendor
    {
        return $this->vendorRepository->create($data);
    }

    /**
     * 出展・出店者を更新
     *
     * @param  array<string, mixed>  $data
     */
    public function updateVendor(Vendor $vendor, array $data): Vendor
    {
        return $this->vendorRepository->update($vendor, $data);
    }

    /**
     * 出展・出店者を削除
     */
    public function deleteVendor(Vendor $vendor): bool
    {
        return $this->vendorRepository->delete($vendor);
    }
}
