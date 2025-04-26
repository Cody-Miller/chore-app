<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChoreLog extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'chore_id',
        'user_id',
        'completed_at'
    ];

    public array $dates = ['completed_at'];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    public function chore()
    {
        return $this->belongsTo(Chore::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
