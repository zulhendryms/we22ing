<?php
namespace App\Core\Security\Traits;

use Illuminate\Support\Facades\DB;

trait PostMessageScript 
{
     /**
     * @param string $data
     * @return string
     */
    public function postMessageScript($data)
    {
        return "<script type='text/javascript'>setTimeout(function(){window.postMessage('".$data."')},100)</script>";
    }

     /**
     * @param string $data
     * @return string
     */
    public function postActionMessage($action)
    {
        $action = '_act:'.$action;
        return $this->postMessageScript($action);
    }
}