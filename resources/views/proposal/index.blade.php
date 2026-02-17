@extends('layouts.app')
@section('title', __( 'proforma.proposals' ))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang( 'proforma.proposals' )
        <small>@lang( 'proforma.manage_your_proposals' )</small>
    </h1>
    <!-- <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> Level</a></li>
        <li class="active">Here</li>
    </ol> -->
</section>

<!-- Main content -->
<section class="content">    
    @component('components.widget', ['class' => 'box-primary', 'title' => __( 'proforma.all_proposals' )])
            @slot('tool')
                <div class="box-tools">
                    <button type="button" class="btn btn-block btn-primary btn-modal" 
                        data-href="{{action([\App\Http\Controllers\ProposalController::class, 'create'])}}" 
                        data-container=".proposal_modal">
                        <i class="fa fa-plus"></i> @lang( 'messages.add' )</button>
                </div>
            @endslot
	
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="">
                    <thead>
                        <tr>
                            <th>@lang( 'proforma.reference' )</th>
                            <th>@lang( 'proforma.owner' )</th>
                            <th>@lang( 'proforma.date' )</th>
                            <th>Email</th>
                            <th>@lang( 'messages.action' )</th>
                        </tr>
                    </thead>
                    <tbody>
						
                         @foreach($proposal as $key=>$item)
                        <tr>
                            <td>
                                # {{$item->id}}
                            </td>
                            <td>
                                {{$item->owner}}
                            </td>
                            <td>
                                {{date('j F, Y', strtotime($item->date))}}
                            </td>
                            <td>
                                {{$item->email}}
                            </td>

                            <td>
                                <a href="quota/{{$item->id}}" class="btn btn-xs btn-success delete_proposal_button" ><i class="fa fa-plus"></i>   @lang("proforma.add_quote")</a>
                                &nbsp;
                                <button type="button" class="btn btn-xs btn-primary btn-modal" 
                                data-href="{{action([\App\Http\Controllers\ProposalController::class, 'edit'], $item->id)}}" 
                                data-container=".proposal_modal"><i class="glyphicon glyphicon-edit"></i> @lang("messages.edit")</button>
                                &nbsp;
                                <button data-href="#" class="btn btn-xs btn-danger delete_proposal_button" data-id="{{$item->id}}"><i class="glyphicon glyphicon-trash"></i> @lang("messages.delete")</button>
                                &nbsp;
                              
                                <button data-href="#" class="btn btn-xs btn-secondary send_proposal_button" data-id="{{$item->id}}"><i class="fa fa-envelope"></i> @lang("proforma.send")</button>
                               
                                &nbsp;
                                <!-- <button data-href="#" class="btn btn-xs btn-danger pdf_proposal_button"><i class="fa fa-file-pdf-o"></i> PDF</button> -->
                                
                                    <form action="{{route('proposal_pdf')}}" method="post" style="display: contents;">
                                    @csrf
                                        <input type="hidden" name="id" value="{{$item->id}}">
                                        <button type="submit" class="btn btn-xs btn-warning"><i class="fa fa-file-pdf"> PDF</i></button>
                                        
                                    </form>
                               
                                
                            </td>
                           
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
    @endcomponent

    <div class="modal fade proposal_modal" tabindex="-1" role="dialog" 
    	aria-labelledby="gridSystemModalLabel">
    </div>

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
    $(document).on('click', 'button.send_proposal_button', function() {
        swal({
            title: LANG.send_title,
            text: LANG.send_mail,
            icon: "success",
            buttons: true,
            dangerMode: true,
        }).then((willDelete) => {
            if (willDelete) {
                var data = $(this).attr("data-id");
                $.ajax({
                    method: "POST",
                    url: "/proposal_send",
                    dataType: "json",
                    data: {id: data},
                    success: function(result){
                        if(result.status == true){
                            toastr.success(result.msg);
                        } else {
                            toastr.error(result.msg);
                        }
                    }
                });
            }
        });
    });
    $(document).on('click', 'button.delete_proposal_button', function(){
        swal({
            title: LANG.sure,
            text: LANG.confirm_delete_proposal,
            icon: "warning",
            buttons: true,
            dangerMode: true,
        }).then((willDelete) => {
            if (willDelete) {
                var data = $(this).attr("data-id");
                $.ajax({
                    method: "POST",
                    url: "/proposal_delete",
                    dataType: "json",
                    data: {id: data},
                    success: function(result){
                        if(result.success == true){
                            //toastr.success(result.msg);
                            location.reload();
                            toastr.success(result.msg);
                        wid
                        } else {
                            toastr.error(result.msg);
                        }
                    }
                });
            }
        });
    });

    //Goals table
    
</script>
@endsection
