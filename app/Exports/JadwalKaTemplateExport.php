<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class JadwalKaTemplateExport implements Export, WithMultipleSheets
{
    public function __construct(private readonly Collection $jpls) {}

    /**
     * @return array<int, JadwalKaTemplateSheetExport>
     */
    public function sheets(): array
    {
        $sheets = $this->jpls
            ->filter(fn ($jpl): bool => filled($jpl->kode_jpl))
            ->map(fn ($jpl): JadwalKaTemplateSheetExport => new JadwalKaTemplateSheetExport(
                (string) $jpl->kode_jpl
            ))
            ->values()
            ->all();

        return $sheets !== []
            ? $sheets
            : [new JadwalKaTemplateSheetExport('GANTI DENGAN KODE JPL')];
    }
}
