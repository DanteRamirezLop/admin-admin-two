@extends('layouts.app')
    @section('title', __('loan.loan'))
@section('content')

<section class="content-header">
    <h1>Informes cotizaciones</h1> 
    <div class="mt-5"></div>
</section>

<section>
    <!-- Main content -->
    <div class="box box-primary">
        <div class="box-body mb-5">
            <div class="row no-print">
                <div class="col-md-12">
                    @component('components.filters', ['title' => __('report.filters')])
                    {!! Form::open(['url' => action([\App\Http\Controllers\CotizarController::class, 'report']), 'method' => 'get' ]) !!}
                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('location_id', __('purchase.business_location') . ':') !!}
                                {!! Form::select('location_id', $business_locations, null, ['class' => 'form-control select2', 'style' => 'width:100%']); !!}
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('waiter_id', __('restaurant.service_staff') . ':') !!}
                                {!! Form::select('waiter_id', $waiters, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('trending_product_date_range', __('report.date_range') . ':') !!}
                                {!! Form::text('date_range', null , ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'id' => 'trending_product_date_range', 'readonly']); !!}
                            </div>
                        </div>
                        <div class="col-sm-12">
                            <button type="submit" class="btn btn-primary pull-right">@lang('report.apply_filters')</button>
                        </div> 
                        {!! Form::close() !!}
                    @endcomponent
                </div>
            </div>

            <div class="row">
                <div class="col-sm-12">
                    @component('components.widget', ['class' => 'box-primary', 'title' => ''])
                        {!! $sells_chart_1->container() !!}
                    @endcomponent
                </div>
            </div>
        </div>
    </div>
</section>
<!-- /.content -->
@stop

@section('javascript')
    <script src="{{ asset('js/report.js?v=' . $asset_v) }}"></script>
    {!! $sells_chart_1->script() !!}
@endsection