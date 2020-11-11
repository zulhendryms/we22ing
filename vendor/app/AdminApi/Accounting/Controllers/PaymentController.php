<?php

namespace App\AdminApi\Accounting\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

use App\Laravel\Http\Controllers\Controller;
use App\Core\Accounting\Resources\CashBankResource;

use App\Core\Accounting\Entities\CashBank;
use App\Core\Accounting\Entities\CashBankDetail;
use App\Core\Accounting\Entities\Account;
use App\Core\Internal\Entities\Status;
use App\Core\Master\Entities\Currency;
use App\Core\Security\Services\RoleModuleService;
use Validator;

class PaymentController extends Controller
{
    protected $roleService;
    
    public function __construct(
        RoleModuleService $roleService  
        )
    {
        $this->roleService = $roleService;
    }

    
}
            