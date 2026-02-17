@extends('layouts.app')
@section('title', __('loan.loan'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1><a href="/loans"> <span class="fa fa-arrow-left"></span> @lang( 'messages.back' )</a></h1>
</section>

<!-- Main content -->
<section class="content">    
	<h3>{{$customer->supplier_business_name}}{{$customer->name}}</h3>
	<div class="row">
        <div class="col-lg-12">
            @component('components.widget', ['class' => 'box-success', 'title' => 'Resumen del prestamo'])  
                <div class="col-lg-6">                
                    <table class="table table-bordered table-striped dataTable">
                        <tbody>
                            <tr>
                                <th scope="row">Cliente</th>
                                <td>{{$customer->supplier_business_name}}{{$customer->name}}</td>
                            </tr>
                            <tr>
                                <th scope="row">Maquinaria</th>
                                <td>{{$loan->product_name}}</td>
                            </tr>
                            <tr>
                                <th scope="row">Código VIN</th>
                                <td>{{$loan->vin}}</td>
                            </tr>
                            
                            <tr>
                                <th scope="row">Fecha del prestamo</th>
                                <td>
                                    @php
                                        $fecha = Carbon::parse($loan->date);
                                        $date = $fecha->isoFormat('dddd MMMM D\, Y'); 
                                    @endphp
                                    {{$date}}
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Total a pagar</th>
                                <td> $ {{number_format($total,2)}}</td>
                            </tr>                            
                        </tbody>
                    </table>                
                </div>
    
                <div class="col-lg-6">                
                    <table class="table table-bordered table-striped dataTable">
                        <tbody>     
                            <tr>
                                <th scope="row">Inicial + Coste tramite + Inicial GPS + Inicial seguro</th>
                                <td>$ {{number_format($loan->initial_amount + $loan->admin_fee + $loan->gps + $loan->insurance ,2)}}</td>
                            </tr>
                            <tr>
                                <th scope="row">Saldo a Financiar</th>
                                <td>$ {{number_format($loan->loan_amount,2)}} </td>
                            </tr>
                            <tr>
                                <th scope="row">Número de pagos</th>
                                <td>{{$loan->number_month}} </td>
                            </tr>
                            <tr>
                                <th scope="row">Tasa de interés anual</th>
                                <td>{{$loan->multiplier}}%</td>
                            </tr>
                            <tr>
                                <th scope="row">Importe total de los intereses</th>
                                <td>$ {{number_format($loan->rate,2)}}</td>
                            </tr>
                            <tr>
                                <th scope="row">Coste del tramite</th>
                                <td>$ {{number_format($loan->admin_fee,2)}}</td>
                            </tr>
                            <tr>
                                <th scope="row">Costo total del GPS</th>
                                <td>$ {{number_format(($loan->gps + $loan->gps_quotes),2)}}</td>
                            </tr>
                            <tr>
                                <th scope="row">Costo total del Seguro</th>
                                <td>$ {{number_format(($loan->insurance + $loan->insurance_quotes),2)}}</td>
                            </tr>
                                <tr>
                                <th scope="row">Coste total del préstamo</th>
                                <td><strong> $ {{number_format($loan->amount,2)}} </strong></td>
                            </tr>
                        </tbody>
                    </table>                
                </div>
                @endcomponent
            </div>


            @if($annexes)
                <div class="col-lg-12">
                    @component('components.widget', ['class' => 'box-success', 'title' => 'Información adicional']) 
                        <table class="table table-bordered table-striped dataTable">
                            <thead>
                                <tr>
                                    <th>Anexo 1</th>
                                    <th>Anexo 2</th> 
                                    <th>Anexo 3</th>
                                    <th>Anexo 4</th> 
                                </tr>
                            </thead>
                            <tbody>
                               <tr>
                                    <td id="{{$loan->id}}" data-id="anexo_1"> 
                                        <div class="eq-height-col">
                                            <input type="text" class="form-control annexe" value="{{$annexes->anexo_1}}" placeholder="Anexo 1"> 
                                            <button class="btn btn-primary editar-btn-annexes"> Actualizar </button>
                                        </div>
                                    </td>
                                    <td id="{{$loan->id}}" data-id="anexo_2"> 
                                        <div class="eq-height-col">
                                        <input type="text" class="form-control annexe"  value="{{$annexes->anexo_2}}" placeholder="Anexo 2"> 
                                        <button class="btn btn-primary editar-btn-annexes"> Actualizar </button>
                                        </div>
                                    </td>
                                     <td id="{{$loan->id}}" data-id="anexo_3"> 
                                        <div class="eq-height-col">
                                        <input type="text" class="form-control annexe"  value="{{$annexes->anexo_3}}" placeholder="Anexo 3"> 
                                        <button class="btn btn-primary editar-btn-annexes"> Actualizar </button>
                                        </div>
                                    </td>
                                     <td id="{{$loan->id}}" data-id="anexo_4">  
                                        <div class="eq-height-col">
                                        <input type="text" class="form-control annexe"  value="{{$annexes->anexo_4}}" placeholder="Anexo 4"> 
                                        <button class="btn btn-primary editar-btn-annexes"> Actualizar </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table> 
                    @endcomponent
                </div>
            @endif
        </div>
        
        @component('components.widget', ['class' => 'box-primary', 'title' => __( 'loan.all_quotes' )])        
            <table class="table table-bordered table-striped dataTable" id="loans_table">
                <thead>
                    <tr>
                        <th>Número de letra</th>
                        <th>Actualizar</th>
                        <th>@lang( 'loan.detail_date' )</th>
                        <th>Saldo inicial</th>
                        <th>+GPS</th> 
                        <th>+Seguro</th>     
                        <th>Pago</th> 
                        <th>Capital</th> 
                        <th>Intereses</th> 
                        <th>Saldo final</th> 
                    </tr>
                </thead>
                <tbody>
                    @foreach($paymentSchedules as $key=>$item)
                        <tr class="list-schedule" data-id="{{$item->id}}">
                            <td>
                                <input type="text" name="number_letter" class="form-control number_letter"  value="{{$item->number_letter}}" placeholder="001-0000">
                            </td>
                            <td class="text-center"> 
                                <button class="btn btn-primary editar-btn"> Actualizar </button>
                            </td>                             
                            <td>     
                                @php
                                    $fecha = Carbon::parse($item->sheduled_date);
                                    $date = $fecha->isoFormat('dddd MMMM D\, Y'); 
                                @endphp
                                {{$date}}
                            </td>
                            <td>$ {{number_format($item->opening_balance,2)}}</td>
                            <td>$ {{number_format($item->gps_quota,2)}}</td>
                            <td>$ {{number_format($item->sure_quota,2)}}</td>
                            <td>$ {{number_format(($item->mount_quota + $item->gps_quota + $item->sure_quota),2)}}</td>
                            <td>$ {{number_format($item->capital,2)}}</td>
                            <td>$ {{number_format($item->interests,2)}}</td>
                            <td>$ {{number_format($item->final_balance,2)}}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endcomponent
</section>
@stop
@section('javascript')
    <script>
            $(document).ready(function () {

              $(".editar-btn").on('click', function () {
                let fila = $(this).closest(".list-schedule"); 
                let id = fila.data("id");
                let number_letter = fila.find(".number_letter").val();
                $.ajax({
                    type: "POST",
                    url: "/letter-annexe-update",
                    data: {
                        id: id,
                        value: number_letter,
                        type: 'letter',
                        celda: ''
                    },
                    dataType: "json",
                    success: function (result) {
                        if (result.success == true) {
                            toastr.success(result.msg);
                        } else {
                            toastr.error(result.msg);
                        }
                    }
                });
            });
            
            $(".editar-btn-annexes").on('click', function () {
               let fila = $(this).closest("td");
               let id = fila.attr("id");
               let celda = fila.data("id");
               let annexe = fila.find(".annexe").val(); 
                $.ajax({
                    type: "POST",
                    url: "/letter-annexe-update",
                    data: {
                        id: id,
                        value: annexe,
                        type: 'annexe',
                        celda: celda,
                    },
                    dataType: "json",
                    success: function (result) {
                        if (result.success == true) {
                            toastr.success(result.msg);
                        } else {
                            toastr.error(result.msg);
                        }
                    }
                });
            });
         });
    </script>
@endsection
