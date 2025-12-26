<?php

namespace App\Exports;

use App\Models\Hotel;
use App\Models\Room;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class HotelExcelExport implements WithMultipleSheets
{
    protected array $hotelIds;

    public function __construct(array $hotelIds)
    {
        $this->hotelIds = $hotelIds;
    }

    public function sheets(): array
    {
        return [
            'Отели' => new HotelSheetExport($this->hotelIds),
            'Комнаты' => new RoomSheetExport($this->hotelIds),
        ];
    }
}
