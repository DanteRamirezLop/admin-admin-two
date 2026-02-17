@extends('layouts.app')
@section('title', __('user.goals'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang( 'user.goals' )
        <small>@lang( 'user.manage_goals' )</small>
    </h1>
</section>

<!-- Main content -->
<section class="content">
    <!-- component('components.filters', ['title' => __('report.filters')])
        <div class="col-md-4">
            <div class="form-group">
                 Form::label('purchase_list_filter_location_id',  __('purchase.business_location') . ':') 
                Form::select('purchase_list_filter_location_id', $business_locations, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); 
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                {!! Form::label('service_staffs', __('restaurant.service_staff') . ':') !!}
                <select class="form-control select2 select2-hidden-accesible" style="width:100%" name="created_by" id="created_by" tabindex="-1" aria-hidden="true">
                    <option value="" selected="selected">Todos</option>
                    foreach($service_staffs as $key=>$staffs)
                    <option value="$key">$staffs</option>
                    endforeach
                </select>
            </div>
        </div>      
        <div class="col-md-4">
            <div class="form-group">
                Form::label('purchase_list_filter_date_range', __('report.date_range') . ':') 
                Form::text('purchase_list_filter_date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'readonly']); 
            </div>
        </div>
    endcomponent -->
    <input type="hidden" value="{{$type}}" id="goal_type">
    @component('components.widget', ['class' => 'box-primary', 'title' => __( 'user.all_goals' )])
       
            @slot('tool')
                <div class="box-tools">
                    <button type="button" class="btn btn-block btn-primary btn-modal" 
                    data-href="{{action([\App\Http\Controllers\GoalController::class, 'create'])}}" 
                    data-container=".goal_modal">
                    <i class="fa fa-plus"></i> @lang('messages.add')</button>
                </div>
            @endslot
        
                <div class="table-responsive">
                    <table class="table table-bordered table-striped dataTable" id="goals_table">
                        <thead>
                            <tr>
                                <th>@lang( 'goal.staff' )</th>
                                <th>@lang( 'goal.goal' )</th>
                                <th>@lang( 'goal.month' )</th>
                                <th>@lang( 'goal.year' )</th>
                                <th>@lang( 'messages.action' )</th>
                            </tr>
                        </thead>
                    </table>
                </div>
   
    @endcomponent
    <div class="modal fade goal_modal" tabindex="-1" role="dialog" 
    	aria-labelledby="gridSystemModalLabel">
    </div>
</section>
<!-- /.content -->
@stop
@section('javascript')
<script type="text/javascript">

    var goals_table = $('#goals_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: '/goals',
        columnDefs: [
            {
                targets: 3,
                orderable: false,
                searchable: false,
            },
        ],
        columns: [
            
            { data: 'name', name: 'name' },
            { data: 'amount', name: 'amount' },
            { data: 'month', name: 'month' },
            { data: 'year', name: 'year' },
            { data: 'action', name: 'action' },
        ],
    });


    $(document).on('click', 'button.edit_goal_button', function() {
        $('div.goal_modal').load($(this).data('href'), function() {
            $(this).modal('show');

            $('form#goal_edit_form').submit(function(e) {
                e.preventDefault();
                var form = $(this);
                var data = form.serialize();

                $.ajax({
                    method: 'POST',
                    url: $(this).attr('action'),
                    dataType: 'json',
                    data: data,
                    beforeSend: function(xhr) {
                        __disable_submit_button(form.find('button[type="submit"]'));
                    },
                    success: function(result) {
                        if (result.success == true) {
                            $('div.goal_modal').modal('hide');
                            goals_table.ajax.reload();
                            toastr.success(result.msg);                            
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                });
            });
        });
    });
  
    $(document).on('click', 'button.delete_goal_button', function() {
        swal({
            title: LANG.sure,
            text: LANG.confirm_delete_goal,
            icon: 'warning',
            buttons: true,
            dangerMode: true,
        }).then(willDelete => {
            if (willDelete) {
                var href = $(this).data('href');
                var data = $(this).serialize();
                console.log(href);
                $.ajax({
                    method: 'DELETE',
                    url: href,
                    dataType: 'json',
                    data: data,
                    success: function(result) {
                        if (result.success == true) {
                            goals_table.ajax.reload();
                            toastr.success(result.msg);                            
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                });
            }
        });
    });

    //Goals table
    
</script>
@endsection
