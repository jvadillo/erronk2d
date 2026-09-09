<?php

namespace App\Models;

use Database\Factories\GoogleRegistrationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoogleRegistration extends Model
{
    /** @use HasFactory<GoogleRegistrationFactory> */
    use HasFactory;

    protected $fillable = ['name', 'email', 'google_id', 'status', 'reviewed_by', 'user_id'];

    protected $hidden = ['google_id'];
}
