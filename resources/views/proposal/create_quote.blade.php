<div class="modal-dialog" role="document">
  <div class="modal-content">
         {!! Form::open(['url' => action([\App\Http\Controllers\QuoteController::class, 'store']), 'method' => 'post', 'unit_add_form' ]) !!}

         <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">@lang( 'quote.add_unit' )</h4>
        </div>

        <div class="modal-body">
            <div class="row">
                <div class="form-group col-sm-6">
                    {!! Form::label('multiplayer', __( 'quote.MULTIPLIER' ) . ':*') !!}
                    <input type="number" step="any" name="multiplayer" id="multiplayer" class="form-control" placeholder="Range: 1.10 - 1.42" require >
                </div>

                <div class="form-group col-sm-6">
                    {!! Form::label('rate', __( 'quote.RATE' ) . ':*') !!}
                    {!! Form::number('rate', null, ['class' => 'form-control', 'placeholder' => "Range: 1 - 50", 'required']); !!}
                </div>

                <div class="form-group col-sm-6">
                    {!! Form::label('multiplayer', __( 'quote.number_month' ) . ':*') !!}
                    <input type="number" step="any" name="number_month" id="number_month" class="form-control" placeholder="Range: 2 - 12 Month" require >
                </div>

                <div class="form-group col-sm-6">
                    {!! Form::label('rate', __( 'quote.period' ) . ':*') !!}
                    <select name="period" id="period" class="form-control" required>
                            <option value="1">@lang('quote.day')</option>
                            <option value="2">@lang('quote.week')</option>
                            <option value="3">@lang('quote.month')</option>
                        </select>
                </div>

                <div class="form-group col-sm-12">
                    {!! Form::label('amount', __( 'quote.AMOUNT' ) . ':*') !!}
                    {!! Form::number('amount', null, ['class' => 'form-control', 'required', 'placeholder' => "Example: 38000"]); !!}
                </div>

                <input type="text" name="proposal_id" value="{{$proposal_id}}" id="proposal_id"  class="hidden">
                
            </div>
        </div>


        <div class="modal-footer">
            <button type="submit" class="btn btn-primary">@lang( 'messages.save' )</button>
            <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close' )</button>
        </div>
        {!! Form::close() !!}
  </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->


