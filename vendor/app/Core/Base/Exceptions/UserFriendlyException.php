<?php

namespace App\Core\Base\Exceptions;

use Illuminate\Http\Request;

class UserFriendlyException extends \Exception
{

    protected $back = false;

   public function __construct($message, \Exception $previous = null)
   {
       parent::__construct($message, null, $previous);
   }

   public function render(Request $request)
   {
       if ($request->expectsJson()) {
           return response()->json([ 'message' => $this->getMessage() ], 400);
       }
       if ($this->back) return back()->withErrors($this->getMessage())->withInput();
       return "<strong style='color:red'>".$this->getMessage()."</strong>";
   }

   public function back($value)
   {
       $this->back = $value;
       return $this;
   }
}