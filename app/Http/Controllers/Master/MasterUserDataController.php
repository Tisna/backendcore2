<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\UserCompany;
use App\Models\UserItmGrp;
use App\Models\UserMenu;
use App\Models\UserWhs;
use App\Models\ViewEmployee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MasterUserDataController extends Controller
{
    /**
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getWhsTo(Request $request)
    {
        $form = json_decode($request->form);
        $connect = $this->connectHana();
        $result = [];
        $db_name = $form->CompanyName;
        $sql = '
                    select "WhsCode", "WhsName",
                           ROW_NUMBER() OVER (PARTITION BY "WhsCode" ORDER BY "WhsCode" DESC) AS "LineNum"
                    from ' . $db_name . '.OWHS
                ';

        $rs = odbc_exec($connect, $sql);

        if (!$rs) {
            exit("Error in SQL");
        }
        $arr = [];
        $index = 1;
        while (odbc_fetch_row($rs)) {
            $arr[] = [
                "WhsCode" => odbc_result($rs, "WhsCode"),
                "WhsName" => odbc_result($rs, "WhsName"),
                "LineNum" => $index,
            ];
            $index++;
        }

        $result = $result["itemWhTo"] = $arr;
        return response()->json($result);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function userRelationship(Request $request): \Illuminate\Http\JsonResponse
    {
        $user_id = $request->user()->username;
        //$form = json_decode($request->form);
        $user_company = UserCompany::where("user_companies.user_id", $user_id)
            ->leftJoin('companies as company', 'company.id', 'user_companies.company_id')
            ->select('db_code', 'db_name')
            ->get();

        $department = substr($request->user()->department, 0, 4);

        $user_list = ViewEmployee::where('Department', 'LIKE', '%' . $department . '%')
            ->orderBy('Name')
            ->get();

        $user_wh = UserWhs::where("user_id", $user_id)->get();
        $result = [];


        foreach ($user_list as $item) {
            $result["userNik"][] = [
                "U_UserName" => $item->Name,
                "U_NIK" => $item->Nik,
            ];

            $result["userDivision"][] = [
                "U_UserName" => $item->Name,
                "U_NIK" => $item->Nik,
                "user_id" => $item->Nik,
                "Division" => $item->Department,
            ];

            $result["user"][] = [
                "U_UserName" => $item->Nik,
                "user_id" => $item->Nik,
            ];
        }

        foreach ($user_company as $item) {
            $result["items"][] = $item->db_code;
        }

        foreach ($user_wh as $item) {
            $result['itemUserWhs'][] = $item->whs_code;
        }

        return response()->json($result);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function whsRelationship(Request $request): \Illuminate\Http\JsonResponse
    {
        // $user_id = $request->user()->user_id;
        // $user_wh = UserWhs::where("user_id", $user_id)->get();
        // $result = [];
        // foreach ($user_wh as $item) {
        //     $result[] = $item->U_WhsCode;
        // }
        // return response()->json([
        //     "items" => $result
        // ]);

        $result = [];
        $user_id = $request->user()->user_id;
        $companyItem = $request->companyItem;
        $arr = [];
        $connect = $this->connectHana();
        $sql = 'select * from "IMIP_ERESV"."OUSR_OWHS" oc
                left join "' . $companyItem . '"."@ADDON_CONFIG" ac
                on oc."U_WhsCode"=ac."U_Value"
                where ac."U_Description"=\'RESV_SUBWH_GI\' and oc."user_id"=' . $user_id . '
        ';
        $rs = odbc_exec($connect, $sql);
        if (!$rs) {
            exit("Error in SQL");
        }
        while (odbc_fetch_row($rs)) {
            $arr[] = [
                "U_WhsCode" => odbc_result($rs, "U_WhsCode"),
            ];
        }
        foreach ($arr as $item) {
            $result[] = $item['U_WhsCode'];
        }
        return response()->json([
            "items" => $result
        ]);
    }


    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getWarehouse(Request $request): \Illuminate\Http\JsonResponse
    {
        $company_db = "IMIP_TEST_1217";
        $user_id = $request->user()->user_id;
        $user_wh = UserWhs::where("user_id", "=", $user_id)
            ->where("U_DbCode", "=", $company_db)->get();
        $result = [];
        $data = [];
        foreach ($user_wh as $item) {
            // $data=['WarehouseCode'=>$item->U_WhsCode,'Locked'=>false,'InStock'=>'0','DefaultWarehouse'=>false];
            // array_push($result, $data);
            $result[] = $item->U_WhsCode;
        }
        return response()->json([
            "result" => $result
        ]);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getWarehousecmb(Request $request): \Illuminate\Http\JsonResponse
    {
        $company_db = "IMIP_TEST_1217";
        $user_id = $request->user()->user_id;
        $user_wh = UserWhs::where("user_id", "=", $user_id)
            ->where("U_DbCode", "=", $company_db)->get();
        $result = [];
        $data = [];
        foreach ($user_wh as $item) {
            $data = ['WarehouseCode' => $item->U_WhsCode, 'Locked' => false, 'InStock' => '0', 'DefaultWarehouse' => false];
            array_push($result, $data);
        }
        return response()->json([
            "result" => $result
        ]);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getWarehouseindex(Request $request): \Illuminate\Http\JsonResponse
    {
        $company_db = "IMIP_TEST_1217";
        $user_id = $request->user()->user_id;
        $user_wh = UserWhs::where("user_id", $user_id)->where("U_DbCode", "=", $company_db)->get();
        $result = [];
        $data = [];
        foreach ($user_wh as $item) {
            $result[] = ['WarehouseCode' => $item->U_WhsCode, 'WarehouseName' => $item->U_WhsCode];
            // array_push($result, $data);
        }
        return response()->json([
            "items" => $result
        ]);
    }

    /**
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function userCompany(Request $request): \Illuminate\Http\JsonResponse
    {
        $options = json_decode($request->options);
        $user = json_decode($request->user);
        $year_local = date('Y');
        $pages = isset($options->page) ? (int)$options->page : 1;
        $filter = isset($request->filter) ? (string)$request->filter : $year_local;
        $row_data = isset($options->itemsPerPage) ? (int)$options->itemsPerPage : 1000;
        $sorts = isset($options->sortBy[0]) ? (string)$options->sortBy[0] : "U_DbCode";
        $order = isset($options->sortDesc[0]) ? "DESC" : "ASC";

        $search = isset($request->q) ? (string)$request->q : "";
        $type = isset($request->type) ? $request->type : null;
        $select_data = isset($request->selectData) ? (string)$request->selectData : "DocNum";
        $offset = ($pages - 1) * $row_data;
        $username = $request->user()->U_UserCode;

        $result = array();
        $query = UserCompany::selectRaw("*, 'actions' as actions")
            ->where("user_id", "=", $user->user_id)
            ->orderBy($sorts, $order);
//        $user = isset($request->user) ? $request->user : null;
//        $query->whereRaw('"U_DbCode NOT" IN (SELECT "U_DbCode" FROM OUSR_COMP WHERE user_id=\'' . $user . '\' )');

        $result["total"] = $query->count();
        $all_data = $query->offset($offset)
            ->limit($row_data)
            ->get();

        $result = array_merge($result, [
            "rows" => $all_data,
            "department" => $all_data,
            "filter" => ['All'],
        ]);
        return response()->json($result);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addCompany(Request $request): \Illuminate\Http\JsonResponse
    {
        $form = $request->form;
        $user = $request->user;
        foreach ($form as $item) {
            $add_company = $this->postAddCompany($user, $item);
            if ($add_company['error']) {
                return response()->json([
                    "error" => true,
                    "message" => $add_company['message'],
                    "trace" => $add_company['trace'],
                ]);
            }
        }

        return response()->json([
            "error" => false,
            "message" => "Company saved!",
        ]);
    }

    /**
     * @param $user
     * @param $form
     * @return false[]
     */
    protected function postAddCompany($user, $form): array
    {
        try {
            $doc_entry = UserCompany::orderBy("U_DocEntry", "DESC")->first();
            $doc_entry = ($doc_entry) ? $doc_entry->U_DocEntry : 0;

            $company = new UserCompany();
            $company->user_id = $user['user_id'];
            $company->U_DbCode = $form['U_DbCode'];
            $company->U_DocEntry = ($doc_entry + 1);
            $company->save();

            return [
                'error' => false,
                'message' => 'Data saved!'
            ];
        } catch (\Exception $exception) {
            return [
                'error' => true,
                'message' => $exception->getMessage(),
                'trace' => $exception->getTrace()
            ];
        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeCompany(Request $request): \Illuminate\Http\JsonResponse
    {
        $form = $request->form;
        foreach ($form as $item) {
            $remove_company = $this->postRemoveCompany($item);

            if ($remove_company['error']) {
                return response()->json([
                    "error" => true,
                    "message" => $remove_company['message'],
                ]);
            }
        }
        return response()->json([
            "error" => true,
            "message" => "Company removed!",
        ]);
    }

    /**
     * @param $form
     * @return array
     */
    protected function postRemoveCompany($form): array
    {
        try {
            $company = UserCompany::where("U_DocEntry", "=", $form['U_DocEntry'])->first();
            if ($company) {
                UserCompany::where("U_DocEntry", "=", $form['U_DocEntry'])->delete();
                return [
                    "error" => false,
                    "message" => "Company removed!",
                ];
            }
            return [
                "error" => true,
                "message" => "Cannot find company!",
            ];
        } catch (\Exception $exception) {
            return [
                "error" => true,
                "message" => $exception->getMessage(),
                "trace" => $exception->getTrace(),
            ];
        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function userMenu(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = json_decode($request->user);
        $year_local = date('Y');
        $result = [];
        $parents = DB::table("U_MENU_OUSR")
            ->leftJoin("U_MENU", "U_MENU.U_DocEntry", "U_MENU_OUSR.MenuId")
            ->where("U_MENU.ParentId", "=", "0")
            ->where("U_MENU_OUSR.UserId", "=", $user->user_id)
            ->select("U_MENU.*", "U_MENU_OUSR.U_DocEntry AS MenuEntry")
            ->orderBy("U_MENU.Position", "ASC")
            ->get();
        $menu_arr = [];
        foreach ($parents as $parent) {
            $children = $this->getChildMenu($parent['U_DocEntry'], $user);
            $menu_arr[] = [
                'icon' => $parent['Icon'],
                'id' => $parent['U_DocEntry'],
                'icon-alt' => $parent['IconAlt'],
                'text' => $parent['Title'],
                'name' => $parent['Title'],
                'docEntry' => $parent['MenuEntry'],
                'model' => false,
                'children' => $children
            ];
        }

        $result = array_merge($result, [
            "rows" => $menu_arr,
            "filter" => ['All'],
        ]);
        return response()->json($result);
    }


    // Menu

    /**
     * @param $parent_id
     * @return array
     */
    protected function getChildMenu($parent_id, $user): array
    {
        $menu_arr = [];
        $children = DB::table("U_MENU_OUSR")
            ->leftJoin("U_MENU", "U_MENU.U_DocEntry", "U_MENU_OUSR.MenuId")
            ->where("U_MENU.ParentId", "=", $parent_id)
            ->where("U_MENU_OUSR.UserId", "=", $user->user_id)
            ->select("U_MENU.*", "U_MENU_OUSR.U_DocEntry AS MenuEntry")
            ->orderBy("U_MENU.Position", "ASC")
            ->get();

        foreach ($children as $child) {
            $menu_arr[] = [
                'icon' => $child['Icon'],
                'id' => $child['U_DocEntry'],
                'icon-alt' => $child['IconAlt'],
                'text' => $child['Title'],
                'name' => $child['Title'],
                'docEntry' => $child['MenuEntry'],
                'parent_id' => $child['ParentId'],
                'route' => $child['Route'],
                'model' => false
            ];
        }
        return $menu_arr;
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addMenu(Request $request): \Illuminate\Http\JsonResponse
    {
        $form = $request->form;
        $user = $request->user;
//        $parent_id = [];
//        foreach ($form as $item) {
//            $parent_id[] = $item['parent_id'];
//        }
//
//        $item_parent = array_values(array_unique($parent_id));
//        $parents = Menu::whereIn("U_DocEntry", $item_parent)->get();
//        foreach ($parents as $parent) {
//            $data = new UserMenu();
//            $data->UserId = $user['user_id'];
//            $data->MenuId = $form['id'];
//            $data->CreatedBy = Auth::user()->user_id;
//            $data->save();
//        }

        foreach ($form as $item) {
            $find_menu = UserMenu::where("UserId", "=", $user['user_id'])
                ->where("MenuId", "=", $item['id'])
                ->count();
            if ($find_menu == 0) {
                $add_company = $this->postAddMenu($user, $item);
                if ($add_company['error']) {
                    return response()->json([
                        "error" => true,
                        "message" => $add_company['message'],
                        "trace" => $add_company['trace'],
                    ]);
                }
            }
        }

        return response()->json([
            "error" => false,
            "message" => "Company saved!",
        ]);
    }

    /**
     * @param $user
     * @param $form
     * @return false[]
     */
    protected function postAddMenu($user, $form): array
    {
        try {
            $data = new UserMenu();
            $data->UserId = $user['user_id'];
            $data->MenuId = $form['id'];
            $data->CreatedBy = Auth::user()->user_id;
            $data->save();

            return [
                'error' => false,
                'message' => 'Data saved!'
            ];
        } catch (\Exception $exception) {
            return [
                'error' => true,
                'message' => $exception->getMessage(),
                'trace' => $exception->getTrace()
            ];
        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeMenu(Request $request): \Illuminate\Http\JsonResponse
    {
        $form = $request->form;
        foreach ($form as $item) {
            $remove_company = $this->postRemoveMenu($item);

            if ($remove_company['error']) {
                return response()->json([
                    "error" => true,
                    "message" => $remove_company['message'],
                ]);
            }
        }
        return response()->json([
            "error" => true,
            "message" => "Company removed!",
        ]);
    }

    /**
     * @param $form
     * @return array
     */
    protected function postRemoveMenu($form): array
    {
        try {
            $data = UserMenu::where("U_DocEntry", "=", $form['docEntry'])->first();
            if ($data) {
                UserMenu::where("U_DocEntry", "=", $form['docEntry'])->delete();
                return [
                    "error" => false,
                    "message" => "Company removed!",
                ];
            }
            return [
                "error" => true,
                "message" => "Cannot find company!",
            ];
        } catch (\Exception $exception) {
            return [
                "error" => true,
                "message" => $exception->getMessage(),
                "trace" => $exception->getTrace(),
            ];
        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function itemGroups(Request $request): \Illuminate\Http\JsonResponse
    {
        $options = json_decode($request->options);
        $user = json_decode($request->user);
        $company = json_decode($request->company);
        $year_local = date('Y');
        $pages = isset($options->page) ? (int)$options->page : 1;
        $filter = isset($request->filter) ? (string)$request->filter : $year_local;
        $row_data = isset($options->itemsPerPage) ? (int)$options->itemsPerPage : 5;
        $sorts = isset($options->sortBy[0]) ? (string)$options->sortBy[0] : "U_DbCode";
        $order = isset($options->sortDesc[0]) ? "DESC" : "ASC";

        $type = isset($request->type) ? $request->type : null;
        $offset = ($pages - 1) * $row_data;
        $connect = $this->connectHana();
        $db_name = $company->U_DbCode;

        $result = array();
        $sql_count = '
					SELECT COUNT(*) AS "CountData" from ' . $db_name . '.OITB
				';

        $rs = odbc_exec($connect, $sql_count);
        $arr = odbc_fetch_array($rs);
        $result["total"] = (int)$arr['CountData'];

        $sql = '
					select "ItmsGrpNam", "ItmsGrpCod",
					       ROW_NUMBER() OVER (PARTITION BY "ItmsGrpCod" ORDER BY "ItmsGrpCod" DESC) AS "LineNum"
					from ' . $db_name . '.OITB
				';

//        $sql .= ' LIMIT ' . $row_data . '
//                    OFFSET ' . $offset . '
//                    ';

        $rs = odbc_exec($connect, $sql);

        if (!$rs) {
            exit("Error in SQL");
        }
        $arr = [];
        $index = 1;
        while (odbc_fetch_row($rs)) {
            $arr[] = [
                "ItmsGrpNam" => odbc_result($rs, "ItmsGrpNam"),
                "ItmsGrpCod" => odbc_result($rs, "ItmsGrpCod"),
                "LineNum" => $index,
            ];
            $index++;
        }

        $result = array_merge($result, [
            "rows" => $arr,
            "filter" => ['All'],
        ]);
        return response()->json($result);
    }
    //end Menu

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function UserItmGrps(Request $request)
    {
        $options = json_decode($request->options);
        $user = json_decode($request->user);
        $company = json_decode($request->company);
        $year_local = date('Y');
        $pages = isset($options->page) ? (int)$options->page : 1;
        $filter = isset($request->filter) ? (string)$request->filter : $year_local;
        $row_data = isset($options->itemsPerPage) ? (int)$options->itemsPerPage : 5;
        $sorts = isset($options->sortBy[0]) ? (string)$options->sortBy[0] : "U_ItmsGrpCod";
        $order = isset($options->sortDesc[0]) ? "DESC" : "ASC";

        $search = isset($request->q) ? (string)$request->q : "";
        $type = isset($request->type) ? $request->type : null;
        $offset = ($pages - 1) * $row_data;
        $username = $request->user()->U_UserCode;

        $result = array();
        $query = UserItmGrp::selectRaw("*")
            ->where("user_id", "=", $user->user_id)
            ->where("U_DbCode", "=", $company->U_DbCode)
            ->orderBy($sorts, $order);

        $result["total"] = $query->count();
        $all_data = $query
//            ->offset($offset)
//            ->limit($row_data)
            ->get();

        $result = array_merge($result, [
            "rows" => $all_data,
            "department" => $all_data,
            "filter" => ['All'],
        ]);
        return response()->json($result);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addItemGroups(Request $request): \Illuminate\Http\JsonResponse
    {
        $form = $request->form;
        $user = $request->user;
        $company = $request->company;
        foreach ($form as $item) {
            $check_data = UserItmGrp::where("user_id", "=", $user['user_id'])
                ->where("U_ItmsGrpCod", "=", $item['ItmsGrpCod'])
                ->where("U_DbCode", "=", $company['U_DbCode'])
                ->count();
            if ($check_data == 0) {
                $add_item = $this->postAddItemGroups($user, $item, $company);
                if ($add_item['error']) {
                    return response()->json([
                        "error" => true,
                        "message" => $add_item['message'],
                        "trace" => $add_item['trace'],
                    ]);
                }
            }
        }

        return response()->json([
            "error" => false,
            "message" => "Company saved!",
        ]);
    }

    /**
     * @param $user
     * @param $form
     * @param $company
     * @return false[]
     */
    protected function postAddItemGroups($user, $form, $company): array
    {
        try {
            $doc_entry = UserItmGrp::orderBy("U_DocEntry", "DESC")->first();
            $doc_entry = ($doc_entry) ? $doc_entry->U_DocEntry : 0;

            $item = new UserItmGrp();
            $item->user_id = $user['user_id'];
            $item->U_ItmsGrpCod = $form['ItmsGrpCod'];
            $item->U_DbCode = $company['U_DbCode'];
            $item->U_DocEntry = ($doc_entry + 1);
            $item->save();

            return [
                'error' => false,
                'message' => 'Data saved!'
            ];
        } catch (\Exception $exception) {
            return [
                'error' => true,
                'message' => $exception->getMessage(),
                'trace' => $exception->getTrace()
            ];
        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeItemGroups(Request $request): \Illuminate\Http\JsonResponse
    {
        $form = $request->form;
        foreach ($form as $item) {
            $remove_company = $this->postRemoveItemGroups($item);

            if ($remove_company['error']) {
                return response()->json([
                    "error" => true,
                    "message" => $remove_company['message'],
                ]);
            }
        }
        return response()->json([
            "error" => true,
            "message" => "Company removed!",
        ]);
    }

    /**
     * @param $form
     * @return array
     */
    protected function postRemoveItemGroups($form): array
    {
        try {
            $company = UserItmGrp::where("U_DocEntry", "=", $form['U_DocEntry'])->first();
            if ($company) {
                UserItmGrp::where("U_DocEntry", "=", $form['U_DocEntry'])->delete();
                return [
                    "error" => false,
                    "message" => "Company removed!",
                ];
            }
            return [
                "error" => true,
                "message" => "Cannot find company!",
            ];
        } catch (\Exception $exception) {
            return [
                "error" => true,
                "message" => $exception->getMessage(),
                "trace" => $exception->getTrace(),
            ];
        }
    }

    /**
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getWhs(Request $request): \Illuminate\Http\JsonResponse
    {
        $options = json_decode($request->options);
        $user = json_decode($request->user);
        $company = json_decode($request->company);
        $year_local = date('Y');
        $pages = isset($options->page) ? (int)$options->page : 1;
        $filter = isset($request->filter) ? (string)$request->filter : $year_local;
        $row_data = isset($options->itemsPerPage) ? (int)$options->itemsPerPage : 5;
        $sorts = isset($options->sortBy[0]) ? (string)$options->sortBy[0] : "U_DbCode";
        $order = isset($options->sortDesc[0]) ? "DESC" : "ASC";

        $type = isset($request->type) ? $request->type : null;
        $offset = ($pages - 1) * $row_data;
        $connect = $this->connectHana();
        $db_name = $company->U_DbCode;

        $result = array();
        $sql_count = '
					SELECT COUNT(*) AS "CountData" from ' . $db_name . '.OWHS
				';

        $rs = odbc_exec($connect, $sql_count);
        $arr = odbc_fetch_array($rs);
        $result["total"] = (int)$arr['CountData'];

        $sql = '
					select "WhsCode", "WhsName",
					       ROW_NUMBER() OVER (PARTITION BY "WhsCode" ORDER BY "WhsCode" DESC) AS "LineNum"
					from ' . $db_name . '.OWHS
				';

//        $sql .= ' LIMIT ' . $row_data . '
//                    OFFSET ' . $offset . '
//                    ';

        $rs = odbc_exec($connect, $sql);

        if (!$rs) {
            exit("Error in SQL");
        }
        $arr = [];
        $index = 1;
        while (odbc_fetch_row($rs)) {
            $arr[] = [
                "WhsCode" => odbc_result($rs, "WhsCode"),
                "WhsName" => odbc_result($rs, "WhsName"),
                "LineNum" => $index,
            ];
            $index++;
        }

        $result = array_merge($result, [
            "rows" => $arr,
            "filter" => ['All'],
        ]);
        return response()->json($result);
    }

    /**
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function userWhs(Request $request): \Illuminate\Http\JsonResponse
    {
        $options = json_decode($request->options);
        $user = json_decode($request->user);
        $company = json_decode($request->company);
        $year_local = date('Y');
        $pages = isset($options->page) ? (int)$options->page : 1;
        $filter = isset($request->filter) ? (string)$request->filter : $year_local;
        $row_data = isset($options->itemsPerPage) ? (int)$options->itemsPerPage : 5;
        $sorts = isset($options->sortBy[0]) ? (string)$options->sortBy[0] : "U_DocEntry";
        $order = isset($options->sortDesc[0]) ? "DESC" : "ASC";

        $search = isset($request->q) ? (string)$request->q : "";
        $type = isset($request->type) ? $request->type : null;
        $offset = ($pages - 1) * $row_data;
        $username = $request->user()->U_UserCode;

        $result = array();
        $query = UserWhs::selectRaw('*')
            ->where("user_id", "=", $user->user_id)
            ->where("U_DbCode", "=", $company->U_DbCode)
            ->orderBy($sorts, $order);

        $result["total"] = $query->count();
        $all_data = $query
//            ->offset($offset)
//            ->limit($row_data)
            ->get();

        $result = array_merge($result, [
            "rows" => $all_data,
            "department" => $all_data,
            "filter" => ['All'],
        ]);
        return response()->json($result);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addWhs(Request $request): \Illuminate\Http\JsonResponse
    {
        $form = $request->form;
        $user = $request->user;
        $company = $request->company;
        foreach ($form as $item) {
            $check_data = UserWhs::where("user_id", "=", $user['user_id'])
                ->where("U_WhsCode", "=", $item['WhsCode'])
                ->where("U_DbCode", "=", $company['U_DbCode'])
                ->count();
            if ($check_data == 0) {
                $add_item = $this->postAddWhs($user, $item, $company);
                if ($add_item['error']) {
                    return response()->json([
                        "error" => true,
                        "message" => $add_item['message'],
                        "trace" => $add_item['trace'],
                    ]);
                }
            }
        }

        return response()->json([
            "error" => false,
            "message" => "Company saved!",
        ]);
    }

    /**
     * @param $user
     * @param $form
     * @param $company
     * @return false[]
     */
    protected function postAddWhs($user, $form, $company): array
    {
        try {
            $doc_entry = UserWhs::orderBy("U_DocEntry", "DESC")->first();
            $doc_entry = ($doc_entry) ? $doc_entry->U_DocEntry : 0;

            $item = new UserWhs();
            $item->user_id = $user['user_id'];
            $item->U_WhsCode = $form['WhsCode'];
            $item->U_DbCode = $company['U_DbCode'];
            $item->U_DocEntry = ($doc_entry + 1);
            $item->save();

            return [
                'error' => false,
                'message' => 'Data saved!'
            ];
        } catch (\Exception $exception) {
            return [
                'error' => true,
                'message' => $exception->getMessage(),
                'trace' => $exception->getTrace()
            ];
        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeWhs(Request $request): \Illuminate\Http\JsonResponse
    {
        $form = $request->form;
        foreach ($form as $item) {
            $remove_item = $this->postRemoveWhs($item);

            if ($remove_item['error']) {
                return response()->json([
                    "error" => true,
                    "message" => $remove_item['message'],
                ]);
            }
        }
        return response()->json([
            "error" => true,
            "message" => "Company removed!",
        ]);
    }

    /**
     * @param $form
     * @return array
     */
    protected function postRemoveWhs($form): array
    {
        try {
            $company = UserWhs::where("U_DocEntry", "=", $form['U_DocEntry'])->first();
            if ($company) {
                UserWhs::where("U_DocEntry", "=", $form['U_DocEntry'])->delete();
                return [
                    "error" => false,
                    "message" => "Company removed!",
                ];
            }
            return [
                "error" => true,
                "message" => "Cannot find company!",
            ];
        } catch (\Exception $exception) {
            return [
                "error" => true,
                "message" => $exception->getMessage(),
                "trace" => $exception->getTrace(),
            ];
        }
    }
}
