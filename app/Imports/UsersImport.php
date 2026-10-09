<?php

namespace App\Imports;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class UsersImport implements ToCollection, WithHeadingRow
{
    /**
     * Menyimpan hasil pembacaan Excel.
     */
    public array $rows = [];

    /**
     * Membaca seluruh baris Excel.
     */
    public function collection(Collection $collection): void
    {
        foreach ($collection as $row) {
            $this->rows[] = [
                'nip' => trim((string) ($row['nip'] ?? '')),
                'name' => trim((string) ($row['nama'] ?? '')),
                'email' => trim((string) ($row['email'] ?? '')),
                'phone' => trim((string) ($row['no_hp'] ?? '')),
                'role' => strtolower(trim((string) ($row['role'] ?? ''))),
                'status' => strtolower(trim((string) ($row['status'] ?? ''))),
            ];
        }
    }
}