<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Utils\ModuleUtil;
use Yajra\DataTables\Facades\DataTables;
use App\Contact;
use App\Loan;
use App\Utils\ProductUtil;
use App\Utils\BusinessUtil;
use Carbon\Carbon;
use App\Product;
use App\User;
use App\Category;
use App\Utils\ContactUtil;
use App\Variation;
use App\Goal;
use App\Delay;
use App\PaymentSchedule;
use App\Utils\TransactionUtil;
use Illuminate\Support\Facades\DB;
use App\BusinessLocation;
use App\Transaction;
use App\TransactionSellLine;
use App\TransactionSellLinesPurchaseLines;
use App\TransactionPayment;
use App\InvoiceScheme;
use App\ScheduleVersion;
use App\PaymentApplication;
use App\ExchangeRates;

class LoanController extends Controller
{
    /**
     * All Utils instance.
     */
    protected $transactionUtil;
    protected $moduleUtil;
    protected $productUtil;
    protected $businessUtil;
    protected $contactUtil;
    /**
     * Create a new controller instance.
     *
     * @param  ProductUtils  $product
     * @return void
     */
    public function __construct(TransactionUtil $transactionUtil,ModuleUtil $moduleUtil, BusinessUtil $businessUtil, ProductUtil $productUtil, ContactUtil $contactUtil)
    {
        $this->productUtil = $productUtil;
        $this->moduleUtil = $moduleUtil;
        $this->businessUtil = $businessUtil;
        $this->contactUtil = $contactUtil;
        $this->transactionUtil = $transactionUtil;
    }


