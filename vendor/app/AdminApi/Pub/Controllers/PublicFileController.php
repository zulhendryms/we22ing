<?php

namespace App\AdminApi\Pub\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Laravel\Http\Controllers\Controller;
use App\Core\Pub\Entities\PublicFile;
use App\Core\Internal\Services\FileService;
use App\Core\Internal\Services\FileCloudService;
use Illuminate\Support\Facades\Auth;
use App\Core\Master\Entities\Item;
use App\Core\Pub\Entities\PublicPost;

class PublicFileController extends Controller
{
    /** @var FileService $fileService */
    protected $fileService;
    protected $fileCloudService;


    /**
     * @param FileService $fileService
     * @return void
     */
    public function __construct(FileService $fileService, FileCloudService $fileCloudService)
    {
        $this->fileService = $fileService;
        $this->fileCloudService = $fileCloudService;
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
            $data = new PublicFile();
            $data->FileName = $filename;
            $data->URL = $url;
            $type = $request->has('Type') ? $request->input('Type') : 'PublicPost';
            if (!in_array($type, ['ItemContent','Item'])) {
                $tmp = PublicPost::where('Oid', $request->input('Oid'))->first();
                $data->PublicPost = $tmp->Oid;
                $data->Company = $tmp->Company;
            } else {
                $data->{$type} = $request->input('Oid');
                $data->Company = $user->Company;
            }
            if (!$data->Company) $data->Company = $user->Company;
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


    // public function migrasiImage(Request $request) {
    //     try {
    //         if ($request->has('item')) {
    //             $i=0;                
    //             // AND LEFT(m.Image1,53) != 'https://acegroup-storage.sgp1.digitaloceanspaces.com/' 
    //             $query = "SELECT Oid,Image1,Image2,Image3,Image4,Image5,Image6,Image7,Image8
    //                 FROM mstitem m 
    //                 WHERE m.Image1 IS NOT NULL 
    //                 AND LEFT(m.Image1,38) = 'http://api1.ezbooking.co:1000/storage/'
    //                 AND m.Company = 'dbb1f435-7a14-4fa0-8535-beceed9cd477'
    //                 LIMIT 50";
    //             $item = DB::select($query);

    //             foreach ($item as $row){
    //                 $i=$i+1;
    //                 logger('Record:'.$i.': '.$row->Oid);
    //                 $extension = pathinfo($row->Image1)['extension'];
    //                 $image = 'data:image/'.$extension.';base64,'.base64_encode(file_get_contents($row->Image1));
    //                 $param = [
    //                     'base64' => $image,
    //                     'Type' => 'image/'.$extension,
    //                     'Disk' => 'images',
    //                 ];

    //                 $file = $this->fileCloudService->upload($param);

    //                 $query = "UPDATE mstitem m SET m.Image1 = '{$file['URL']}' WHERE m.Oid = '{$row->Oid}'";
    //                 DB::update($query);
                    
    //                 if($row->Image2){
    //                     $i=$i+1;
    //                     logger('Record:'.$i.': '.$row->Oid);
    //                     $found = strpos($row->Image2, 'http://api1.ezbooking.co:1000/storage') !== FALSE;
    //                     if($found){
    //                         $extension = pathinfo($row->Image2)['extension'];
    //                         $image = 'data:image/'.$extension.';base64,'.base64_encode(file_get_contents($row->Image2));
    //                         $param = [
    //                             'base64' => $image,
    //                             'Type' => 'image/'.$extension,
    //                             'Disk' => 'images',
    //                         ];
    //                         $file = $this->fileCloudService->upload($param);

    //                         $query = "UPDATE mstitem m SET m.Image2 = '{$file['URL']}' WHERE m.Oid = '{$row->Oid}'";
    //                         DB::update($query);
    //                     }
    //                 }

    //                 if($row->Image3){
    //                     $i=$i+1;
    //                     logger('Record:'.$i.': '.$row->Oid);
    //                     $found = strpos($row->Image3, 'http://api1.ezbooking.co:1000/storage') !== FALSE;
    //                     if($found){
    //                         $extension = pathinfo($row->Image3)['extension'];
    //                         $image = 'data:image/'.$extension.';base64,'.base64_encode(file_get_contents($row->Image3));
    //                         $param = [
    //                             'base64' => $image,
    //                             'Type' => 'image/'.$extension,
    //                             'Disk' => 'images',
    //                         ];

    //                         $file = $this->fileCloudService->upload($param);
    //                         $query = "UPDATE mstitem m SET m.Image3 = '{$file['URL']}' WHERE m.Oid = '{$row->Oid}'";
    //                         DB::update($query);
    //                     }
    //                 }

    //                 if($row->Image4){
    //                     $i=$i+1;
    //                     logger('Record:'.$i.': '.$row->Oid);
    //                     $found = strpos($row->Image4, 'http://api1.ezbooking.co:1000/storage') !== FALSE;
    //                     if($found){
    //                         $extension = pathinfo($row->Image4)['extension'];
    //                         $image = 'data:image/'.$extension.';base64,'.base64_encode(file_get_contents($row->Image4));
    //                         $param = [
    //                             'base64' => $image,
    //                             'Type' => 'image/'.$extension,
    //                             'Disk' => 'images',
    //                         ];

    //                         $file = $this->fileCloudService->upload($param);
    //                         $query = "UPDATE mstitem m SET m.Image4 = '{$file['URL']}' WHERE m.Oid = '{$row->Oid}'";
    //                         DB::update($query);
    //                     }
    //                 }

    //                 if($row->Image5){
    //                     $i=$i+1;
    //                     logger('Record:'.$i.': '.$row->Oid);
    //                     $found = strpos($row->Image5, 'http://api1.ezbooking.co:1000/storage') !== FALSE;
    //                     if($found){
    //                         $extension = pathinfo($row->Image5)['extension'];
    //                         $image = 'data:image/'.$extension.';base64,'.base64_encode(file_get_contents($row->Image5));
    //                         $param = [
    //                             'base64' => $image,
    //                             'Type' => 'image/'.$extension,
    //                             'Disk' => 'images',
    //                         ];

    //                         $file = $this->fileCloudService->upload($param);
    //                         $query = "UPDATE mstitem m SET m.Image5 = '{$file['URL']}' WHERE m.Oid = '{$row->Oid}'";
    //                         DB::update($query);
    //                     }
    //                 }

    //                 if($row->Image6){
    //                     $i=$i+1;
    //                     logger('Record:'.$i.': '.$row->Oid);
    //                     $found = strpos($row->Image6, 'http://api1.ezbooking.co:1000/storage') !== FALSE;
    //                     if($found){
    //                         $extension = pathinfo($row->Image6)['extension'];
    //                         $image = 'data:image/'.$extension.';base64,'.base64_encode(file_get_contents($row->Image6));
    //                         $param = [
    //                             'base64' => $image,
    //                             'Type' => 'image/'.$extension,
    //                             'Disk' => 'images',
    //                         ];

    //                         $file = $this->fileCloudService->upload($param);
    //                         $query = "UPDATE mstitem m SET m.Image6 = '{$file['URL']}' WHERE m.Oid = '{$row->Oid}'";
    //                         DB::update($query);
    //                     }
    //                 }

    //                 if($row->Image7){
    //                     $i=$i+1;
    //                     logger('Record:'.$i.': '.$row->Oid);
    //                     $found = strpos($row->Image7, 'http://api1.ezbooking.co:1000/storage') !== FALSE;
    //                     if($found){
    //                         $extension = pathinfo($row->Image7)['extension'];
    //                         $image = 'data:image/'.$extension.';base64,'.base64_encode(file_get_contents($row->Image7));
    //                         $param = [
    //                             'base64' => $image,
    //                             'Type' => 'image/'.$extension,
    //                             'Disk' => 'images',
    //                         ];

    //                         $file = $this->fileCloudService->upload($param);
    //                         $query = "UPDATE mstitem m SET m.Image7 = '{$file['URL']}' WHERE m.Oid = '{$row->Oid}'";
    //                         DB::update($query);
    //                     }
    //                 }

    //                 if($row->Image8){
    //                     $i=$i+1;
    //                     logger('Record:'.$i.': '.$row->Oid);
    //                     $found = strpos($row->Image8, 'http://api1.ezbooking.co:1000/storage') !== FALSE;
    //                     if($found){
    //                         $extension = pathinfo($row->Image8)['extension'];
    //                         $image = 'data:image/'.$extension.';base64,'.base64_encode(file_get_contents($row->Image8));
    //                         $param = [
    //                             'base64' => $image,
    //                             'Type' => 'image/'.$extension,
    //                             'Disk' => 'images',
    //                         ];

    //                         $file = $this->fileCloudService->upload($param);
    //                         $query = "UPDATE mstitem m SET m.Image8 = '{$file['URL']}' WHERE m.Oid = '{$row->Oid}'";
    //                         DB::update($query);
    //                     }
    //                 }
    //             }  
    //         }

    //         if ($request->has('country')) {
    //             $i=0;                
    //             $query = "SELECT m.Oid,m.Image, m.ImageThumbnail, m.ImageHeader, m.ImageBanner
    //                 FROM syscountry m
    //                 WHERE m.Image IS NOT NULL 
    //                 AND LEFT(m.Image,38) = 'http://api1.ezbooking.co:1000/storage/'
    //                 LIMIT 50";
    //             $country = DB::select($query);

    //             foreach ($country as $row){
    //                 $i=$i+1;
    //                 logger('Record:'.$i.': '.$row->Oid);
    //                 $extension = pathinfo($row->Image)['extension'];
    //                 $image = 'data:image/'.$extension.';base64,'.base64_encode(file_get_contents($row->Image));
    //                 $param = [
    //                     'base64' => $image,
    //                     'Type' => 'image/'.$extension,
    //                     'Disk' => 'images',
    //                 ];

    //                 $file = $this->fileCloudService->upload($param);

    //                 $query = "UPDATE syscountry m SET m.Image = '{$file['URL']}' WHERE m.Oid = '{$row->Oid}'";
    //                 DB::update($query);

    //                 if($row->ImageThumbnail){
    //                     $i=$i+1;
    //                     logger('Record:'.$i.': '.$row->Oid);
    //                     $found = strpos($row->ImageThumbnail, 'http://api1.ezbooking.co:1000/storage') !== FALSE;
    //                     if($found){
    //                         $extension = pathinfo($row->ImageThumbnail)['extension'];
    //                         $image = 'data:image/'.$extension.';base64,'.base64_encode(file_get_contents($row->ImageThumbnail));
    //                         $param = [
    //                             'base64' => $image,
    //                             'Type' => 'image/'.$extension,
    //                             'Disk' => 'images',
    //                         ];
    //                         $file = $this->fileCloudService->upload($param);

    //                         $query = "UPDATE syscountry m SET m.ImageThumbnail = '{$file['URL']}' WHERE m.Oid = '{$row->Oid}'";
    //                         DB::update($query);
    //                     }
    //                 }

    //                 if($row->ImageHeader){
    //                     $i=$i+1;
    //                     logger('Record:'.$i.': '.$row->Oid);
    //                     $found = strpos($row->ImageHeader, 'http://api1.ezbooking.co:1000/storage') !== FALSE;
    //                     if($found){
    //                         $extension = pathinfo($row->ImageHeader)['extension'];
    //                         $image = 'data:image/'.$extension.';base64,'.base64_encode(file_get_contents($row->ImageHeader));
    //                         $param = [
    //                             'base64' => $image,
    //                             'Type' => 'image/'.$extension,
    //                             'Disk' => 'images',
    //                         ];
    //                         $file = $this->fileCloudService->upload($param);

    //                         $query = "UPDATE syscountry m SET m.ImageHeader = '{$file['URL']}' WHERE m.Oid = '{$row->Oid}'";
    //                         DB::update($query);
    //                     }
    //                 }

    //                 if($row->ImageBanner){
    //                     $i=$i+1;
    //                     logger('Record:'.$i.': '.$row->Oid);
    //                     $found = strpos($row->ImageBanner, 'http://api1.ezbooking.co:1000/storage') !== FALSE;
    //                     if($found){
    //                         $extension = pathinfo($row->ImageBanner)['extension'];
    //                         $image = 'data:image/'.$extension.';base64,'.base64_encode(file_get_contents($row->ImageBanner));
    //                         $param = [
    //                             'base64' => $image,
    //                             'Type' => 'image/'.$extension,
    //                             'Disk' => 'images',
    //                         ];
    //                         $file = $this->fileCloudService->upload($param);

    //                         $query = "UPDATE syscountry m SET m.ImageBanner = '{$file['URL']}' WHERE m.Oid = '{$row->Oid}'";
    //                         DB::update($query);
    //                     }
    //                 }
    //             }
    //         }

    //         if ($request->has('bank')) {
    //             $i=0;                
    //             $query = "SELECT m.Oid,m.Image1, m.Image2, m.Image3, m.ImageSignature
    //                 FROM mstbank  m
    //                 WHERE m.Image1 IS NOT NULL 
    //                 AND LEFT(m.Image1,38) = 'http://api1.ezbooking.co:1000/storage/'
    //                 AND m.Company = 'dbb1f435-7a14-4fa0-8535-beceed9cd477'
    //                 LIMIT 50";
    //             $bank = DB::select($query);

    //             foreach ($bank as $row){
    //                 $i=$i+1;
    //                 logger('Record:'.$i.': '.$row->Oid);
    //                 $extension = pathinfo($row->Image1)['extension'];
    //                 $image = 'data:image/'.$extension.';base64,'.base64_encode(file_get_contents($row->Image1));
    //                 $param = [
    //                     'base64' => $image,
    //                     'Type' => 'image/'.$extension,
    //                     'Disk' => 'images',
    //                 ];

    //                 $file = $this->fileCloudService->upload($param);

    //                 $query = "UPDATE mstbank m SET m.Image1 = '{$file['URL']}' WHERE m.Oid = '{$row->Oid}'";
    //                 DB::update($query);

    //                 if($row->Image2){
    //                     $i=$i+1;
    //                     logger('Record:'.$i.': '.$row->Oid);
    //                     $found = strpos($row->Image2, 'http://api1.ezbooking.co:1000/storage') !== FALSE;
    //                     if($found){
    //                         $extension = pathinfo($row->Image2)['extension'];
    //                         $image = 'data:image/'.$extension.';base64,'.base64_encode(file_get_contents($row->Image2));
    //                         $param = [
    //                             'base64' => $image,
    //                             'Type' => 'image/'.$extension,
    //                             'Disk' => 'images',
    //                         ];
    //                         $file = $this->fileCloudService->upload($param);

    //                         $query = "UPDATE mstbank m SET m.Image2 = '{$file['URL']}' WHERE m.Oid = '{$row->Oid}'";
    //                         DB::update($query);
    //                     }
    //                 }

    //                 if($row->Image3){
    //                     $i=$i+1;
    //                     logger('Record:'.$i.': '.$row->Oid);
    //                     $found = strpos($row->Image3, 'http://api1.ezbooking.co:1000/storage') !== FALSE;
    //                     if($found){
    //                         $extension = pathinfo($row->Image3)['extension'];
    //                         $image = 'data:image/'.$extension.';base64,'.base64_encode(file_get_contents($row->Image3));
    //                         $param = [
    //                             'base64' => $image,
    //                             'Type' => 'image/'.$extension,
    //                             'Disk' => 'images',
    //                         ];
    //                         $file = $this->fileCloudService->upload($param);

    //                         $query = "UPDATE mstbank m SET m.Image3 = '{$file['URL']}' WHERE m.Oid = '{$row->Oid}'";
    //                         DB::update($query);
    //                     }
    //                 }

    //                 if($row->ImageSignature){
    //                     $i=$i+1;
    //                     logger('Record:'.$i.': '.$row->Oid);
    //                     $found = strpos($row->ImageSignature, 'http://api1.ezbooking.co:1000/storage') !== FALSE;
    //                     if($found){
    //                         $extension = pathinfo($row->ImageSignature)['extension'];
    //                         $image = 'data:image/'.$extension.';base64,'.base64_encode(file_get_contents($row->ImageSignature));
    //                         $param = [
    //                             'base64' => $image,
    //                             'Type' => 'image/'.$extension,
    //                             'Disk' => 'images',
    //                         ];
    //                         $file = $this->fileCloudService->upload($param);

    //                         $query = "UPDATE mstbank m SET m.ImageSignature = '{$file['URL']}' WHERE m.Oid = '{$row->Oid}'";
    //                         DB::update($query);
    //                     }
    //                 }
    //             }
    //         }

    //         if ($request->has('image')) {
    //             $i=0;                
    //             $query = "SELECT m.Oid, m.Image
    //                 FROM mstimage m
    //                 WHERE m.Image IS NOT NULL 
    //                 AND m.Oid IN ('1425487e-2e7b-46c8-800e-1f591de8ebad',
    //                 '30a8b61b-6425-460b-a713-aee9ee3333c8')";
    //             $img = DB::select($query);

    //             foreach ($img as $row){
    //                 $i=$i+1;
    //                 logger('Record:'.$i.': '.$row->Oid);
    //                 $extension = pathinfo($row->Image)['extension'];
    //                 $image = 'data:image/'.$extension.';base64,'.base64_encode(file_get_contents($row->Image));
    //                 $param = [
    //                     'base64' => $image,
    //                     'Type' => 'image/'.$extension,
    //                     'Disk' => 'images',
    //                 ];

    //                 $file = $this->fileCloudService->upload($param);

    //                 $query = "UPDATE mstimage m SET m.Image = '{$file['URL']}' WHERE m.Oid = '{$row->Oid}'";
    //                 DB::update($query);
    //             }
    //         }

    //         return response()->json(
    //             null,
    //             Response::HTTP_NO_CONTENT
    //         );
    //     } catch (\Exception $e) {
    //         return response()->json(
    //             errjson($e),
    //             Response::HTTP_UNPROCESSABLE_ENTITY
    //         );
    //     }
    // }

    // public function index(Request $request)
    // {        
    //     try {            
    //         $user = Auth::user();
    //         $type = $request->input('type') ?: 'combo';
    //         $data = PublicFile::whereNull('GCRecord');
    //         if ($user->BusinessPartner) $data = $data->where('Code', $user->BusinessPartner);
    //         $data = $data->get();
    //         return $data;
    //     } catch (\Exception $e) {
    //         return response()->json(
    //             errjson($e),
    //             Response::HTTP_NOT_FOUND
    //         );
    //     } 
    // }
    
    // public function show(File $data)
    // {
    //     try {            
    //         return $data;
    //     } catch (\Exception $e) {
    //         return response()->json(
    //             errjson($e),
    //             Response::HTTP_NOT_FOUND
    //         );
    //     }
    // }

    // public function save(Request $request, $Oid = null)
    // {
    //     try {            
    //         if (!$Oid) $data = new File();
    //         else $data = PublicFile::findOrFail($Oid);
    //         DB::transaction(function () use ($request, &$data) {
    //             if ($request->has('purchaserequest')) $data->PurchaseRequest = $request->input('purchaserequest');
    //             $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))))   ; //WILLIAM ZEF
    //             $excluded = ['File'];
    //             $disabled = array_merge(disabledFieldsForEdit(), $excluded);
    //             foreach ($request as $field => $key) {
    //                 if (in_array($field, $disabled)) continue;
    //                 $data->{$field} = $request->{$field};
    //             }
    //             if (isset($request->Image->base64)) $data->Image = $this->fileService->uploadImage($request->Image);
    //             $data->save();
    //             if(!$data) throw new UserFriendlyException('Data is failed to be saved');
    //         });

    //         // $data = (new ImageResource($data))->type('detail');
    //         $data = PublicFile::findOrFail($data->Oid);
    //         return response()->json(
    //             $data, Response::HTTP_CREATED
    //         );
    //     } catch (\Exception $e) {
    //         return response()->json(
    //             errjson($e),
    //             Response::HTTP_UNPROCESSABLE_ENTITY
    //         );
    //     }
    // }

    // public function destroy(File $data)
    // {
    //     $fileToDelete = str_replace(config('app.url').'/storage', '', $data->File);
        
    //     try {
    //         if (! Storage::disk('public')->delete($fileToDelete)) {
    //             // [zfx] TODO: throw failed throw new Exception('file cannot be deleted');
    //             // skip for now
    //         }
    //         DB::transaction(function () use ($data, &$fileToDelete) {
    //             $data->delete();
    //         });
    //         return response()->json(
    //             null, Response::HTTP_NO_CONTENT
    //         );
    //     } catch (\Exception $e) {
    //         return response()->json(
    //             errjson($e),
    //             Response::HTTP_UNPROCESSABLE_ENTITY
    //         );
    //     }
    // }
}
