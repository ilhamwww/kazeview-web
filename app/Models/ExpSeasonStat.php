<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpSeasonStat extends Model
{
    protected $fillable = ['season_id', 'user_id', 'points', 'level', 'rank'];

    public function season()
    {
        return $this->belongsTo(ExpSeason::class, 'season_id');
    }
}
