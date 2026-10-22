<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Records that a chore/pill reminder went out to a user, so each one is only sent once.
 */
class SentReminder extends Model
{
    protected $fillable = [
        'remindable_type',
        'remindable_id',
        'user_id',
        'for_date',
        'scheduled_time',
    ];

    public function remindable()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
