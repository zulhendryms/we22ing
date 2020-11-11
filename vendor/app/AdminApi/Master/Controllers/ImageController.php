<?php

namespace App\AdminApi\Master\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Laravel\Http\Controllers\Controller;
use App\Core\Master\Entities\Image;
use Illuminate\Support\Facades\DB;
use App\Core\Internal\Services\FileService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Core\Internal\Services\AutoNumberService;
use App\Core\Internal\Services\FileCloudService;
use App\Core\Pub\Entities\PublicPost;

class ImageController extends Controller
{
    /** @var FileService $fileService */
    protected $fileService;
    private $autoNumberService;
    protected $fileCloudService;

    /**
     * @param FileService $fileService
     * @return void
     */
    public function __construct(FileService $fileService,AutoNumberService $autoNumberService, FileCloudService $fileCloudService)
    {
        $this->fileService = $fileService;
        $this->autoNumberService = $autoNumberService;
        $this->fileCloudService = $fileCloudService;
    }

    public function index(Request $request)
    {        
        try {            
            $user = Auth::user();
            $type = $request->input('type') ?: 'combo';
            $data = Image::whereNull('GCRecord');
            if ($request->has('SearchItem')) $data->where('Item',$request->input('SearchItem'));
            if ($user->BusinessPartner) $data = $data->where('Code', $user->BusinessPartner);
            $data = $data->get();
            return $data;
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        } 
    }
    
    public function show(Image $data)
    {
        try {            
            return $data;
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }

    public function save(Request $request, $Oid = null)
    {
        try {            
            if (!$Oid) $data = new Image();
            else $data = Image::findOrFail($Oid);
            DB::transaction(function () use ($request, &$data) {
                $type = $request->has('Type') ? $request->input('Type') : 'PublicPost';
                $user = Auth::user();

                if (!in_array($type, ['ItemContent','Item'])) {
                    $post = PublicPost::where('Oid',$request->input('Oid'))->first();
                    $data->PublicPost = $post->Oid;
                    $data->Company = $post->Company;
                } else {                    
                    // $post = PublicPost::where('Oid',$request->input('Oid'))->first();
                    $data->{$type} = $request->input('Oid');
                    $data->Company = $user->Company;
                }
                
                $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))))   ; //WILLIAM ZEF
                // $excluded = ['Image'];
                // $disabled = array_merge(disabledFieldsForEdit(), $excluded);
                // foreach ($request as $field => $key) {
                //     if (in_array($field, $disabled)) continue;
                //     $data->{$field} = $request->{$field};
                // }
                if (isset($request->Image->base64)) $data->Image = $this->fileCloudService->uploadImage($request->Image, $data->Image);
                $data->save();
                if ($data->Code == '<<Auto>>') $data->Code = $this->autoNumberService->generate($data, 'mstimage');
                if(!$data) throw new \Exception('Data is failed to be saved');
            });

            // $data = (new ImageResource($data))->type('detail');
            $data = Image::findOrFail($data->Oid);
            return response()->json(
                $data, Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function destroy(Image $data)
    {
        // $fileToDelete = str_replace(config('app.url').'/storage', '', $data->Image);
        
        try {
            // if (! Storage::disk('public')->delete($fileToDelete)) {
            //     // [zfx] TODO: throw failed throw new Exception('file cannot be deleted');
            //     // skip for now
            // }
            DB::transaction(function () use ($data) {
                $name = basename($data->Image);
                $this->fileCloudService->deleteImage($name);
                $data->delete();
            });
            return response()->json(
                null, Response::HTTP_NO_CONTENT
            );
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }
}
