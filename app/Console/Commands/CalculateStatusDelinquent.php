<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Loan;
use App\PaymentSchedule;
use App\Delay;
use App\Transaction;
use App\Mail\NotificacionPrestamoLibra;
use Illuminate\Support\Facades\Mail;
use App\Utils\NotificationUtil;
use App\Business;
use Illuminate\Support\Facades\Log;

class CalculateStatusDelinquent extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pos:calculateStatusLoan';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calcula el estado del prestamo así como las letras y agrega la mora en el calendario de pagos';

    /**
     * Execute the console command.
     *
     * @return int
     */

     public function __construct(NotificationUtil $notificationUtil)
    {
        parent::__construct();

        $this->notificationUtil = $notificationUtil;
    }


    public function handle(){
        try {
            $customers = [];
            $numeros = [];

            DB::beginTransaction();
            $prestamos = Loan::whereIn('status', ['approved', 'in arrears', 'partial'])->get();
            foreach ($prestamos as $prestamo) {
                // ¿Existe una versión activa para este préstamo?
                $hasActiveVersion = DB::table('payment_schedules as psx')
                    ->join('schedule_versions as svx', 'svx.id', '=', 'psx.schedule_version_id')
                    ->where('psx.loan_id', $prestamo->id)
                    ->where('svx.status', 'active')
                    ->exists();

                // Traer SOLO cuotas overdue del cronograma correcto (activo o NULL)
                $letra_pagos = PaymentSchedule::query()
                    ->from('payment_schedules as ps')
                    ->leftJoin('schedule_versions as sv', 'sv.id', '=', 'ps.schedule_version_id')
                    ->where('ps.loan_id', $prestamo->id)          // ✅ CRÍTICO: filtrar por préstamo
                    ->where('ps.status', 'overdue')
                    ->when(
                        $hasActiveVersion,
                        fn ($q) => $q->where('sv.status', 'active'),
                        fn ($q) => $q->whereNull('ps.schedule_version_id')
                    )
                    ->select('ps.*')
                    ->get();

                foreach ($letra_pagos as $letra_pago) {

                    $registro_moratorio = Delay::where('loan_id', $prestamo->id)
                        ->where('payment_schedule_id', $letra_pago->id)
                        ->first();

                    if ($registro_moratorio && $registro_moratorio->status === 'late') {

                        $late_amount_late = ($letra_pago->mount_quota + $letra_pago->initial) * 0.00111;

                        // Aumentar en la mora un día más de atraso
                        $registro_moratorio->days_late = $registro_moratorio->days_late + 1;
                        $registro_moratorio->late_amount = $registro_moratorio->late_amount + $late_amount_late;
                        $registro_moratorio->save();

                        // Actualizar totales de la transacción
                        $transaction = Transaction::find($prestamo->transaction_id);
                        $transaction->final_total += $late_amount_late;
                        $transaction->additional_expense_value_2 += $late_amount_late;
                        $transaction->save();

                        // Para mail
                        $customers[] = $prestamo->type_product;

                        // Para SMS
                        if (!empty($prestamo->contact->mobile)) {
                            $numeros[] = $prestamo->contact->mobile;
                        }
                    }
                }
            }

            DB::commit();

            #Envio de mensaje a nuestro correo electronico para recordar la mora de los clientes
            // if($customers){
            //         $array_customers= array_values(array_unique($customers));
            //     try {
            //         $correo = new NotificacionPrestamoLibra($array_customers);
            //         $addressee = ['informes@librainternational.com.pe','mdios@librainternational.com.pe'];
            //         Mail::to($addressee)->send($correo);
            //     } catch (\Exception $mailException) {
            //         \Log::emergency('Si se realizo El calculo de la mora, pero hay un Error al enviar correo: ' . $mailException->getMessage());
            //         exit($mailException->getMessage());
            //     }
            // }
            Log::info('MiJob se ejecutó correctamente a las ' . now());

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());
            exit($e->getMessage());
        }
    }

}
