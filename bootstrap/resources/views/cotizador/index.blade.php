@extends('layouts.app')
@section('title', __('loan.loan'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>Cotizaciones <small> Administra tus cotizaciones</small> </h1> 
</section>

<!-- Main content -->
<section class="content">
        <div class="row">
            <div class="mt-5"></div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="nav-tabs-custom">

                    <ul class="nav nav-tabs">
                        <li class="active">
                            <a href="#pending_job_sheet_tab" data-toggle="tab" aria-expanded="true">
                                <i class="fas fa-coins text-orange"></i> Credito
                            </a>
                        </li>
                        <li>
                            <a href="#completed_job_sheet_tab" data-toggle="tab" aria-expanded="true">
                                <i class="fa fas fa-money-bill text-success"></i> Contado
                            </a>
                        </li>
                    </ul>
                    
                    @component('components.widget', ['title' =>''])
                        @can('customer.view_own')
                            @slot('tool')
                                <div class="box-tools">
                                    <a href="/cotizar/create" class="btn btn-block btn-primary"> <i class="fa fa-plus"></i> @lang('messages.add') </a>
                                </div>
                            @endslot
                        @endcan
                        <div class="tab-content">
                            <div class="tab-pane active" id="pending_job_sheet_tab">
                                @can('customer.view_own')
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped" id="loan_table">
                                            <thead>
                                                <tr>
                                                    <th>Fecha de la cotización</th>
                                                    <th>Vendedor</th>
                                                    <th>Cliente</th>
                                                    <th>Atendido por</th>
                                                    <th>Maquinaria</th>
                                                    <th>Fuente de contacto</th>
                                                    <th>Cuotas</th>
                                                    <th>Importe del prestamo</th> 
                                                    <th>Importe total de los intereses</th>
                                                    <th>Coste total del préstamo</th> 
                                                    <th>@lang( 'messages.action' )</th>
                                                </tr>
                                            </thead>
                                        </table>
                                    </div>
                                @endcan
                            </div>
             
                            <div class="tab-pane" id="completed_job_sheet_tab">
                                @can('customer.view_own')
                                    <div class="table-responsive ">
                                        <table class="table table-bordered table-striped" id="loan_contado" style="width:100%">
                                            <thead>
                                                <tr>
                                                    <th>Fecha de la cotización</th>
                                                    <th>Vendedor</th>
                                                    <th>Cliente</th>
                                                    <th>Atendido por</th>
                                                    <th>Maquinaria</th>
                                                     <th>Fuente de contacto</th>
                                                    <th>Total</th>
                                                    <th>@lang( 'messages.action' )</th>
                                                </tr>
                                            </thead>
                                        </table>
                                    </div>
                                @endcan
                            </div>
                        </div>
                    @endcomponent
                </div> 
            </div>
        </div>

         <!-- END Nueva tabla-->
        <div class="modal fade loan_modal" tabindex="-1" role="dialog" 
            aria-labelledby="gridSystemModalLabel">
        </div>
        
</section>
<!-- /.content -->
@stop
@section('javascript')
<script type="text/javascript">
$(document).ready(function () {
    loan_table = $('#loan_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url:'/cotizar',
            "data": function ( d ) {
                 d.is_credit = 1;
            }
        },
        
        columnDefs: [
            {
                targets: 3,
                orderable: true,
                searchable: false,
            },
        ],
        order: [
            [0, 'desc']
        ],
        columns: [
            { data: 'created_at', name: 'created_at' },
             { data: 'seller', name: 'seller' },
            { data: 'type_product', name: 'type_product' },
            { data: 'waiter', name: 'waiter' },
            { data: 'period', name: 'period' },
             { data: 'contact_source', name: 'contact_source' },
            { data: 'number_month', name: 'number_month' },
            { data: 'loan_amount', name: 'loan_amount' },
            { data: 'rate', name: 'rate' },
            { data: 'amount', name: 'amount' },
            { data: 'action', name: 'action' },
        ],
    });


    loan_contado = $('#loan_contado').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url:'/cotizar',
            "data": function ( d ) {
                 d.is_credit = 0;
            }
        },
        columnDefs: [
            {
                targets: 3,
                orderable: true,
                searchable: false,
            },
        ],
        order: [
            [0, 'desc']
        ],
        columns: [
            { data: 'created_at', name: 'created_at' },
             { data: 'seller', name: 'seller' },
            { data: 'type_product', name: 'type_product' },
            { data: 'waiter', name: 'waiter' },
            { data: 'period', name: 'period' },
            {data: 'contact_source',name:'contact_source'},
            { data: 'product_price', name: 'product_price' },
            { data: 'action', name: 'action' },
        ],
        
    });


    $(document).on('click', 'button.delete_loan_button', function() {
        swal({
            title: LANG.sure,
            text: LANG.confirm_delete_loan,
            icon: 'warning',
            buttons: true,
            dangerMode: true,
        }).then(willDelete => {
            if (willDelete) {
                var href = $(this).data('href');
                var data = $(this).serialize();

                $.ajax({
                    method: 'DELETE',
                    url: href,
                    dataType: 'json',
                    data: data,
                    success: function(result) {
                        if (result.success == true) {
                            toastr.success(result.msg);
                            loan_table.ajax.reload();
                            loan_contado.ajax.reload();
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                });
            }
        });
    });
});
    
</script>
@endsection
