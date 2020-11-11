<?php

namespace App\AdminApi\Chat\Controllers;

use App\Laravel\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Core\Security\Entities\User;
use App\Core\Chat\Entities\ChatRoom;
use App\Core\Chat\Entities\ChatMessage;
use App\Core\Chat\Entities\ChatRoomUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Validator;
use App\Core\Internal\Services\OneSignalService;
use App\Core\Internal\Services\SocketioService;
use App\Core\Internal\Services\FileCloudService;

class ChatController extends Controller 
{
    protected $OneSignalService;
    protected $SocketioService;
    protected $fileCloudService;
    public function __construct(OneSignalService $OneSignalService, SocketioService $SocketioService)
    {
        $this->SocketioService = $SocketioService;
        $this->OneSignalService= $OneSignalService;
        $this->fileCloudService = new FileCloudService();
    }
    public function history(Request $request)
    {
        $user = Auth::user();
        $data = null;
        if ($request->has('oid')) {
            $data = ChatRoom::findOrFail($request->input('oid'));
            foreach($data->Details as $row) $row = $user->returnUserObj($row,'User');
        } else {            
            $data = ChatRoom::with(['UserObj','UserAdminObj','Users','Users.UserObj'])
                ->whereHas('Users', function ($query) use ($user) {
                    $query->where('User', $user->Oid);
                });
            
            if ($user->BusinessPartner) $data = $data->where('Code', $user->BusinessPartner);
            if ($request->has('oid')) $data = $data->where('Oid', $request->input('oid'));
            if ($request->has('status')) $data = $data->whereIn('Status', $request->input('status'))->limit(50);
            // if ($request->has('user')) $data = $data->where('UserAdmin', $request->input('user'))->limit(50);
            // $data = $data->where(function($query) use ($user)
            // {
            //     $query->whereNull('UserAdmin')
            //     ->orWhere('UserAdmin',$user->Oid);
            // });
            $data = $data->orderBy('CreatedAt','Desc')->limit(50)->get();
            foreach($data as $row) {
                // $row = $user->returnUserObj($row,'User');
                $row = $user->returnUserObj($row,'UserAdmin');
                if ($row->RoomType == 'Support') {
                    $tmp = $row->UserObj->UserProfileObj();
                    unset($row->UserObj);
                    $row->UserObj = [
                        'Name' => $tmp->Name,
                        'Image' => $tmp->Image,
                        'Color' => $tmp->Color,
                    ];
                } elseif ($row->RoomType == 'Group') {
                    unset($row->UserObj);
                    $row->UserObj = [
                        'Name' => $row->Name,
                        'Image' => $row->Image,
                        'Color' => 'ff3300',
                    ];
                }
                foreach($row->Users as $u) {
                    $u = $user->returnUserObj($u,'User');
                    // $tmp = $u->UserObj ? $u->UserObj->UserProfileObj() : null;
                    
                    if ($row->RoomType == 'Private' && $u->User != $user->Oid) {
                        $tmp = $u->UserObj;
                        if ($tmp) {
                            unset($row->UserObj);
                            $row->UserObj = [
                                'Name' => $tmp->Name,
                                'Image' => $tmp->Image,
                                'Color' => $tmp->Color,
                            ];
                            // if ($row->Oid == '5cec98c9-0446-45de-9974-9f3ec2bf2681') dd($row);
                        }
                    }           
                }
            }
        }
        return response()->json(
            $data,
            Response::HTTP_OK
        );
    }
    private function showSub($Oid) {        
        $user = Auth::user();
        $data = ChatRoom::findOrFail($Oid);
        foreach($data->Details as $row) $row = $user->returnUserObj($row,'User');
        return $data;
    }

