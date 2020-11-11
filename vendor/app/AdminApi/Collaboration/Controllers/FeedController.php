<?php

namespace App\AdminApi\Collaboration\Controllers;

use Validator;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Laravel\Http\Controllers\Controller;
use App\Core\Security\Services\RoleModuleService;
use App\Core\Base\Services\HttpService;
use App\Core\Internal\Services\AutoNumberService;
use App\Core\Pub\Entities\PublicPost;
use App\Core\Internal\Services\FileCloudService;
use App\Core\Pub\Entities\PublicPostLike;
use App\AdminApi\Development\Controllers\CRUDDevelopmentController;

class FeedController extends Controller
{
    protected $roleService;
    private $module;
    private $crudController;
    public function __construct(
        RoleModuleService $roleService
    ) {
        $this->roleService = $roleService;
        $this->module = 'pubpost';
        $this->crudController = new CRUDDevelopmentController();
    }

    public function config(Request $request)
    {
        try {
            return $this->crudController->config($this->module);
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function presearch(Request $request)
    {
        try {
            return $this->crudController->presearch($this->module);
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    private function generatePost($row, $description)
    {
        $user = Auth::user();
        $comments = [];
        foreach ($row->Comments as $comment) {
            $comments[] = [
                "Oid" => $comment->Oid,
                "Company" => $comment->Company,
                "PublicPost" => $comment->PublicPost,
                "User" => $comment->User,
                'UserObj' => $comment->UserObj->UserProfileObj(),
                "CreatedAt" => $comment->CreatedAt,
                "Message" => $comment->Message,
                "Image" => $comment->Image
            ];
        }
        $liked = PublicPostLike::where('PublicPost', $row->Oid)->where('CreatedBy', $user->Oid)->first();
        if ($row->ObjectType == 'PurchaseOrder') $url = "purchaseorder?item=" . $row->Oid . "&type=" . ($row->StatusObj->Code == 'posted' ? "PurchaseOrder" : "PurchaseRequest");
        elseif ($row->ObjectType == 'CashBank') $url = "cashbank?item=" . $row->Oid;
        else $url = $row->Url;
        if (in_array($row->ObjectType, ['PostVideo', 'PostImage', 'PostText'])) $data = [];
        else $data = [
            'Code' => $row->Code,
            'Date' => $row->Date ?: now(),
            'BusinessPartner' => $row->BusinessPartnerObj ? $row->BusinessPartnerObj->Name : null,
            'Account' => $row->AccountObj ? $row->AccountObj->Name : null,
            'TotalAmount' => $row->TotalAmount,
            'Status' => $row->StatusObj ? $row->StatusObj->Code : null,
            'Note' => $row->Note,
        ];
        $action = [
            'name' => 'Open',
            'icon' => 'ArrowUpRightIcon',
            'type' => 'open_view',
            'portalget' => "development/table/vueview?code=" . $row->ObjectType,
            'get' => strtolower($row->ObjectType) . "/" . $row->Oid,
        ];
        dd($row->CreatedByObj->UserProfileObj());
        return [
            'Oid' => $row->Oid,
            'User' => $row->CreatedByObj ? $row->CreatedByObj->Name : null,
            'UserObj' => $row->CreatedByObj->UserProfileObj(),
            'CreatedAt' => $row->CreatedAt ?: now(),
            'Description' => $description,
            'CountLikes' => $row->CountLike ?: 0,
            'CountComments' => $row->CountComment ?: 0,
            'Image' => $row->Image,
            'Action' => $action,
            'ActionUrl' => $url,
            'ObjectType' => $row->ObjectType,
            'Data' => $data,
            'Code' => $row->Code,
            'Date' => $row->Date ?: now(),
            'BusinessPartner' => $row->BusinessPartnerObj ? $row->BusinessPartnerObj->Name : null,
            'Account' => $row->AccountObj ? $row->AccountObj->Name : null,
            'TotalAmount' => $row->TotalAmount,
            'Status' => $row->StatusObj ? $row->StatusObj->Code : null,
            'Note' => $row->Note,
            'Comments' => $comments,
            'Liked' => isset($liked) ? true : false
        ];
    }

    public function postLike(Request $request)
    {
        $user = Auth::user();
        $post = PublicPost::findOrFail($request->input('PublicPost'));
        if (!$post) return null;

        $data = PublicPostLike::where('PublicPost', $post->Oid)->where('CreatedBy', $user->Oid)->first();
        if (!$data) $data = new PublicPostLike();
        $data->Company = $post->Company;
        $data->PublicPost = $post->Oid;
        $data->save();

        $post->CountLike = $post->Likes()->count();
        $post->save();
    }

    public function list(Request $request)
    {
        $user = Auth::user();
        $result = [];
        $count = 20;

        // $query = "SELECT n.PublicPost FROM notificationuser nu 
        //     LEFT OUTER JOIN user u ON u.Oid = nu.User 
        //     LEFT OUTER JOIN notification n ON n.Oid = nu.Notification
        //     WHERE nu.User = '{$user->Oid}'
        //     AND IFNULL(nu.DateRead,now()) IS NOT NULL 
        //     AND n.Type = 'Comment' AND n.PublicPost IS NOT NULL
        //     ORDER BY n.CreatedAt DESC LIMIT 10;";
        $query = "SELECT n.PublicPost FROM notification n
            LEFT OUTER JOIN user u ON u.Oid = n.User 
            WHERE n.User = '{$user->Oid}'
            AND IFNULL(n.DateRead,now()) IS NOT NULL 
            AND n.Type = 'Comment' AND n.PublicPost IS NOT NULL
            ORDER BY n.CreatedAt DESC LIMIT 10;";
        $tmp = DB::select($query); // AND nu.DateRead IS NULL 
        $tmp = collect($tmp)->pluck('PublicPost');
        $data = PublicPost::with('LastCommentedByObj', 'CreatedByObj', 'BusinessPartnerObj', 'StatusObj', 'AccountObj')->whereIn('Oid', $tmp)->get();
        foreach ($data as $row) {
            if ($row->LastCommentedByObj) $msg = $row->LastCommentedByObj->Name . ' write a comment at ' . $row->LastCommentedAt;
            else $msg = 'A new comment at ' . $row->LastCommentedAt;
            $result[] = $this->generatePost($row, $msg);
        }

        $query = "SELECT data.Oid FROM pubapproval data 
            LEFT OUTER JOIN pubpost p ON data.PublicPost = p.Oid
            LEFT OUTER JOIN sysstatus s On s.Oid = p.Status
            LEFT OUTER JOIN pubapproval prev ON p.Oid = prev.PublicPost AND prev.Sequence = data.Sequence - 1 AND prev.Action != 'Request'
            WHERE s.Code = 'submit'
            AND data.User = '{$user->Oid}' 
            AND data.ActionDate IS NOT NULL
            AND data.Type IS NOT NULL
            AND IFNULL(data.Action,'') != 'Request'
            AND CASE WHEN data.Sequence = 1 THEN TRUE ELSE TRUE END
            ORDER BY data.CreatedAt DESC LIMIT 10";
        //AND data.ActionDate IS NULL
        //AND CASE WHEN data.Sequence = 1 THEN TRUE ELSE prev.ActionDate IS NOT NULL END
        $tmp = DB::select($query);
        $tmp = collect($tmp)->pluck('Oid');
        $data = PublicPost::with('LastCommentedByObj', 'CreatedByObj', 'BusinessPartnerObj', 'StatusObj', 'AccountObj')->whereIn('Oid', $tmp)->get();
        foreach ($data as $row) {
            $result[] = $this->generatePost($row, 'This post is requires your approval');
        }

        $query = "SELECT data.Oid FROM pubpost data 
            WHERE data.ObjectType IN ('PostVideo','PostImage','PostText')
            ORDER BY data.CreatedAt DESC LIMIT 10";
        $tmp = DB::select($query);
        $tmp = collect($tmp)->pluck('Oid');
        $data = PublicPost::with('LastCommentedByObj', 'CreatedByObj', 'BusinessPartnerObj', 'StatusObj', 'AccountObj', 'Comments')->whereIn('Oid', $tmp)->get();
        foreach ($data as $row) {
            $result[] = $this->generatePost($row, $row->Description);
        }

        return $result;
    }

    public function save(Request $request, $Oid = null)
    {
        try {
            $data;
            DB::transaction(function () use ($request, &$data, $Oid) {
                $data = $this->crudController->saving($this->module, $request, $Oid, false);
                if (!isset($data->ObjectType)) $data->ObjectType = 'PostText';
                if (isset($data->Image)) $data->ObjectType = 'PostImage';
                elseif (isset($data->Url) && strpos($data->Url, 'youtube') > 0) $data->ObjectType = 'PostVideo';
                $data->save();
                if ($data->Code == '<<Auto>>') $data->Code = $this->autoNumberService->generate($data, 'pubpost');
                if (!$data) throw new \Exception('Data is failed to be saved');
            });
            $data = $this->generatePost($data, $data->Description);

            return response()->json(
                $data,
                Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }
}
