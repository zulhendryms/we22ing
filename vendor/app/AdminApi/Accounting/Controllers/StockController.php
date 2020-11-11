<?php

namespace App\AdminApi\Accounting\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Laravel\Http\Controllers\Controller;
use App\Core\Accounting\Entities\Journal;
use App\Core\Accounting\Resources\JournalCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class StockController extends Controller
{
    
    public function index(Request $request, $Oid = null)
    {
        $auto = "'Auto'" ;
        $posted = "'posted'";

        try {            
            $user = Auth::user();
            $query = "SELECT i.Code AS ItemCode,
                            i.Name,
                            st.Date,
                            st.Code,
                            i.WarehouseName AS Warehouse,
                            st.Type AS Type,
                            st.Quantity,
                            st.Amount,
                            st.Item
                    FROM (
                            SELECT i.Oid AS Item,
                            w.Oid AS Warehouse,
                            CONCAT(i.Name,' - ',i.Code) AS ItemName,
                            CONCAT(w.Name,' - ',w.Code) AS WarehouseName,
                            i.ItemGroup,
                            i.Code AS Code,
                            i.Name AS Name
                            FROM mstitem i, mstwarehouse w
                        ) AS i
                        LEFT OUTER JOIN mstitemgroup ig ON ig.Oid = i.ItemGroup
                        LEFT OUTER JOIN (
                            SELECT st.Item,
                                st.Oid,
                                st.Warehouse,
                                st.Date,
                                st.Code,
                                jt.Name AS Type,
                                st.StockQuantity AS Quantity,
                                st.StockCost AS Amount
                            FROM trdtransactionstock st 
                            LEFT OUTER JOIN sysjournaltype jt ON st.JournalType = jt.Oid
                            LEFT OUTER JOIN sysstatus s ON s.Oid = st.Status
                            WHERE st.GCRecord IS NULL AND s.Code = ".$posted." AND jt.Code != ".$auto."  AND st.Item = '{$Oid}'
                            
                            UNION ALL
                    
                            SELECT st.Item,
                                st.Oid,
                                st.Warehouse,
                                st.Date,
                                st.Code,
                                jt.Name AS Type,
                                SUM(st.StockQuantity) AS Quantity,
                                SUM(st.StockCost) / SUM(st.StockQuantity) AS Amount
                            FROM trdtransactionstock st 
                            LEFT OUTER JOIN sysjournaltype jt ON st.JournalType = jt.Oid 
                            LEFT OUTER JOIN sysstatus s ON s.Oid = st.Status
                            WHERE st.GCRecord IS NULL AND s.Code = ".$posted." AND jt.Code = ".$auto." AND st.Item = '{$Oid}'
                            GROUP BY st.Item, st.Warehouse
                        ) st ON st.Item = i.Item AND st.Warehouse = i.Warehouse
                        WHERE st.Quantity IS NOT NULL AND 4=4
                        ORDER BY st.Date, st.Code";
                        
            logger($query);
            $data = DB::select($query);

            return response()->json(
                $data,
                Response::HTTP_OK
            );
            
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }
}
            