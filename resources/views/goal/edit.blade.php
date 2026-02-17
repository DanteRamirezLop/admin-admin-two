<div class="modal-dialog modal-md" role="document">
  <div class="modal-content">

  {!! Form::open(['url' => action([\App\Http\Controllers\GoalController::class, 'update'], [$goals->id]), 'method' => 'PUT', 'id' => 'goal_edit_form' ]) !!}
 	<div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      <h4 class="modal-title">@lang('goal.edit_goal')</h4>
    </div>

	<div class="modal-body">
        <div class="row">            
            <div class="col-md-6 contact_type_div">
                <div class="form-group">
                    <label for="user_id">Empleado</label>
                    <div class="input-group">
                        <span class="input-group-addon">
                            <i class="fa fa-user-secret"></i>
                        </span>
                        <select class="form-control select2 select2-hidden-accesible" style="width:100%" name="user_id" id="user_id" tabindex="-1" aria-hidden="true">
                            <option value="" selected="selected">Seleccionar personal</option>
                            @foreach($service_staffs as $key=>$staffs)
                            <option value="{{$staffs->id}}" {{$goals->user_id==$staffs->id?'selected':''}}>{{$staffs->surname}} {{$staffs->first_name}} {{$staffs->last_name}}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="col-md-6 contact_type_div">
                <div class="form-group">                    
                    <label for="user_id">Meta</label>
                    <div class="input-group">
                        <span class="input-group-addon">
                            <i class="fa fa-money-bill-alt"></i>
                        </span>
                        <input class="form-control" type="number" placeholder="Monto" name="amount" id="amount" value="{{$goals->amount}}">
                    </div>
                </div>
            </div>
            <div class="col-md-6 contact_type_div">
                <div class="form-group">                    
                    <label for="user_id">Mes</label>
                    <div class="input-group">
                        <span class="input-group-addon">
                            <i class="fa fa-calendar"></i>
                        </span>
                        <select class="form-control" name="month" id="month">
                            <option value="1" {{$goals->month==1?'selected':''}}>Enero</option>
                            <option value="2" {{$goals->month==2?'selected':''}}>Febrero</option>
                            <option value="3" {{$goals->month==3?'selected':''}}>Marzo</option>
                            <option value="4" {{$goals->month==4?'selected':''}}>Abril</option>
                            <option value="5" {{$goals->month==5?'selected':''}}>Mayo</option>
                            <option value="6" {{$goals->month==6?'selected':''}}>Junio</option>
                            <option value="7" {{$goals->month==7?'selected':''}}>Julio</option>
                            <option value="8" {{$goals->month==8?'selected':''}}>Agosto</option>
                            <option value="9" {{$goals->month==9?'selected':''}}>Setiembre</option>
                            <option value="10" {{$goals->month==10?'selected':''}}>Octubre</option>
                            <option value="11" {{$goals->month==11?'selected':''}}>Noviembre</option>
                            <option value="12" {{$goals->month==12?'selected':''}}>Diciembre</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="col-md-6 contact_type_div">
                <div class="form-group">                    
                    <label for="user_id">Año</label>
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
                    <textarea class="form-control" placeholder="Description" name="description" id="description" cols="50" rows="3">{{$goals->description}}</textarea>
                </div>                
            </div>
        </div>
    </div>

	<div class="modal-footer">
      <button type="submit" class="btn btn-primary">@lang( 'messages.update' )</button>
      <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close' )</button>
    </div>

    {!! Form::close() !!}
  
  </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->

