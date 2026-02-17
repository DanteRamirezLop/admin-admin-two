@extends('layouts.app')
@section('title', __('loan.loan'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1><a href="/cotizar"> <span class="fa fa-arrow-left"></span> @lang('messages.back')</a></h1>
</section>

<!-- Main content -->
<section class="content">    
	<h3>{{$customer->supplier_business_name}}{{$customer->name}}</h3>
	<div class="row">
            <div class="col-lg-12">
            @component('components.widget', ['class' => 'box-warning', 'title' => 'Resumen de la cotización'])  
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
                                <th scope="row">Fecha de cotización</th>
                                <td>
                                    @php
                                        $fecha = Carbon::parse($loan->created_at);
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

                @if($loan->period == 2)  
                    <div class="col-lg-6">                
                        <table class="table table-bordered table-striped dataTable">
                            <tbody>     
                                <tr>
                                    <th scope="row">Inicial + Coste tramite + Inicial GPS + Inicial seguro</th>
                                    <td> 
                                        ${{number_format($loan->initial_amount + $loan->admin_fee + $loan->gps + $loan->insurance ,2)}}
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">Saldo a Financiar</th>
                                    <td> $ {{number_format($loan->loan_amount,2)}} </td>
                                </tr>
                                <tr>
                                    <th scope="row">Número de pagos</th>
                                    <td>  {{$loan->number_month}} </td>
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
                                <th scope="row">Tasa de inicial</th>
                                    <td>$ {{number_format($loan->start_rate,2)}} </td>
                                </tr>
                                <tr>
                                    <th scope="row">Coste total del préstamo</th>
                                    <td><strong> $ {{number_format($loan->amount,2)}} </strong></td>
                                </tr>
                            </tbody>
                        </table>                
                    </div>
                @endif
                @endcomponent
            </div>
        </div>

    @if($loan->period == 2)                                
            <input type="hidden" value="{{$type}}" id="loan_type">
            @component('components.widget', ['class' => 'box-primary', 'title' => __( 'loan.all_quotes' )])        
            <table class="table table-bordered table-striped dataTable text-center" id="loans_table">
                <thead>
                    <tr>
                        <th>@lang( 'loan.detail_id' )</th>
                        <th>@lang( 'loan.detail_date' )</th>
                        <th>Saldo inicial</th>
                        <th>+GPS</th> 
                        <th>+Seguro</th>
                        <th>+Inicial</th> 
                        <th>Pago</th> 
                        <th>Capital</th> 
                        <th>Intereses</th> 
                        <th>Saldo final</th> 
                    </tr>
                </thead>
                <tbody>
                    @php
                        $count=0;
                    @endphp
                        @foreach($detail as $key=>$item)
                            <tr>
                                <td>{{$item->id}}</td>                            
                                <td>     
                                    @php
                                        $fecha = Carbon::parse($item->date);
                                        $date = $fecha->isoFormat('dddd MMMM D\, Y'); 
                                    @endphp
                                    {{$date}}
                                </td>
                                <td>${{number_format($item->saldo_inicial,2)}}</td>
                                <td>${{number_format($item->gps,2)}}</td>
                                <td>${{number_format($item->seguro,2)}} </td>

                                @if(isset($item->initial))
                                    <td>${{number_format($item->initial,2)}}</td>
                                    <td>$ {{number_format(($item->amount + $item->gps + $item->seguro + $item->initial),2)}}</td>
                                @else
                                    <td>$0.00</td>
                                    <td>$ {{number_format(($item->amount + $item->gps + $item->seguro),2)}}</td>
                                @endif

                                <td>$ {{number_format($item->capital,2)}}</td>
                                <td>$ {{number_format($item->interes,2)}}</td>
                                <td>$ {{number_format($item->saldo_final,2)}}</td>
                            </tr>
                        @endforeach

                        <tr>
                            <td></td>
                            <td> <h3>Coste total del préstamo</h3></td>
                            <td></td>
                            <td style="vertical-align: middle;">
                                <h4>$ {{number_format($loan->amount,2)}}</h4>
                            </td>
                            <td></td> 
                        </tr>
                    </tbody>
            </table>
        @endcomponent
    @endif
</section>
<!-- /.content -->
@stop
@section('javascript')
    <script type="text/javascript">
        //Date range as a button
        $('#purchase_list_filter_date_range').daterangepicker(
            dateRangeSettings,
            function (start, end) {
                $('#purchase_list_filter_date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
            purchase_table.ajax.reload();
            }
        );
        $('#purchase_list_filter_date_range').on('cancel.daterangepicker', function(ev, picker) {
            $('#purchase_list_filter_date_range').val('');
            purchase_table.ajax.reload();
        });  

        $(document).on('click', 'button.pagar', function(){
            swal({
                title: LANG.sure,
                text: LANG.confirm_pagar,
                icon: "info",
                buttons: true,
            }).then((willDelete) => {
                if (willDelete) {
                    var id = $(this).attr("data-id");
                    var quote = $(this).attr("data-id-quote");
                    $.ajax({
                        method: "POST",
                        url: "/loan_edit_quote",
                        dataType: "json",
                        data: {id: id,quote: quote},
                        success: function(result){
                            if(result.success == true){
                                toastr.success(result.msg);
                                location.reload();
                            } else {
                                toastr.error(result.msg);
                            }
                        }
                    });
                }
            });
        });
    </script>
@endsection
