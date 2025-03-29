<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{


    protected $connection = "mongodb";
    protected $collection = 'settings';


    protected $fillable = [
        'title',
        'key',
        'value',
    ];
}
