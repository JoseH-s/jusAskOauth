<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TokenCnj extends Model
{
    protected $table = 'token_cnj';

    protected $fillable = ['token', 'tenant'];
}
