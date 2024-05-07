<?php

namespace App\Http\repository;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class usersRepository
{
    public function findOneByMail($mail)
    {
       return  DB::table('users')->where('email','=',$mail)->first();
    }

}