    public function index()
    {   

        if (request()->ajax()) {
           $business_id = request()->session()->get('user.business_id');
           $psAgg = DB::table('payment_schedules as ps')
            ->leftJoin('schedule_versions as sv', 'sv.id', '=', 'ps.schedule_version_id')
            ->selectRaw("
                ps.loan_id,
                COALESCE(SUM(
                    CASE WHEN ps.status <> 'pending'
                    THEN ps.mount_quota + ps.gps_quota + ps.sure_quota + ps.admin_fee_quota + ps.initial
                    ELSE 0 END
                ),0) as delay,

                COALESCE(SUM(
                    CASE WHEN ps.status = 'pending'
                    THEN ps.mount_quota + ps.gps_quota + ps.sure_quota + ps.admin_fee_quota + ps.initial
                    ELSE 0 END
                ),0) as for_due
            ")
            ->where(function ($q) {
                // Subquery correlacionado: ¿este loan_id tiene alguna versión activa?
                $activeVersionExists = function ($sq) {
                    $sq->selectRaw('1')
                    ->from('payment_schedules as psx')
                    ->join('schedule_versions as svx', 'svx.id', '=', 'psx.schedule_version_id')
                    ->whereColumn('psx.loan_id', 'ps.loan_id')
                    ->where('svx.status', 'active')
                    ->limit(1);
                };
                $q
                // CASO A: Si NO existe versión activa => usa SOLO originales (NULL)
                ->where(function ($q1) use ($activeVersionExists) {
                    $q1->whereNotExists($activeVersionExists)
                    ->whereNull('ps.schedule_version_id');
                })
                // CASO B: Si SÍ existe versión activa => usa SOLO filas de esa(s) versión(es) activa(s)
                ->orWhere(function ($q2) use ($activeVersionExists) {
                    $q2->whereExists($activeVersionExists)
                    ->where('sv.status', 'active');
                });
            })
            ->groupBy('ps.loan_id');

            $dAgg = DB::table('delays as d')
            ->selectRaw("
                d.loan_id,
                COALESCE(SUM(
                    CASE WHEN d.status = 'late'
                    THEN d.late_amount
                    ELSE 0 END
                ),0) as mora
            ")
            ->whereNull('d.deleted_at')
            ->groupBy('d.loan_id');

            $loans = Loan::query()
            ->leftJoin('transactions', 'loans.transaction_id', '=', 'transactions.id')
            ->leftJoinSub($psAgg, 'psa', function ($join) {
                $join->on('psa.loan_id', '=', 'loans.id');
            })
            ->leftJoinSub($dAgg, 'da', function ($join) {
                $join->on('da.loan_id', '=', 'loans.id');
            })
            ->where('loans.business_id', $business_id)
            ->where('loans.status', '!=', 'quotation')
            ->select(
                'loans.id',
                'loans.loan_amount',
                'loans.amount',
                'loans.created_at',
                'loans.transaction_id',
                'loans.status',
                'loans.vin',
                'loans.type_product',
                'loans.product_name',
                'loans.number_month',
                'loans.waiter',
                'transactions.final_total as final_total',
                DB::raw('(SELECT SUM(IF(TP.is_return = 1,-1*TP.amount,TP.amount))
                        FROM transaction_payments AS TP
                        WHERE TP.transaction_id = transactions.id) as total_paid'),
       
                DB::raw('(SELECT 
                            SUM(IF(TP.is_return = 1,-1*TP.amount,TP.amount))
                            FROM transaction_payments AS TP
                            WHERE TP.transaction_id = transactions.id AND TP.payment_schedule_id IS NOT NULL
                        ) as total_only_payments'),

                DB::raw('COALESCE(psa.delay,0) as delay'),
                DB::raw('COALESCE(da.mora,0) as mora'),
                DB::raw('COALESCE(psa.for_due,0) as for_due'),
            )
            ->get();

            return Datatables::of($loans)->addColumn(
                    'action',
                     function ($row){
                             if (auth()->user()->can('user.view') || auth()->user()->can('user.create') || auth()->user()->can('roles.view')){                
                            $html = '<div class="btn-group">
                                    <button type="button" class="btn btn-info dropdown-toggle btn-xs" 
                                        data-toggle="dropdown" aria-expanded="false">'.
                                        __('messages.actions').
                                        '<span class="caret"></span><span class="sr-only">Toggle Dropdown
                                        </span>
                                    </button>
                                        <ul class="dropdown-menu dropdown-menu-left" role="menu">.   
                                            <li><a href="'.route('addNumberLetter',[$row->id]).'"><i class="fa fa-list" aria-hidden="true"></i> Asignar número de letra</a></li>';

                                $html .= '<li class="divider"></li>';
                                $html .= '<li><a href="'.action([\App\Http\Controllers\LoanController::class, 'show'], [$row->id]).'" "><i class="fas fa fa-calendar" aria-hidden="true"></i> Calendario de pagos</a></li>';
                                $html .= '<li class="divider"></li>';
                                $html .= '<li><a href="'.action([\App\Http\Controllers\LoanPaymentController::class, 'statemenPDF'], [$row->id]).'"  ><i class="fas fa fa-download" aria-hidden="true"></i> Descargar estado de cuenta</a></li>';
                                $html .= '<li><a href="#" class="print-invoice" data-href="'.route('sell.printInvoice', [$row->transaction_id]).'"><i class="fas fa-print" aria-hidden="true"></i> '.__('lang_v1.print_invoice').'</a></li>';
                                $html .= '<li><a href="'.action([\App\Http\Controllers\TransactionPaymentController::class, 'show'], [$row->transaction_id]).'" class="view_payment_modal"><i class="fas fa-money-bill-alt"></i> '.__('purchase.view_payments').'</a></li>';
                                $html .= '<li><a href="#" data-href="'.action([\App\Http\Controllers\SellController::class, 'show'], [$row->transaction_id]).'" class="btn-modal" data-container=".view_modal"><i class="fas fa-eye" aria-hidden="true"></i> '.__('messages.view').'</a></li>';
                                $html .= '<li><a href="'.action([\App\Http\Controllers\LoanController::class, 'destroy'], [$row->id]).'" class="delete-sale"> <i class="fas fa-trash"></i> '.__('messages.delete').'</a></li>';
                        }else{
                          $html = '<div class="btn-group">
                                    <button type="button" class="btn btn-info dropdown-toggle btn-xs" 
                                        data-toggle="dropdown" aria-expanded="false">'.
                                        __('messages.actions').
                                        '<span class="caret"></span><span class="sr-only">Toggle Dropdown
                                        </span>
                                    </button>
                                     <ul class="dropdown-menu dropdown-menu-left" role="menu">
                                     <li><a href="'.action([\App\Http\Controllers\LoanPaymentController::class, 'statemenPDF'], [$row->id]).'"  ><i class="fas fa fa-download" aria-hidden="true"></i> Descargar estado de cuenta</a></li>';
                        } 
                        
                            $html .= '</ul></div>';
                            return $html;
                     }
                )->addColumn(
                    'label',
                    function ($row){
                        switch ($row->status) {
                            case "approved":
                                $label = '<span class="label label-info">Aprobado</span>';
                                break;
                            case"partial":
                                $label = '<span class="label label-info">Parcial</span>';
                                break;
                            case"in arrears":
                                $label = '<span class="label label-danger">Atrasado</span>';
                                break;
                            case"cancelled":
                                $label = '<span class="label label-default">Cancelado</span>';
                                break;
                            case"paid":
                                $label = '<span class="label label-success">pagado</span>';
                                break;
                        }
                        return $label;
                     }
                )
                 ->addColumn('total_delay', function ($row) {
                     
                   
                    
                   $mora = round($row->mora);
                    if($mora){
                        $total_delay = bcsub($row->delay, $row->total_only_payments, 4);
                    }else{
                        $total_delay = 0;
                    }

                    
                    
                     if($total_delay < 0 && $total_delay > -0.25) {
                        $total_delay = 0;
                    }
                    $total_delay_html = '<span class="payment_due" data-orig-value="'.$total_delay.'">'.$this->transactionUtil->num_f($total_delay, true).'</span>';
                    return $total_delay_html;
                })

                ->addColumn('total_to_delay', function ($row) {
              
              
                    $mora = round($row->mora);
                    if($mora){
                        $paid_partial = 0;
                    }else{
                        $paid_partial = bcsub($row->delay, $row->total_only_payments, 4);
                    }

                    $total_to_delay =  $row->for_due + $paid_partial;
                    
                    
                    
                    $total_to_delay_html = '<span class="payment_due" data-orig-value="'.$total_to_delay.'">'.$this->transactionUtil->num_f($total_to_delay, true).'</span>';
                    return $total_to_delay_html;
                })
                ->addColumn('total_remaining', function ($row) {
                    
                    
          
                    
                    
                     $mora = round($row->mora);
                    if($mora){
                         $total_remaining = bcsub($row->delay, $row->total_only_payments, 4) + $row->mora;
                    }else{
                        $total_remaining = 0;
                    }
                    
                    if($total_remaining < 0 && $total_remaining > -0.25) {
                        $total_remaining = 0;
                    }
                    
                    $total_remaining_html = '<span class="payment_due" data-orig-value="'.$total_remaining.'">'.$this->transactionUtil->num_f($total_remaining, true).'</span>';
                    return $total_remaining_html;
                })

                 ->addColumn('total_mora', function ($row) {
                    $total_mora = $row->mora;
                    $total_mora_html = '<span class="payment_due" data-orig-value="'.$total_mora.'">'.$this->transactionUtil->num_f($total_mora, true).'</span>';
                    return $total_mora_html;
                })

                 ->addColumn('total_remaining_mora', function ($row) {
                    $total_remaining = $row->final_total - $row->total_paid;
                    $total_remaining_html = '<span class="payment_due" data-orig-value="'.$total_remaining.'">'.$this->transactionUtil->num_f($total_remaining, true).'</span>';
                    return $total_remaining_html;
                })

                ->editColumn(
                    'final_total',
                    '<span class="final-total" data-orig-value="{{$final_total}}"> @format_currency($final_total)  </span>'
                )
                ->editColumn(
                    'total_paid',
                    '<span class="total-paid" data-orig-value="{{$total_paid}}">@format_currency($total_paid)</span>'
                )

                ->editColumn('loan_amount','$ {{number_format($loan_amount,2)}}')
                ->editColumn('amount','$ {{number_format($amount,2)}}')
                ->editColumn('created_at','{{date("Y/m/d",strtotime($created_at))}}')
                ->rawColumns(['action','label','seller','total','final_total','total_paid','total_remaining','total_delay','total_to_delay','total_mora','total_remaining_mora'])
                ->make(true);
        }
        $type = request()->get('id');
        return view('loan.index',compact('type'));
    }

    public function create()
    {
        $business_id = request()->session()->get('user.business_id');
        $customer = Contact::where('type', 'customer')->where('business_id',$business_id)->get();
        $category = Category::where("name","Maquinarias")->first();
        $products = Product::where('category_id',$category->id)->where('is_inactive',0)->get();
        $walk_in_customer = $this->contactUtil->getWalkInCustomer($business_id);
        //variables en Credito
        $filing_fee = Goal::where('name','coste-tramite')->first();
        $gps = Goal::where('name','gps')->first();
        $insurance = Goal::where('name','seguro')->first();
        $waiters = $this->transactionUtil->getModuleStaff($business_id,'customer.view_own',true);
        $rightNow = Carbon::now();
        return view('loan.create',compact('customer','products','walk_in_customer','filing_fee','gps','insurance','waiters','rightNow'));
    }

 public function store(Request $request)
    {
        try {
                DB::beginTransaction();
                //------------CAPTURA DE DATOS DEL FORMULARIO---------------
                $initial_amount = $request->input('pay_initial'); //Cantidad a pagar de la inicial
                $multiplayer = $request->input('multiplayer'); //Tasa anual 
                $number_month = $request->input('number_month'); //Número de meses del prestamo
                $type_initial = $request->input('type_initial'); //Tipo de inicial 1= completo, 2= fraccionado
                $option_tramite = $request->input('option_tramite');
                $option_gps = $request->input('option_gps');
                $option_seguro = $request->input('option_seguro');
                $business_id = request()->session()->get('user.business_id'); 
                $product_id = $request->input('product_id'); 
                $product = Product::find($product_id);
                $created_on = $this->transactionUtil->uf_date($request->input('created_on'), true);
                $date = Carbon::parse($created_on);
                $meses_gps_seguro = $request->input('mounth_expenses_financed'); // Número de meses que se va a Fraccionar EL SEGURO, GATOS DE TRAMITE Y GPS
                $mounth_fracction_initial = $request->input('mounth_fracction'); // Número de mese que se va a fininciar la inicial
                
                // -----VALIDAR QUE LOS MESES DEL PRESTAMO SEA MAYOR A LOS MESES DE LA INICIAL Y LOS GASTOS FINANCIADOS----
                if($number_month < $meses_gps_seguro ){
                    $output = ['success' => false,
                        'msg' => 'EL PRESTAMO NO SE CREO, los meses del financiamineto no puede ser mayor a los meses del prestamo'
                    ];
                    return redirect('loans')->with('status', $output);
                }
                if($number_month < $mounth_fracction_initial ){
                    return  $output = ['success' => false,
                        'msg' => 'EL PRESTAMO NO SE CREO, los meses del financiamineto no puede ser mayor a los meses del prestamo'
                    ];
                    return redirect('loans')->with('status', $output);
                }
                
                //-----VALIDAR CODIGO PERU +51-------
                if($request->input('mobile')){
                    $mobile = $this->formatearTelefonoPeru($request->input('mobile'));
                }else{
                    $mobile = $request->input('mobile');
                }
                //-----ACTUALIZAR EMAIL Y TELEFONO DEL CLIENTE-------
                $customer_id = $request->input('contact_id');
                $customer = Contact::find($customer_id);
                $customer->email = $request->input('email'); 
                $customer->mobile = $mobile; 
                $customer->save();
                //----CAPTURA DE DATOS DEL PRODUCTO------
                $type_product =$request->input('customer');
                $waiter = $request->input('waiter');
                $variation_id = $request->input('variation');
                $variation =  Variation::find($variation_id);
                $product_mount = $variation->sell_price_inc_tax; //Precio de la maquinaria seleccionada
                //-----INICIALIZACION DE VARIABLES------
                $json = [];
                $pay_initial = 0;
                //$initial_amount = 0;
                $gps_coutes = 0;
                $seguro_coutes = 0;
                $admin_fee_coutes = 0;
                $admin_fee_init = 0;
                $gps_init = 0; 
                $seguro_init = 0; 
                $admin_fee_quotes = 0;
                $gps_quotes = 0; 
                $insurance_quotes = 0; 
                $amount_fracction = 0;
                $mounth_fracction = 0;
                $initial_cuotes = 0;
                $taxes_fraccion = 0;
                //----TERMINOS Y CONDICIONES ACTUALES----------------
                $terms =  Goal::where('name','terminos-y-condiciones')->first()->description;
                //-----VALIDAR SI SE COBRA EL COSTO DE TRAMITE-----
                if($option_tramite == 1){ 
                    $admin_fee_tbl =  Goal::where('name','coste-tramite')->first(); //Cotos Total del tramite
                    $admin_fee_init = $admin_fee_tbl->amount_inicial; //Inicial del tramite, normalmente es 800 que es todo el tramite para que no se fraccione
                    $admin_fee_total =  $admin_fee_tbl->amount_total; //Inicial del tramite
                    $admin_fee_quotes = round($admin_fee_total - $admin_fee_init,2);
                    $admin_fee_coutes = $admin_fee_quotes / $meses_gps_seguro; //Monto de la cuota del tramite
                }
                //-----VALIDAR SI SE COBRA EL GPS-----
                if($option_gps == 1){
                    $gps_tbl =  Goal::where('name','gps')->first();
                    $gps_init = $gps_tbl->amount_inicial;
                    $gps_amount_total = $gps_tbl->amount_total;
                    $gps_quotes = round($gps_amount_total - $gps_init,2);
                    $gps_coutes = $gps_quotes / $meses_gps_seguro; 
                }
                //-----VALIDAR SI SE COBRA EL SEGURO-----
                if($option_seguro == 1){
                    $seguro_tbl =  Goal::where('name','seguro')->first();
                    $seguro_init =  $seguro_tbl->amount_inicial; 
                    $seguro_amount_total = $seguro_tbl->amount_total;
                    $insurance_quotes = round($seguro_amount_total - $seguro_init,2);
                    $seguro_coutes = $insurance_quotes / $meses_gps_seguro; 
                }
                // ---CALCULAR PORCENTAJE DE LA INICIAL---
                $pay_initial = (100 * $initial_amount) / $product_mount;  
                //---VALIDAR QUE LA INICIAL SEA FRACCIONADA O NO----
                if($type_initial == 2){
                    $amount_fracction = $request->input('amount_fracction'); // Monto de la fraccion de la inicial
                    $mounth_fracction = $request->input('mounth_fracction'); // Número de meses a fraccionar la inicial
                    $rate_fracction = $request->input('rate_fracction'); // Taza anual de la fraccion de la inicial
                    $initial_cuotes = $this->calculateQuote($rate_fracction, $mounth_fracction, $amount_fracction); 
                    $mount_axu = $initial_cuotes *  $mounth_fracction;
                    $taxes_fraccion = bcsub($mount_axu, $amount_fracction,4); // Impuestos generados por la fraccion de la inicial
                }
                //-----MONTO DEL PRESTAMO-----
                $loan_amount =  round($product_mount - $initial_amount,4);
                //-----CALCULAR LA CUOTA MENSUAL DEL PRESTAMO CON FORMULA FRANCESA-----
                    $tasaMensual = ($multiplayer / 100) / 12; // Calcular la cuota mensual usando la fórmula del préstamo francés
                    if ($tasaMensual > 0) {
                        $cuota = $loan_amount * ($tasaMensual * pow(1 + $tasaMensual, $number_month)) / (pow(1 + $tasaMensual, $number_month) - 1);
                    } else {
                        $cuota = $loan_amount / $number_month; // Si la tasa es 0, simplemente dividir el monto total entre el número de meses
                    }
                    $amount_fraccion = round($cuota, 4);
                //--------MONTO DEL PRESTAMO--------
                $saldo = $loan_amount; 
                //--------GENERAR EL CALENDARIO DE PAGOS EN JSON--------
                $date_ini = date("d-m-Y",strtotime($date));
                $json = [];
                for ($i=1; $i < $number_month + 1; $i++) {
                    $date_ini = $date->addMonths(1); // Sumo los meses
                    $saldo_inicial = $saldo; //saldo Inicial
                    $interes = $saldo * $tasaMensual; // Interés del mes
                    $amortizacion =  $cuota - $interes; // Pago a capital
                    $saldo -= $amortizacion; // Nuevo saldo
                    if ($saldo < 0) $saldo = 0; // Evitar valores negativos
                    //--------Franccion de Inicial----
                    if($i <= $mounth_fracction){
                        $initial_fraccion = $initial_cuotes;
                    }else{
                        $initial_fraccion = 0;
                    }
                    //--------Franccion de GPS, SEGURO Y TRAMITE----
                    if($i <= $meses_gps_seguro){
                        $gps =  $gps_coutes;
                        $seguro = $seguro_coutes;
                        $adminfee = $admin_fee_coutes;
                    }else{
                        $gps = 0;
                        $seguro = 0;
                        $adminfee = 0;
                    }
                    //---------------------
                    $date_quota = date("Y-m-d",strtotime($date_ini));
                    //---------------------
                    array_push($json, array(
                        'id' => $i, 
                        'date' => $date_quota,
                        'saldo_inicial' => $saldo_inicial,
                        'amount' => $amount_fraccion,
                        'capital' => $amortizacion, 
                        'interes' => $interes,
                        'saldo_final'=> $saldo, 
                        'total_pay' => 0, 
                        'status' => 0,
                        'gps'=> $gps,
                        'seguro'=> $seguro,
                        'initial'=> $initial_fraccion, 
                        'admin_fee'=> $adminfee,
                    ));
                }
                //Coste total del préstamo
                $coste_prestamo =   ($amount_fraccion * $number_month);
                $coste_prestamo_gps_seguro =   $coste_prestamo +  $gps_quotes +  $insurance_quotes + $admin_fee_quotes;
                //Importe total de los intereses
                $intereses =  round($coste_prestamo - $loan_amount,4);
                $user_id = auth()->user()->id;
                //Decodificar Anexoxs
                $op_annexes = [];
                $op_annexes["anexo_1"] = $request->input('anexo_1');
                $op_annexes["anexo_2"] =  $request->input('anexo_2');
                $op_annexes["anexo_3"] =  $request->input('anexo_3');
                $op_annexes["anexo_4"] =  $request->input('anexo_4');
                $annexes = json_encode($op_annexes);
                $loan = Loan::create([
                    'customer_id' => $customer_id,
                    'user_id' => $user_id,
                    'business_id' => $business_id, 
                    'product_id' =>  $product_id,
                    'status'=> 'approved',
                    'product_name' => $product->name,
                    'date' => $created_on,
                    'type_product' => $type_product,
                    'period' => 2, //1 Contado / 2 Credito 
                    'number_month' => $number_month, // meses del prestamo
                    'multiplier' => $multiplayer,//Taza anual
                    'rate' => $intereses, // los intereces a pagar
                    'amount' => $coste_prestamo_gps_seguro, // El cotos total del prestamo: lo prestado más intereces
                    'quotes' => json_encode($json), //Pagos mensuales del prestamo
                    'admin_fee' => $admin_fee_init, // Gasto administrativo, se cobra con la inicial y no se agrega al prestamo
                    'gps' => $gps_init, // Gasto del GPS, se cobra con la inicial y el resto se agrega al prestamo en fraccion
                    'insurance' => $seguro_init, // Gasto del seguro, se cobra con la inicial y el resto se agrega al prestamo en fraccio
                    'gps_quotes' => $gps_quotes, 
                    'insurance_quotes' => $insurance_quotes,
                    'admin_fee_quotes' => $admin_fee_quotes, 
                    'loan_amount'=> $loan_amount, //Monto del prestamos 
                    'product_price'=> $product_mount, //Precio de la maquinari en el momento de la cotización 
                    'initial_percentage'=> $pay_initial, //porcentaje de la incial 
                    'initial_amount'=> $initial_amount, //Cantida a pagar de la Incial 
                    'contact_source' => $request->input('contact_source'),  //Fuente de contacto
                    'terms'=>$terms,
                    'waiter' =>$waiter,
                    'vin'=> $request->input('vin'),
                    'annexes'=>$annexes,
                    'initial_fraction'=> $amount_fracction,
                    'mounth_initial'=> $mounth_fracction,
                    'start_rate'=>$taxes_fraccion,
                ]);
                $this->generateQuota($loan);
                //Crear la ventas predeterminadas en el ERP
                $input = $this->newSale($loan,$taxes_fraccion);
                $invoice_total = [
                    'total_before_tax'=>$product_mount,
                    'tax'=> 0,
                ];
                $transaction = $this->transactionUtil->createSellTransaction($business_id, $input, $invoice_total, $user_id, false);
                $transaction->payment_status = 'partial';
                $transaction->save();
                //Editar loand
                $loan->transaction_id = $transaction->id;
                $loan->status = 'partial';
                $loan->save();
                // Fecha del prestamo
                $paid_on = Carbon::parse($created_on);
                //Creando los pagos
                $this->newSaleLine($loan,$transaction->id,$variation_id);
                $note_init = 'Initial ';
                if($admin_fee_init !== 0){
                    $initial_amount += $admin_fee_init;
                    $note_init .= '+ Coste de tramite ';
                }
                if($gps_init !== 0){
                    $initial_amount += $gps_init;
                    $note_init .= '+ Inicial GPS ';
                }
                if($seguro_init !== 0){
                     $initial_amount += $seguro_init;
                    $note_init .= '+ Inicial del seguro ';
                }
                $initial_amount = round($initial_amount -  $amount_fracction,4);
                $this->newTransaction($transaction,$initial_amount,$user_id,$customer_id,$paid_on,$note_init);

                DB::commit();

                $output = ['success' => true,'msg' => 'successfully added'];

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());
            $output = ['success' => false,'msg' => 'Error: '.$e->getLine().'Message:'.$e->getMessage()];
        }
        return redirect('loans')->with('status', $output);
    }

    private function calculateQuote($multiplayer, $number_month, $loan_amount){
        $tasaMensual = ($multiplayer / 100) / 12; 
        if ($tasaMensual > 0) {
            $cuota = $loan_amount * ($tasaMensual * pow(1 + $tasaMensual, $number_month)) / (pow(1 + $tasaMensual, $number_month) - 1);
        } else {
            $cuota = $loan_amount / $number_month; // Si la tasa es 0, simplemente dividir el monto total entre el número de meses
        }
        $amount_fraccion = round($cuota, 4);
        return  $amount_fraccion;
    }

    //Se puede reducir con la funcion en TransactionUtil->newTransaction
    private function newTransaction(Transaction $transaction, $amount,$user_id,$payment_for,$paid_on,$note_init){
            $ref_count = $this->transactionUtil->setAndGetReferenceCount('sell_payment',$transaction->business_id);
            //Generate reference number
            $payment_ref_no = $this->transactionUtil->generateReferenceNumber('sell_payment', $ref_count, $transaction->business_id);
            
            $note = '';
            TransactionPayment::create([
                'transaction_id'=> $transaction->id,
                'business_id'=> $transaction->business_id,
                'is_return'=> 0,
                'amount'=> $amount,
                'method'=> 'cash',
                'payment_type'=> null,
                'transaction_no'=> null,
                'card_transaction_number'=> null,
                'card_number'=> null,
                'card_type'=> 'credit',
                'card_holder_name'=> null,
                'card_month'=> null,
                'card_year'=> null,
                'card_security'=> null,
                'cheque_number'=> null,
                'bank_account_number'=> null,
                'paid_on'=> $paid_on,
                'created_by'=> $user_id,
                'paid_through_link'=> 0,
                'gateway'=> null,
                'is_advance'=> 0,
                'payment_for'=> $payment_for,
                'parent_id'=> null,
                'note'=> $note_init,
                'document'=> null,
                'payment_ref_no'=> $payment_ref_no,
                'account_id'=> null,
            ]);
    }

    private function newSaleLine(Loan $loan,$transaction_id,$variation_id){
       $transactionSellLine = TransactionSellLine::create([
            'transaction_id'=>$transaction_id,
            'product_id' => $loan->product_id,
            'variation_id' => $variation_id,
            'quantity' => 1,
            'mfg_waste_percent' => 0,
            'secondary_unit_quantity' => 0, 
            'quantity_returned' =>0,
            'unit_price_before_discount' => $loan->product_price,
            'unit_price' => $loan->product_price,
            'line_discount_type' => 'fixed',
            'line_discount_amount' => 0,
            'unit_price_inc_tax' => $loan->product_price,
            'item_tax' => 0,
            'tax_id' => null,
            'discount_id' => null,
            'sell_line_note' => null,
            'so_quantity_invoiced'=> 0,
	        'children_type'=>'',
            'sub_unit_id' => null,
            'res_service_staff_id' => null,
            'res_line_order_status' => null,
            'so_line_id' => null,
        ]);
        TransactionSellLinesPurchaseLines::create([
            'sell_line_id'=> $transactionSellLine->id,
            'stock_adjustment_line_id'=>null,
            'purchase_line_id'=>0,
            'quantity'=>1,
            'qty_returned'=>0,
        ]);
    }

    private function newSale(Loan $loan, $taxes_fraccion){
        $location_id = BusinessLocation::where('business_id',$loan->business_id)->first()->id;
        $mount_optional =  $loan->admin_fee + $loan->admin_fee_quotes +$loan->gps + $loan->insurance + $loan->gps_quotes + $loan->insurance_quotes; //Suma del GPS + Seguro + gasto administrativo
        $input = [
                'location_id' => $location_id,
                'contact_id' => $loan->customer_id,
                'res_waiter_id'=> auth()->user()->id,
                'final_total' => $loan->product_price + $loan->rate + $mount_optional +  $taxes_fraccion, //Precio de la maquinaria + el importe total de interes + SEGUR0+ TRAMINTE+ GPS
                'status' => 'final',
                'additional_notes' => '',
                'transaction_date' => \Carbon::now(),
                'tax_rate_id' => null,
                'sale_note' => null,
                'commission_agent' => null,
                'discount_type' => 'percentage',
                'discount_amount' => 0,
                'is_direct_sale' => 1,
                'exchange_rate' => 1,
                'recur_interval' =>  1,
                'recur_interval_type' => 'days',
                'pay_term_number' => $loan->number_month,
                'pay_term_type' =>'months',
                'additional_expense_key_1'=>'Importe total de los intereses',
                'additional_expense_value_1'=>$loan->rate,
                'additional_expense_key_2'=>'Cargos / Intereses por mora',
                'additional_expense_value_2'=>0,
                'additional_expense_key_3'=>'Coste de tramite, GPS y seguro',
                'additional_expense_value_3'=> $mount_optional,
                'additional_expense_key_4'=>'Importe de los intereses de inicial',
                'additional_expense_value_4'=> $taxes_fraccion,
            
            ];
        return  $input;
    }

    private function generateQuota(Loan $loan){
        $quotas = json_decode($loan->quotes);
        $count = 1;
        $prefix = '000-';
        $scheme = InvoiceScheme::where('business_id',$loan->business_id)->where('name','Numero de letra')->first();
        if($scheme){
            $count = $scheme->invoice_count;
            $prefix = $scheme->prefix;
        }
        foreach($quotas as $quota){
            $number_letter = $prefix.$count;
            PaymentSchedule::create([
                'loan_id'=>$loan->id,
                'number_quota'=>$quota->id,
                'sheduled_date'=>$quota->date,
                'mount_quota'=>$quota->amount,
                'status'=>'pending',
                'opening_balance'=>$quota->saldo_inicial,
                'capital'=>$quota->capital,
                'interests'=>$quota->interes,
                'final_balance'=>$quota->saldo_final,
                'gps_quota'=>$quota->gps,
                'sure_quota'=>$quota->seguro,
                'admin_fee_quota'=>$quota->admin_fee,
                'number_letter'=>$number_letter,
                'initial'=> isset($quota->initial) ? $quota->initial : 0,
            ]);
            $count ++;
        }
        if($scheme){
            $scheme->invoice_count =  $count;
            $scheme->save();
        }
    }
	
	public function destroy($id)
    {
        if (! auth()->user()->can('loan.delete')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                $business_id = request()->user()->business_id;
                $loan = Loan::where('business_id', $business_id)->findOrFail($id);
                
                if ($loan) {
                    DB::beginTransaction();
                    $transaction_id = $loan->transaction_id;
                    if ($transaction_id) {
                        $output = $this->transactionUtil->deleteSale($business_id, $transaction_id); //Eliminar la transacción
                        $loan->delete(); //Eliminar el Prestamo y sus relaciones
                        $output = ['success' => true,'msg' => __('loan.deleted_success'),];
                    }else{
                         $output = ['success' => false,'msg' =>  __('lang_v1.loan_cannot_be_deleted'),];
                    }
                } else {
                    $output = ['success' => false,'msg' =>  __('lang_v1.loan_cannot_be_deleted'),];
                }

                DB::commit();
            } catch (\Exception $e) {
                 DB::rollBack();
                \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());
                $output = ['success' => false,
                    'msg' => '__("messages.something_went_wrong")',
                ];
            }
            return $output;
        }
    }

    public function show($id)
    {
        $business_id = request()->session()->get('user.business_id');
        $type = request()->get('type');
        $loan = Loan::find($id);
        $annexes = json_decode($loan->annexes);
        $countVersion = ScheduleVersion::where('loan_id', $loan->id)->count();
        $scheduleVersionId = ScheduleVersion::where('loan_id', $loan->id)
        ->where('status', 'active')
        ->value('id'); // trae solo el id (o null)

        $paymentSchedules = PaymentSchedule::where('loan_id', $loan->id)
            ->when($scheduleVersionId, fn ($q) => $q->where('schedule_version_id', $scheduleVersionId))
            ->get();

        $customer = Contact::find($loan->customer_id);
		$user = User::find($loan->user_id);
        $total =  $loan->amount + $loan->admin_fee + $loan->gps  + $loan->insurance  +  $loan->initial_amount + $loan->start_rate;
        // ver si no tiene cuotas parciales para habilitar el pago a capital                       
        $canPayCapital = !PaymentSchedule::where('loan_id',$loan->id)->whereNotIn('status',['paid','pending'])->exists();
        //hay mora?
        $there_is_mora =  Delay::where('loan_id',$loan->id)->where('status','late')->exists(); //Mora actual 
        return view('loan.show')->with(compact('countVersion','annexes','canPayCapital','there_is_mora','paymentSchedules','type','customer','loan','user','total'));
    }


  public function addPayment($payment_schedules_id){
        if (request()->ajax()) {
            //busco el tipo de cambio del dia
            $search_date = Carbon::now()->format('Y-m-d');
            $exchange_rates = ExchangeRates::where('search_date',$search_date)->first();
            $exchange_rates = $exchange_rates ? $exchange_rates : 1;
            
            $payment_schedule = PaymentSchedule::findOrFail($payment_schedules_id);
            if ($payment_schedule->payment_status != 'paid') {
                $business_id = request()->session()->get('user.business_id');
                 //Accounts
                $accounts = $this->moduleUtil->accountsDropdown($business_id, true, false, true);
                $show_advance = in_array($payment_schedule->type, ['sell', 'purchase']) ? true : false;
                $payment_types = $this->transactionUtil->payment_types(null, $show_advance,$business_id);
                //Buscar el metodo de pago vinculado al PaymentShadule y restarlo al monto total 
                $amount = $this->transactionUtil->amountToPay($payment_schedule);
                $paid_on = Carbon::now()->toDateTimeString();
                $view = view('loan.payment_row')->with(compact('exchange_rates','payment_schedule','amount','paid_on','payment_types','accounts'))->render();
                $output = ['status' => 'due','view' => $view, ];
            } else {
                $output = ['status' => 'paid','view' => '','msg' => __('purchase.amount_already_paid'),  ];
            }
            return json_encode($output);
        }
    }

    //amount
    public function addCapital($loan_id,$type)
    {
        if (! auth()->user()->can('purchase.payments') && ! auth()->user()->can('sell.payments') && ! auth()->user()->can('all_expense.access') && ! auth()->user()->can('view_own_expense')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            if (request()->ajax()) {
                $loan = Loan::find($loan_id);
                $schedule_version = ScheduleVersion::where('loan_id', $loan->id)->where('status','active')->first();
                $schedule_version_id = $schedule_version ? $schedule_version->id : NULL;
                $business_id = request()->session()->get('user.business_id');
                $transaction = Transaction::where('business_id', $business_id)->with(['contact', 'location'])->findOrFail( $loan->transaction_id);
                if ($transaction->payment_status != 'paid') {
                    $show_advance = in_array($transaction->type, ['sell', 'purchase']) ? true : false;
                    $payment_types = $this->transactionUtil->payment_types(null, false,$business_id);
                    $accounts = $this->moduleUtil->accountsDropdown($business_id, true, false, true);
                    $paid_on = Carbon::now()->toDateTimeString();
                    $rows = PaymentSchedule::query()
                    ->where('loan_id', $loan->id)
                    ->where('schedule_version_id', $schedule_version_id)
                    ->orderBy('id')
                    ->get();

                    $nextPending = $rows->firstWhere('status', 'pending');
                    if (!$nextPending) {
                        return; // no hay cuotas pendientes
                    }

                    $amount = (float) $nextPending->opening_balance;

                    if($type == 'total'){
                        $other_expense = $this->transactionUtil->getOtherExpensesPaid($loan->id); // Monto de otros gastos pagados como el gps, seguro que en si son tiene intereses solo estan fraccionados 
                        $amount = round($amount + $other_expense,4); 
                        $amount_formated = $this->transactionUtil->num_f($amount);
                        $view = view('loan.payment_total')->with(compact('transaction','loan','amount','amount_formated','paid_on','payment_types','accounts','type'))->render();
                    }else{
                        $amount_formated = $this->transactionUtil->num_f($amount);
                        $view = view('loan.payment_capital')->with(compact('transaction','loan','amount','amount_formated','paid_on','payment_types','accounts','type'))->render();
                    }

                    $output = ['status' => 'due','view' => $view];
                } else {
                    $output = ['status' => 'paid','view' => '','msg' => __('purchase.amount_already_paid'),];
                }
                return json_encode($output);
            }
            //code...
        } catch (\Throwable $th) {
           Log::emergency('File:'.$th->getFile().'Line:'.$th->getLine().'Message:'.$th->getMessage());
           $output = ['success' => false,
            'msg' => 'Error: '.$th->getLine().'Message:'.$th->getMessage(),
            ];  
        }
    }

     public function prices(Request $request){
        $options = '';
        $options .= '<option selected disabled >Selecciona un precio</option>';
        $variations = Variation::where('product_id',$request->id)->get();
        foreach ($variations as $key => $variation) {
            $options .= "<option value='".$variation->id."'> $ ".  number_format($variation->sell_price_inc_tax,2) ." </option>";
        }
        return response()->json(['status' => true, 'options' =>  $options]);
    }
    
     public function formatearTelefonoPeru($numero) {
        // Eliminar espacios, guiones y otros caracteres no numéricos excepto el "+"
        $numero = preg_replace('/[^\d+]/', '', $numero);
        // Si el número ya comienza con +51, lo devolvemos tal cual
        if (strpos($numero, '+51') === 0) {
            return $numero;
        }
        // Si comienza con 51 sin el "+", le agregamos el "+"
        if (strpos($numero, '51') === 0) {
            return '+' . $numero;
        }
        // Si no tiene el código, se lo agregamos
        return '+51' . $numero;
    }

    public function addNumberLetter($id){
        $business_id = request()->session()->get('user.business_id');
        $type = request()->get('type');
        $loan = Loan::find($id);
        $annexes = json_decode($loan->annexes);
        $paymentSchedules = PaymentSchedule::where('loan_id', $loan->id)->get();
        $customer = Contact::find($loan->customer_id);
		$user = User::find($loan->user_id);
        $total =  $loan->amount + $loan->admin_fee + $loan->gps  + $loan->insurance  +  $loan->initial_amount;
        return view('loan.number_letter')->with(compact('annexes','paymentSchedules','type','customer','loan','user','total'));
    }

    public function updateLetterAnnexe(Request $request){
        try {
            
            if($request->type == 'letter'){
                $goal = PaymentSchedule::find($request->id);
                $goal->number_letter = $request->value;
                $goal->save();
            }

            if($request->type == 'annexe'){
                $loan =  Loan::find($request->id);
                $annexes = json_decode($loan->annexes);

                if ($request->celda == 'anexo_1') {
                    $annexes->anexo_1 = $request->value;
                }

                if ($request->celda == 'anexo_2') {
                    $annexes->anexo_2 = $request->value;
                }

                if ($request->celda == 'anexo_3') {
                    $annexes->anexo_3 = $request->value;
                }

                 if ($request->celda == 'anexo_4') {
                    $annexes->anexo_4 = $request->value;
                }

                $loan->annexes = json_encode($annexes);
                $loan->save();
            }

            $output = ['success' => true,'msg' => 'Actualizado',];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());
            $output = ['success' => false,'msg' => 'Error: '.$e->getLine().'Message:'.$e->getMessage(),];
        }
        return $output;
    }

    public function update(Request $request, $id)
    {

     try {
            
            $loan = Loan::find($id);
            $date_right_now = Carbon::now();
            $countVersion = ScheduleVersion::where('loan_id', $loan->id)->count();
            if($countVersion){
              $output = ['success' => False,'msg' => 'No se puedo actulizar',];
            }else{
                foreach($loan->paymentSchedule as $item){
                    $date_shedule = Carbon::parse($item->sheduled_date);
                    $days_late = $date_shedule->diffInDays($date_right_now, false); //Dias atrasado
                    if($days_late > 0){
                        if($item->status != 'paid'){
                            $item->status ='overdue';
                            $item->save();
                        }
                    }
                }
                $output = ['success' => true,'msg' => 'Actualizado',];
            }
            
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());
            $output = ['success' => false,'msg' => 'Error: '.$e->getLine().'Message:'.$e->getMessage(),];
        }
        return $output;
    }

    public function table($id)
    {
        $delay_id = $id;
        $paymentSchedules = PaymentSchedule::where('loan_id',$id)->get();
        return view('loan.table_quotes', compact('paymentSchedules','delay_id' ));
    }



}
