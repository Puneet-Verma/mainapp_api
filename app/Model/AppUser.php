<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class AppUser extends Model
{
    protected $table = 'tbl_appUsers';

    public $primaryKey = 'rowId';

    public $timestamps = false;
}
