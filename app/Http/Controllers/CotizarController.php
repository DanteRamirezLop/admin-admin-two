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
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;
use App\Mail\Loanquotes;
use App\Product;
use App\User;
use App\Category;
use App\Utils\ContactUtil;
use App\Variation;
use App\Media;
use App\Goal;
use App\Charts\CommonChart;
use App\BusinessLocation;
use App\Utils\TransactionUtil;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

class CotizarController extends Controller
{
    /**
     * All Utils instance.
     */
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
       
        $is_admin = $this->businessUtil->is_admin(auth()->user());

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');
            
             if ($is_admin) {
                if (request()->get('is_credit') === '1') {
                    $loans = Loan::where('business_id',$business_id)->orderBy('id','DESC')->where('period',2)->where('status','quotation')->get();     
                }else{
                    $loans = Loan::where('business_id',$business_id)->orderBy('id','DESC')->where('period',1)->where('status','quotation')->get();
                }       
            }
            else{
                if (request()->get('is_credit') === '1') {
                    $loans = Loan::where('business_id',$business_id)->where('user_id', auth()->user()->id)->where('period',2)->where('status','quotation')->orderBy('id','DESC')->get();
                }else{
                    $loans = Loan::where('business_id',$business_id)->where('user_id', auth()->user()->id)->where('period',1)->where('status','quotation')->orderBy('id','DESC')->get();
                } 
            }
            
