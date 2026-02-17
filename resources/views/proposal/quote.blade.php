@extends('layouts.app')
@section('title', __( 'quote.name' ))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h3>
        
        <a href="/proposals"> <span class="fa fa-arrow-left"></span> @lang( 'messages.back' ) </a>
    </h3>
    <!-- <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> Level</a></li>
        <li class="active">Here</li>
    </ol> -->
</section>

<!-- Main content -->
<section class="content">
    @component('components.widget', ['class' => 'box-primary', 'title' => __( 'proforma.proposal' ).' N°'.$proposal_id.' - '. __( 'quote.name' )])
        @can('unit.create')
            @slot('tool')
                <div class="box-tools">
                    <button type="button" class="btn btn-block btn-primary btn-modal" 
                        data-href="{{action([\App\Http\Controllers\QuoteController::class, 'show'],$proposal_id)}}" 
                        data-container=".quote_modal">
                        <i class="fa fa-plus"></i> @lang( 'messages.add' )  </button>
                </div>
            @endslot
        @endcan
        @can('unit.view')
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="">
                    <thead>
                        <tr>
                            <th>@lang( 'quote.PRODUCT_TIPE' )</th>
                            <th>@lang( 'quote.period' )</th>
                            <th>@lang( 'quote.number_month' )</th>
                            <th>@lang( 'quote.MULTIPLIER' )</th>
                            <th>@lang( 'quote.RATE' )</th>
                            <th>@lang( 'quote.AMOUNT' )</th>
                            <th>@lang( 'quote.PAYBACK_AMOUNT' ) </th>
                            <th>@lang( 'quote.ADMIN_FEE' ) </th>                               
                            <th>@lang( 'messages.action' )</th>
                        </tr>
                    </thead>
                    <tbody>
                         @foreach($quote as $key=>$item)
                        <tr>

                            <td>
                                MCA
                            </td>
                            <td>
                                @if($item->period==1)
                                    @lang( 'quote.day' )
                                @endif
                                @if($item->period==2)
                                    @lang( 'quote.week' )
                                @endif
                                @if($item->period==3)
                                    @lang( 'quote.month' )
                                @endif
                            </td>
                            <td>                                
                                {{$item->number_month}}
                            </td>
                            <td>
                                {{$item->multiplayer}}
                            </td>
                            <td>
                                {{$item->rate}}%
                            </td>
                            <td>
                                ${{number_format($item->amount,2)}}
                            </td>
                            <td>
                                ${{number_format($item->admin_fee,2)}}
                            </td>     
                            <td>
                                ${{number_format($item->amount*0.04,2)}}
                            </td>                       
                            <td>
                      
                                <button type="button" class="btn btn-xs btn-primary btn-modal" 
                                data-href="{{action([\App\Http\Controllers\QuoteController::class, 'edit'], $item->id)}}" 
                                data-container=".quote_modal"><i class="glyphicon glyphicon-edit"></i> @lang("messages.edit")</button>
                                &nbsp;
                                <button data-href="#" class="btn btn-xs btn-danger delete_quote_button" data-id="{{$item->id}}"><i class="glyphicon glyphicon-trash"></i> @lang("messages.delete")</button>
                           
                            </td>
            
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endcan
    @endcomponent

    <div class="modal fade quote_modal" tabindex="-1" role="dialog" 
    	aria-labelledby="gridSystemModalLabel">
    </div>

</section>
<!-- /.content -->

@stop
@section('javascript')

    <script>
        $(document).on('click', 'button.delete_quote_button', function(){
        swal({
            title: LANG.sure,
            text: LANG.confirm_delete_quote,
            icon: "warning",
            buttons: true,
            dangerMode: true,
        }).then((willDelete) => {
            if (willDelete) {
                var data = $(this).attr("data-id");
				console.log(data);
                $.ajax({
                    method: "POST",
                    url: "/quote_delete",
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
    </script>

@endsection
