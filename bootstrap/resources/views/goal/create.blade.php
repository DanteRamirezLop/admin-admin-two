<div class="modal-dialog modal-md" role="document">
  <div class="modal-content">
  @php
    $form_id = 'goal_add_form';
    if(isset($quick_add)){
      $form_id = 'quick_add_contact';
    }

    if(isset($store_action)) {
      $url = $store_action;
      $type = 'lead';
      $customer_groups = [];
    } else {
      $url = action([\App\Http\Controllers\GoalController::class, 'store']);
      $type = isset($selected_type) ? $selected_type : '';
      $sources = [];
      $life_stages = [];
    }
  @endphp
    {!! Form::open(['url' => $url, 'method' => 'post', 'id' => $form_id ]) !!}
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      <h4 class="modal-title">@lang('goal.add_goal')</h4>
    </div>

    <div class="modal-body">
        <div class="row">            
            <div class="col-md-6 contact_type_div">
                <div class="form-group">
                    <label for="user_id">Empleado *</label>
                    <div class="input-group">
                        <span class="input-group-addon">
                            <i class="fa fa-user-secret"></i>
                        </span>
                        <select required class="form-control select2 select2-hidden-accesible" style="width:100%" name="user_id" id="user_id" tabindex="-1" aria-hidden="true">
                            <option value="" selected="selected">Seleccionar personal</option>
                            @foreach($service_staffs as $key=>$staffs)
                            <option value="{{$key}}">{{$staffs}}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="col-md-6 contact_type_div">
                <div class="form-group">                    
                    <label for="user_id">Meta *</label>
                    <div class="input-group">
                        <span class="input-group-addon">
                            <i class="fa fa-money-bill-alt"></i>
                        </span>
                        <input class="form-control" required type="number" placeholder="Monto" name="amount" id="amount">
                    </div>
                </div>
            </div>
            <div class="col-md-6 contact_type_div">
                <div class="form-group">                    
                    <label for="user_id">Mes *</label>
                    <div class="input-group">
                        <span class="input-group-addon">
                            <i class="fa fa-calendar"></i>
                        </span>
                        <select required class="form-control" name="month" id="month">
                            <option value="" selected="selected">Seleccione el Mes</option>
                            <option value="1">Enero</option>
                            <option value="2">Febrero</option>
                            <option value="3">Marzo</option>
                            <option value="4">Abril</option>
                            <option value="5">Mayo</option>
                            <option value="6">Junio</option>
                            <option value="7">Julio</option>
                            <option value="8">Agosto</option>
                            <option value="9">Setiembre</option>
                            <option value="10">Octubre</option>
                            <option value="11">Noviembre</option>
                            <option value="12">Diciembre</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="col-md-6 contact_type_div">
                <div class="form-group">                    
                    <label for="user_id">Año *</label>
                    <div class="input-group">
                        <span class="input-group-addon">
                            <i class="fa fa-calendar"></i>
                        </span>
                        <input class="form-control" require type="text" disabled name="year" id="year" value="{{$year}}">
                    </div>
                </div>                
            </div>
            <div class="col-md-12 contact_type_div">
                <div class="form-group">                    
                    <label for="user_id">Descripción</label>
                    <textarea class="form-control" placeholder="Description" name="description" id="description" cols="50" rows="3"></textarea>
                </div>                
            </div>
        </div>
    </div>

    <div class="modal-footer">
      <button class="btn btn-primary add_goal_button">@lang( 'messages.save' )</button>
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