           return Datatables::of($loans)->addColumn(
                    'action',
                    '
                        <a href="/cotizar/detail/{{$id}}" class="btn btn-xs btn-info"><i class="fas fa-eye"></i> Ver detalles </a>
                    
                        &nbsp;
                        <form action="/cotizar_pdf" method="post" style="display: contents;">
                        @csrf
                            <input type="hidden" name="id" value="{{$id}}">
                            <button type="submit" class="btn btn-xs btn-warning my-2"><i class="fa fa-file-pdf"> Descargar PDF</i></button>                                        
                        </form>
                        &nbsp; 
                        @can("business_settings.access")
                            <button data-href="{{action(\'App\Http\Controllers\CotizarController@destroy\', [$id])}}" class="btn btn-xs btn-danger delete_loan_button"><i class="glyphicon glyphicon-trash"></i> @lang("messages.delete")</button>
                        @endcan
                    '
                )
                ->editColumn('loan_amount','$ {{number_format($loan_amount,2)}}')
                ->editColumn('seller',function ($row) {
                           return $row->getNameUser();
                    })
                ->editColumn('amount','$ {{number_format($amount,2)}}')
                ->editColumn('rate','$ {{number_format($rate,2)}}')
                ->editColumn('multiplier','{{$multiplier}}%')
                ->editColumn('created_at','{{date("Y/m/d",strtotime($created_at))}}')
                ->editColumn('period', '{{$product_name}}')
                ->editColumn('product_price', '$ {{number_format($product_price,2)}}')
                ->rawColumns(['action'])
                ->make(true);
        }

        $type = request()->get('id');
        return view('cotizador.index',compact('type'));
    }
    
    
    public function Create()
    {
        $business_id = request()->session()->get('user.business_id');
        $customer = Contact::where('type', 'customer')->where('business_id',$business_id)->get();
        $category = Category::where("name","Maquinarias")->first();
        //$products = Product::where('category_id',$category->id)->where('is_inactive',0)->get();
        $products = Product::where('category_id',$category->id)->where('is_inactive',0)->where(function ($query) {
                $query->whereNull('product_custom_field6')
                    ->orWhere('product_custom_field6', 0);
          })->get();
    
        $walk_in_customer = $this->contactUtil->getWalkInCustomer($business_id);
        //variables en Credito
        $filing_fee = Goal::where('name','coste-tramite')->first()->amount_total;
        $gps = Goal::where('name','gps')->first()->amount_total;
        $insurance = Goal::where('name','seguro')->first()->amount_total;
        if (auth()->user()->can('business_settings.access')) {
            $waiters = $this->transactionUtil->getModuleStaff($business_id,'customer.view_own',true);
            $initial = Goal::where('name','initial')->first();
            $percentages = json_decode($initial->description);
            return view('cotizador.create_admin',compact('percentages','customer','products','walk_in_customer','filing_fee','gps','insurance','waiters'));
        }else{
            return view('cotizador.create_seller',compact('customer','products','walk_in_customer','filing_fee','gps','insurance'));
        }
    }


    
    public function store(Request $request)
    {
         try {
            $period = $request->input('option'); //Credito o contado
            $option_tramite = $request->input('option_tramite');
            $option_gps = $request->input('option_gps');
            $option_seguro = $request->input('option_seguro');
            //-----------------------------
            $business_id = request()->session()->get('user.business_id'); 
            $product_id = $request->input('product_id'); 
            $product = Product::find($product_id);
            $date = Carbon::now();
            $customer_id = $request->input('contact_id');
            
            //validar prefijo numerico Peru +51
            if($request->input('mobile')){
                $mobile = $this->formatearTelefonoPeru($request->input('mobile'));
            }else{
                $mobile =  $request->input('mobile');
            }
                
            //Editar el correo y el numero telefonico del cliente 
            $customer = Contact::find($customer_id);
            $customer->email = $request->input('email'); 
            $customer->mobile = $mobile; 
            $customer->save();
            //--------------------------------------
            $type_product =$request->input('customer');
            //obtener el precio de la maquinaria a cotizar
            if($request->input('waiter')){
                $waiter = $request->input('waiter');
            }else{
                $waiter = auth()->user()->first_name.' '.auth()->user()->last_name;
            }
            if($request->input('prices')){
                $product_mount = $request->input('prices'); //precio de la maquinaria  seleccionada
            }else{
                $variation = Variation::where('product_id',$product_id)->first();
                $product_mount = $variation->sell_price_inc_tax; //precio de la maquinaria 
            }
            //**************** 
            $terms =  Goal::where('name','terminos-y-condiciones')->first()->description;
            $json = [];
            $pay_initial = 0;
            $initial_amount = 0;
            $admin_fee = 0;
            $seguro_coutes = 0;
            $seguro_init = 0;
            $insurance_quotes = 0;
            $gps_init = 0;
            $gps_coutes = 0;
            $gps_quotes = 0;
            $gps_amount_total = 0;
            $seguro_amount_total = 0;
            // Cotizar a creadito
            if($period == 2){
                
                 //----------------------------
                $number_month = $request->input('number_month');// Numero de meses
                if($number_month < 12){
                      $meses_gps_seguro = $number_month;
                }else{
                     $meses_gps_seguro = 12; //Los meses en que se fracciona el seguro y el GPS, siempre es el mismo
                }
                //-------------

                //validar si el cobra el costo del tramite
                if($option_tramite == 1){ 
                    $admin_fee_tbl =  Goal::where('name','coste-tramite')->first(); //Cotos Total del GPS
                    $admin_fee =  $admin_fee_tbl->amount_total;
                }
                //Valida si se cobra el GPS
                if($option_gps == 1){
                    $gps_tbl =  Goal::where('name','gps')->first(); //Cotos Total del GPS
                    $gps_init = $gps_tbl->amount_inicial;
                    $gps_amount_total = $gps_tbl->amount_total;// Inicial del gps
                    $gps_quotes = $gps_amount_total -  $gps_init;
                    $gps_coutes = $gps_quotes / $meses_gps_seguro; //Cuota de 12 meses en el gps
                }
                //Valida si se cobra el Seguro
                if($option_seguro == 1){
                    $seguro_tbl =  Goal::where('name','seguro')->first(); // Costo total del Seguro
                    $seguro_init =  $seguro_tbl->amount_inicial; // Inicial del seguro
                    $seguro_amount_total = $seguro_tbl->amount_total;
                    $insurance_quotes = $seguro_amount_total - $seguro_init;
                    $seguro_coutes = $insurance_quotes / $meses_gps_seguro; //Cuota de 12 meses en el gps
                }
                //----------------------------
               
                $pay_initial =  $request->input('pay_initial'); //porcentaje inicial 
                $multiplayer = $request->input('multiplayer');// Tasa anual 
                $initial_amount = ($product_mount*($pay_initial/100)); //Pago de la inicial en porcentaje
                $loan_amount =   $product_mount - $initial_amount;
                //Calcular Pagos mensual
                    $tasaMensual = ($multiplayer / 100) / 12; // Calcular la cuota mensual usando la fórmula del préstamo francés
                    if ($tasaMensual > 0) {
                        $cuota = $loan_amount * ($tasaMensual * pow(1 + $tasaMensual, $number_month)) / (pow(1 + $tasaMensual, $number_month) - 1);
                    } else {
                        $cuota = $loan_amount / $number_month; // Si la tasa es 0, simplemente dividir el monto total entre el número de meses
                    }
                    $amount_fraccion = round($cuota, 4);
                //----------------
                $saldo = $loan_amount; //Saldo del préstamo
                $date_ini = date("d-m-Y",strtotime($date));
                $json = [];
                for ($i=1; $i < $number_month + 1; $i++) {
                    $date_ini = $date->addMonths(1); // Sumar 1 Mes
                    //----------------------
                    $saldo_inicial = $saldo; //saldo Inicial
                    $interes = $saldo * $tasaMensual; // Interés del mes
                    $amortizacion =  $cuota - $interes; // Pago a capital
                    $saldo -= $amortizacion; // Nuevo saldo
                    if ($saldo < 0) $saldo = 0; // Evitar valores negativos
                    if($i <= $meses_gps_seguro){
                        $gps =  $gps_coutes;
                        $seguro = $seguro_coutes;
                    }else{
                        $gps = 0;
                        $seguro = 0;
                    }
                     $date_quota = date("Y-m-d",strtotime($date_ini));
                    //----------------------
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
                    ));
                }
                //Coste total del préstamo
                $coste_prestamo =   ($amount_fraccion * $number_month);
                $coste_prestamo_gps_seguro =   $coste_prestamo +  $gps_quotes +  $insurance_quotes;
                //Importe total de los intereses
                $intereses =  $coste_prestamo - $loan_amount;
            }else{
                //Cotizar a contado
                $number_month = 0;
                $multiplayer =  0;
                $intereses = 0;
                $coste_prestamo_gps_seguro = 0;
                $admin_fee = 0;
                $gps_init = 0;
                $seguro_init = 0;
                $loan_amount= 0;
                $gps_quotes = 0;
                $insurance_quotes = 0;
            }
            Loan::create([
                'customer_id' => $customer_id,
                'user_id' => auth()->user()->id,
                'business_id' => $business_id, 
                'product_id' =>  $product_id,
                'status'=> 'quotation',
                'product_name' => $product->name,
                'date' => Carbon::now(),
                'type_product' => $type_product,
                'period' => $period, //1 Contado - 2 Credito 
                'number_month' => $number_month, // meses del prestamo
                'multiplier' => $multiplayer,//Taza anual
                'rate' => $intereses, // los intereces a pagar
                'amount' => $coste_prestamo_gps_seguro, // El cotos total del prestamo: lo prestado más intereces
                'quotes' => json_encode($json), //Pagos mensuales del prestamo
                'admin_fee' => $admin_fee, // Gasto administrativo, se cobra con la inicial y no se agrega al prestamo
                'gps' => $gps_init, // Gasto del GPS, se cobra con la inicial y el resto se agrega al prestamo en fraccion
                'insurance' => $seguro_init, // Gasto del seguro, se cobra con la inicial y el resto se agrega al prestamo en fraccio
                'gps_quotes' => $gps_quotes, 
                'insurance_quotes' => $insurance_quotes,
                'loan_amount'=> $loan_amount, //Monto del prestamos 
                'product_price'=> $product_mount, //Precio de la maquinari en el momento de la cotización 
                'initial_percentage'=> $pay_initial, //porcentaje de la incial 
                'initial_amount'=> $initial_amount, //Cantida a pagar de la Incial 
                'contact_source' => $request->input('contact_source'),  //Fuente de contacto
                'terms'=>$terms,
                'waiter' =>$waiter,
            ]);
            $output = ['success' => true,
                    'msg' => 'successfully added',
            ];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());
            $output = ['success' => false,
            'msg' => 'Error: '.$e->getLine().'Message:'.$e->getMessage(),
            ];
        }
        return redirect('cotizar')->with('status', $output);
    }


    public function storeAdmin(Request $request)
    {
         try {
                DB::beginTransaction();
                //------------CAPTURA DE DATOS DEL FORMULARIO---------------
                $initial_amount = $request->input('pay_initial');
                $type_initial = $request->input('type_initial');
                $multiplayer = $request->input('multiplayer');// Tasa anual 
                $period = $request->input('option'); //Credito o contado
                $option_tramite = $request->input('option_tramite');
                $option_gps = $request->input('option_gps');
                $option_seguro = $request->input('option_seguro');
                $business_id = request()->session()->get('user.business_id'); 
                $product_id = $request->input('product_id'); 
                $product = Product::find($product_id);
                $date = Carbon::now();
                //-----VALIDAR CODIGO PERU +51-------
                if($request->input('mobile')){
                    $mobile = $this->formatearTelefonoPeru($request->input('mobile'));
                }else{
                    $mobile =  $request->input('mobile');
                }
                //-----ACTUALIZAR EMAIL Y TELEFONO DEL CLIENTE------- 
                $customer_id = $request->input('contact_id');
                $customer = Contact::find($customer_id);
                $customer->email = $request->input('email'); 
                $customer->mobile = $mobile; 
                $customer->save();
                //--------------------------------------
                $type_product =$request->input('customer');
                //obtener el precio de la maquinaria a cotizar
                if($request->input('waiter')){
                    $waiter = $request->input('waiter');
                }else{
                    $waiter = auth()->user()->first_name.' '.auth()->user()->last_name;
                }
                if($request->input('prices')){
                    $product_mount = $request->input('prices'); //precio de la maquinaria  seleccionada
                }else{
                    $variation = Variation::where('product_id',$product_id)->first();
                    $product_mount = $variation->sell_price_inc_tax; //precio de la maquinaria 
                }
                //-----INICIALIZACION DE VARIABLES------
                $json = [];
                $pay_initial = 0;
                $admin_fee = 0;
                $seguro_coutes = 0;
                $seguro_init = 0;
                $gps_init = 0;
                $gps_coutes = 0;
                $gps_quotes = 0;
                $gps_amount_total = 0;
                $seguro_amount_total = 0;
                $insurance_quotes = 0;
                $amount_fracction = 0;
                $mounth_fracction = 0;
                $initial_cuotes = 0;
                $taxes_fraccion = 0;
                //----TERMINOS Y CONDICIONES ACTUALES----------------
                $terms =  Goal::where('name','terminos-y-condiciones')->first()->description;
            // Cotizar a creadito
            if($period == 2){
                $number_month = $request->input('number_month');// Numero de meses
                if($number_month < 12){
                      $meses_gps_seguro = $number_month;
                }else{
                     $meses_gps_seguro = 12; //Los meses en que se fracciona el seguro y el GPS, siempre es el mismo
                }
                //validar si el cobra el costo del tramite
                if($option_tramite == 1){ 
                    $admin_fee_tbl =  Goal::where('name','coste-tramite')->first(); //Cotos Total del GPS
                    $admin_fee =  $admin_fee_tbl->amount_total;
                }
                //Valida si se cobra el GPS
                if($option_gps == 1){
                    $gps_tbl =  Goal::where('name','gps')->first(); //Cotos Total del GPS
                    $gps_init = $gps_tbl->amount_inicial;
                    $gps_amount_total = $gps_tbl->amount_total;// Inicial del gps
                    $gps_quotes = $gps_amount_total -  $gps_init;
                    $gps_coutes = $gps_quotes / $meses_gps_seguro; //Cuota de 12 meses en el gps
                }
                //Valida si se cobra el Seguro
                if($option_seguro == 1){
                    $seguro_tbl =  Goal::where('name','seguro')->first(); // Costo total del Seguro
                    $seguro_init =  $seguro_tbl->amount_inicial; // Inicial del seguro
                    $seguro_amount_total = $seguro_tbl->amount_total;
                    $insurance_quotes = $seguro_amount_total - $seguro_init;
                    $seguro_coutes = $insurance_quotes / $meses_gps_seguro; //Cuota de 12 meses en el gps
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
                //----------------
                $saldo = $loan_amount; //Saldo del préstamo
                $date_ini = date("d-m-Y",strtotime($date));
                $json = [];
                for ($i=1; $i < $number_month + 1; $i++) {
                    $date_ini = $date->addMonths(1); // Sumar 1 Mes
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
                    }else{
                        $gps = 0;
                        $seguro = 0;
                    }
                     $date_quota = date("Y-m-d",strtotime($date_ini));
                    //----------------------
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
                    ));
                }
                //Coste total del préstamo
                $coste_prestamo =  ($amount_fraccion * $number_month);
                $coste_prestamo_gps_seguro =  $coste_prestamo +  $gps_quotes +  $insurance_quotes;
                //Importe total de los intereses
                $intereses =  round($coste_prestamo - $loan_amount,4);
            }else{
                //VARIABLES SI SE COTIZA AL CONTADO
                $number_month = 0;
                $multiplayer =  0;
                $intereses = 0;
                $coste_prestamo_gps_seguro = 0;
                $admin_fee = 0;
                $gps_init = 0;
                $seguro_init = 0;
                $loan_amount= 0;
                $gps_quotes = 0;
                $insurance_quotes = 0;
                $initial_amount = 0;
            }

            Loan::create([
                'customer_id' => $customer_id,
                'user_id' => auth()->user()->id,
                'business_id' => $business_id, 
                'product_id' =>  $product_id,
                'status'=> 'quotation',
                'product_name' => $product->name,
                'date' => Carbon::now(),
                'type_product' => $type_product,
                'period' => $period, //1 Contado - 2 Credito 
                'number_month' => $number_month, // meses del prestamo
                'multiplier' => $multiplayer,//Taza anual
                'rate' => $intereses, // los intereces a pagar
                'amount' => $coste_prestamo_gps_seguro, // El cotos total del prestamo: lo prestado más intereces
                'quotes' => json_encode($json), //Pagos mensuales del prestamo
                'admin_fee' => $admin_fee, // Gasto administrativo, se cobra con la inicial y no se agrega al prestamo
                'gps' => $gps_init, // Gasto del GPS, se cobra con la inicial y el resto se agrega al prestamo en fraccion
                'insurance' => $seguro_init, // Gasto del seguro, se cobra con la inicial y el resto se agrega al prestamo en fraccio
                'gps_quotes' => $gps_quotes, 
                'insurance_quotes' => $insurance_quotes,
                'loan_amount'=> $loan_amount, //Monto del prestamos 
                'product_price'=> $product_mount, //Precio de la maquinari en el momento de la cotización 
                'initial_percentage'=> $pay_initial, //porcentaje de la incial 
                'initial_amount'=> $initial_amount, //Cantida a pagar de la Incial 
                'contact_source' => $request->input('contact_source'),  //Fuente de contacto
                'terms'=>$terms,
                'waiter' =>$waiter,
                'initial_fraction'=> $amount_fracction,
                'mounth_initial'=> $mounth_fracction,
                'start_rate'=>$taxes_fraccion,
            ]);

            DB::commit();
            $output = ['success' => true,'msg' => 'successfully added',];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());
            $output = ['success' => false,'msg' => 'Error: '.$e->getLine().'Message:'.$e->getMessage(),];
        }
        return redirect('cotizar')->with('status', $output);
    }


	public function destroy($id)
    {
        if (! auth()->user()->can('cotizador.delete')) {
            abort(403, 'Unauthorized action.');
        }
        if (request()->ajax()) {
            try {
                $business_id = request()->user()->business_id;
                $loan = Loan::where('business_id', $business_id)->findOrFail($id);
                if ($loan) {
                    $loan->delete();
                    $output = ['success' => true,
                        'msg' => __('cotizador.deleted_success'),
                    ];
                } else {
                    $output = ['success' => false,
                        'msg' =>  __('lang_v1.loan_cannot_be_deleted'),
                    ];
                }
            } catch (\Exception $e) {
                \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

                $output = ['success' => false,
                    'msg' => '__("messages.something_went_wrong")',
                ];
            }
            return $output;
        }
    }

    public function detail($id)
    {
        $business_id = request()->session()->get('user.business_id');
        $type = request()->get('type');
        $loan = Loan::find($id);
        $detail = json_decode($loan->quotes);
        $customer = Contact::find($loan->customer_id);
		$user = User::find($loan->user_id);
        //Total a pagar - total
        if($loan->period == 2){
             $total =  $loan->amount + $loan->admin_fee + $loan->gps  + $loan->insurance  +  $loan->initial_amount + $loan->start_rate;
        }else{
            $total = $loan->product_price;
        }
        return view('cotizador.detail')->with(compact('detail','type','customer','loan','user','total'));
    }

    public function update(Request $request)
    {
        try {
            $id = $request->id;
            $quote_id = $request->quote;
            $loan = Loan::find($id);
            $json = json_decode($loan->quotes);
            foreach ($json as $value) {
               if ($value->id == $quote_id) {
                $value->total_pay = $value->amount;
                $value->status = 1;
               }
            }
            $loan->quotes = json_encode($json);
            $loan->save();
            $output = ['success' => true,
                'msg' => 'successfully added',
            ];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());
            $output = ['success' => false,
            'msg' => 'Error: '.$e->getLine().'Message:'.$e->getMessage(),
                // 'msg' => __('messages.something_went_wrong'),
            ];
        }
        return $output;
    }


    public function pdf(Request $request)
    {
        $id = $request->id;
        $loan = Loan::find($id);
        $customer = Contact::find($loan->customer_id);
        $user = User::find($loan->user_id);
        $product = Product::find($loan->product_id);
        //imagenes de la descripción tecnica
        $variation = $product->variations->first();
        $images = Media::where('model_id',$variation->id)->where('model_type','App\Variation')->get();
        //---------
        $quotes = json_decode($loan->quotes);
        //Calcular fecha de la vigencia
        $fecha = Carbon::parse($loan->date);
        $date = $fecha->isoFormat('D/MM/Y');
        $anio = $fecha->isoFormat('Y');
        $aux = $fecha->addDays(10);
        $date_valid = $aux->isoFormat('D/MM/Y');
        //Total a pagar
        if($loan->period == 2){
             $total =  $loan->amount + $loan->admin_fee + $loan->gps  + $loan->insurance  +  $loan->initial_amount + $loan->start_rate;
        }else{
            $total = $loan->product_price;
        }
        $pdf = Pdf::set_option('isRemoteEnabled', true)->loadView('cotizador.pdf',compact('anio','loan','quotes','date','customer','user','product','total','date_valid','images'));
        return $pdf->download('cotizador.pdf');
    }


    public function confi_update(Request $request){
        try {
            $goal = Goal::find($request->id);
            $goal->amount_total = $request->amount_total;
            $goal->amount_inicial = $request->amount_inicial;
            $goal->description = $request->description;
            $goal->save();

            $output = ['success' => true,'msg' => 'Actualizado',];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());
            $output = ['success' => false,
            'msg' => 'Error: '.$e->getLine().'Message:'.$e->getMessage(),
            ];
        }
        return $output;
    }
    
    public function confi(Request $request){
        $percentages = []; //Porcentajes de la inicial
        $business_id = request()->session()->get('user.business_id');
        $type = request()->get('type');
        $terms = Goal::first();
        $goalInitial = Goal::where('name','initial')->first();
        if($goalInitial->description){
            $percentages = json_decode($goalInitial->description);
        }
        $goals = Goal::orderBy('id', 'asc')->skip(2)->take(10)->get();
        return view('cotizador.confi')->with(compact('terms','type','goals','percentages'));
    }


  
    public function checkDNI(Request $request){
        $business_id = request()->session()->get('user.business_id'); 
        $created_by = $request->session()->get('user.id');
        $query = Contact::where('contact_id',$request->dni)->where('business_id',$business_id);
        $contact = $query->first();
        // SI EL CONTACTO EXISTE, VERIFICAR SI EL USUARIO TIENE ACCESO
        if($contact){
            $is_my_contact = $query->where('created_by',$created_by)->exists(); //VERIFICA SI EL CONTACTO FUE CREADO POR EL USUARIO ACTUAL
            if(!$is_my_contact){
                $is_admin = $this->contactUtil->is_admin(auth()->user());  // VALIDA SI EL USUARIO ES ADMINISTRADOR - YA QUE ESTE USUARIO NO NECESITA PERMISOS
                if(!$is_admin){
                    //VERIFICAR SI YA TIENE PERMISOS 
                    $assigned_to_user =  $contact->userHavingAccess()->wherePivot('user_id',$created_by)->exists();
                    if(!$assigned_to_user){
                        //ASIGNAR PERMISO AL USUARIO ACTUAL EL ACCESO AL CONTACTO
                        $contact->userHavingAccess()->attach($created_by);
                    }
                }
            }
            return response()->json([
                'status' => true,
                'name'=> $contact->name,
                'contact_id'=> $contact->id,
                'mobile'=> $contact->mobile,
                'email'=> $contact->email,
            ]);
        }

        $dni = json_encode(['dni' => $request->dni]);
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://apiperu.dev/api/dni",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_POSTFIELDS => $dni,        
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/json',
                'Authorization:'. ' Bearer ' .config('services.apiperu.token')
            ],        
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        $data = json_decode($response, true);

        if ($err) {
            return response()->json(['status' => false, 'msg' => "ERROR"]);
        } else {
            if($data["success"]){
                $contact = new Contact([
                    'business_id'=>$business_id, 
                    'type'=>'customer',
                    'name'=>$data["data"]["nombre_completo"], 
                    'first_name'=>$data["data"]["nombres"], 
                    'last_name'=>$data["data"]["apellido_paterno"] . ' ' . $data["data"]["apellido_materno"], 
                    'contact_id'=>$data["data"]["numero"],
                    'contact_status'=>'active',
                    'mobile'=>'999999999', 
                    'email'=>'ejemplo@gmail.com',
                    'created_by'=>$created_by, 
                ]);
                $contact->save();
                return response()->json([
                    'status' => true,
                    'name'=> $contact->name,
                    'contact_id'=> $contact->id,
                    'mobile'=> $contact->mobile,
                    'email'=> $contact->email,
                ]);
                
            } else {
                return response()->json(['status' => false,'msg' => 'DNI no econtrado']);
            }
        }
    }
    

    public function checkRUC(Request $request){
        $business_id = request()->session()->get('user.business_id'); 
        $created_by = $request->session()->get('user.id');
        $query = Contact::where('contact_id',$request->ruc)->where('business_id',$business_id);
        $contact = $query->first();
        if($contact){
            $is_my_contact = $query->where('created_by',$created_by)->exists(); 
            if(!$is_my_contact){
                $is_admin = $this->contactUtil->is_admin(auth()->user()); 
                if(!$is_admin){
                    $assigned_to_user =  $contact->userHavingAccess()->wherePivot('user_id',$created_by)->exists();
                    if(!$assigned_to_user){
                        $contact->userHavingAccess()->attach($created_by);
                    }
                }
            }
             return response()->json([
                'status' => true,
                'name'=> $contact->supplier_business_name,
                'contact_id'=> $contact->id,
                'mobile'=> $contact->mobile,
                'email'=> $contact->email,
            ]);
        }

         $ruc = json_encode(['ruc' => $request->ruc]);
         $curl = curl_init();
         curl_setopt_array($curl, array(
            CURLOPT_URL => "https://apiperu.dev/api/ruc",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_POSTFIELDS => $ruc,        
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/json',
                'Authorization:'. ' Bearer ' .config('services.apiperu.token')
            ],        
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        $data = json_decode($response, true);

        if ($err) {
            return response()->json(['status' => false, 'msg' => "ERROR"]);
        } else {
            if($data["success"]){
                $contact = new Contact([
                    'business_id'=>$business_id, 
                    'type'=>'customer',
                    'supplier_business_name'=>$data["data"]["nombre_o_razon_social"], 
                    'contact_id'=>$data["data"]["ruc"],
                    'city'=> $data["data"]["provincia"],
                    'state'=> $data["data"]["departamento"],
                    'address_line_1'=>$data["data"]["direccion_completa"],
                    'contact_status'=>'active',
                    'mobile'=>'999999999', 
                    'email'=>'ejemplo@gmail.com',
                    'created_by'=>$created_by, 
                ]);
                $contact->save();
                return response()->json([
                    'status' => true,
                    'name'=> $contact->supplier_business_name,
                    'contact_id'=> $contact->id,
                    'mobile'=> $contact->mobile,
                    'email'=> $contact->email,
                ]);

            } else {
                return response()->json(['status' => false,'msg' => 'RUC no econtrado']);
            }
        }
    }
    

    public function checkCustomerSunat(Request $request){
        try {
            $identy = json_encode([$request->type => $request->identy]);
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://apiperu.dev/api/".$request->type,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_POSTFIELDS => $identy,        
                CURLOPT_HTTPHEADER => [
                    'Accept: application/json',
                    'Content-Type: application/json',
                    'Authorization:'. ' Bearer ' .config('services.apiperu.token')
                ],        
            ));
            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);
            $data = json_decode($response, true);
            //code...
             if ($err) {
                return response()->json(['status' => false, 'msg' => "ERROR EN LA PETICION"]);
            } else {
                 if($data["success"]){
                    if($request->type == 'dni'){
                        return response()->json([
                            'status' => true,
                            'contact_id'=>$data["data"]["numero"],
                            'first_name'=>$data["data"]["nombres"], 
                            'last_name'=>$data["data"]["apellido_paterno"] . ' ' . $data["data"]["apellido_materno"],
                            'city'=> '',
                            'state'=> '',
                            'address_line_1'=>'',
                        ]);
                    }
                    if($request->type == 'ruc'){
                        return response()->json([
                            'status' => true,
                            'contact_id'=>$data["data"]["ruc"],
                            'supplier_business_name'=>$data["data"]["nombre_o_razon_social"], 
                            'city'=> $data["data"]["provincia"],
                            'state'=> $data["data"]["departamento"],
                            'address_line_1'=>$data["data"]["direccion_completa"],
                        ]);
                    }
                 }else{
                    return response()->json(['status' => false, 'msg' => "ERROR EN LA BUSQUEDA ". $identy]);
                 }
            }
        } catch (\Throwable $th) {
             return response()->json(['status' => false, 'msg' => "ERROR!!, consulta con soporte tecnico"]);
        }
    }
    

    
    public function report(Request $request){
        $labels = [];
        $dates = [];
        $business_id = $request->session()->get('user.business_id');
        $filters = $request->only(['waiter_id', 'location_id']);

        $date_range = $request->input('date_range');
        if (! empty($date_range)) {
            $date_range_array = explode('~', $date_range);
            $filters['start_date'] = $this->transactionUtil->uf_date(trim($date_range_array[0]));
            $filters['end_date'] = $this->transactionUtil->uf_date(trim($date_range_array[1]));
            $filters['end_date'] =$filters['end_date']." 23:59:59";
        } else {
            $filters['start_date'] = \Carbon::now()->startOfMonth()->format('Y-m-d');
            $filters['end_date'] = \Carbon::now()->endOfMonth()->format('Y-m-d');
        }

        $business_locations = BusinessLocation::forDropdown($business_id, true);
  
  
          $waiters = $this->transactionUtil->getModuleStaff($business_id,'customer.view_own',true);
  

        $query = Loan::where('business_id',$business_id);
        if (! empty($filters['waiter_id'])) {
            $query->where('user_id', $filters['waiter_id']);
        }

        $query->whereBetween('created_at', [$filters['start_date'], $filters['end_date']]);
        $loan = $query->get();
        $loan = $loan->map(function ($loan) {
            return [
                'id' => $loan->id,
                'date' => $loan->fecha_registro,
            ];
        });

        $periodo = CarbonPeriod::create($filters['start_date'], $filters['end_date']);
        foreach ($periodo as $fecha) {
            $labels[] = date('j M Y', strtotime($fecha->toDateString()));
            $total_sell_on_date = $loan->where('date',$fecha->toDateString())->count();
            if (! empty($total_sell_on_date)) {
                $all_sell_values[] = (float) $total_sell_on_date;
            } else {
                $all_sell_values[] = 0;
            }
        }
    
        $sells_chart_1 = new CommonChart;
        $sells_chart_1->labels($labels);
        $sells_chart_1->dataset('Cotizaciones Totales', 'line', $all_sell_values);
        return view('cotizador.report', compact('sells_chart_1','business_locations','date_range','waiters'));
    }

    public function reportWaiter(Request $request){
        $labels = [];
        $dates = [];
        $business_id = $request->session()->get('user.business_id');

        $date_range = $request->input('date_range');
        if (! empty($date_range)) {
            $date_range_array = explode('~', $date_range);
            $filters['start_date'] = $this->transactionUtil->uf_date(trim($date_range_array[0]));
            $filters['end_date'] = $this->transactionUtil->uf_date(trim($date_range_array[1]));
            $filters['end_date'] =$filters['end_date']." 23:59:59";
        } else {
            $filters['start_date'] = \Carbon::now()->startOfMonth()->format('Y-m-d');
            $filters['end_date'] = \Carbon::now()->endOfMonth()->format('Y-m-d');
        }
       
        $business = BusinessLocation::find($business_id);
        $user = User::find(auth()->user()->id);
        $query = Loan::where('business_id',$business_id)->where('user_id',auth()->user()->id);
        $query->whereBetween('created_at',[$filters['start_date'], $filters['end_date']]);
        $loan = $query->get();
       
        $loan = $loan->map(function ($loan) {
            return [
                'id' => $loan->id,
                'date' => $loan->fecha_registro,
            ];
        });

        $periodo = CarbonPeriod::create($filters['start_date'], $filters['end_date']);
        foreach ($periodo as $fecha) {
            $labels[] = date('j M Y', strtotime($fecha->toDateString()));
            $total_sell_on_date = $loan->where('date',$fecha->toDateString())->count();
            if (! empty($total_sell_on_date)) {
                $all_sell_values[] = (float) $total_sell_on_date;
            } else {
                $all_sell_values[] = 0;
            }
        }
    
        $sells_chart_1 = new CommonChart;
        $sells_chart_1->labels($labels);
        $sells_chart_1->dataset('Cotizaciones Totales', 'line', $all_sell_values);
        return view('cotizador.report_waiter', compact('sells_chart_1','date_range','business','user'));
    }


    public function prices(Request $request){
        $options = '';
        $options .= '<option selected disabled >Selecciona un precio</option>';

        $variations = Variation::where('product_id',$request->id)->get();

        foreach ($variations as $key => $variation) {
            $options .= "<option value='".$variation->sell_price_inc_tax."'> $ ".  number_format($variation->sell_price_inc_tax,2) ." </option>";
        }
        return response()->json(['status' => true, 'options' =>  $options]);
    }
    
    
    public function initial(Request $request){
        //FALTA VALIDAR LOS NUMERO REPETIDOS
        $percentages = [];
        $goalInitial = Goal::where('name','initial')->first();
        if($goalInitial->description){
            $percentages = json_decode($goalInitial->description);
        }

        if($request->type == "add"){
            array_push($percentages, $request->value);
        }else{
            unset($percentages[$request->value]);
        }
        
        //antes de guardar mantener el formato []
        $arrayPercentages= array_values($percentages);
        $goalInitial->description = json_encode($arrayPercentages);
        $goalInitial->save();

        return response()->json(['status' => true, 'msg' => "Porcentaje de la inicial actulizado", 'values'=>$percentages]);
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

   
}
