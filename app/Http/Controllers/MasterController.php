<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use App\Type;
use App\Category;
use App\Subscription;
use Session;
use Illuminate\Support\Facades\Auth;
use Validator;

class MasterController extends Controller
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

    //Type 
    public function getTypeData()
    {
        if (Auth::guard('admin')->check()) {
            $id = $this->admin->rid;
            $type = DB::table('bil_AddIUnits')->where(['is_active' => '0', 'cid' => $id])->get();
        } else if (Auth::guard('web')->check()) {
            $type = DB::table('bil_AddIUnits')->where(['is_active' => '0'])->get();
        } else if (Auth::guard('employee')->check()) {
            $cid = $this->employee->cid;
            $lid = $this->employee->lid;
            $emp_id = $this->employee->id;
            $role = $this->employee->role;
            $sub_emp_id = $this->employee->sub_emp_id;
            $client_data = \App\Admin::select('location')->where(['rid' => $cid])->first();


            if ($client_data->location == "single" && $role == 2) {
                $type = DB::table('bil_AddIUnits')->where(['is_active' => '0', 'cid' => $cid])->get();
            } else if ($client_data->location == "multiple" && $role == 2) {
                if ($sub_emp_id != "") {
                    $type = DB::table('bil_AddIUnits')
                        ->where(['is_active' => '0', 'cid' => $cid, 'lid' => $lid])
                        ->orWhere(['emp_id' => $sub_emp_id])
                        ->orWhere(['emp_id' => $emp_id])
                        ->get();
                } else {
                    $type = DB::table('bil_AddIUnits')->where(['is_active' => '0', 'cid' => $cid, 'lid' => $lid])->get();
                }
            } else if ($client_data->location == "multiple" && $role == 1) {
                $type = DB::table('bil_AddIUnits')->where(['is_active' => '0', 'cid' => $cid, 'lid' => $lid])->get();
            }
        }
        return view('master_data.type_data', ['type' => $type]);
    }

    public function getType()
    {
        return view('master_data.add_type');
    }

    public function editType()
    {
        $type_id = $_GET['type_id'];
        if (Auth::guard('admin')->check()) {
            $id = $this->admin->rid;
            $query = Type::where('Unit_id', $type_id)->where(['is_active' => '0', 'cid' => $id])->first();
        } else if (Auth::guard('web')->check()) {
            $query = Type::where('Unit_id', $type_id)->where(['is_active' => '0'])->first();
        } else if (Auth::guard('employee')->check()) {
            $cid = $this->employee->cid;
            $lid = $this->employee->lid;
            $emp_id = $this->employee->id;
            $role = $this->employee->role;
            $sub_emp_id = $this->employee->sub_emp_id;

            $client_data = \App\Admin::select('location')->where(['rid' => $cid])->first();
            if ($client_data->location == "single" && $role == 2) {
                $query = Type::where('Unit_id', $type_id)->where(['is_active' => '0', 'cid' => $cid, 'lid' => $lid])->first();
            } else {
                $query = Type::where('Unit_id', $type_id)->where(['is_active' => '0', 'cid' => $cid, 'lid' => $lid])
                    ->first();
            }
        }
        return view('master_data.edit_type', ['type_data' => $query]);
    }

    public function addType(Request $request)
    {
        $requestData = $request->all();
        $validatedData = Validator::make($request->all(), [
            'Unit_name' => 'bail|required',
            'Unit_Taxvalue' => 'required'
        ], [
            'Unit_name.required' => 'Unit Name is required!',
            'Unit_Taxvalue.required' => 'Unit TaxValue is required!'
        ])->validate();

        if (Auth::guard('admin')->check()) {
            $requestData['cid'] = $this->admin->rid;
        } else if (Auth::guard('employee')->check()) {
            $requestData['cid'] = $this->employee->cid;
            $requestData['lid'] = $this->employee->lid;
            $requestData['emp_id'] = $this->employee->id;
        }
        Type::create($requestData);
        Session::flash('alert-success', 'Unit added successfully.');
        return redirect('type_data');
    }

    public function updateType(Request $request)
    {
        $requestData = $request->all();
        $type_id = $requestData['Unit_Id'];
        $requestData['sync_flag'] = 0;
        $query = Type::findorfail($type_id);
        $query->update($requestData);
        Session::flash('alert-success', 'Unit updated successfully.');
        return redirect('type_data');
    }

    public function deleteType($unit_id)
    {
        $status = 1;
        $query = Type::where('Unit_Id', $unit_id)->update(['is_active' => $status, 'sync_flag' => 0]);
        return redirect('type_data');
    }


    //Category

    public function getCategoryData()
    {
        $flag = 0;
        if (Auth::guard('admin')->check()) {
            $id = $this->admin->rid;
            $flag = 1;
            $category = DB::table('bil_category')
                ->select('bil_category.*', 'bil_type.type_name')
                ->leftjoin('bil_type', 'bil_type.type_id', '=', 'bil_category.type_id')
                ->where(['bil_category.is_active' => '0', 'bil_category.cid' => $id])
                // ->orderBy('bil_category.cat_name', 'asc')
                ->get();
        } else if (Auth::guard('web')->check()) {
            $category = DB::table('bil_category')
                ->select('bil_category.*', 'bil_type.type_name')
                ->leftjoin('bil_type', 'bil_type.type_id', '=', 'bil_category.type_id')
                ->where(['bil_category.is_active' => '0'])
                // ->orderBy('bil_category.cat_name', 'asc')
                ->get();
        } else if (Auth::guard('employee')->check()) {
            $cid = $this->employee->cid;
            $lid = $this->employee->lid;
            $emp_id = $this->employee->id;
            $role = $this->employee->role;
            $sub_emp_id = $this->employee->sub_emp_id;
            $client_data = \App\Admin::select('location')->where(['rid' => $cid])->first();
            if ($client_data->location == "single" && $role == 2) {

                $category = DB::table('bil_category')
                    ->select('bil_category.*', 'bil_type.type_name')
                    ->leftjoin('bil_type', 'bil_type.type_id', '=', 'bil_category.type_id')
                    ->where(['bil_category.is_active' => '0', 'bil_category.cid' => $cid])
                    // ->orderBy('bil_category.cat_name', 'asc')
                    ->get();
            } else if ($client_data->location == "multiple" && $role == 2) {
                if ($sub_emp_id != "") {
                    $category = DB::table('bil_category')
                        ->select('bil_category.*', 'bil_type.type_name')
                        ->leftjoin('bil_type', 'bil_type.type_id', '=', 'bil_category.type_id')
                        ->where(['bil_category.is_active' => '0', 'bil_category.cid' => $cid, 'bil_category.lid' => $lid])
                        // ->orderBy('bil_category.cat_name', 'asc')
                        ->get();
                } else {
                    $category = DB::table('bil_category')
                        ->select('bil_category.*', 'bil_type.type_name')
                        ->leftjoin('bil_type', 'bil_type.type_id', '=', 'bil_category.type_id')
                        ->where(['bil_category.is_active' => '0', 'bil_category.cid' => $cid, 'bil_category.lid' => $lid, 'emp_id' => $emp_id])
                        //->orderBy('bil_category.cat_name', 'asc')
                        ->get();
                }
            } else if ($client_data->location == "multiple" && $role == 1) {
                $flag = 1;
                $category = DB::table('bil_category')
                    ->select('bil_category.*', 'bil_type.type_name')
                    ->leftjoin('bil_type', 'bil_type.type_id', '=', 'bil_category.type_id')
                    ->where(['bil_category.is_active' => '0', 'bil_category.cid' => $cid, 'bil_category.lid' => $lid])
                    // ->orderBy('bil_category.cat_name', 'asc')
                    ->get();
            }
        }
        return view('master_data.category_data', ['category' => $category, 'flag' => $flag]);
    }

    public function getCategory()
    {
        return view('master_data.add_category');
    }

    public function addCategory(Request $request)
    {
        $requestData = $request->all();
        $validatedData = Validator::make($request->all(), [
            'cat_name' => 'bail|required'
        ], ['cat_name.required' => 'Category Name is required!'])->validate();

        if (Auth::guard('admin')->check()) {
            $requestData['cid'] = $this->admin->rid;
        } else if (Auth::guard('employee')->check()) {
            $requestData['cid'] = $this->employee->cid;
            $requestData['lid'] = $this->employee->lid;
            $requestData['emp_id'] = $this->employee->id;
        }
        if (!empty($requestData['cat_image'])) {
            $filename = $requestData['cat_image']->getClientOriginalName();
            $destination = 'cat_images';
            $requestData['cat_image']->move($destination, $filename);
            $requestData['cat_image'] = $filename;
        } else {
            $requestData['cat_image'] = "cat.png";
        }
        Category::create($requestData);
        Session::flash('alert-success', 'Category added successfully.');
        return redirect('category_data');
    }

    public function editCategory()
    {
        $cat_id = $_GET['cat_id'];
        // echo $cat_id;
        //        exit;

        if (Auth::guard('admin')->check()) {
            $id = $this->admin->rid;
            $query = DB::table('bil_category')
                ->select('bil_category.*')
                ->where(['cat_id' => $cat_id, 'bil_category.is_active' => '0', 'cid' => $id])
                ->orderBy('bil_category.cat_name', 'asc')
                ->first();
        } else if (Auth::guard('web')->check()) {
            $query = DB::table('bil_category')
                ->select('bil_category.*')
                ->where(['cat_id' => $cat_id, 'bil_category.is_active' => '0'])
                ->orderBy('bil_category.cat_name', 'asc')
                ->first();
        } else if (Auth::guard('employee')->check()) {
            $cid = $this->employee->cid;
            $lid = $this->employee->lid;
            $emp_id = $this->employee->id;
            $role = $this->employee->role;
            $sub_emp_id = $this->employee->sub_emp_id;
            $client_data = \App\Admin::select('location')->where(['rid' => $cid])->first();
            if ($client_data->location == "single" && $role == 2) {

                $query = DB::table('bil_category')
                    ->select('bil_category.*')
                    ->where(['cat_id' => $cat_id, 'bil_category.is_active' => '0', 'cid' => $cid, 'lid' => $lid])
                    ->orderBy('bil_category.cat_name', 'asc')
                    ->first();
            } else {

                $query = DB::table('bil_category')
                    ->select('bil_category.*')
                    ->where(['cat_id' => $cat_id, 'bil_category.is_active' => '0', 'cid' => $cid, 'lid' => $lid])
                    ->orderBy('bil_category.cat_name', 'asc')
                    ->first();
                //                     echo "in else";
                //                echo $cat_id;
                //                print_r($query);
                //                exit;
            }
        }

        return view('master_data.edit_category', ['category_data' => $query]);
    }

    public function updateCategory(Request $request)
    {
        $requestData = $request->all();
        $validatedData = Validator::make($request->all(), [
            'cat_name' => 'bail|required'
        ], ['cat_name.required' => 'Category Name is required!'])->validate();
        $cat_id = $requestData['cat_id'];
        $cat_name = $requestData['cat_name'];
        $requestData['sync_flag'] = 0;
        $query = Category::findorfail($cat_id);
        $query->update($requestData);
        Session::flash('alert-success', 'Category Updated Successfully.');
        return redirect('category_data');
    }

    public function deleteCategory($cat_id)
    {
        $status = 1;
        $query = Category::where('cat_id', $cat_id)->update(['is_active' => $status, 'sync_flag' => 0]);
        return redirect('category_data');
    }

    //Subscription

    public function getSubscriptionData()
    {
        if (Auth::guard('admin')->check()) {
            $id = $this->admin->rid;
            $sub_data = DB::table('tbl_subscription')->where(['is_active' => '1', 'cid' => $id])->orderBy('sub_name', 'asc')->get();
        } else if (Auth::guard('web')->check()) {
            $sub_data = DB::table('tbl_subscription')->where(['is_active' => '1'])->orderBy('sub_name', 'asc')->get();
        } else if (Auth::guard('employee')->check()) {
            $cid = $this->employee->cid;
            $lid = $this->employee->lid;
            $emp_id = $this->employee->id;
            $sub_data = DB::table('tbl_subscription')->where(['is_active' => '1', 'cid' => $cid, 'lid' => $lid, 'emp_id' => $emp_id])->orderBy('sub_name', 'asc')->get();
        }
        return view('master_data.subscription_data', ['sub_data' => $sub_data]);
    }

    public function getSubscription()
    {
        return view('master_data.add_subscription');
    }

    public function addSubscription(Request $request)
    {
        $requestData = $request->all();
        if (Auth::guard('admin')->check()) {
            $requestData['cid'] = $this->admin->rid;
        } else if (Auth::guard('employee')->check()) {
            $requestData['cid'] = $this->employee->cid;
            $requestData['lid'] = $this->employee->lid;
            $requestData['emp_id'] = $this->employee->id;
        }
        Subscription::create($requestData);
        Session::flash('alert-success', 'Added Successfully.');
        return redirect('subscription_data');
    }

    public function editSubscription()
    {
        $sub_id = $_GET['sub_id'];
        if (Auth::guard('admin')->check()) {
            $id = $this->admin->rid;
            $query = Subscription::where('sub_id', $sub_id)->where(['is_active' => '1', 'cid' => $id])->first();
        } else if (Auth::guard('web')->check()) {
            $query = Subscription::where('sub_id', $sub_id)->where(['is_active' => '1'])->first();
        } else if (Auth::guard('employee')->check()) {
            $cid = $this->employee->cid;
            $lid = $this->employee->lid;
            $emp_id = $this->employee->id;
            $query = Subscription::where('sub_id', $sub_id)->where(['is_active' => '1', 'cid' => $cid, 'lid' => $lid, 'emp_id' => $emp_id])->first();
        }
        return view('master_data.edit_subscription', ['sub_data' => $query]);
    }

    public function updateSubscription(Request $request)
    {
        $requestData = $request->all();
        $sub_id = $requestData['sub_id'];
        $sub_name = $requestData['sub_name'];
        $sub_users_no = $requestData['sub_users_no'];
        $sub_price = $requestData['sub_price'];
        if ($sub_id != "") {
            $query = Subscription::findorfail($sub_id);
            DB::table('tbl_subscription')
                ->where('sub_id', '=', $sub_id)
                ->update(['sub_name' => $sub_name, 'sub_users_no' => $sub_users_no, 'sub_price' => $sub_price]);
            Session::flash('alert-success', 'Updated Successfully.');
        } else {
            Session::flash('alert-error', 'Error Updating');
        }
        return redirect('subscription_data');
    }

    public function deleteSubscription($sub_id)
    {
        $status = 0;
        $query = Subscription::where('sub_id', $sub_id)
            ->update(['is_active' => $status]);
        return redirect('subscription_data');
    }

    public function dashboard_enq_list()
    {
        $status_id = $_GET['status_id'];
        $query = \App\Enquiry::where('status_id', $status_id)->get();
        $enq_status = \App\EnquiryStatus::select('status_name')->where(['id' => $status_id])->first();
        return view('dashboard_enq_list', ['status_data' => $query, 'status_nm' => $enq_status]);
    }

    //Customer
    public function getCustomerData()
    {
        if (Auth::guard('admin')->check()) {
            $id = $this->admin->rid;
            $customer_data = \App\Customer::where(['is_active' => '0', 'cid' => $id])->get();
        } else if (Auth::guard('web')->check()) {
            $customer_data = \App\Customer::where(['is_active' => '0'])->get();
        } else if (Auth::guard('employee')->check()) {
            $cid = $this->employee->cid;
            $lid = $this->employee->lid;
            $emp_id = $this->employee->id;
            $role = $this->employee->role;
            $sub_emp_id = $this->employee->sub_emp_id;
            $client_data = \App\Admin::select('location')->where(['rid' => $cid])->first();
            if ($client_data->location == "single" && $role == 2) {
                $customer_data = \App\Customer::where(['is_active' => '0', 'cid' => $cid])->get();
            } else if ($client_data->location == "multiple" && $role == 2) {
                $customer_data = \App\Customer::where(['is_active' => '0', 'cid' => $cid, 'lid' => $lid])->get();
            } else if ($client_data->location == "multiple" && $role == 1) {
                $customer_data = \App\Customer::where(['is_active' => '0', 'cid' => $cid, 'lid' => $lid])->get();
            }
        }

        return view('master_data.customer_data', ['customer_data' => $customer_data]);
    }

    public function getCustomer()
    {
        return view('master_data.add_customer');
    }

    public function addCustomer(Request $request)
    {
        $requestData = $request->all();
        $validatedData = Validator::make($request->all(), [
            'cust_name' => 'bail|required',
            'mobile_no' => 'required'
        ], [
            'cust_name.required' => 'Customer Name is required!',
            'mobile_no.required' => 'Mobile no is required!'
        ])->validate();

        if (Auth::guard('admin')->check()) {
            $requestData['cid'] = $this->admin->rid;
        } else if (Auth::guard('employee')->check()) {
            $requestData['cid'] = $this->employee->cid;
            $requestData['lid'] = $this->employee->lid;
            $requestData['emp_id'] = $this->employee->id;
        }
        //        echo "<pre>";
        //        print_r($requestData);exit;
        \App\Customer::create($requestData);
        Session::flash('alert-success', 'Customer added successfully.');
        return redirect('customer_data');
    }

    public function editCustomer()
    {
        $cust_id = $_GET['cust_id'];

        if (Auth::guard('admin')->check()) {
            $id = $this->admin->rid;
            $query = \App\Customer::where('cust_id', $cust_id)->where(['is_active' => '0', 'cid' => $id])->first();
        } else if (Auth::guard('web')->check()) {
            $query = \App\Customer::where('cust_id', $cust_id)->where(['is_active' => '0'])->first();
        } else if (Auth::guard('employee')->check()) {
            $cid = $this->employee->cid;
            $lid = $this->employee->lid;
            $emp_id = $this->employee->id;
            $role = $this->employee->role;
            $sub_emp_id = $this->employee->sub_emp_id;
            $client_data = \App\Admin::select('location')->where(['rid' => $cid])->first();
            if ($client_data->location == "single" && $role == 2) {
                $query = \App\Customer::where('cust_id', $cust_id)->where(['is_active' => '0', 'cid' => $cid])->first();
            } else {
                $query = \App\Customer::where('cust_id', $cust_id)->where(['is_active' => '0', 'cid' => $cid, 'lid' => $lid])
                    ->first();
            }
        }
        return view('master_data.edit_customer', ['customer_data' => $query]);
    }

    public function updateCustomer(Request $request)
    {
        $requestData = $request->all();
        $validatedData = Validator::make($request->all(), [
            'cust_name' => 'bail|required',
            'mobile_no' => 'required'
        ], [
            'cust_name.required' => 'Customer Name is required!',
            'mobile_no.required' => 'Mobile no is required!'
        ])->validate();
        $cust_id = $requestData['cust_id'];
        $requestData['sync_flag'] = 0;
        $users = \App\Customer::findorfail($cust_id);
        $users->update($requestData);
        Session::flash('alert-success', 'Customer updated successfully.');
        return redirect('customer_data');
    }

    public function deleteCustomer($cust_id)
    {
        $status = 1;
        $query = \App\Customer::where('cust_id', $cust_id)
            ->update(['is_active' => $status, 'sync_flag' => 0]);
        return redirect('customer_data');
    }

    //Item
    public function getItemData()
    {
        if (Auth::guard('admin')->check()) {
            $id = $this->admin->rid;
            $item_data = \App\Item::where(['cid' => $id])->get();
        } else if (Auth::guard('web')->check()) {
            $item_data = \App\Item::orderBy('item_name', 'asc')->get();
        } else if (Auth::guard('employee')->check()) {
            $cid = $this->employee->cid;
            $lid = $this->employee->lid;
            $emp_id = $this->employee->id;
            $role = $this->employee->role;
            $sub_emp_id = $this->employee->sub_emp_id;

            $client_data = \App\Admin::select('location')->where(['rid' => $cid])->first();
            if ($client_data->location == "single" && $role == 2) {
                $item_data = \App\Item::where(['cid' => $cid])->get();
            } else if ($client_data->location == "multiple" && $role == 2) {
                $item_data = \App\Item::where(['is_active' => '0', 'cid' => $cid, 'lid' => $lid])
                    ->orWhere(['emp_id' => $sub_emp_id])
                    ->orWhere(['emp_id' => $emp_id])
                    ->get();
            } else if ($client_data->location == "multiple" && $role == 1) {
                $item_data = \App\Item::where(['cid' => $cid, 'lid' => $lid])->get();
            }
        }
        return view('master_data.item_data', ['item_data' => $item_data]);
    }
    public function getItemFilter()
    {
        $filter = $_GET["filter"];
        if (Auth::guard('admin')->check()) {
            $id = $this->admin->rid;
            $item_data = DB::table('bil_AddItems')
                ->select('bil_AddItems.*', 'bil_category.cat_name', 'bil_AddIUnits.Unit_Taxvalue')
                ->leftjoin('bil_category', 'bil_category.cat_id', '=', 'bil_AddItems.item_category')
                ->leftjoin('bil_AddIUnits', 'bil_AddIUnits.Unit_id', '=', 'bil_AddItems.item_units')
                ->orderBy('item_name', 'asc')
                ->where(['bil_AddItems.is_active' => $filter, 'bil_AddItems.cid' => $id])
                ->get();
        } else if (Auth::guard('web')->check()) {
            $item_data = \App\Item::orderBy('item_name', 'asc')->where('is_active', $filter)->get();
        } else if (Auth::guard('employee')->check()) {
            $cid = $this->employee->cid;
            $lid = $this->employee->lid;
            $emp_id = $this->employee->id;
            $role = $this->employee->role;
            $sub_emp_id = $this->employee->sub_emp_id;
            $client_data = \App\Admin::select('location')->where(['rid' => $cid])->first();
            if ($client_data->location == "single" && $role == 2) {
                $item_data = \App\Item::orderBy('item_name', 'asc')->where(['is_active' => $filter, 'cid' => $cid])->get();
            } else if ($client_data->location == "multiple" && $role == 2) {
                //                echo "multiple role 2";

                $item_data = \App\Item::orderBy('item_name', 'asc')
                    ->where(['is_active' => $filter, 'cid' => $cid, 'lid' => $lid])
                    ->orWhere(['emp_id' => $sub_emp_id])
                    ->orWhere(['emp_id' => $emp_id])
                    ->get();
            } else if ($client_data->location == "multiple" && $role == 1) {
                $item_data = \App\Item::orderBy('item_name', 'asc')->where(['is_active' => $filter, 'cid' => $cid, 'lid' => $lid])->get();
            }
        }
        echo json_encode($item_data);
    }
    public function getItem()
    {
        if (Auth::guard('admin')->check()) {
            $id = $this->admin->rid;
            $category = DB::table('bil_category')
                ->select('bil_category.*', 'bil_type.type_name')
                ->leftjoin('bil_type', 'bil_type.type_id', '=', 'bil_category.type_id')
                ->where(['bil_category.is_active' => '0', 'bil_category.cid' => $id])
                ->orderBy('bil_category.cat_name', 'asc')
                ->get();
            $unit_data = DB::table('bil_AddIUnits')->where(['is_active' => '0', 'cid' => $id])->get();
        } else if (Auth::guard('web')->check()) {
            $category = DB::table('bil_category')
                ->select('bil_category.*', 'bil_type.type_name')
                ->leftjoin('bil_type', 'bil_type.type_id', '=', 'bil_category.type_id')
                ->where(['bil_category.is_active' => '0'])
                ->orderBy('bil_category.cat_name', 'asc')
                ->get();
            $unit_data = DB::table('bil_AddIUnits')->where(['is_active' => '0'])->get();
        } else if (Auth::guard('employee')->check()) {
            $cid = $this->employee->cid;
            $lid = $this->employee->lid;
            $emp_id = $this->employee->id;
            $role = $this->employee->role;
            $sub_emp_id = $this->employee->sub_emp_id;
            $client_data = \App\Admin::select('location')->where(['rid' => $cid])->first();
            if ($client_data->location == "single" && $role == 2) {
                $category = DB::table('bil_category')
                    ->select('bil_category.*', 'bil_type.type_name')
                    ->leftjoin('bil_type', 'bil_type.type_id', '=', 'bil_category.type_id')
                    ->where(['bil_category.is_active' => '0', 'bil_category.cid' => $cid, 'bil_category.lid' => $lid])
                    ->orderBy('bil_category.cat_name', 'asc')
                    ->get();
                $unit_data = DB::table('bil_AddIUnits')->where(['is_active' => '0', 'cid' => $cid, 'lid' => $lid])->get();
            } else if ($client_data->location == "multiple" && $role == 2) {
                $category = DB::table('bil_category')
                    ->select('bil_category.*', 'bil_type.type_name')
                    ->leftjoin('bil_type', 'bil_type.type_id', '=', 'bil_category.type_id')
                    ->where(['bil_category.is_active' => '0', 'bil_category.cid' => $cid, 'bil_category.lid' => $lid])
                    ->orderBy('bil_category.cat_name', 'asc')
                    ->get();
                $unit_data = DB::table('bil_AddIUnits')->where(['is_active' => '0', 'cid' => $cid, 'lid' => $lid])->get();
            } else if ($client_data->location == "multiple" && $role == 1) {
                $category = DB::table('bil_category')
                    ->select('bil_category.*', 'bil_type.type_name')
                    ->leftjoin('bil_type', 'bil_type.type_id', '=', 'bil_category.type_id')
                    ->where(['bil_category.is_active' => '0', 'bil_category.cid' => $cid, 'bil_category.lid' => $lid])
                    ->orderBy('bil_category.cat_name', 'asc')
                    ->get();
                $unit_data = DB::table('bil_AddIUnits')->where(['is_active' => '0', 'cid' => $cid, 'lid' => $lid])->get();
            }
        }


        return view('master_data.add_item', ['category_data' => $category, 'unit_data' => $unit_data]);
    }

    public function addItem(Request $request)
    {
        $requestData = $request->all();
        $validatedData = Validator::make($request->all(), [
            'item_name' => 'bail|required',
            'item_rate' => 'required',
            'item_dis' => 'required',
            'item_disrate' => 'required',
            'item_tax' => 'required',
            'item_category' => 'required'
        ], [
            'item_name.required' => 'Item Name is required!',
            'item_rate.required' => 'Item Rate is required!',
            'item_dis.required' => 'Item Discount is required!',
            'item_disrate' => 'Item Discount Rate is required',
            'item_tax.required' => 'Item Tax is required!',
            'item_category.required' => 'Category is required!'

        ])->validate();
        $requestData['item_date'] = date('Y-m-d');
        if (Auth::guard('admin')->check()) {
            $requestData['cid'] = $this->admin->rid;
            $cid = $this->admin->rid;

            $item_code = \App\Item::where(['cid' => $cid])->count() + 1;
            $requestData['item_code'] = $item_code;
        } else if (Auth::guard('employee')->check()) {
            $requestData['cid'] = $this->employee->cid;
            $requestData['lid'] = $this->employee->lid;
            $requestData['emp_id'] = $this->employee->id;
            $cid = $this->employee->cid;
            $lid = $this->employee->lid;
            $item_code = \App\Item::where(['cid' => $cid, 'lid' => $lid])->count() + 1;
            $requestData['item_code'] = $item_code;
        }

        \App\Item::create($requestData);
        Session::flash('alert-success', 'Item added successfully.');
        return redirect('item_data');
    }

    public function deleteItem($item_id)
    {
        $status = 1;
        $query = \App\Item::select('*')->where('item_id', $item_id)->first();
        if ($query->is_active == 0)
            $query->update(['is_active' => 1, 'sync_flag' => 0]);
        else
            $query->update(['is_active' => 0, 'sync_flag' => 0]);
        return redirect('item_data');
    }
    public function editItem()
    {
        $item_id = $_GET['item_id'];
        if (Auth::guard('admin')->check()) {
            $id = $this->admin->rid;
            $category = DB::table('bil_category')
                ->select('bil_category.*', 'bil_type.type_name')
                ->leftjoin('bil_type', 'bil_type.type_id', '=', 'bil_category.type_id')
                ->where(['bil_category.is_active' => '0', 'bil_category.cid' => $id])
                ->orderBy('bil_category.cat_name', 'asc')
                ->get();
            $unit_data = DB::table('bil_AddIUnits')->where(['is_active' => '0', 'cid' => $id])->get();
            $query = \App\Item::where('item_id', $item_id)->where(['is_active' => '0', 'cid' => $id])->first();
        } else if (Auth::guard('web')->check()) {
            $category = DB::table('bil_category')
                ->select('bil_category.*', 'bil_type.type_name')
                ->leftjoin('bil_type', 'bil_type.type_id', '=', 'bil_category.type_id')
                ->where(['bil_category.is_active' => '0'])
                ->orderBy('bil_category.cat_name', 'asc')
                ->get();
            $unit_data = DB::table('bil_AddIUnits')->where(['is_active' => '0'])->get();
            $query = \App\Item::where('item_id', $item_id)->where(['is_active' => '0'])->first();
        } else if (Auth::guard('employee')->check()) {
            $cid = $this->employee->cid;
            $lid = $this->employee->lid;
            $emp_id = $this->employee->id;
            $role = $this->employee->role;
            $sub_emp_id = $this->employee->sub_emp_id;

            $client_data = \App\Admin::select('location')->where(['rid' => $cid])->first();
            if ($client_data->location == "single" && $role == 2) {
                $category = DB::table('bil_category')
                    ->select('bil_category.*', 'bil_type.type_name')
                    ->leftjoin('bil_type', 'bil_type.type_id', '=', 'bil_category.type_id')
                    ->where(['bil_category.is_active' => '0', 'bil_category.cid' => $cid])
                    ->orderBy('bil_category.cat_name', 'asc')
                    ->get();


                $unit_data = DB::table('bil_AddIUnits')->where(['is_active' => '0', 'cid' => $cid])->get();
                $query = \App\Item::where('item_id', $item_id)
                    ->where(['is_active' => '0', 'cid' => $cid])->first();
            } else {
                $category = DB::table('bil_category')
                    ->select('bil_category.*', 'bil_type.type_name')
                    ->leftjoin('bil_type', 'bil_type.type_id', '=', 'bil_category.type_id')
                    ->where(['bil_category.is_active' => '0', 'bil_category.cid' => $cid, 'bil_category.lid' => $lid])
                    ->orWhere(['bil_category.emp_id' => $emp_id])
                    ->orWhere(['bil_category.emp_id' => $sub_emp_id])
                    ->orderBy('bil_category.cat_name', 'asc')
                    ->get();
                $unit_data = DB::table('bil_AddIUnits')
                    ->where(['is_active' => '0', 'cid' => $cid, 'lid' => $lid])
                    ->orWhere(['emp_id' => $emp_id])
                    ->orWhere(['emp_id' => $sub_emp_id])
                    ->get();
                $query = \App\Item::where('item_id', $item_id)
                    ->where(['is_active' => '0', 'cid' => $cid, 'lid' => $lid])
                    ->first();
            }
        }
        return view('master_data.edit_item', ['item_data' => $query, 'category_data' => $category, 'unit_data' => $unit_data]);
    }
    public function updateItem(Request $request)
    {
        $requestData = $request->all();
        //        echo "<pre/>";print_r($requestData);exit;
        $validatedData = Validator::make($request->all(), [
            'item_name' => 'bail|required',
            'item_rate' => 'required',
            'item_dis' => 'required',
            'item_disrate' => 'required',
            'item_tax' => 'required',
            'item_category' => 'required'
        ], [
            'item_name.required' => 'Item Name is required!',
            'item_rate.required' => 'Item Rate is required!',
            'item_dis.required' => 'Item Discount is required!',
            'item_disrate' => 'Item Discount Rate is required',
            'item_tax.required' => 'Item Tax is required!',
            'item_category.required' => 'Category is required!'

        ])->validate();
        $item_id = $requestData['item_id'];
        $requestData['sync_flag'] = 0;
        $users = \App\Item::findorfail($item_id);
        $users->update($requestData);
        Session::flash('alert-success', 'Item updated successfully.');
        return redirect('item_data');
    }

    public function getCity($id)
    {
        $city = \App\City::select('city_id', 'city_name')->where(['state_code' => $id])->get();
        $data = "";
        $data = '<option value="">-- Select City -- </option>';
        foreach ($city as $c) {
            $data .= '<option value="' . $c->city_id . '">' . $c->city_name . '</option>';
        }
        echo $data;
    }



    public function addSupplier(Request $request)
    {
        $requestData = $request->all();
        $validatedData = Validator::make($request->all(), [
            'sup_name' => 'bail|required',
            'sup_mobile_no' => 'required',
            'sup_email_id' => 'required'
        ], [
            'sup_name.required' => 'Supplier Name is required!',
            'sup_mobile_no.required' => 'Supplier Mobile No is required!',
            'sup_email_id.required' => 'Supplier Email id is required!'

        ])->validate();
        if (Auth::guard('admin')->check()) {
            $requestData['cid'] = $this->admin->rid;
        } else if (Auth::guard('employee')->check()) {
            $requestData['cid'] = $this->employee->cid;
            $requestData['lid'] = $this->employee->lid;
            $requestData['emp_id'] = $this->employee->id;
        }
        //        echo "<pre>";
        //        print_r($requestData);exit;
        \App\Supplier::create($requestData);
        Session::flash('alert-success', 'Supplier added successfully.');
        return redirect('supplier_data');
    }

    public function getSupplier()
    {
        return view('master_data.add_supplier');
    }

    public function deleteSupplier($sup_id)
    {
        $status = 1;
        $requestData['sync_flag'] = 0;
        $query = \App\Supplier::where('sup_id', $sup_id)
            ->update(['is_active' => $status, 'sync_flag' => 0]);
        return redirect('supplier_data');
    }

    public function getSupplierData()
    {
        if (Auth::guard('admin')->check()) {
            $id = $this->admin->rid;
            $supplier_data = \App\Supplier::where(['is_active' => '0', 'cid' => $id])->get();
        } else if (Auth::guard('web')->check()) {
            $supplier_data = \App\Supplier::where(['is_active' => '0'])->get();
        } else if (Auth::guard('employee')->check()) {
            $cid = $this->employee->cid;
            $lid = $this->employee->lid;
            $emp_id = $this->employee->id;
            $role = $this->employee->role;
            $sub_emp_id = $this->employee->sub_emp_id;
            $client_data = \App\Admin::select('location')->where(['rid' => $cid])->first();
            if ($client_data->location == "single" && $role == 2) {
                $supplier_data = \App\Supplier::where(['is_active' => '0', 'cid' => $cid])->get();
            } else if ($client_data->location == "multiple" && $role == 2) {
                $supplier_data = \App\Supplier::where(['is_active' => '0', 'cid' => $cid, 'lid' => $lid])->get();
            } else if ($client_data->location == "multiple" && $role == 1) {
                $supplier_data = \App\Supplier::where(['is_active' => '0', 'cid' => $cid, 'lid' => $lid])->get();
            }
        }
        return view('master_data.supplier_data', ['supplier_data' => $supplier_data]);
    }

    public function editSupplier()
    {
        $sup_id = $_GET['sup_id'];
        if (Auth::guard('admin')->check()) {
            $id = $this->admin->rid;
            $query = \App\Supplier::where('sup_id', $sup_id)->where(['is_active' => '0', 'cid' => $id])->first();
        } else if (Auth::guard('web')->check()) {
            $query = \App\Supplier::where('sup_id', $sup_id)->where(['is_active' => '0'])->first();
        } else if (Auth::guard('employee')->check()) {
            $cid = $this->employee->cid;
            $lid = $this->employee->lid;
            $emp_id = $this->employee->id;
            $role = $this->employee->role;
            $sub_emp_id = $this->employee->sub_emp_id;

            $client_data = \App\Admin::select('location')->where(['rid' => $cid])->first();
            if ($client_data->location == "single" && $role == 2) {
                $query = \App\Supplier::where('sup_id', $sup_id)->where(['is_active' => '0', 'cid' => $cid])->first();
                //echo "<pre>";
                //echo $cid;
                //print_r($query);
                //exit;

            } else {
                $query = \App\Supplier::where('sup_id', $sup_id)->where(['is_active' => '0', 'cid' => $cid, 'lid' => $lid])
                    ->first();
            }
        }


        return view('master_data.edit_supplier', ['supplier_data' => $query]);
    }
    public function updateSupplier(Request $request)
    {
        $requestData = $request->all();
        $validatedData = Validator::make($request->all(), [
            'sup_name' => 'bail|required',
            'sup_mobile_no' => 'required',
            'sup_email_id' => 'required'
        ], [
            'sup_name.required' => 'Supplier Name is required!',
            'sup_mobile_no.required' => 'Supplier Mobile No is required!',
            'sup_email_id.required' => 'Supplier Email id is required!'

        ])->validate();
        //        echo "<pre/>";print_r($requestData);exit;
        $requestData['sync_flag'] = 0;
        $sup_id = $requestData['sup_id'];
        $users = \App\Supplier::findorfail($sup_id);
        $users->update($requestData);
        Session::flash('alert-success', 'Supplier updated successfully.');
        return redirect('supplier_data');
    }

    public function check()
    {
        $type = $_GET['type'];
        $data = $_GET['data'];

        if ($type == "Category") {
            if (Auth::guard('admin')->check()) {
                $id = $this->admin->rid;
                $query = Category::where(['cat_name' => $data, 'cid' => $id, 'is_active' => '0'])->first();
            } else if (Auth::guard('web')->check()) {
                $query = Category::where(['cat_name' => $data, 'is_active' => '0'])->first();
            } else if (Auth::guard('employee')->check()) {
                $cid = $this->employee->cid;
                $lid = $this->employee->lid;
                $emp_id = $this->employee->id;
                $role = $this->employee->role;
                $sub_emp_id = $this->employee->sub_emp_id;
                $client_data = \App\Admin::select('location')->where(['rid' => $cid])->first();
                if ($client_data->location == "single" && $role == 2) {
                    $query = Category::where(['cat_name' => $data, 'cid' => $id, 'lid' => $lid, 'is_active' => '0'])->first();
                } else if ($client_data->location == "multiple" && $role == 2) {
                    $query = Category::where(['cat_name' => $data, 'cid' => $id, 'lid' => $lid, 'is_active' => '0'])->first();
                } else if ($client_data->location == "multiple" && $role == 1) {
                    $query = Category::where(['cat_name' => $data, 'cid' => $id, 'lid' => $lid, 'is_active' => '0'])->first();
                }
            }
            //$query = Category::where('cat_name', $data)->first();
            if (!empty($query))
                echo json_encode("Already Exist");
        } else if ($type == "Unit") {
            if (Auth::guard('admin')->check()) {
                $id = $this->admin->rid;
                $query = Type::where(['Unit_name' => $data, 'cid' => $id, 'is_active' => '0'])->first();
            } else if (Auth::guard('web')->check()) {
                $query = Type::where(['Unit_name' => $data, 'is_active' => '0'])->first();
            }
            if (!empty($query))
                echo json_encode("Already Exist");

            /* $query = Type::where(['cid'=>$id,'lid'=>$lid,'is_active' => '0'])->where('Unit_name', $data)->first();
            if(!empty($query))
            echo json_encode("Already Exist");*/
        }
    }
    public function getPOSData()
    {
        if (Auth::guard('admin')->check()) {
            $id = $this->admin->rid;
            $payment_data = DB::table('bil_payement_type')->where(['is_active' => '0', 'cid' => $id])->get();
            $point_data = DB::table('bil_point_of_contact')->where(['is_active' => '0', 'cid' => $id])->get();
        } else if (Auth::guard('web')->check()) {
            $payment_data = DB::table('bil_AddIUnits')->where(['is_active' => '0'])->get();
        } else if (Auth::guard('employee')->check()) {
            $cid = $this->employee->cid;
            $lid = $this->employee->lid;
            $emp_id = $this->employee->id;
            $role = $this->employee->role;
            $sub_emp_id = $this->employee->sub_emp_id;
            $client_data = \App\Admin::select('location')->where(['rid' => $cid])->first();


            if ($client_data->location == "single" && $role == 2) {
                $payment_data = DB::table('bil_payement_type')->where(['is_active' => '0', 'cid' => $cid])->get();
                $point_data = DB::table('bil_point_of_contact')->where(['is_active' => '0', 'cid' => $cid])->get();
            } else if ($client_data->location == "multiple" && $role == 2) {

                if ($sub_emp_id != "") {
                    $payment_data = DB::table('bil_payement_type')
                        ->where(['is_active' => '0', 'cid' => $cid, 'lid' => $lid])
                        ->orWhere(['emp_id' => $sub_emp_id])
                        ->orWhere(['emp_id' => $emp_id])
                        ->get();
                    $point_data = DB::table('bil_point_of_contact')
                        ->where(['is_active' => '0', 'cid' => $cid, 'lid' => $lid])
                        ->orWhere(['emp_id' => $sub_emp_id])
                        ->orWhere(['emp_id' => $emp_id])
                        ->get();
                } else {
                    $payment_data = DB::table('bil_payement_type')->where(['is_active' => '0', 'cid' => $cid, 'lid' => $lid])->get();
                    $point_data = DB::table('bil_point_of_contact')->where(['is_active' => '0', 'cid' => $cid, 'lid' => $lid])->get();
                }
            } else if ($client_data->location == "multiple" && $role == 1) {
                $payment_data = DB::table('bil_payement_type')->where(['is_active' => '0', 'cid' => $cid, 'lid' => $lid])->get();
                $point_data = DB::table('bil_point_of_contact')->where(['is_active' => '0', 'cid' => $cid, 'lid' => $lid])->get();
            }
        }
        return view('master_data.pos_data', ['payment_data' => $payment_data, 'point_data' => $point_data]);
    }
    public function addPayment()
    {
        return view('master_data.add_payment');
    }
    public function addPOS()
    {
        return view('master_data.add_pos');
    }
    public function savePayment(Request $request)
    {
        $requestData = $request->all();
        // dd($requestData);
        // $validatedData = Validator::make($request->all(), [
        //     'payment_type' => 'bail|required'
        // ], [
        //     'payment_type.required' => 'Payment Type is required!'

        // ])->validate();
        if (Auth::guard('admin')->check()) {
            $requestData['cid'] = $this->admin->rid;
        } else if (Auth::guard('employee')->check()) {
            $requestData['cid'] = $this->employee->cid;
            $requestData['lid'] = $this->employee->lid;
            $requestData['emp_id'] = $this->employee->id;
        }
        if (isset($requestData['payment_type'])) {

            \App\PaymentType::create($requestData);
            $type = "Payment Type";
        }
        if (isset($requestData['point_of_contact'])) {
            // $validatedData = Validator::make($request->all(), [
            //     'point_of_contact' => 'bail|required'
            // ], [
            //     'point_of_contact.required' => 'Point Of Contact is required!'

            // ])->validate();
            \App\PointOfContact::create($requestData);
            $type = "POS";
        }
        Session::flash('alert-success', $type . ' Added Successfully.');
        return redirect('payment_data');
    }
    public function DeletePayment($sup_id)
    {
        $status = 1;
        $requestData['sync_flag'] = 0;
        $query = \App\PaymentType::where('id', $sup_id)
            ->update(['is_active' => $status, 'sync_flag' => 0]);
        return redirect('payment_data');
    }
    public function DeleteContact($sup_id)
    {
        $status = 1;
        $requestData['sync_flag'] = 0;
        $query = \App\PointOfContact::where('id', $sup_id)
            ->update(['is_active' => $status, 'sync_flag' => 0]);
        return redirect('payment_data');
    }
    public function editContact()
    {
        $sup_id = $_GET['id'];
        if (Auth::guard('admin')->check()) {
            $id = $this->admin->rid;
            $query = \App\PointOfContact::where('id', $sup_id)->where(['is_active' => '0', 'cid' => $id])->first();
        } else if (Auth::guard('web')->check()) {
            $query = \App\PointOfContact::where('id', $sup_id)->where(['is_active' => '0'])->first();
        } else if (Auth::guard('employee')->check()) {
            $cid = $this->employee->cid;
            $lid = $this->employee->lid;
            $emp_id = $this->employee->id;
            $role = $this->employee->role;
            $sub_emp_id = $this->employee->sub_emp_id;

            $client_data = \App\Admin::select('location')->where(['rid' => $cid])->first();
            if ($client_data->location == "single" && $role == 2) {
                $query = \App\PointOfContact::where('id', $sup_id)->where(['is_active' => '0', 'cid' => $cid])->first();
                //echo "<pre>";
                //echo $cid;
                //print_r($query);
                //exit;

            } else {
                $query = \App\PointOfContact::where('id', $sup_id)->where(['is_active' => '0', 'cid' => $cid, 'lid' => $lid])
                    ->first();
            }
        }


        return view('master_data.edit_contact', ['point_of_data' => $query]);
    }
    public function UpdateContact(Request $request)
    {
        $requestData = $request->all();
        //        echo "<pre/>";print_r($requestData);exit;
        $requestData['sync_flag'] = 0;
        $sup_id = $requestData['id'];
        $users = \App\PointOfContact::findorfail($sup_id);
        $users->update($requestData);
        Session::flash('alert-success', 'POS Updated Successfully.');
        return redirect('payment_data');
    }
    public function editPayment()
    {
        $sup_id = $_GET['id'];
        if (Auth::guard('admin')->check()) {
            $id = $this->admin->rid;
            $query = \App\PaymentType::where('id', $sup_id)->where(['is_active' => '0', 'cid' => $id])->first();
        } else if (Auth::guard('web')->check()) {
            $query = \App\PaymentType::where('id', $sup_id)->where(['is_active' => '0'])->first();
        } else if (Auth::guard('employee')->check()) {
            $cid = $this->employee->cid;
            $lid = $this->employee->lid;
            $emp_id = $this->employee->id;
            $role = $this->employee->role;
            $sub_emp_id = $this->employee->sub_emp_id;

            $client_data = \App\Admin::select('location')->where(['rid' => $cid])->first();
            if ($client_data->location == "single" && $role == 2) {
                $query = \App\PaymentType::where('id', $sup_id)->where(['is_active' => '0', 'cid' => $cid])->first();
                //echo "<pre>";
                //echo $cid;
                //print_r($query);
                //exit;

            } else {
                $query = \App\PaymentType::where('id', $sup_id)->where(['is_active' => '0', 'cid' => $cid, 'lid' => $lid])
                    ->first();
            }
        }


        return view('master_data.edit_payment', ['payment_data' => $query]);
    }
    public function UpdatePayment(Request $request)
    {
        $requestData = $request->all();
        //        echo "<pre/>";print_r($requestData);exit;
        $validatedData = Validator::make($request->all(), [
            'payment_type' => 'bail|required'
        ], [
            'payment_type.required' => 'Payment Type is required!'
        ])->validate();
        $requestData['sync_flag'] = 0;
        $sup_id = $requestData['id'];
        $users = \App\PaymentType::findorfail($sup_id);
        $users->update($requestData);
        Session::flash('alert-success', 'Payment Updated Successfully.');
        return redirect('payment_data');
    }
}

