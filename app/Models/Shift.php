<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shift extends Model
{
    use HasFactory;

    protected $table = 'shifts';

    protected $fillable = [
        'nama_shift',
        'jam_mulai',
        'jam_selesai',
    ];

    public function dinas(): HasMany
    {
        return $this->hasMany(Dinas::class, 'shift_id');
    }
}