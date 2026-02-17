<?php

namespace App\Http\Controllers;

use App\SellingPriceGroup;
use App\Utils\ModuleUtil;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role; //Reemplazar por use App\Goal;
use Yajra\DataTables\Facades\DataTables;
use App\BusinessLocation;
use App\Contact;
use App\Utils\ProductUtil;
use App\Utils\BusinessUtil;
use App\User;
use App\Goal;
use Carbon\Carbon;

class GoalController extends Controller
{
    /**
     * All Utils instance.
     */
    protected $moduleUtil;
    protected $productUtil;
    protected $businessUtil;


    /**
     * Create a new controller instance.
     *
     * @param  ProductUtils  $product
     * @return void
     */
     public function __construct(ModuleUtil $moduleUtil, BusinessUtil $businessUtil, ProductUtil $productUtil)
     {
         $this->productUtil = $productUtil;
         $this->moduleUtil = $moduleUtil;
         $this->businessUtil = $businessUtil;
        
     }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {      

        if (request()->ajax()) {

            $business_id = request()->session()->get('user.business_id');  
            $goals = Goal::leftJoin('users as u', 'goals.user_id', '=', 'u.id')
                        ->where('u.business_id',$business_id)
                        ->orderBy('goals.id','DESC')
                        ->select('goals.year as year',
                            'goals.id as id',
                            'goals.month as month',
                            'goals.amount as amount',
                            'u.surname as surname',
                            'u.first_name as first_name',
                            'u.last_name as last_name',
                        )
                        ->get();


            return Datatables::of($goals)
                ->addColumn(
                    'action',
                    '@can("goals.update")
                    <button data-href="{{action(\'App\Http\Controllers\GoalController@edit\', [$id])}}" class="btn btn-xs btn-primary edit_goal_button"><i class="glyphicon glyphicon-edit"></i> @lang("messages.edit")</button>
                        &nbsp;
                    @endcan
                    @can("goals.delete")
                        <button data-href="{{action(\'App\Http\Controllers\GoalController@destroy\', [$id])}}" class="btn btn-xs btn-danger delete_goal_button"><i class="glyphicon glyphicon-trash"></i> @lang("messages.delete")</button>
                    @endcan'
                )
                ->editColumn('amount','$ {{number_format($amount,2)}}')
                ->editColumn('name','{{$surname}} {{$first_name}} {{$last_name}}')
                ->removeColumn('id')
                ->editColumn('month',
                    '@switch($month)
                                @case (1)
                                    @lang( "goal.january" )
                                    @break;
                                @case (2)
                                    @lang( "goal.february" )
                                    @break;
                                @case (3)
                                    @lang( "goal.march" )
                                    @break;
                                @case (4)
                                    @lang( "goal.april" )
                                    @break;
                                @case (5)
                                    @lang( "goal.may" )
                                    @break;
                                @case (6)
                                    @lang( "goal.june" )
                                    @break;
                                @case (7)
                                    @lang( "goal.july" )
                                    @break;
                                @case (8)
                                    @lang( "goal.august" )
                                    @break;
                                @case (9)
                                    @lang( "goal.september" )
                                    @break;
                                @case (10)
                                    @lang( "goal.october" )
                                    @break;
                                @case (11)
                                    @lang( "goal.november" )
                                    @break;
                                @case (12)
                                    @lang( "goal.december" )
                                    @break;
                            @endswitch'
                )
                ->rawColumns(['action'])
                ->make(true);
        }
        $type = request()->get('type');
        return view('goal.index',compact('type'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $default_datetime = $this->businessUtil->format_date('now', true);
        $date = Carbon::now();
        $year = $date->format('Y');
        $business_id = request()->session()->get('user.business_id');
        $service_staffs = null;
        if ($this->productUtil->isModuleEnabled('service_staff')) {
            $service_staffs = $this->productUtil->serviceStaffDropdown($business_id);
        }
        return view('goal.create')->with(compact('default_datetime','service_staffs','year'));       
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {       
        $date = Carbon::now();
        $year = $date->format('Y'); 
        try {
            $user = $request->input('user_id');
            $amount = $request->input('amount');
            // $year = $request->input('year');
            $month = $request->input('month');
            $description = $request->input('description');
            Goal::create([
                'user_id' => $user,
                'amount' => $amount,
                'year' => $year,
                'month' => $month,
                'description' => $description
            ]);
            
            $output = ['success' => true,
                    'msg' => 'agregad correctamente',
                ];
            
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false,
            'msg' => __('messages.something_went_wrong'),
            ];
        }

        return $output;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $date = Carbon::now();
        $year = $date->format('Y'); 
        $service_staffs = User::all();
        $goals = Goal::find($id);
        return view('goal.edit')->with(compact('service_staffs','goals','year'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if (! auth()->user()->can('unit.update')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                $input = $request->only(['user_id', 'amount', 'year', 'month', 'description']);
                $goal = Goal::find($id);
                $goal->user_id = $input['user_id'];
                $goal->amount = $input['amount'];
                $goal->month = $input['month'];
                $goal->description = $input['description'];

                $goal->save();
                
                $output = ['success' => true,
                        'msg' => 'successfully added',
                    ];
                
            } catch (\Exception $e) {
                \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

                $output = ['success' => false,
                    'msg' => __('messages.something_went_wrong'),
                ];
            }
            return $output;
        }     
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (! auth()->user()->can('unit.delete')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                $goal = Goal::find($id);

                $goal->delete();
                    $output = ['success' => true,
                        'msg' => __('goal.deleted_success'),
                    ];
            } catch (\Exception $e) {
                \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

                $output = ['success' => false,
                    'msg' => __('messages.something_went_wrong'),
                ];
            }
            return $output;    
        }    
    }


   


    private function __createPermissionIfNotExists($permissions)
    {
        // $exising_permissions = Permission::whereIn('name', $permissions)
        //                             ->pluck('name')
        //                             ->toArray();

        // $non_existing_permissions = array_diff($permissions, $exising_permissions);

        // if (! empty($non_existing_permissions)) {
        //     foreach ($non_existing_permissions as $new_permission) {
        //         $time_stamp = \Carbon::now()->toDateTimeString();
        //         Permission::create([
        //             'name' => $new_permission,
        //             'guard_name' => 'web',
        //         ]);
        //     }
        // }
    }
}
