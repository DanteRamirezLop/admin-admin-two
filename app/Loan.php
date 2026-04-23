<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Exceptions\RoleAlreadyExists;
use Spatie\Permission\Guard;
use Carbon\Carbon;

class Loan extends Model
{
    //Loands status = ['quotation','approved','partial','in arrears','cancelled','paid']
    //LOANDS TYPE = ['sale','rent-sale']
    use HasFactory;
    protected $table = 'loans';
    protected $fillable = [
        'customer_id',
        'user_id',
		'business_id',
        'product_id',
        'status',
        'product_name',
        'date',
        'number_month', 
        'quotes',

        'type_product',// customer_name
        'period', // type_quotation
        'multiplier',// annual_interest_rate
        'rate', //total_amount_interest
        'amount',// total_cost_loan
		'loan_amount', //balance_to_financed - Saldo a Financiar
        'gps', // initial_gps - Inicial GPS
        'insurance',// initial_insurance - Inicial Seguro
        'admin_fee', //initial_admin_fee - Inicial Gasto de Administración

        'gps_quotes',
        'insurance_quotes',
        'admin_fee_quotes',
        'product_price',
        'initial_percentage',
        'initial_amount',
        'contact_source',
        'terms',
        'waiter',
        'transaction_id',
        'vin',
        'annexes',
        'initial_fraction',
        'mounth_initial',
        'start_rate',
        'interest_saved',

        'type',
    ];
    
   // protected $casts = [
    //    'annexes'=> 'array'
   // ];

    public function contact()
    {
        return $this->belongsTo(\App\Contact::class, 'customer_id');
    }

    public function user()
    {
        return $this->belongsTo(\App\User::class, 'user_id');
    }

    public function getNameUser(){
        if($this->user->getRoleNameAttribute() == 'Admin'){
            return 'Oficina';
        }else{
            return $this->user->first_name .' '. $this->user->last_name;
        }
    }

    public function getFechaRegistroAttribute()
    {
        return Carbon::parse($this->created_at)->format('Y-m-d');
    }

    public function paymentSchedule(){
        return $this->hasMany(PaymentSchedule::class);
    }
    
     protected static function booted()
    {
        static::deleting(function ($loan) {
            foreach ($loan->paymentSchedule as $schedule) {
                // Eliminar la relación uno a uno con delay
                if ($schedule->delay) {
                    $schedule->delay->delete();
                }
                $schedule->delete();
            }
        });
    }


}