    public function roomSupportAccept(Request $request)
    {
        $user = Auth::user();
        $data = ChatRoom::with([
            'Details.UserObj' => function ($query) {$query->addSelect('Oid', 'UserName', 'Name','Image');},
            ])->with(['Details', 'Users'])->findOrFail($request->input('oid'));
        if ($data->UserAdmin) throw new \Exception('Ticket has been handled by others');
        if ($data->Status == 'CLOSE') throw new \Exception('Ticket has been closed');
        
        $msg = $user->Name.' has accepted your request and start to server you now ('.now().')';
        
        $data->UserAdmin = $user->Oid;
        $data->LastMessage = $msg;
        $data->LastUser = $user->Oid;
        $data->Status = 'ACTIVE';
        $data->save();

        $detailmsg = new ChatMessage();
        $detailmsg->User = $user->Oid;
        $detailmsg->Message = $msg;
        $detailmsg->DeviceId = '';
        $detailmsg->ChatRoom = $data->Oid;
        $detailmsg->MessageType = 'text';
        $detailmsg->save();
            
        //kirim ke user
        // if ($data->RoomType == 'Support') $this->sendNotification($user->Name.' chat', $request->Message, $data->Company, $data->User);

        DB::delete("DELETE FROM chatroomuser WHERE ChatRoom='{$data->Oid}' AND User NOT IN ('{$user->Oid}','{$data->User}')");
        // $chatRoomUser = ChatRoomUser::where('ChatRoom',$data->Oid)->get();
        // foreach($chatRoomUser as $row) {
        //     if ($row->User != $user->Oid && $row->User != $data->User) $row->delete();
        // }
        
        return response()->json(
            $data,
            Response::HTTP_OK
        );
    }

