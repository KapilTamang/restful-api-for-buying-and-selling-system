<?php 

namespace App\Traits;

trait adminActions {

    public function before($user, $ability) 
    {
        if($user->isAdmin()) 
        {
            return true;
        }
    }
}