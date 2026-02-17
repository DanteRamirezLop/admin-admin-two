<div class="modal-dialog modal-md" role="document">
  <div class="modal-content">

  {!! Form::open(['url' => action([\App\Http\Controllers\QuoteController::class, 'update'], [$quote->id]), 'method' => 'PUT', 'id' => 'quote_edit_form' ]) !!}
 	<div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      <h4 class="modal-title">@lang('quote.edit_quote')</h4>
    </div>

    

	<div class="modal-body">
        <div class="row">     
            
            <div class="form-group col-sm-6">
            {!! Form::label('multiplayer', __( 'quote.MULTIPLIER' ) . ':*') !!}
                <input type="number" step="any" value="{{$quote->multiplayer}}" name="multiplayer" id="multiplayer" class="form-control" placeholder="Range: 1.10 - 1.42" require >
            </div>

            <div class="form-group col-sm-6">
            {!! Form::label('rate', __( 'quote.RATE' ) . ':*') !!}
                {!! Form::number('rate', $quote->rate, ['class' => 'form-control', 'placeholder' => "Range: 1 - 50", 'required']); !!}
            </div>

            <div class="form-group col-sm-6">
                    {!! Form::label('multiplayer', __( 'quote.number_month' ) . ':*') !!}
                    {!! Form::number('number_month', $quote->number_month, ['class' => 'form-control', 'placeholder' => "Range: 2 - 12 Month", 'required']); !!}
                </div>

                <div class="form-group col-sm-6">
                    {!! Form::label('rate', __( 'quote.period' ) . ':*') !!}
                    <select name="period" id="period" class="form-control" required>
                            <option value="1" {{ '1' == $quote->period ? 'selected': ''}}>@lang('quote.day')</option>
                            <option value="2" {{ '2' == $quote->period ? 'selected': ''}}>@lang('quote.week')</option>
                            <option value="3" {{ '3' == $quote->period ? 'selected': ''}}>@lang('quote.month')</option>
                        </select>
                </div>

            <div class="form-group col-sm-12">
            {!! Form::label('amount', __( 'quote.AMOUNT' ) . ':*') !!}
                {!! Form::number('amount', $quote->amount, ['class' => 'form-control', 'required', 'placeholder' => "Example: 38000"]); !!}
            </div>
     
           
                <input type="text" name="proposal_id" id="proposal_id" value="{{$quote->proposal_quote_id}}" class="hidden" >

        </div>
    </div>

	<div class="modal-footer">
      <button class="btn btn-primary">@lang( 'messages.save' )</button>
      <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close' )</button>
    </div>

    {!! Form::close() !!}
  
  </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->

@section('javascript')
	<script src="{{ asset('js/pos.js?v=' . $asset_v) }}"></script>
	<script src="{{ asset('js/product.js?v=' . $asset_v) }}"></script>
	<script src="{{ asset('js/opening_stock.js?v=' . $asset_v) }}"></script>

	<!-- Call restaurant module if defined -->
    @if(in_array('tables' ,$enabled_modules) || in_array('modifiers' ,$enabled_modules) || in_array('service_staff' ,$enabled_modules))
    	<script src="{{ asset('js/restaurant.js?v=' . $asset_v) }}"></script>
    @endif
    
@endsection