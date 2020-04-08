<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use App\Type;
use App\Category;
use App\Subscription;
use Session;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set("Asia/Kolkata");
        $this->middleware('auth.basic');
        $this->middleware(function ($request, $next) {
            $this->user = Auth::user();
            $this->admin = Auth::guard('admin')->user();
            $this->employee = Auth::guard('employee')->user();
            return $next($request);
        });
    }

    public function getSale()
    {
        $location_data = $employee_data = '';
        if (Auth::guard('admin')->check()) {
            $cid = $this->admin->rid;
            if ($this->admin->location == "multiple") {
                $location_data = \App\EnquiryLocation::select('*')->where(['cid' => $cid, 'is_active' => 0])->get();
            }

            $employee_data = \App\Employee::select('*')->where(['cid' => $cid, 'is_active' => 0])->get();
            //          echo "<pre/>";print_r();exit;
        }
        if (Auth::guard('employee')->check()) {
            $cid = $this->employee->cid;
            $lid = $this->employee->lid;
            $role = $this->employee->role;
            if ($role == 1) {
                $employee_data = \App\Employee::select('*')->where(['cid' => $cid, 'lid' => $lid, 'is_active' => 0, 'role' => 2])->get();
            }
        }

        //echo "<pre>"; print_r($employee_data); echo '</pre>'; exit;
        return view('reports.sale_report', ['location_data' => $location_data, 'employee_data' => $employee_data]);
    }
    public function fetchSale(Request $request)
    {
        $requestData = $request->all();
        $total_amount = 0;
        $result = array();
        $from_date = $requestData["from_date"];
        if (!empty($requestData["to_date"]))
            $to_date = $requestData["to_date"];
        else
            $to_date = $from_date;

        $from_date = date($from_date . ' 00:00:00', time());
        $to_date   = date($to_date . ' 23:59:00', time());

        if (Auth::guard('admin')->check()) {
            $cid = $this->admin->rid;
            if (isset($requestData['location'])) {

                $lid = $requestData['location'];
                //  echo $lid;exit;
                if ($lid == "all") {
                    if (isset($requestData['employee'])) {
                        $bill_data = DB::table('bil_AddBillMaster')
                            ->select('*')
                            ->whereBetween('bill_date', [$from_date, $to_date])
                            ->where(['cid' => $cid, 'emp_id' => $requestData['employee'], 'isactive' => 0])
                            ->orderBy('bill_date')
                            ->orderBy('bill_no')
                            ->get();
                    } else {
                        $bill_data = DB::table('bil_AddBillMaster')
                            ->select('*')
                            ->whereBetween('bill_date', [$from_date, $to_date])
                            ->where(['cid' => $cid, 'isactive' => 0])
                            ->orderBy('bill_date')
                            ->orderBy('bill_no')
                            ->get();
                    }
                } else {
                    if (isset($requestData['employee'])) {
                        $bill_data = DB::table('bil_AddBillMaster')
                            ->select('*')
                            ->whereBetween('bill_date', [$from_date, $to_date])
                            ->where(['cid' => $cid, 'lid' => $lid, 'emp_id' => $requestData['employee'], 'isactive' => 0])
                            ->orderBy('bill_date')
                            ->orderBy('bill_no')
                            ->get();
                    } else {
                        $bill_data = DB::table('bil_AddBillMaster')
                            ->select('*')
                            ->whereBetween('bill_date', [$from_date, $to_date])
                            ->where(['cid' => $cid, 'lid' => $lid, 'isactive' => 0])
                            ->orderBy('bill_date')
                            ->orderBy('bill_no')
                            ->get();
                    }
                }
            } else {
                if (isset($requestData['employee'])) {
                    $bill_data = DB::table('bil_AddBillMaster')
                        ->select('*')
                        ->whereBetween('bill_date', [$from_date, $to_date])
                        ->where(['cid' => $cid, 'emp_id' => $requestData['employee'], 'isactive' => 0])
                        ->orderBy('bill_date')
                        ->orderBy('bill_no')
                        ->get();
                } else {
                    //   echo "in else".$from_date."&".$to_date;
                    $bill_data = DB::table('bil_AddBillMaster')
                        ->select('*')
                        ->whereBetween('bill_date', [$from_date, $to_date])
                        ->where(['cid' => $cid, 'isactive' => 0])
                        ->orderBy('bill_date')
                        ->orderBy('bill_no')
                        ->get();
                }
            }
        } else if (Auth::guard('web')->check()) {
            $bill_data = DB::table('bil_AddBillMaster')
                ->select('*')
                ->whereBetween('bill_date', [$from_date, $to_date])
                ->orderBy('bill_date')
                ->orderBy('bill_no')
                ->get();
        } else if (Auth::guard('employee')->check()) {
            $cid = $this->employee->cid;
            $lid = $this->employee->lid;
            $emp_id = $this->employee->id;
            $role = $this->employee->role;
            $sub_emp_id = $this->employee->sub_emp_id;
            $client_data = \App\Admin::select('location')->where(['rid' => $cid])->first();

            // echo $client_data->location."".$role;exit;
            if ($client_data->location == "single" && $role == 2) {
                $bill_data = DB::table('bil_AddBillMaster')
                    ->select('*')
                    ->whereBetween('bill_date', [$from_date, $to_date])
                    ->where(['cid' => $cid, 'isactive' => 0])
                    ->orderBy('bill_date')
                    ->orderBy('bill_no')
                    ->get();
            } else if ($client_data->location == "multiple" && $role == 2) {
                //                echo "Lid".$lid."<br/>CID: ".$cid."<br/>Emp ID: ".$emp_id."<br/>Sub Emp ID: ".$sub_emp_id."<br>";
                //                    echo "in if";echo $emp_id;
                if ($sub_emp_id != "") {
                    //   echo "in sub if";

                    $bill_data = DB::table('bil_AddBillMaster')
                        ->select('*')
                        ->where(['cid' => $cid, 'lid' => $lid, 'isactive' => 0])
                        ->whereBetween('bill_date', [$from_date, $to_date])
                        ->orderBy('bill_date')
                        ->orderBy('bill_no')
                        ->get();
                } else {
                    $bill_data = DB::table('bil_AddBillMaster')
                        ->select('*')
                        ->whereBetween('bill_date', [$from_date, $to_date])
                        ->where(['cid' => $cid, 'lid' => $lid, 'isactive' => 0])
                        ->orderBy('bill_date')
                        ->orderBy('bill_no')
                        ->get();
                }
            } else if ($client_data->location == "multiple" && $role == 1) {
                $bill_data = DB::table('bil_AddBillMaster')
                    ->select('*')
                    ->whereBetween('bill_date', [$from_date, $to_date])
                    ->where(['cid' => $cid, 'lid' => $lid, 'isactive' => 0])
                    ->orderBy('bill_date')
                    ->orderBy('bill_no')
                    ->get();
            }
        }
        $i = 1;
        $result_final = array();
        // echo "<pre/>";print_r($bill_data);exit;
        foreach ($bill_data as $data) {
            $total_amount = $total_amount + $data->bill_totalamt;
            $result_data['bill_no'] = $data->bill_no;
            if ($data->bill_code == NULL)
                $data->bill_code = "";
            $result_data['order_no'] = $data->bill_code;
            $customer_data = \App\Customer::select('*')->where(['cust_id' => $data->cust_id])->first();
            if (!empty($customer_data)) {
                $result_data['cust_name'] = $customer_data->cust_name;
            } else {
                if ($data->cust_name == NULL)
                    $data->cust_name = "";
                $result_data['cust_name'] = $data->cust_name;
            }

            $result_data['bill_totalamt'] = $data->bill_totalamt;
            $result_data['cash_or_credit'] = $data->cash_or_credit;
            //echo $data->point_of_contact;
            $point_of_data = \App\PointOfContact::select('*')->where(['id' => $data->point_of_contact])->first();

            if (!empty($point_of_data))
                $result_data['point_of_contact'] = $point_of_data->point_of_contact;
            else
                $result_data['point_of_contact'] = '';
            // echo "<pre/>";            print_r($result_data);
            if (isset($data->lid)) {
                $location_data = \App\EnquiryLocation::select('*')->where(['loc_id' => $data->lid])->first();
                $result_data['loc_name'] = $location_data->loc_name;
            } else {
                $result_data['loc_name'] = 'Own';
            }
            //	 echo "CID=".$data->cid;
            //	 echo "LID=".$data->lid;
            // echo $data->emp_id;
            $user_data = \App\Employee::select('*')->where(['cid' => $data->cid, 'lid' => $data->lid, 'id' => $data->emp_id])->first();
            //echo "<pre/>";print_r($user_data);exit;
            if (!empty($user_data)) {

                $result_data['user'] = $user_data->name;
            } else {
                $admin_data = \App\Admin::select('*')->where(['rid' => $data->cid])->first();
                $result_data['user'] = $admin_data->reg_personname;
            }
            $result_data['date'] = $data->bill_date;
            array_push($result_final, $result_data);
        }
        // exit;
        $result['amount'] = round($total_amount, 2);
        $result['other_data'] = $result_final;
        echo json_encode($result);
    }
    public function getEmployee()
    {
        $lid = $_GET['location'];
        $sdata = '';
        if (Auth::guard('admin')->check()) {
            $cid = $this->admin->rid;
        }
        if ($lid == "all")
            $result_data = \App\Employee::select('*')->where(['cid' => $cid, 'is_active' => '0'])->get();
        else
            $result_data = \App\Employee::select('*')->where(['lid' => $lid, 'is_active' => '0'])->get();
        $sdata .= '<option value="">---Select Employee---</option>';
        foreach ($result_data as $data) {
            $sdata .= '<option value="' . $data->id . '">' . $data->name . '</option>';
        }
        echo $sdata;
    }

    public function downloadSale(Request $request)
    {
        $requestData = $request->all();
        $from_date = $requestData["from_date"];

        if (!empty($requestData["to_date"]))
            $to_date = $requestData["to_date"];
        else
            $to_date = $from_date;

        $from_date = date($from_date . ' 00:00:00', time());
        $to_date   = date($to_date . ' 23:59:00', time());

        if (Auth::guard('admin')->check()) {
            $cid = $this->admin->rid;
            $cid = $this->admin->rid;
            if (isset($requestData['location'])) {

                $lid = $requestData['location'];
                //  echo $lid;exit;
                if ($lid == "all") {
                    if (isset($requestData['employee'])) {
                        $bill_data = DB::table('bil_AddBillMaster')
                            ->select('*')
                            ->whereBetween('bill_date', [$from_date, $to_date])
                            ->where(['cid' => $cid, 'emp_id' => $requestData['employee'], 'isactive' => 0])
                            ->orderBy('bill_date')
                            ->orderBy('bill_no')
                            ->get();
                    } else {
                        $bill_data = DB::table('bil_AddBillMaster')
                            ->select('*')
                            ->whereBetween('bill_date', [$from_date, $to_date])
                            ->where(['cid' => $cid, 'isactive' => 0])
                            ->orderBy('bill_date')
                            ->orderBy('bill_no')
                            ->get();
                    }
                } else {
                    $bill_data = DB::table('bil_AddBillMaster')
                        ->select('*')
                        ->whereBetween('bill_date', [$from_date, $to_date])
                        ->where(['cid' => $cid, 'lid' => $lid, 'isactive' => 0])
                        ->orderBy('bill_date')
                        ->orderBy('bill_no')
                        ->get();
                }
            } else {
                if (isset($requestData['employee'])) {
                    $bill_data = DB::table('bil_AddBillMaster')
                        ->select('*')
                        ->whereBetween('bill_date', [$from_date, $to_date])
                        ->where(['cid' => $cid, 'emp_id' => $requestData['employee'], 'isactive' => 0])
                        ->orderBy('bill_date')
                        ->orderBy('bill_no')
                        ->get();
                } else {
                    $bill_data = DB::table('bil_AddBillMaster')
                        ->select('*')
                        ->whereBetween('bill_date', [$from_date, $to_date])
                        ->where(['cid' => $cid, 'isactive' => 0])
                        ->orderBy('bill_date')
                        ->orderBy('bill_no')
                        ->get();
                }
            }
        } else if (Auth::guard('web')->check()) {
            $bill_data = DB::table('bil_AddBillMaster')
                ->select('*')
                ->whereBetween('bill_date', [$from_date, $to_date])
                ->orderBy('bill_date')
                ->orderBy('bill_no')
                ->get();
        } else if (Auth::guard('employee')->check()) {
            $cid = $this->employee->cid;
            $lid = $this->employee->lid;
            $emp_id = $this->employee->id;
            $role = $this->employee->role;
            $sub_emp_id = $this->employee->sub_emp_id;
            $client_data = \App\Admin::select('location')->where(['rid' => $cid])->first();

            // echo $client_data->location."".$role;exit;
            if ($client_data->location == "single" && $role == 2) {
                $bill_data = DB::table('bil_AddBillMaster')
                    ->select('*')
                    ->whereBetween('bill_date', [$from_date, $to_date])
                    ->where(['cid' => $cid, 'isactive' => 0])
                    ->orderBy('bill_date')
                    ->orderBy('bill_no')
                    ->get();
            } else if ($client_data->location == "multiple" && $role == 2) {
                //                echo "Lid".$lid."<br/>CID: ".$cid."<br/>Emp ID: ".$emp_id."<br/>Sub Emp ID: ".$sub_emp_id."<br>";
                //                    echo "in if";echo $emp_id;
                if ($sub_emp_id != "") {
                    //   echo "in sub if";

                    $bill_data = DB::table('bil_AddBillMaster')
                        ->select('*')
                        ->where(['cid' => $cid, 'lid' => $lid, 'isactive' => 0])
                        ->whereBetween('bill_date', [$from_date, $to_date])
                        ->orderBy('bill_date')
                        ->orderBy('bill_no')
                        ->get();
                } else {
                    $bill_data = DB::table('bil_AddBillMaster')
                        ->select('*')
                        ->whereBetween('bill_date', [$from_date, $to_date])
                        ->where(['cid' => $cid, 'lid' => $lid, 'isactive' => 0])
                        ->orderBy('bill_date')
                        ->orderBy('bill_no')
                        ->get();
                }
            } else if ($client_data->location == "multiple" && $role == 1) {
                $bill_data = DB::table('bil_AddBillMaster')
                    ->select('*')
                    ->whereBetween('bill_date', [$from_date, $to_date])
                    ->where(['cid' => $cid, 'lid' => $lid, 'isactive' => 0])
                    ->orderBy('bill_date')
                    ->orderBy('bill_no')
                    ->get();
            }
        }
        return view('reports.download_sales_report', ['bill_data' => $bill_data]);
    }
    public function getInventory()
    {
        $location_data = $employee_data = '';
        if (Auth::guard('admin')->check()) {
            $cid = $this->admin->rid;
            if ($this->admin->location == "multiple") {
                $location_data = \App\EnquiryLocation::select('*')->where(['cid' => $cid])->get();
            }

            $employee_data = \App\Employee::select('*')->where(['cid' => $cid, 'is_active' => 0])->get();
        }
        return view('reports.inventory_report', ['location_data' => $location_data, 'employee_data' => $employee_data]);
    }

    public function fetchInventory(Request $request)
    {
        $requestData = $request->all();
        $from_date = $requestData["from_date"];
        if (!empty($requestData["to_date"]))
            $to_date = $requestData["to_date"];
        else
            $to_date = $from_date;
        $i = 1;

        $from_date = date($from_date . ' 00:00:00', time());
        $to_date   = date($to_date . ' 23:59:00', time());

        if (Auth::guard('admin')->check()) {
            $cid = $this->admin->rid;
            if (isset($requestData['location'])) {

                $lid = $requestData['location'];
                //  echo $lid;exit;
                if ($lid == "all") {
                    if (isset($requestData['employee'])) {
                        $bill_data = DB::table('bil_inventory')
                            ->select('bil_inventory.*', 'bil_AddItems.item_name as inventoryitemid', 'bil_AddItems.item-stock')
                            ->leftjoin('bil_AddItems', 'bil_AddItems.item_id', '=', 'bil_inventory.inventoryitemid')
                            ->whereBetween('bil_inventory.created_at', [$from_date, $to_date])
                            ->where(['bil_inventory.cid' => $cid, 'bil_inventory.emp_id' => $requestData['employee']])
                            ->get();
                    } else {
                        $bill_data = DB::table('bil_inventory')
                            ->select('bil_inventory.*', 'bil_AddItems.item_name as inventoryitemid', 'bil_AddItems.item_stock')
                            ->leftjoin('bil_AddItems', 'bil_AddItems.item_id', '=', 'bil_inventory.inventoryitemid')
                            ->whereBetween('bil_inventory.created_at', [$from_date, $to_date])
                            ->where(['bil_inventory.cid' => $cid])
                            ->get();
                    }
                } else {
                    if (isset($requestData['employee'])) {
                        $bill_data = DB::table('bil_inventory')
                            ->select('bil_inventory.*', 'bil_AddItems.item_name as inventoryitemid', 'bil_AddItems.item_stock')
                            ->leftjoin('bil_AddItems', 'bil_AddItems.item_id', '=', 'bil_inventory.inventoryitemid')
                            ->whereBetween('bil_inventory.created_at', [$from_date, $to_date])
                            ->where(['bil_inventory.cid' => $cid, 'bil_inventory.lid' => $lid, 'bil_inventory.emp_id' => $requestData['employee']])
                            ->get();
                    } else {
                        $bill_data = DB::table('bil_inventory')
                            ->select('bil_inventory.*', 'bil_AddItems.item_name as inventoryitemid', 'bil_AddItems.item-stock')
                            ->leftjoin('bil_AddItems', 'bil_AddItems.item_id', '=', 'bil_inventory.inventoryitemid')
                            ->whereBetween('bil_inventory.created_at', [$from_date, $to_date])
                            ->where(['bil_inventory.cid' => $cid, 'bil_inventory.lid' => $lid])
                            ->get();
                    }
                }
            } else {
                if (isset($requestData['employee'])) {
                    $bill_data = DB::table('bil_inventory')
                        ->select('bil_inventory.*', 'bil_AddItems.item_name as inventoryitemid', 'bil_AddItems.item_stock')
                        ->leftjoin('bil_AddItems', 'bil_AddItems.item_id', '=', 'bil_inventory.inventoryitemid')
                        ->whereBetween('bil_inventory.created_at', [$from_date, $to_date])
                        ->where(['bil_inventory.cid' => $cid, 'bil_inventory.emp_id' => $requestData['employee']])
                        ->get();
                } else {
                    $bill_data = DB::table('bil_inventory')
                        ->select('bil_inventory.*', 'bil_AddItems.item_name as inventoryitemid', 'bil_AddItems.item_stock')
                        ->leftjoin('bil_AddItems', 'bil_AddItems.item_id', '=', 'bil_inventory.inventoryitemid')
                        ->whereBetween('bil_inventory.created_at', [$from_date, $to_date])
                        ->where(['bil_inventory.cid' => $cid])
                        ->get();
                }
            }
        } else if (Auth::guard('web')->check()) {
            $bill_data = DB::table('bil_inventory')
                ->select('bil_inventory.*', 'bil_AddItems.item_name as inventoryitemid')
                ->leftjoin('bil_AddItems', 'bil_AddItems.item_id', '=', 'bil_inventory.inventoryitemid')
                ->whereBetween('bil_inventory.created_at', [$from_date, $to_date])
                ->get();
        } else if (Auth::guard('employee')->check()) {
            $cid = $this->employee->cid;
            $lid = $this->employee->lid;
            $emp_id = $this->employee->id;
            $role = $this->employee->role;
            $sub_emp_id = $this->employee->sub_emp_id;
            $client_data = \App\Admin::select('location')->where(['rid' => $cid])->first();
            if ($client_data->location == "single" && $role == 2) {
                $bill_data = DB::table('bil_inventory')
                    ->select('bil_inventory.*', 'bil_AddItems.item_name as inventoryitemid')
                    ->leftjoin('bil_AddItems', 'bil_AddItems.item_id', '=', 'bil_inventory.inventoryitemid')
                    ->whereBetween('bil_inventory.created_at', [$from_date, $to_date])
                    ->where(['bil_inventory.cid' => $cid])
                    ->get();
            } else if ($client_data->location == "multiple" && $role == 2) {
                if ($sub_emp_id != "") {
                    $bill_data = DB::table('bil_inventory')
                        ->select('bil_inventory.*', 'bil_AddItems.item_name as inventoryitemid')
                        ->leftjoin('bil_AddItems', 'bil_AddItems.item_id', '=', 'bil_inventory.inventoryitemid')
                        ->whereBetween('bil-inventory.created_at', [$from_date, $to_date])
                        ->where(['bil_inventory.cid' => $cid, 'bil_inventory.lid' => $lid])
                        ->get();
                } else {
                    $bill_data = DB::table('bil_inventory')
                        ->select('bil_inventory.*', 'bil_AddItems.item_name as inventoryitemid')
                        ->leftjoin('bil_AddItems', 'bil_AddItems.item_id', '=', 'bil_inventory.inventoryitemid')
                        ->whereBetween('created_at', [$from_date, $to_date])
                        ->where(['bil_inventory.cid' => $cid, 'bil_inventory.lid' => $lid])
                        ->get();
                }
            } else if ($client_data->location == "multiple" && $role == 1) {
                $bill_data = DB::table('bil_inventory')
                    ->select('bil_inventory.*', 'bil_AddItems.item_name as inventoryitemid')
                    ->leftjoin('bil_AddItems', 'bil_AddItems.item_id', '=', 'bil_inventory.inventoryitemid')
                    ->whereBetween('bil_inventory.created_at', [$from_date, $to_date])
                    ->where(['bil_inventory.cid' => $cid, 'bil_inventory.lid' => $lid])
                    ->get();
            }
        }

        $tdata = '';
        $result_final = array();
        foreach ($bill_data as $data) {
            $supplier_data = \App\Supplier::select('*')->where(['sup_id' => $data->inventorysupid])->first();
            if (!empty($supplier_data))
                $result_data['sup_name'] = $supplier_data->sup_name;
            else
                $result_data['sup_name'] = "";
            $result_data['inventoryitemid'] = $data->inventoryitemid;
            $result_data['inventoryitemquantity'] = $data->inventoryitemquantity;
            $result_data['inventorystatus'] = $data->inventorystatus;
            $result_data['stock'] = $data->item_stock;
            if (isset($requestData['location'])) {
                $location_data = \App\EnquiryLocation::select('*')->where(['loc_id' => $data->lid])->first();
                $result_data['loc_name'] = $location_data->loc_name;
            } else {
                $result_data['loc_name'] = 'Own';
            }
            $user_data = \App\Employee::select('*')->where(['cid' => $data->cid, 'lid' => $data->lid, 'id' => $data->emp_id])->first();
            if (empty($user_data)) {
                $user_data = \App\Admin::select('*')->where(['rid' => $data->cid])->first();
                $result_data['user'] = $user_data->reg_personname;
            } else
                $result_data['user'] = $user_data->name;
            $result_data['date'] = $data->created_at;
            array_push($result_final, $result_data);
            $i++;
        }

        $result['other_data'] = $result_final;
        echo json_encode($result);
    }
    public function DownloadInventory(Request $request)
    {
        $requestData = $request->all();
        $from_date = $requestData["from_date"];
        if (!empty($requestData["to_date"]))
            $to_date = $requestData["to_date"];
        else
            $to_date = $from_date;

        $from_date = date($from_date . ' 00:00:00', time());
        $to_date   = date($to_date . ' 23:59:00', time());

        if (Auth::guard('admin')->check()) {
            $cid = $this->admin->rid;
            if (isset($requestData['location'])) {

                $lid = $requestData['location'];
                //  echo $lid;exit;
                if ($lid == "all") {
                    if (isset($requestData['employee'])) {
                        $bill_data = DB::table('bil_inventory')
                            ->select('bil_inventory.*', 'bil_AddItems.item_name as inventoryitemid', 'bil_AddItems.item_stock')
                            ->leftjoin('bil_AddItems', 'bil_AddItems.item_id', '=', 'bil_inventory.inventoryitemid')
                            ->whereBetween('bil_inventory.created_at', [$from_date, $to_date])
                            ->where(['bil_inventory.cid' => $cid, 'bil_inventory.emp_id' => $requestData['employee']])
                            ->get();
                    } else {
                        $bill_data = DB::table('bil_inventory')
                            ->select('bil_inventory.*', 'bil_AddItems.item_name as inventoryitemid', 'bil_AddItems.item_stock')
                            ->leftjoin('bil_AddItems', 'bil_AddItems.item_id', '=', 'bil_inventory.inventoryitemid')
                            ->whereBetween('bil_inventory.created_at', [$from_date, $to_date])
                            ->where(['bil_inventory.cid' => $cid])
                            ->get();
                    }
                } else {
                    $bill_data = DB::table('bil_inventory')
                        ->select('bil_inventory.*', 'bil_AddItems.item_name as inventoryitemid', 'bil_AddItems.item_stock')
                        ->leftjoin('bil_AddItems', 'bil_AddItems.item_id', '=', 'bil_inventory.inventoryitemid')
                        ->whereBetween('bil_inventory.created_at', [$from_date, $to_date])
                        ->where(['bil_inventory.cid' => $cid, 'bil_inventory.lid' => $lid])
                        ->get();
                }
            } else {
                if (isset($requestData['employee'])) {
                    $bill_data = DB::table('bil_inventory')
                        ->select('bil_inventory.*', 'bil_AddItems.item_name as inventoryitemid', 'bil_AddItems.item_stock')
                        ->leftjoin('bil_AddItems', 'bil_AddItems.item_id', '=', 'bil_inventory.inventoryitemid')
                        ->whereBetween('bil_inventory.created_at', [$from_date, $to_date])
                        ->where(['bil_inventory.cid' => $cid, 'bil_inventory.emp_id' => $requestData['employee']])
                        ->get();
                } else {
                    $bill_data = DB::table('bil_inventory')
                        ->select('bil_inventory.*', 'bil_AddItems.item_name as inventoryitemid', 'bil_AddItems.item_stock')
                        ->leftjoin('bil_AddItems', 'bil_AddItems.item_id', '=', 'bil_inventory.inventoryitemid')
                        ->whereBetween('bil_inventory.created_at', [$from_date, $to_date])
                        ->where(['bil_inventory.cid' => $cid])
                        ->get();
                }
            }
        } else if (Auth::guard('web')->check()) {
            $bill_data = DB::table('bil_inventory')
                ->select('bil_inventory.*', 'bil_AddItems.item_name as inventoryitemid')
                ->leftjoin('bil_AddItems', 'bil_AddItems.item_id', '=', 'bil_inventory.inventoryitemid')
                ->whereBetween('created_at', [$from_date, $to_date])
                ->get();
        } else if (Auth::guard('employee')->check()) {
            $cid = $this->employee->cid;
            $lid = $this->employee->lid;
            $emp_id = $this->employee->id;
            $role = $this->employee->role;
            $sub_emp_id = $this->employee->sub_emp_id;
            $client_data = \App\Admin::select('location')->where(['rid' => $cid])->first();
            //            echo $client_data->location."&".$role;exit;
            if ($client_data->location == "single" && $role == 2) {
                $bill_data = DB::table('bil_inventory')
                    ->select('bil_inventory.*', 'bil_AddItems.item_name as inventoryitemid')
                    ->leftjoin('bil_AddItems', 'bil_AddItems.item_id', '=', 'bil_inventory.inventoryitemid')
                    ->whereBetween('created_at', [$from_date, $to_date])
                    ->where(['bil_inventory.cid' => $cid])
                    ->get();
            } else if ($client_data->location == "multiple" && $role == 2) {
                if ($sub_emp_id != "") {
                    $bill_data = DB::table('bil_inventory')
                        ->select('bil_inventory.*', 'bil_AddItems.item_name as inventoryitemid')
                        ->leftjoin('bil_AddItems', 'bil_AddItems.item_id', '=', 'bil_inventory.inventoryitemid')
                        ->whereBetween('created_at', [$from_date, $to_date])
                        ->where(['bil_inventory.cid' => $cid, 'bil_inventory.lid' => $lid])
                        ->get();
                } else {
                    $bill_data = DB::table('bil_inventory')
                        ->select('bil_inventory.*', 'bil_AddItems.item_name as inventoryitemid')
                        ->leftjoin('bil_AddItems', 'bil_AddItems.item_id', '=', 'bil_inventory.inventoryitemid')
                        ->whereBetween('created_at', [$from_date, $to_date])
                        ->where(['bil_inventory.cid' => $cid, 'bil_inventory.lid' => $lid])
                        ->get();
                }
            } else if ($client_data->location == "multiple" && $role == 1) {
                $bill_data = DB::table('bil_inventory')
                    ->select('bil_inventory.*', 'bil_AddItems.item_name as inventoryitemid')
                    ->leftjoin('bil_AddItems', 'bil_AddItems.item_id', '=', 'bil_inventory.inventoryitemid')
                    ->whereBetween('created_at', [$from_date, $to_date])
                    ->where(['bil_inventory.cid' => $cid, 'bil_inventory.lid' => $lid])
                    ->get();
            }
        }
        //  echo "<pre/>";print_r($inventory_data);exit;

        return view('reports.download_inventory_report', ['inventory_data' => $bill_data]);
    }
    public function getItem()
    {
        if (Auth::guard('admin')->check()) {
            $id = $this->admin->rid;
            $item_data = \App\Item::orderBy('item_name', 'asc')->where(['is_active' => '0', 'cid' => $id])->get();
        } else if (Auth::guard('web')->check()) {
            $item_data = \App\Item::orderBy('item_name', 'asc')->where(['is_active' => '0'])->get();
        } else if (Auth::guard('employee')->check()) {
            $cid = $this->employee->cid;
            $lid = $this->employee->lid;
            $emp_id = $this->employee->id;
            $role = $this->employee->role;
            $sub_emp_id = $this->employee->sub_emp_id;
            $client_data = \App\Admin::select('location')->where(['rid' => $cid])->first();
            if ($client_data->location == "single" && $role == 2) {
                $item_data = \App\Item::where(['is_active' => '0', 'cid' => $cid])->orderBy('item_name', 'asc')->get();
            } else if ($client_data->location == "multiple" && $role == 2) {

                if ($sub_emp_id != "") {
                    $item_data = \App\Item::where(['is_active' => '0', 'cid' => $cid, 'lid' => $lid])
                        ->orWhere(['emp_id' => $sub_emp_id])
                        ->orWhere(['emp_id' => $emp_id])
                        ->orderBy('item_name', 'asc')
                        ->get();
                } else {
                    $item_data = \App\Item::where(['is_active' => '0', 'cid' => $cid])
                        ->orderBy('item_name', 'asc')
                        ->get();
                }
            } else if ($client_data->location == "multiple" && $role == 1) {
                $item_data = \App\Item::where(['is_active' => '0', 'cid' => $cid])
                    ->orderBy('item_name', 'asc')
                    ->get();
            }
        }
        return view('reports.item_report', ['item_data' => $item_data]);
    }
    public function downloadItem(Request $request)
    {
        if (Auth::guard('admin')->check()) {
            $id = $this->admin->rid;
            $item_data = \App\Item::orderBy('item_name', 'asc')->where(['is_active' => '0', 'cid' => $id])->get();
        } else if (Auth::guard('web')->check()) {
            $item_data = \App\Item::orderBy('item_name', 'asc')->where(['is_active' => '0'])->get();
        } else if (Auth::guard('employee')->check()) {
            $cid = $this->employee->cid;
            $lid = $this->employee->lid;
            $emp_id = $this->employee->id;
            $role = $this->employee->role;
            $sub_emp_id = $this->employee->sub_emp_id;
            $client_data = \App\Admin::select('location')->where(['rid' => $cid])->first();
            if ($client_data->location == "single" && $role == 2) {
                $item_data = \App\Item::where(['is_active' => '0', 'cid' => $cid])->orderBy('item_name', 'asc')->get();
            } else if ($client_data->location == "multiple" && $role == 2) {

                if ($sub_emp_id != "") {
                    $item_data = \App\Item::where(['is_active' => '0', 'cid' => $cid, 'lid' => $lid])
                        ->orWhere(['emp_id' => $sub_emp_id])
                        ->orWhere(['emp_id' => $emp_id])
                        ->orderBy('item_name', 'asc')
                        ->get();
                } else {
                    $item_data = \App\Item::where(['is_active' => '0', 'cid' => $cid])
                        ->orderBy('item_name', 'asc')
                        ->get();
                }
            } else if ($client_data->location == "multiple" && $role == 1) {
                $item_data = \App\Item::where(['is_active' => '0', 'cid' => $cid])
                    ->orderBy('item_name', 'asc')
                    ->get();
            }
        }
        return view('reports.download_item_report', ['item_data' => $item_data]);
    }

    public function getItemSale()
    {
        $location_data = $employee_data = '';
        if (Auth::guard('admin')->check()) {
            $cid = $this->admin->rid;
            if ($this->admin->location == "multiple") {
                $location_data = \App\EnquiryLocation::select('*')->where(['cid' => $cid])->get();
            }

            $employee_data = \App\Employee::select('*')->where(['cid' => $cid, 'is_active' => 0])->get();
        }

        if (Auth::guard('employee')->check()) {
            $cid = $this->employee->cid;
            $lid = $this->employee->lid;
            //$employee_data= \App\PointOfContact::select('*')->where(['cid'=>$cid,'lid'=>$lid,'is_active'=>0])->get();
            $employee_data = \App\Employee::select('*')->where(['cid' => $cid, 'lid' => $lid, 'is_active' => 0, 'role' => 2])->get();
        }

        return view('reports.item_sale_report', ['location_data' => $location_data, 'employee_data' => $employee_data]);
    }

    public function fetchItemSale(Request $request)
    {
        $requestData = $request->all();
        $from_date = $requestData["from_date"];
        if (!empty($requestData["to_date"]))
            $to_date = $requestData["to_date"];
        else
            $to_date = $from_date;

        $from_date = date($from_date . ' 00:00:00', time());
        $to_date   = date($to_date . ' 23:59:00', time());
        if (Auth::guard('admin')->check()) {
            $id = $this->admin->rid;
            if (isset($requestData['location'])) {

                $lid = $requestData['location'];
                //  echo $lid;exit;
                if ($lid == "all") {
                    if (isset($requestData['employee'])) {
                        $bill_data = DB::table('bil_AddBillDetail')
                            ->select('item_name')
                            ->selectRaw('sum(item_totalrate) as final_rate')
                            ->selectRaw('avg(item_rate) as total_rate')
                            ->selectRaw('sum(item_qty) as total_qty')
                            ->groupBy('item_name')
                            ->orderBy('item_name')
                            ->whereBetween('created_at_TIMESTAMP', [$from_date, $to_date])
                            ->where(['cid' => $id, 'emp_id' => $requestData['employee'], 'isactive' => 0])
                            ->get();
                    } else {
                        $bill_data = DB::table('bil_AddBillDetail')
                            ->select('item_name')
                            ->selectRaw('sum(item_totalrate) as final_rate')
                            ->selectRaw('avg(item_rate) as total_rate')
                            ->selectRaw('sum(item_qty) as total_qty')
                            ->groupBy('item_name')
                            ->orderBy('item_name')
                            ->whereBetween('created_at_TIMESTAMP', [$from_date, $to_date])
                            ->where(['cid' => $id, 'isactive' => 0])
                            ->get();
                    }
                } else {
                    if (isset($requestData['employee'])) {
                        $bill_data = DB::table('bil_AddBillDetail')
                            ->select('item_name')
                            ->selectRaw('sum(item_totalrate) as final_rate')
                            ->selectRaw('avg(item_rate) as total_rate')
                            ->selectRaw('sum(item_qty) as total_qty')
                            ->groupBy('item_name')
                            ->orderBy('item_name')
                            ->whereBetween('created_at_TIMESTAMP', [$from_date, $to_date])
                            ->where(['cid' => $id, 'lid' => $lid, 'emp_id' => $requestData['employee'], 'isactive' => 0])
                            ->get();
                    } else {
                        $bill_data = DB::table('bil_AddBillDetail')
                            ->select('item_name')
                            ->selectRaw('sum(item_totalrate) as final_rate')
                            ->selectRaw('avg(item_rate) as total_rate')
                            ->selectRaw('sum(item_qty) as total_qty')
                            ->groupBy('item_name')
                            ->orderBy('item_name')
                            ->whereBetween('created_at_TIMESTAMP', [$from_date, $to_date])
                            ->where(['cid' => $id, 'lid' => $lid, 'isactive' => 0])
                            ->get();
                    }
                }
            } else {
                if (isset($requestData['employee'])) {
                    $bill_data = DB::table('bil_AddBillDetail')
                        ->select('item_name')
                        ->selectRaw('sum(item_totalrate) as final_rate')
                        ->selectRaw('avg(item_rate) as total_rate')
                        ->selectRaw('sum(item_qty) as total_qty')
                        ->groupBy('item_name')
                        ->orderBy('item_name')
                        ->where(['cid' => $id, 'emp_id' => $requestData['employee'], 'isactive' => 0])
                        ->get();
                } else {

                    $bill_data = DB::table('bil_AddBillDetail')
                        ->select('item_name')
                        ->selectRaw('sum(item_totalrate) as final_rate')
                        ->selectRaw('avg(item_rate) as total_rate')
                        ->selectRaw('sum(item_qty) as total_qty')
                        ->groupBy('item_name')
                        ->orderBy('item_name')
                        ->whereBetween('created_at_TIMESTAMP', [$from_date, $to_date])
                        ->where(['cid' => $id, 'isactive' => 0])
                        ->get();
                    //  echo "<pre/>";print_r($bill_data);
                }
            }
        } else if (Auth::guard('web')->check()) {
            $bill_data = DB::table('bil_AddBillDetail')
                ->select('item_name')
                ->selectRaw('sum(item_totalrate) as final_rate')
                ->selectRaw('avg(item_rate) as total_rate')
                ->selectRaw('sum(item_qty) as total_qty')
                ->groupBy('item_name')
                ->orderBy('item_name')
                ->whereBetween('created_at_TIMESTAMP', [$from_date, $to_date])
                ->get();
        } else if (Auth::guard('employee')->check()) {
            $cid = $this->employee->cid;
            $lid = $this->employee->lid;
            $emp_id = $this->employee->id;
            $role = $this->employee->role;
            $sub_emp_id = $this->employee->sub_emp_id;
            $client_data = \App\Admin::select('location')->where(['rid' => $cid, 'isactive' => 0])->first();
            if ($client_data->location == "single" && $role == 2) {
                $bill_data = DB::table('bil_AddBillDetail')
                    ->select('item_name')
                    ->selectRaw('sum(item_totalrate) as final_rate')
                    ->selectRaw('avg(item_rate) as total_rate')
                    ->selectRaw('sum(item_qty) as total_qty')
                    ->groupBy('item_name')
                    ->orderBy('item_name')
                    ->whereBetween('created_at_TIMESTAMP', [$from_date, $to_date])
                    ->where(['cid' => $cid, 'isactive' => 0])
                    ->get();
            } else if ($client_data->location == "multiple" && $role == 2) {
                if ($sub_emp_id != "") {
                    $bill_data = DB::table('bil_AddBillDetail')
                        ->select('item_name')
                        ->selectRaw('sum(item_totalrate) as final_rate')
                        ->selectRaw('avg(item_rate) as total_rate')
                        ->selectRaw('sum(item_qty) as total_qty')
                        ->groupBy('item_name')
                        ->orderBy('item_name')
                        ->whereBetween('created_at_TIMESTAMP', [$from_date, $to_date])
                        ->where(['cid' => $cid, 'lid' => $lid, 'isactive' => 0])
                        ->get();
                } else {
                    $bill_data = DB::table('bil_AddBillDetail')
                        ->select('item_name')
                        ->selectRaw('sum(item_totalrate) as final_rate')
                        ->selectRaw('avg(item_rate) as total_rate')
                        ->selectRaw('sum(item_qty) as total_qty')
                        ->groupBy('item_name')
                        ->orderBy('item_name')
                        ->whereBetween('created_at_TIMESTAMP', [$from_date, $to_date])
                        ->where(['cid' => $cid, 'lid' => $lid, 'isactive' => 0])
                        ->get();
                }
            } else if ($client_data->location == "multiple" && $role == 1) {
                $bill_data = DB::table('bil_AddBillDetail')
                    ->select('item_name')
                    ->selectRaw('sum(item_totalrate) as final_rate')
                    ->selectRaw('avg(item_rate) as total_rate')
                    ->selectRaw('sum(item_qty) as total_qty')
                    ->groupBy('item_name')
                    ->orderBy('item_name')
                    ->whereBetween('created_at_TIMESTAMP', [$from_date, $to_date])
                    ->where(['cid' => $cid, 'lid' => $lid, 'isactive' => 0])
                    ->get();
            }
        }
        $i = 1;
        $tdata = '';
        $total_amount = 0;
        $result_final = array();
        foreach ($bill_data as $data) {

            $result_data['item_name'] = $data->item_name;
            $item_data = \App\Item::select('*')->where(['item_name' => $data->item_name])->first();
            if (!empty($item_data))
                $result_data['icode'] = $item_data->item_id;
            else
                $result_data['icode'] = "";
            $result_data['item_qty'] = $data->total_qty;
            $result_data['item_rate'] = $data->total_rate;
            $result_data['item_totalrate'] = $data->final_rate;

            /*$user_data= \App\Employee::select('*')->where(['cid'=>$data->cid,'lid'=>$data->lid,'id'=>$data->emp_id])->first();
             if(empty($user_data))
             {
                $user_data= \App\Admin::select('*')->where(['rid'=>$data->cid])->first();
              $result_data['user']=$user_data->reg_personname;  
             }
            else
            $result_data['user']=$user_data->name;  */
            $result_data['user'] = "";
            $result_data['date'] = "";
            $total_amount = $total_amount + $data->final_rate;
            array_push($result_final, $result_data);


            $i++;
        }
        $result['amount'] = round($total_amount, 2);
        $result['other_data'] = $result_final;
        echo json_encode($result);
    }
    public function downloadItemSale(Request $request)
    {
        $requestData = $request->all();
        $from_date = $requestData["from_date"];

        if (!empty($requestData["to_date"]))
            $to_date = $requestData["to_date"];
        else
            $to_date = $from_date;

        $from_date = date($from_date . ' 00:00:00', time());
        $to_date   = date($to_date . ' 23:59:00', time());
        if (Auth::guard('admin')->check()) {
            $id = $this->admin->rid;
            if (isset($requestData['location'])) {

                $lid = $requestData['location'];
                //  echo $lid;exit;
                if ($lid == "all") {
                    if (isset($requestData['employee'])) {
                        $bill_data = DB::table('bil_AddBillDetail')
                            ->select('item_name')
                            ->selectRaw('sum(item_totalrate) as final_rate')
                            ->selectRaw('avg(item_rate) as total_rate')
                            // ->selectRaw('item_rate as total_rate')
                            ->selectRaw('sum(item_qty) as total_qty')
                            ->groupBy('item_name')
                            ->orderBy('item_name')
                            ->whereBetween('created_at_TIMESTAMP', [$from_date, $to_date])
                            ->where(['cid' => $id, 'emp_id' => $requestData['employee'], 'isactive' => 0])
                            ->get();
                    } else {
                        $bill_data = DB::table('bil_AddBillDetail')
                            ->select('item_name')
                            ->selectRaw('sum(item_totalrate) as final_rate')
                            ->selectRaw('avg(item_rate) as total_rate')
                            ->selectRaw('sum(item_qty) as total_qty')
                            ->groupBy('item_name')
                            ->orderBy('item_name')
                            ->whereBetween('created_at_TIMESTAMP', [$from_date, $to_date])
                            ->where(['cid' => $id, 'isactive' => 0])
                            ->get();
                    }
                } else {
                    if (isset($requestData['employee'])) {
                        $bill_data = DB::table('bil_AddBillDetail')
                            ->select('item_name')
                            ->selectRaw('sum(item_totalrate) as final_rate')
                            ->selectRaw('avg(item_rate) as total_rate')
                            ->selectRaw('sum(item_qty) as total_qty')
                            ->groupBy('item_name')
                            ->orderBy('item_name')
                            ->whereBetween('created_at_TIMESTAMP', [$from_date, $to_date])
                            ->where(['cid' => $id, 'lid' => $lid, 'emp_id' => $requestData['employee'], 'isactive' => 0])
                            ->get();
                    } else {
                        $bill_data = DB::table('bil_AddBillDetail')
                            ->select('item_name')
                            ->selectRaw('sum(item_totalrate) as final_rate')
                            ->selectRaw('avg(item_rate) as total_rate')
                            ->selectRaw('sum(item_qty) as total_qty')
                            ->groupBy('item_name')
                            ->orderBy('item_name')
                            ->whereBetween('created_at_TIMESTAMP', [$from_date, $to_date])
                            ->where(['cid' => $id, 'lid' => $lid, 'isactive' => 0])
                            ->get();
                    }
                }
            } else {
                if (isset($requestData['employee'])) {
                    $bill_data = DB::table('bil_AddBillDetail')
                        ->select('item_name')
                        ->selectRaw('sum(item_totalrate) as final_rate')
                        ->selectRaw('avg(item_rate) as total_rate')
                        ->selectRaw('sum(item_qty) as total_qty')
                        ->groupBy('item_name')
                        ->orderBy('item_name')
                        ->where(['cid' => $id, 'emp_id' => $requestData['employee'], 'isactive' => 0])
                        ->get();
                } else {

                    $bill_data = DB::table('bil_AddBillDetail')
                        ->select('item_name')
                        ->selectRaw('sum(item_totalrate) as final_rate')
                        ->selectRaw('avg(item_rate) as total_rate')
                        // ->selectRaw('item_rate as total_rate')
                        ->selectRaw('sum(item_qty) as total_qty')
                        ->groupBy('item_name')
                        ->orderBy('item_name')
                        ->whereBetween('created_at_TIMESTAMP', [$from_date, $to_date])
                        ->where(['cid' => $id, 'isactive' => 0])
                        // ->toSql();
                        ->get();
                    //  echo "<pre/>";print_r($bill_data);
                }
                // dd($bill_data);
                // var_dump($bill_data);
                // exit();
            }
        } else if (Auth::guard('web')->check()) {
            $bill_data = DB::table('bil_AddBillDetail')
                ->select('item_name')
                ->selectRaw('sum(item_totalrate) as final_rate')
                ->selectRaw('avg(item_rate) as total_rate')
                ->selectRaw('sum(item_qty) as total_qty')
                ->groupBy('item_name')
                ->orderBy('item_name')
                ->whereBetween('created_at_TIMESTAMP', [$from_date, $to_date])
                ->get();
        } else if (Auth::guard('employee')->check()) {
            $cid = $this->employee->cid;
            $lid = $this->employee->lid;
            $emp_id = $this->employee->id;
            $role = $this->employee->role;
            $sub_emp_id = $this->employee->sub_emp_id;
            $client_data = \App\Admin::select('location')->where(['rid' => $cid, 'isactive' => 0])->first();
            if ($client_data->location == "single" && $role == 2) {
                $bill_data = DB::table('bil_AddBillDetail')
                    ->select('item_name')
                    ->selectRaw('sum(item_totalrate) as final_rate')
                    ->selectRaw('avg(item_rate) as total_rate')
                    ->selectRaw('sum(item_qty) as total_qty')
                    ->groupBy('item_name')
                    ->orderBy('item_name')
                    ->whereBetween('created_at_TIMESTAMP', [$from_date, $to_date])
                    ->where(['cid' => $cid, 'isactive' => 0])
                    ->get();
            } else if ($client_data->location == "multiple" && $role == 2) {
                if ($sub_emp_id != "") {
                    $bill_data = DB::table('bil_AddBillDetail')
                        ->select('item_name')
                        ->selectRaw('sum(item_totalrate) as final_rate')
                        ->selectRaw('avg(item_rate) as total_rate')
                        ->selectRaw('sum(item_qty) as total_qty')
                        ->groupBy('item_name')
                        ->orderBy('item_name')
                        ->whereBetween('created_at_TIMESTAMP', [$from_date, $to_date])
                        ->where(['cid' => $cid, 'lid' => $lid, 'isactive' => 0])
                        ->get();
                } else {
                    $bill_data = DB::table('bil_AddBillDetail')
                        ->select('item_name')
                        ->selectRaw('sum(item_totalrate) as final_rate')
                        ->selectRaw('avg(item_rate) as total_rate')
                        ->selectRaw('sum(item_qty) as total_qty')
                        ->groupBy('item_name')
                        ->orderBy('item_name')
                        ->whereBetween('created_at_TIMESTAMP', [$from_date, $to_date])
                        ->where(['cid' => $cid, 'lid' => $lid, 'isactive' => 0])
                        ->get();
                }
            } else if ($client_data->location == "multiple" && $role == 1) {
                $bill_data = DB::table('bil_AddBillDetail')
                    ->select('item_name')
                    ->selectRaw('sum(item_totalrate) as final_rate')
                    ->selectRaw('avg(item_rate) as total_rate')
                    ->selectRaw('sum(item_qty) as total_qty')
                    ->groupBy('item_name')
                    ->orderBy('item_name')
                    ->whereBetween('created_at_TIMESTAMP', [$from_date, $to_date])
                    ->where(['cid' => $cid, 'lid' => $lid, 'isactive' => 0])
                    ->get();
            }
        }
        return view('reports.download_item_sales_report', ['bill_data' => $bill_data]);
    }
    public function getCancelSale()
    {
        $location_data = $employee_data = '';
        if (Auth::guard('admin')->check()) {
            $cid = $this->admin->rid;
            if ($this->admin->location == "multiple") {
                $location_data = \App\EnquiryLocation::select('*')->where(['cid' => $cid])->get();
            }

            $employee_data = \App\Employee::select('*')->where(['cid' => $cid, 'is_active' => 0])->get();
            //          echo "<pre/>";print_r();exit;
        }
        return view('reports.cancel_sale_report', ['location_data' => $location_data, 'employee_data' => $employee_data]);
    }
    public function fetchCancelSale(Request $request)
    {
        $requestData = $request->all();
        $total_amount = 0;
        $result = array();
        $from_date = $requestData["from_date"];
        if (!empty($requestData["to_date"]))
            $to_date = $requestData["to_date"];
        else
            $to_date = $from_date;

        $from_date = date($from_date . ' 00:00:00', time());
        $to_date   = date($to_date . ' 23:59:00', time());

        if (Auth::guard('admin')->check()) {
            $cid = $this->admin->rid;
            if (isset($requestData['location'])) {

                $lid = $requestData['location'];
                //  echo $lid;exit;
                if ($lid == "all") {
                    if (isset($requestData['employee'])) {
                        $bill_data = DB::table('bil_AddBillMaster')
                            ->select('*')
                            ->whereBetween('bill_date', [$from_date, $to_date])
                            ->where(['cid' => $cid, 'emp_id' => $requestData['employee'], 'isactive' => 1])
                            ->orderBy('bill_date')
                            ->orderBy('bill_no')
                            ->get();
                    } else {
                        $bill_data = DB::table('bil_AddBillMaster')
                            ->select('*')
                            ->whereBetween('bill_date', [$from_date, $to_date])
                            ->where(['cid' => $cid, 'isactive' => 1])
                            ->orderBy('bill_date')
                            ->orderBy('bill_no')
                            ->get();
                    }
                } else {
                    if (isset($requestData['employee'])) {
                        $bill_data = DB::table('bil_AddBillMaster')
                            ->select('*')
                            ->whereBetween('bill_date', [$from_date, $to_date])
                            ->where(['cid' => $cid, 'lid' => $lid, 'emp_id' => $requestData['employee'], 'isactive' => 1])
                            ->orderBy('bill_date')
                            ->orderBy('bill_no')
                            ->get();
                    } else {
                        $bill_data = DB::table('bil_AddBillMaster')
                            ->select('*')
                            ->whereBetween('bill_date', [$from_date, $to_date])
                            ->where(['cid' => $cid, 'lid' => $lid, 'isactive' => 1])
                            ->orderBy('bill_date')
                            ->orderBy('bill_no')
                            ->get();
                    }
                }
            } else {
                if (isset($requestData['employee'])) {
                    $bill_data = DB::table('bil_AddBillMaster')
                        ->select('*')
                        ->whereBetween('bill_date', [$from_date, $to_date])
                        ->where(['cid' => $cid, 'emp_id' => $requestData['employee'], 'isactive' => 1])
                        ->orderBy('bill_date')
                        ->orderBy('bill_no')
                        ->get();
                } else {
                    $bill_data = DB::table('bil_AddBillMaster')
                        ->select('*')
                        ->whereBetween('bill_date', [$from_date, $to_date])
                        ->where(['cid' => $cid, 'isactive' => 1])
                        ->orderBy('bill_date')
                        ->orderBy('bill_no')
                        ->get();
                }
            }
        } else if (Auth::guard('web')->check()) {
            $bill_data = DB::table('bil_AddBillMaster')
                ->select('*')
                ->whereBetween('bill_date', [$from_date, $to_date])
                ->orderBy('bill_date')
                ->orderBy('bill_no')
                ->get();
        } else if (Auth::guard('employee')->check()) {
            $cid = $this->employee->cid;
            $lid = $this->employee->lid;
            $emp_id = $this->employee->id;
            $role = $this->employee->role;
            $sub_emp_id = $this->employee->sub_emp_id;
            $client_data = \App\Admin::select('location')->where(['rid' => $cid])->first();

            // echo $client_data->location."".$role;exit;
            if ($client_data->location == "single" && $role == 2) {
                $bill_data = DB::table('bil_AddBillMaster')
                    ->select('*')
                    ->whereBetween('bill_date', [$from_date, $to_date])
                    ->where(['cid' => $cid, 'isactive' => 1])
                    ->orderBy('bill_date')
                    ->orderBy('bill_no')
                    ->get();
            } else if ($client_data->location == "multiple" && $role == 2) {
                //                echo "Lid".$lid."<br/>CID: ".$cid."<br/>Emp ID: ".$emp_id."<br/>Sub Emp ID: ".$sub_emp_id."<br>";
                //                    echo "in if";echo $emp_id;
                if ($sub_emp_id != "") {
                    //   echo "in sub if";

                    $bill_data = DB::table('bil_AddBillMaster')
                        ->select('*')
                        ->where(['cid' => $cid, 'lid' => $lid, 'isactive' => 1])
                        ->whereBetween('bill_date', [$from_date, $to_date])
                        ->orderBy('bill_date')
                        ->orderBy('bill_no')
                        ->get();
                } else {
                    $bill_data = DB::table('bil_AddBillMaster')
                        ->select('*')
                        ->whereBetween('bill_date', [$from_date, $to_date])
                        ->where(['cid' => $cid, 'lid' => $lid, 'isactive' => 1])
                        ->orderBy('bill_date')
                        ->orderBy('bill_no')
                        ->get();
                }
            } else if ($client_data->location == "multiple" && $role == 1) {
                $bill_data = DB::table('bil_AddBillMaster')
                    ->select('*')
                    ->whereBetween('bill_date', [$from_date, $to_date])
                    ->where(['cid' => $cid, 'lid' => $lid, 'isactive' => 1])
                    ->orderBy('bill_date')
                    ->orderBy('bill_no')
                    ->get();
            }
        }
        $i = 1;
        $result_final = array();
        foreach ($bill_data as $data) {
            $total_amount = $total_amount + $data->bill_totalamt;
            $result_data['bill_no'] = $data->bill_no;
            if ($data->bill_code == NULL)
                $data->bill_code = "";
            $result_data['order_no'] = $data->bill_code;
            $customer_data = \App\Customer::select('*')->where(['cust_id' => $data->cust_id])->first();
            if (!empty($customer_data)) {
                $result_data['cust_name'] = $customer_data->cust_name;
            } else {
                if ($data->cust_name == NULL)
                    $data->cust_name = "";
                $result_data['cust_name'] = $data->cust_name;
            }

            $result_data['bill_totalamt'] = $data->bill_totalamt;
            $result_data['cash_or_credit'] = $data->cash_or_credit;

            $point_of_data = \App\PointOfContact::select('*')->where(['id' => $data->point_of_contact])->first();

            if (!empty($point_of_data))
                $result_data['point_of_contact'] = $point_of_data->point_of_contact;
            else
                $result_data['point_of_contact'] = '';
            if (isset($requestData['location'])) {
                $location_data = \App\EnquiryLocation::select('*')->where(['loc_id' => $data->lid])->first();
                $result_data['loc_name'] = $location_data->loc_name;
            } else {
                $result_data['loc_name'] = 'Own';
            }
            $user_data = \App\Employee::select('*')->where(['cid' => $data->cid, 'lid' => $data->lid, 'id' => $data->emp_id])->first();
            if (empty($user_data)) {
                $user_data = \App\Admin::select('*')->where(['rid' => $data->cid])->first();
                $result_data['user'] = $user_data->reg_personname;
            } else
                $result_data['user'] = $user_data->name;
            $result_data['date'] = $data->bill_date;
            array_push($result_final, $result_data);
        }
        $result['amount'] = round($total_amount, 2);
        $result['other_data'] = $result_final;
        echo json_encode($result);
    }
    public function downloadCancelSale(Request $request)
    {
        $requestData = $request->all();
        $from_date = $requestData["from_date"];

        if (!empty($requestData["to_date"]))
            $to_date = $requestData["to_date"];
        else
            $to_date = $from_date;

        $from_date = date($from_date . ' 00:00:00', time());
        $to_date   = date($to_date . ' 23:59:00', time());

        if (Auth::guard('admin')->check()) {
            $cid = $this->admin->rid;
            $cid = $this->admin->rid;
            if (isset($requestData['location'])) {

                $lid = $requestData['location'];
                //  echo $lid;exit;
                if ($lid == "all") {
                    if (isset($requestData['employee'])) {
                        $bill_data = DB::table('bil_AddBillMaster')
                            ->select('*')
                            ->whereBetween('bill_date', [$from_date, $to_date])
                            ->where(['cid' => $cid, 'emp_id' => $requestData['employee'], 'isactive' => 1])
                            ->orderBy('bill_date')
                            ->orderBy('bill_no')
                            ->get();
                    } else {
                        $bill_data = DB::table('bil_AddBillMaster')
                            ->select('*')
                            ->whereBetween('bill_date', [$from_date, $to_date])
                            ->where(['cid' => $cid, 'isactive' => 0])
                            ->orderBy('bill_date')
                            ->orderBy('bill_no')
                            ->get();
                    }
                } else {
                    $bill_data = DB::table('bil_AddBillMaster')
                        ->select('*')
                        ->whereBetween('bill_date', [$from_date, $to_date])
                        ->where(['cid' => $cid, 'lid' => $lid, 'isactive' => 1])
                        ->orderBy('bill_date')
                        ->orderBy('bill_no')
                        ->get();
                }
            } else {
                if (isset($requestData['employee'])) {
                    $bill_data = DB::table('bil_AddBillMaster')
                        ->select('*')
                        ->whereBetween('bill_date', [$from_date, $to_date])
                        ->where(['cid' => $cid, 'emp_id' => $requestData['employee'], 'isactive' => 1])
                        ->orderBy('bill_date')
                        ->orderBy('bill_no')
                        ->get();
                } else {
                    $bill_data = DB::table('bil_AddBillMaster')
                        ->select('*')
                        ->whereBetween('bill_date', [$from_date, $to_date])
                        ->where(['cid' => $cid, 'isactive' => 1])
                        ->orderBy('bill_date')
                        ->orderBy('bill_no')
                        ->get();
                }
            }
        } else if (Auth::guard('web')->check()) {
            $bill_data = DB::table('bil_AddBillMaster')
                ->select('*')
                ->whereBetween('bill_date', [$from_date, $to_date])
                ->orderBy('bill_date')
                ->orderBy('bill_no')
                ->get();
        } else if (Auth::guard('employee')->check()) {
            $cid = $this->employee->cid;
            $lid = $this->employee->lid;
            $emp_id = $this->employee->id;
            $role = $this->employee->role;
            $sub_emp_id = $this->employee->sub_emp_id;
            $client_data = \App\Admin::select('location')->where(['rid' => $cid])->first();

            // echo $client_data->location."".$role;exit;
            if ($client_data->location == "single" && $role == 2) {
                $bill_data = DB::table('bil_AddBillMaster')
                    ->select('*')
                    ->whereBetween('bill_date', [$from_date, $to_date])
                    ->where(['cid' => $cid, 'isactive' => 1])
                    ->orderBy('bill_date')
                    ->orderBy('bill_no')
                    ->get();
            } else if ($client_data->location == "multiple" && $role == 2) {
                //                echo "Lid".$lid."<br/>CID: ".$cid."<br/>Emp ID: ".$emp_id."<br/>Sub Emp ID: ".$sub_emp_id."<br>";
                //                    echo "in if";echo $emp_id;
                if ($sub_emp_id != "") {
                    //   echo "in sub if";

                    $bill_data = DB::table('bil_AddBillMaster')
                        ->select('*')
                        ->where(['cid' => $cid, 'lid' => $lid, 'isactive' => 1])
                        ->whereBetween('bill_date', [$from_date, $to_date])
                        ->orderBy('bill_date')
                        ->orderBy('bill_no')
                        ->get();
                } else {
                    $bill_data = DB::table('bil_AddBillMaster')
                        ->select('*')
                        ->whereBetween('bill_date', [$from_date, $to_date])
                        ->where(['cid' => $cid, 'lid' => $lid, 'isactive' => 1])
                        ->orderBy('bill_date')
                        ->orderBy('bill_no')
                        ->get();
                }
            } else if ($client_data->location == "multiple" && $role == 1) {
                $bill_data = DB::table('bil_AddBillMaster')
                    ->select('*')
                    ->whereBetween('bill_date', [$from_date, $to_date])
                    ->where(['cid' => $cid, 'lid' => $lid, 'isactive' => 1])
                    ->orderBy('bill_date')
                    ->orderBy('bill_no')
                    ->get();
            }
        }
        return view('reports.download_cancel_sales_report', ['bill_data' => $bill_data]);
    }
}

