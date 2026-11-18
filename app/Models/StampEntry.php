<?php

namespace App\Models;

use Database\Factories\StampEntryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StampEntry extends Model
{
    /** @use HasFactory<StampEntryFactory> */
    use HasFactory;

    protected $fillable = ['user_id', 'stamps', 'reason', 'order_id'];
}
