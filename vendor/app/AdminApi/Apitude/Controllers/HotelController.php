<?php

namespace App\AdminApi\Apitude\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Laravel\Http\Controllers\Controller;
use App\Core\Apitude\Entities\Hotel;
use App\Core\Apitude\Entities\HotelImage;
use App\Core\Master\Entities\HotelECommerce;
use App\Core\Internal\Services\FileCloudService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Core\Security\Services\RoleModuleService;
use App\AdminApi\Development\Controllers\CRUDDevelopmentController;

class HotelController extends Controller
{
    protected $roleService;
    private $module;
    private $crudController;
    public function __construct(
        RoleModuleService $roleService
    ) {
        $this->roleService = $roleService;
        $this->module = 'apitudehotel';
        $this->crudController = new CRUDDevelopmentController();
    }
    public function fields()
    {
        $fields = []; //f = 'FIELD, t = TITLE
        $fields[] = ['w' => 0, 'n' => 'code',];
        $fields[] = ['w' => 0, 'n' => 'name',];
        $fields[] = ['w' => 0, 'n' => 'country_code',];
        $fields[] = ['w' => 0, 'n' => 'destination_code',];
        $fields[] = ['w' => 0, 'n' => 'zone_code',];
        $fields[] = ['w' => 0, 'n' => 'address',];
        $fields[] = ['w' => 0, 'n' => 'ezb_is_active',];
        return $fields;
    }

    public function config(Request $request)
    {
        try {
            $fields = $this->crudController->jsonConfig($this->fields(), false, true);
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }
    // public function config(Request $request) {
    //     return$this->httpService->get('/portal/api/development/table/vuemaster?code=ApitudeapiHotel');
    // }
    
    public function list(Request $request)
    {
        $data = DB::table('apitudeapi_hotel as data') //jointable
            ->leftJoin('company AS Company', 'Company.Oid', '=', 'data.Company');
        $data = $this->crudController->jsonList($data, $this->fields(), $request, 'apitude_hotel', 'Oid');
        $role = $this->roleService->list('ApitudeHotel'); //rolepermission
        foreach ($data as $row) $row->Action = $this->roleService->generateActionMaster($role);
        return $this->crudController->jsonListReturn($data, $this->fields());
    }

    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $type = $request->input('type') ?: 'combo';
            $data = Hotel::where('ezb_is_active', '1');
            if ($user->BusinessPartner) $data = $data->where('Code', $user->BusinessPartner);
            $data = $data->get();
            $role = $this->roleService->list('ApitudeHotel'); //rolepermission
            $result = [];
            foreach ($data as $row) {
                $result[] = [
                    'code' => $row->code,
                    'name' => $row->name,
                    'country_code' => $row->country_code,
                    'destination_code' => $row->destination_code,
                    'zone_code' => $row->zone_code,
                    'address' => $row->address,
                    'ezb_is_active' => $row->ezb_is_active,
                    'Role' => $this->roleService->generateActionMaster($role)
                ];
            }
            return $result;
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }

