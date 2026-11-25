<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpSeason extends Model
{
    protected $fillable = ['number', 'roman', 'starts_at', 'ends_at', 'closed_at'];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function stats()
    {
        return $this->hasMany(ExpSeasonStat::class, 'season_id');
    }

    public static function roman(int $n): string
    {
        $map = [1000 => 'M', 900 => 'CM', 500 => 'D', 400 => 'CD', 100 => 'C', 90 => 'XC', 50 => 'L', 40 => 'XL', 10 => 'X', 9 => 'IX', 5 => 'V', 4 => 'IV', 1 => 'I'];
        $out = '';

        foreach ($map as $value => $symbol) {
            while ($n >= $value) {
                $out .= $symbol;
                $n -= $value;
            }
        }

        return $out ?: 'I';
    }
}
