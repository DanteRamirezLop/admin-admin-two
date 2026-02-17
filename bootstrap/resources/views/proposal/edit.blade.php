<div class="modal-dialog modal-md" role="document">
  <div class="modal-content">

  {!! Form::open(['url' => action([\App\Http\Controllers\ProposalController::class, 'update'], [$proposals->id]), 'method' => 'PUT', 'id' => 'proposal_edit_form' ]) !!}
 	<div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      <h4 class="modal-title">@lang('proforma.edit_proposal')</h4>
    </div>

	<div class="modal-body">
        <div class="row">
            <div class="col-md-4 contact_type_div">
                <div class="form-group">
                    <label for="user_id">Date</label>
                    <div class="input-group">
                        <span class="input-group-addon">
                            <i class="fa fa-calendar"></i>
                        </span>
                        <input class="form-control" type="date" name="date" id="date" value="{{$proposals->date}}">
                    </div>
                </div>
            </div>
        </div>
        <div class="row">            
            <div class="col-md-6 contact_type_div">
                <div class="form-group">
                    <label for="user_id">Owner</label>
                    <div class="input-group">
                        <span class="input-group-addon">
                            <i class="fa fa-user-secret"></i>
                        </span>
                        <input class="form-control" type="text" placeholder="Owner" name="owner" id="owner" value="{{$proposals->owner}}">
                    </div>
                </div>
            </div>
            <div class="col-md-6 contact_type_div">
                <div class="form-group">                    
                    <label for="user_id">Email</label>
                    <div class="input-group">
                        <span class="input-group-addon">
                            <i class="fa fa-envelope"></i>
                        </span>
                        <input class="form-control" type="email" placeholder="Email" name="email" id="email" value="{{$proposals->email}}">
                    </div>
                </div>
            </div>   
            <div class="col-md-12 contact_type_div">
                <div class="form-group">                    
                    <label for="user_id">Content</label>
                    <textarea class="form-control" name="content" id="content" cols="50" rows="5">{{$proposals->content}}</textarea>
                </div>                
            </div>
            <div class="col-md-12 contact_type_div">
                <div class="form-group">                    
                    <label for="user_id">Note</label>
                    <textarea class="form-control" name="note" id="note" cols="50" rows="4">{{$proposals->note}}</textarea>
                </div>                
            </div>         
        </div>
    </div>

	<div class="modal-footer">
      <button class="btn btn-primary add_proposal_button">@lang( 'messages.save' )</button>
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