    public function show(Hotel $data)
    {
        try {
            $data = Hotel::with(['Images', 'Rooms', 'Rooms.ItemObj'])->where('code', $data->code)->firstOrFail();
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
        $request = json_decode(json_encode(object_to_array(json_decode($request->getContent())))); //WILLIAM ZEF

        try {
            if (!$Oid) $data = new Hotel();
            else $data = Hotel::where('code', $Oid)->firstOrFail();
            DB::transaction(function () use ($request, &$data) {
                $disabled = ['code', 'Images', 'ezb_image', 'last_update', 'created_at', 'updated_at', 'deleted_at'];
                foreach ($request as $field => $key) {
                    if (in_array($field, $disabled)) continue;
                    $data->{$field} = $request->{$field};
                }
                if (isset($request->ezb_image->base64)) $data->ezb_image = $this->fileCloudService->uploadImage($request->ezb_image, $data->ezb_image);

                $data->save();

                $query = "INSERT INTO mstitemecommercehotel (Oid, Company, HotelCode, ECommerce, IsActive)
                    SELECT UUID(), i.Company,'" . $data->code . "', i.Oid, 1
                    FROM mstecommerce i 
                    LEFT OUTER JOIN mstitemecommercehotel ie ON i.Oid = ie.ECommerce AND ie.HotelCode = '" . $data->code . "'
                    WHERE ie.Oid IS NULL";

                DB::insert($query);

                if ($data->Images()->count() != 0) {
                    foreach ($data->Images as $rowdb) {
                        $found = false;
                        foreach ($request->Images as $rowapi) {
                            if (isset($rowapi->hotel_code)) {
                                if ($rowdb->hotel_code == $rowapi->hotel_code) $found = true;
                            }
                        }
                        if (!$found) {
                            $detail = HotelImage::where('hotel_code', $rowdb->hotel_code)->delete();
                        }
                    }
                }
                if ($request->Images) {
                    logger(2);
                    $details = [];
                    foreach ($request->Images as $row) {
                        if (isset($row->hotel_code)) {
                            $detail = HotelImage::where('hotel_code', $row->hotel_code)->firstOrFail();
                            $detail->image_type_code = $row->image_type_code;
                            $detail->room_code = $row->room_code;
                            $detail->path = $row->path;
                            $detail->order = $row->order;
                            $detail->visual_order = $row->visual_order;
                            $detail->ezb_is_active = $row->ezb_is_active;
                            $detail->save();
                        } else {
                            logger(3);
                            if (isset($row->path->base64)) {
                                logger(4);
                                $path = $this->fileCloudService->uploadImage($row->path);
                            } else {
                                logger(5);
                                $path = $row->path;
                            }
                            $details[] = new HotelImage([
                                'image_type_code' => $row->image_type_code,
                                'room_code' => $row->room_code,
                                'path' => $path,
                                'order' => $row->order,
                                'visual_order' => $row->visual_order,
                                'ezb_is_active' => $row->ezb_is_active,
                            ]);
                        }
                    }
                    $data->Images()->saveMany($details);
                    $data->load('Images');
                    $data->fresh();
                }

                if (!$data) throw new \Exception('Data is failed to be saved');
            });

            return $data;
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

    public function changeIsActive(Hotel $data)
    {
        try {
            DB::transaction(function () use ($data) {
                $data->ezb_is_active = false;
                $data->save();
            });
            return response()->json(
                $data,
                Response::HTTP_NO_CONTENT
            );
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function listitemecommerce(Request $request)
    {
        try {
            $hotel = $request->input('hotel');
            $data = HotelECommerce::with(['ECommerceObj'])->where('HotelCode', $hotel);
            $data = $data->get();
            return $data;
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }

    public function saveitemecommerce(Request $request)
    {
        $hotel = $request->input('hotel');
        $request = json_decode(json_encode(object_to_array(json_decode($request->getContent())))); //WILLIAM ZEF

        // try {            
        $data = Hotel::where('code', $hotel)->firstOrFail();
        DB::transaction(function () use ($request, &$data) {
            $disabled = ['code', 'HotelECommerces', 'ezb_image', 'last_update', 'created_at', 'updated_at', 'deleted_at'];
            foreach ($request as $field => $key) {
                if (in_array($field, $disabled)) continue;
                $data->{$field} = $request->{$field};
            }

            $data->save();

            if ($data->HotelECommerces()->count() != 0) {
                foreach ($data->HotelECommerces as $rowdb) {
                    $found = false;
                    foreach ($request->HotelECommerces as $rowapi) {
                        if (isset($rowapi->Oid)) {
                            if ($rowdb->Oid == $rowapi->Oid) $found = true;
                        }
                    }
                    if (!$found) {
                        $detail = HotelECommerce::findOrFail($rowdb->Oid);
                        $detail->delete();
                    }
                }
            }
            if ($request->HotelECommerces) {
                $details = [];
                $disabled = ['Oid', 'HotelCode', 'GCRecord', 'OptimisticLock', 'CreatedAt', 'UpdatedAt', 'CreatedAtUTC', 'UpdatedAtUTC', 'CreatedBy', 'UpdatedBy'];
                foreach ($request->HotelECommerces as $row) {
                    if (isset($row->Oid)) {
                        $detail = HotelECommerce::findOrFail($row->Oid);
                        foreach ($row as $field => $key) {
                            if (in_array($field, $disabled)) continue;
                            $detail->{$field} = $row->{$field};
                        }
                        $detail->save();
                    } else {
                        $arr = [];
                        foreach ($row as $field => $key) {
                            if (in_array($field, $disabled)) continue;
                            $arr = array_merge($arr, [
                                $field => $row->{$field},
                            ]);
                        }
                        $details[] = new HotelECommerce($arr);
                    }
                }
                $data->HotelECommerces()->saveMany($details);
                $data->load('HotelECommerces');
                $data->fresh();
            }

            if (!$data) throw new \Exception('Data is failed to be saved');
        });

        // $data = (new ProductionOrderResource($data))->type('detail');
        return response()->json(
            $data,
            Response::HTTP_CREATED
        );
        // } catch (\Exception $e) {
        //     return response()->json(
        //         errjson($e),
        //         Response::HTTP_UNPROCESSABLE_ENTITY
        //     );
        // }
    }
}
