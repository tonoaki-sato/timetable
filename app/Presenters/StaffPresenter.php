<?php

namespace App\Presenters;

use App\Models\Staff;

class StaffPresenter
{
    public function __construct(
        protected Staff $staff
    ) {}

    /**
     * 専用マイシフトURLを取得
     */
    public function myShiftUrl(): string
    {
        return route('staff.myshift', ['token' => $this->staff->access_token]);
    }
}
