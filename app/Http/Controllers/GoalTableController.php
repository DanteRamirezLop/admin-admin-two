<?php

namespace App\Http\Controllers;


use App\Utils\BusinessUtil;
use App\Utils\ContactUtil;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Goal;
use App\Transaction;
use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Spatie\Activitylog\Models\Activity;
use Yajra\DataTables\Facades\DataTables;

use Illuminate\Support\Facades\Auth;

class GoalTableController extends Controller
{
     /**
     * All Utils instance.
     */
    protected $contactUtil;

    protected $businessUtil;

    protected $transactionUtil;

    protected $productUtil;

    /**
     * Constructor
     *
     * @param  ProductUtils  $product
     * @return void
     */
    public function __construct(ContactUtil $contactUtil, BusinessUtil $businessUtil, TransactionUtil $transactionUtil, ModuleUtil $moduleUtil, ProductUtil $productUtil)
    {
        $this->contactUtil = $contactUtil;
        $this->businessUtil = $businessUtil;
        $this->transactionUtil = $transactionUtil;
        $this->moduleUtil = $moduleUtil;
        $this->productUtil = $productUtil;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $is_admin = $this->businessUtil->is_admin(auth()->user());
        $business_id = request()->session()->get('user.business_id');  

        if (! $is_admin && ! auth()->user()->hasAnyPermission(['sell.view', 'sell.create', 'direct_sell.access', 'direct_sell.view', 'view_own_sell_only', 'view_commission_agent_sell', 'access_shipping', 'access_own_shipping', 'access_commission_agent_shipping', 'so.view_all', 'so.view_own'])) {
            abort(403, 'Unauthorized action.');
        }


        // ->where('users.id',$user)
        if (request()->ajax()) {
            if (!$is_admin){
                $user = auth()->user()->id;
                $goal = User::leftJoin('goals as g', 'users.id', '=', 'g.user_id')
                ->leftJoin('transactions as t', 'users.id', '=', 't.res_waiter_id')
                ->where('users.id',$user)
                ->where('g.month',date('m'))
                ->where('g.year',date('Y'))
                ->where('users.business_id',$business_id)
                ->select(
                    DB::raw('SUM(IF(MONTH(t.created_at) = MONTH(CURDATE()), IF(YEAR(t.created_at)=YEAR(CURDATE()),t.final_total,0),0)) as total_sell'),
                    'users.email as username',
                    'users.surname as surname',
                    'users.first_name as first_name',
                    'users.last_name as last_name',
                    'g.amount as amount' 
                )->groupBy('username');
            }else{
                $goal = User::leftJoin('goals as g', 'users.id', '=', 'g.user_id')
                ->leftJoin('transactions as t', 'users.id', '=', 't.res_waiter_id')
                ->where('g.month',date('m'))
                ->where('g.year',date('Y'))
                ->where('users.business_id',$business_id)
                ->select(
                    DB::raw('SUM(IF(MONTH(t.created_at) = MONTH(CURDATE()), IF(YEAR(t.created_at)=YEAR(CURDATE()),t.final_total,0),0)) as total_sell'),
                    'users.email as username',
                    'users.surname as surname',
                    'users.first_name as first_name',
                    'users.last_name as last_name',
                    'g.amount as amount' 
                )->groupBy('username');
            }
                   
            $datatable = Datatables::of($goal)
            ->addColumn('conatct_name', '{{$surname}} {{$first_name}} {{$last_name}}')
            ->addColumn('status', '@if($amount<$total_sell) <span class="fa fa-check-circle text-success"></span>  @else <span class="fa fas fa-minus-circle text-red"></span>   @endif')
            ->editColumn(
                'amount',
                '$ {{number_format($amount,2)}}'
            )
            ->editColumn(
                'total_sell',
                '$ {{number_format($total_sell,2)}}'
            )->removeColumn('id');
  
             $rawColumns = ['status','conatct_name'];

            return $datatable->rawColumns($rawColumns)->make(true);
        }
    }

    
}