    public function roomPrivate(Request $request) {
        $userFrom = Auth::user();
        $userTo = User::findOrFail($request->input('user'));
        $device = $request->has('device') ? $request->input('device') : null;

        $data = DB::select("SELECT Oid FROM chatroom 
            WHERE (RoomType='Private' AND User='{$userFrom}' AND UserAdmin='{$userTo}' AND Status = 'Active') 
            OR (RoomType='Private' AND User='{$userTo}' AND UserAdmin='{$userFrom}' AND Status = 'Active')");
        if (!$data) $data = new ChatRoom();
        else {
            $data = ChatRoom::with([
                'Details.UserObj' => function ($query) {$query->addSelect('Oid', 'UserName', 'Name','Image');},
            ])->with(['Details', 'Users'])->findOrFail($data[0]->Oid)->first();
            if ($data) return $data;
        }

        $msg = $userFrom->UserName.' has requested a chat at '.now();
        $data->Company = $userFrom->Company;
        $data->LastMessage = $msg;
        $data->LastUser = $userFrom->Oid;
        $data->UserAdmin = $userFrom->Oid;
        $data->User = $userTo->Oid;
        $data->Status = 'Active';
        $data->DeviceId = $device;
        $data->RoomType = 'Private';
        $data->save();

        $detailmsg = new ChatMessage();
        $detailmsg->Company = $userFrom->Company;
        $detailmsg->User = $userFrom->Oid;
        $detailmsg->Message = $msg;
        $detailmsg->DeviceId = $device;
        $detailmsg->ChatRoom = $data->Oid;
        $detailmsg->MessageType = 'text';
        $detailmsg->save();
        
        $detailuser = new ChatRoomUser();
        $detailuser->Company = $userFrom->Company;
        $detailuser->User = $userFrom->Oid;
        $detailuser->ChatRoom = $data->Oid;
        $detailuser->save();
        
        $detailuser = new ChatRoomUser();
        $detailuser->Company = $userTo->Company;
        $detailuser->User = $userTo->Oid;
        $detailuser->ChatRoom = $data->Oid;
        $detailuser->save();

        // $this->sendNotification('New Chat Request', $msg, $userTo->Oid, $data);
        return $data;
    }

    public function users(Request $request) {    
        $user = Auth::user();
        $data = User::whereNull('GCRecord')
            ->where('Oid','!=',$user->Oid)
            ->addSelect('Oid','UserName','Name')
            ->get();
        $result = [];
        foreach($data as $row) $result[] = $row->UserProfileObj();
        return $result;
    }

    private function sendNotification($title, $message, $to, $room, $datamsg = null) {    
        $user = Auth::user();
        $tmp = $user->UserProfileObj();        
        $userObj = [
            'Oid'=>$room->Oid,
            'Name'=> $room->RoomType == 'Group' ? $room->Name : $tmp->Name,
            'Image'=> $room->RoomType == 'Group' ? $room->Image : $tmp->Image,
            'Color'=> $room->RoomType == 'Group' ? 'ff3300' : $tmp->Color,
        ];
        $datamsg->UserObj = $userObj;
        $param = [
            "Company"=> $room->Company,
            "Code"=> $user->UserName,
            "Date"=> now()->addHours(company_timezone())->toDateTimeString(),
            "Title"=> $title,
            "Message"=> $message,
            "Type"=> 'Chat',
            "Icon"=> 'IconBox',
            "Color"=> '#000000',
            "Room"=> $room->Oid,
            "Detail"=> $datamsg,
        ];
        // dd($company.'_'.$to);    
        $this->OneSignalService->sendNotification($title, $message, $to, 'administrator');
        $this->SocketioService->sendNotificationChat($param, $room->Company.'_'.$to);
    }

    private function sendNotificationTest($title, $message, $to, $room) {    
        // $user = Auth::user();
        $param = [
            "Company"=> "8b38ce18-882a-11ea-b45b-1a582ceaab05",
            "Code"=> $to,
            "Date"=> now(),
            "Title"=> $title,
            "Message"=> $message,
            "Type"=> 'Chat',
            "Icon"=> 'IconBox',
            "Color"=> '#000000',
            "Room"=> $room,
            "Detail"=> null,
        ];
        // dd($company.'_'.$to);    
        if ($message=='onesignal' || $message == 'semua') $this->OneSignalService->sendNotification($title, $message, "8b38ce18-882a-11ea-b45b-1a582ceaab05_".$to, 'administrator');
        if ($message=='nodejs' || $message == 'semua') $this->SocketioService->sendNotificationChat("8b38ce18-882a-11ea-b45b-1a582ceaab05_".$to, $param);
    }

    public function sendMessageTest(Request $request) {
        // $user = Auth::user();
        $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))))   ; //WILLIAM ZEF
        $this->sendNotificationTest('new chat', $request->Message, "da337d12-83bc-4d11-b271-a8a7f92c9845", $request->ChatRoom);
            // {
            //     "ChatRoom": "fb667994-ab99-4168-9e51-8a2ff16adc49",
            //     "Message": "s",
            //     "Image": null,
            //     "Device": "fb667994-ab99-4168-9e51-8a2ff16adc49"
            // }
        return [
            "User" => "da337d12-83bc-4d11-b271-a8a7f92c9845",
            "Message" => $request->Message,
            "DeviceId" => $request->Device,
            "ChatRoom" => $request->ChatRoom,
            "MessageType" => "text",
            "Company" => "8b38ce18-882a-11ea-b45b-1a582ceaab05",
            "CreatedBy" => "da337d12-83bc-4d11-b271-a8a7f92c9845",
            "UpdatedBy" => "da337d12-83bc-4d11-b271-a8a7f92c9845",
            "Oid" => "3b3838f1-eab4-4fad-b102-1288a00a7d45",
            "CreatedAt" => "2020-05-21 09 =>15 =>50",
            "UpdatedAt" => "2020-05-21 09 =>15 =>50",
            "UpdatedAtUTC" => "2020-05-21 02 =>15 =>50",
            "CreatedAtUTC" => "2020-05-21 02 =>15 =>50",
            "UserObj" => [
                "Oid" => "fb667994-ab99-4168-9e51-8a2ff16adc49",
                "Name" => "Vivi",
                "Image" => null,
                "Color" => "1FCBA3"
            ]
        ];        
    }

    public function sendMessage(Request $request)
    {      
        $validate = (array)json_decode($request->getContent());
        $validator = Validator::make($validate, [
            'Message'=>'required|max:2000',
            'Device'=>'required',
            'ChatRoom' => 'required|exists:chatroom,Oid',
        ],
        [
            'Message.required'=>'Message is required',
            'Message.max'=>'Message should not be more than 2000 characters',
            'Device.required'=>'Device is required',
            'ChatRoom.required'=>__('_.ChatRoom').__('error.required'),
            'ChatRoom.exists'=>__('_.ChatRoom').__('error.exists'),
        ]);
        if ($validator->fails()) {
            return response()->json(
                $validator->errors(),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        // $request = object_to_array(json_decode($request->getContent()));
        $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))))   ; //WILLIAM ZEF
        $data = new ChatMessage();
        DB::transaction(function () use ($request, &$data, &$dataParent) {
            $user = Auth::user();
            $data->User = $user->Oid;
            $data->Message = $request->Message;
            $data->DeviceId = $request->Device;
            $data->ChatRoom = $request->ChatRoom;
            if (isset($request->Image->base64)) $data->Image = $this->fileCloudService->uploadImage($request->Image, $data->Image);
            $data->MessageType = $data->Image ? 'image' : 'text';
            $data->save();
            if(!$data) throw new \Exception('Data is failed to be saved');

            $dataParent = ChatRoom::findOrFail($data->ChatRoom);
            $dataParent->LastMessage = $request->Message;
            $dataParent->LastUser = $data->User;
            $dataParent->save();
        });

        //kirim ke user
        $user = Auth::user();
        if ($data->RoomType == 'Support') 
            $this->sendNotification($user->Name.' chat', $request->Message, $data->User, $dataParent, $data);
        else {
            $users = ChatRoomUser::where('ChatRoom',$data->ChatRoom)->where('User','!=',$user->Oid)->get();
            foreach ($users as $row) 
                $this->sendNotification($user->Name.' chat', $request->Message, $row->User, $dataParent, $data);
        }
        
        return response()->json(
            $data,
            Response::HTTP_OK
        );
    }

    public function roomSupportClose(Request $request)
    {
        $user = Auth::user();
        $data = ChatRoom::with([
            'Details.UserObj' => function ($query) {$query->addSelect('Oid', 'UserName', 'Name','Image');},
            ])->with(['Details', 'Users'])->findOrFail($request->input('oid'));
        if ($data->Status == 'CLOSE') throw new \Exception('Ticket has been closed');
        $data->Status = 'CLOSE';
        $data->save();

        // if ($data->RoomType == 'Support') $this->sendNotification('Chat is ended', 'Your chat has been ended', $data->User, $data);

        return response()->json(
            $data,
            Response::HTTP_OK
        );
    }

    private function functionRoomUser($room, $u) {
        $user = Auth::user();
        $detailuser = new ChatRoomUser();
        $detailuser->Company = $room->Company;
        $detailuser->ChatRoom = $room->Oid;
        $detailuser->User = $u;
        $detailuser->save();

        $msg = $user->UserName.' has created a room '.now();
        // $this->sendNotification('New Chat Group', $msg, $room->Company, $u, $room);
    }

    public function roomGroup(Request $request) {
        $device = $request->input('device');
        $Oid = $request->has('Oid') ? $request->input('Oid') : null;
        $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))))   ; //WILLIAM ZEF
        $user = Auth::user();

        $data=null;
        if ($Oid) $data = ChatRoom::with('Users')->where('Oid',$Oid)->first();
        if (!$data) $data = new ChatRoom();

        $data->Company = $user->Company;
        $data->User = $user->Oid;
        $data->Code = $request->Code;
        $data->Name = $request->Name;
        $data->DeviceId = $device;
        $data->RoomType = 'Group';
        $data->Status = 'Active';
        $data->save();

        $found = false;
        foreach ($data->Users as $dbUser) if ($user->Oid == $dbUser->User) $found = true;
        if (!$found) $this->functionRoomUser($data, $user->Oid);
        
        foreach ($request->Users as $reqUser) {
            $found = false;
            // logger($reqUser." ".$dbUser->User." ".($reqUser." ".$dbUser->User));
            foreach ($data->Users as $dbUser) if ($reqUser == $dbUser->User) $found = true;
            if (!$found) $this->functionRoomUser($data, $reqUser);
        }

        if (!$data->Oid) {
            $msg = $user->UserName.' has created a room '.now();
            $detailmsg = new ChatMessage();
            $detailmsg->Company = $user->Company;
            $detailmsg->User = $user->Oid;
            $detailmsg->Message = $msg;
            $detailmsg->DeviceId = $device;
            $detailmsg->ChatRoom = $data->Oid;
            $detailmsg->save();
            
            $data->LastMessage = $msg;
            $data->LastUser = $user->Oid;
            $data->save();
        }
        
        return $this->showSub($data->Oid);
    }

    public function roomGroupDelete(Request $request) {
        $device = $request->input('device');
        $Oid = $request->has('Oid') ? $request->input('Oid') : null;

        $data=null;
        $data = ChatRoom::where('Oid',$Oid)->first();
        $data->GCRecord = 99999;
        $data->save();
        return $data;
    }

    public function roomGroupAddUser(Request $request) {
        $device = $request->input('device');
        $Oid = $request->has('Oid') ? $request->input('Oid') : null;
        $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))))   ; //WILLIAM ZEF
        $user = Auth::user();

        $data=null;
        if ($Oid) $data = ChatRoom::with('Users')->where('Oid',$Oid)->first();
        
        foreach ($request as $reqUser) {
            $found = false;
            // logger($reqUser." ".$dbUser->User." ".($reqUser." ".$dbUser->User));
            foreach ($data->Users as $dbUser) if ($reqUser == $dbUser->User) $found = true;
            if (!$found) $this->functionRoomUser($data, $reqUser);
        }
        return $this->showSub($data->Oid);
    }

    public function roomGroupDeleteUser(Request $request) {
        $device = $request->input('device');
        $Oid = $request->has('Oid') ? $request->input('Oid') : null;
        $user = $request->has('User') ? $request->input('User') : null;

        $data=null;
        $data = ChatRoomUser::where('ChatRoom',$Oid)->where('Oid',$user)->first();
        if (!$data) $data = ChatRoomUser::where('ChatRoom',$Oid)->where('User',$user)->first();
        $data->delete();
        return $this->showSub($Oid);
    }

    public function roomGroupLeave(Request $request) {
        $device = $request->input('device');
        $Oid = $request->has('Oid') ? $request->input('Oid') : null;
        $user = Auth::user();

        $data=null;
        $data = ChatRoomUser::where('ChatRoom',$Oid)->where('Oid',$user->Oid)->first();
        if (!$data) $data = ChatRoomUser::where('ChatRoom',$Oid)->where('User',$user->Oid)->first();
        $data->delete();
        return $this->showSub($Oid);
    }

    public function upload(Request $request) {
        $files = $request->file('file');
        $result = [];
        foreach ($files as $key => $value) {
            $filename = now()->format('ymdHis').'-'.str_random(3);
            $extension = $value->getClientOriginalExtension();
            $keyFileName = preg_replace('/[^a-zA-Z0-9-]/', '', encrypt($filename));
            $keyFileName = $keyFileName.'.'.$extension;
            $filename .= '.'.$extension;
            $url = $this->fileCloudService->putFile($value, $keyFileName);

            $user = Auth::user();
            $data = new ChatMessage();
            $data->File = $url;
            // $data->URL = $filename;
            $data->User = $user->Oid;
            $data->ChatRoom = $request->input('Oid');
            $data->MessageType = 'file';
            $data->save();
            if(!$data) throw new \Exception('Data is failed to be saved');

            $dataParent = ChatRoom::findOrFail($data->ChatRoom);
            $dataParent->LastMessage = 'New File';
            $dataParent->LastUser = $data->User;
            $dataParent->save();
            $data->save();
            $result[] = $data;
        }

        return response()->json($result);
    }

    public function deleteFile(PublicFile $data) {
        try {
            $name = basename($data->URL);
            $this->fileCloudService->deleteFile($name);
            $data->delete();

            return response()->json(
                null,
                Response::HTTP_NO_CONTENT
            );
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }   
    }
}