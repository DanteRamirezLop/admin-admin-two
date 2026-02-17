@extends('layouts.app')
@section('title', __('loan.loan'))

@section('content')

<section class="content-header">
    <h1>Configuraciones</h1> 
    <div class="mt-5"></div>
</section>

<section>
    <!-- Main content -->
    <div class="box box-primary">
        <div class="box-body mb-5">
            <div class="row">
                <div class="col-sm-12">
                    <div class="form-group">
                        {!! Form::label('product_description', 'Términos y condiciones de la cotización:') !!}
                        {!! Form::textarea('product_description', $terms->description, ['class' => 'form-control']); !!}
                    </div>
                </div>
        
                <div class="col-sm-12 text-center mt-5">
                    <input id="id_terminos" type="hidden" value="{{$terms->id}}">
                    <button type="button" class="btn btn-primary btn-big" id="terminos_edit" >
                        @lang('messages.save')
                    </button>
                </div>
            </div>

            <div class="row mt-5">
                <div class="col-sm-12">
                    <div class="form-group">
                        {!! Form::label('cotización', 'Porcentaje de Inicial:') !!}
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-sm-3">
                    <div class="input-group">
                        <div class="input-group-addon"><b>%</b></div>
                        <input type="number" id="tagInput" placeholder="Ejemplo: 25" class="form-control">
                    </div>
                </div>
                <div class="col-sm-3">
                    <button id="addTagBtn" class="btn btn-primary"> <i class="fa fa-plus"></i> Agregar </button>
                </div>
            </div>

            <div class="row m-5">
                    <div class="col-md-12 mt-5">
                        <div class="row mb-3"> <span>Valores:</span> </div>
                        <div id="tagList" class="row">
                            @foreach($percentages as $key=>$percentage)
                                <div class="col-md-1"> {{$percentage}}% 
                                    <button class="removeTag btn btn-xs btn-danger" data-value="{{$key}}" > <i class="glyphicon glyphicon-trash"></i></button>
                                </div>
                            @endforeach
                        </div>
                    </div>
            </div>
                
          <div class="row"><div class="form-group"></div></div>

            <div class="row mt-5">
                <div class="col-sm-12">
                    <div class="form-group">
                        {!! Form::label('cotización', 'Variables generales de la cotización en crédito:') !!}
                    </div>
                </div>
            </div>

            <ul class="list-group list-group-flush">
                @foreach($goals as $goal)
                    <li data-id="{{$goal->id}}" class="list-group-item">
                        <div class="row">
                            <div class="col-md-2">
                                <div class="form-group">
                                    <h4> {{$goal->description}}</h4>
                                    <input type="hidden" name="description" class="description" value="{{$goal->description}}">
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group col-sm-12">
                                    {!! Form::label('filing_fee', 'Costo total' ) !!}
                                    <input type="number" step="any" name="amount_total"  value="{{$goal->amount_total}}" class="form-control amount_total"   >
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group col-sm-12">
                                    {!! Form::label('filing_fee', 'Inicial' ) !!}  @show_tooltip(__('La inicial representa la cantidad que se añadirá al PAGO INICIAL del cliente en una cotización a crédito, la diferencia se fracciona en las cuotas del crédito, este monto tiene que ser igual o menor al monto total.'))
                                    <input type="number" step="any" name="amount_inicial"  value="{{$goal->amount_inicial}}" class="form-control amount_inicial"   > 
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="form-group col-sm-12">
                                    {!! Form::label('filing_fee', ' ' ) !!}
                                    <button type="button" class="btn btn-primary form-control editar-btn" >
                                        @lang('messages.save')
                                    </button>
                                </div>
                            </div>

                        </div>
                    </li>
                @endforeach
            </ul>


        </div>
    </div>
</section>
<!-- /.content -->
@stop
@section('javascript')
    <script src="{{ asset('js/product.js?v=' . $asset_v) }}"></script>
    <script>
        
        $(document).on('click', '#terminos_edit', function() {
           var description = tinymce.get("product_description").getContent();
           var id = $("#id_terminos").val();
            $.ajax({
                method: 'POST',
                url: '/confi-update',
                dataType: 'json',
                data: {
                    id: id,
                    amount_total: 0,
                    amount_inicial:0,
                    description: description
                },
                success: function(result) {
                    if (result.success == true) {
                        toastr.success(result.msg);
                    } else {
                        toastr.error(result.msg);
                    }
                },
            });
        });

        $(document).on('click','.editar-btn', function () {
            let fila = $(this).closest("li"); // Obtener la fila
            let id = fila.data("id");
            let amount_total = parseFloat(fila.find(".amount_total").val());
            let amount_inicial = parseFloat(fila.find(".amount_inicial").val());
            let description = fila.find(".description").val();

            if (amount_inicial > amount_total){
                swal("Oops...!!", "La Inicial no puede ser mayor al costo total del "+description, "warning");
                return false;
            }

            $.ajax({
                type: "POST",
                url: "/confi-update",
                data: {
                    id: id,
                    amount_total: amount_total,
                    amount_inicial:amount_inicial,
                    description: description 
                },
                dataType: "json",
                success: function (result) {
                    if (result.success == true) {
                        toastr.success(result.msg);
                    } else {
                        toastr.error(result.msg);
                    }
                }
            });
        });

        //REGISTRO DE LOS PORCENTAJES DE LA INICIAL
        $(document).ready(function () {
            // let tagsArray = [];
            let token_location = $('meta[name="csrf-token"]').attr('content');
            
            function renderTags(tagsArray) {
                $("#tagList").empty();
                tagsArray.forEach(function (tag, index) {
                    $("#tagList").append(`
                        <div class="col-md-1">
                            ${tag} %
                            <button class="removeTag btn btn-xs btn-danger" data-value="${index}"> <i class="glyphicon glyphicon-trash"></i></button>
                        </div>
                    `);
                });
            }

            $("#addTagBtn").on("click", function () {
                let value = $("#tagInput").val().trim();
                if (value !== "") {
                    $("#tagInput").val("");
                    $.ajax({
                        type: "POST",
                        url: "/confi-initial",
                        data: {
                            _token: token_location,
                            value: value,
                            type: 'add'
                        },
                        dataType: "json",
                         success: function (result) {
                            //Actuliar el listado de iniciales
                            if (result.status == true) {
                                renderTags(result.values);
                                toastr.success(result.msg);
                            } else {
                                toastr.error(result.msg);
                            }
                        },
                        error: function(xhr, status, error){
                            console.error("❌ Error AJAX:", status, error);
                            console.log('status:', xhr.responseText);
                            toastr.error('Error en la peticion');
                        }
                        
                    });
                }
            });

            // Eliminar etiqueta
            $("#tagList").on("click", ".removeTag", function () {
                let index = $(this).data("value");
                 $.ajax({
                    type: "POST",
                    url: "/confi-initial",
                    data: {
                        _token: token_location,
                        value: index,
                        type: 'delete'
                    },
                    dataType: "json",
                        success: function (result) {
                        //Actuliar el listado de iniciales
                        if (result.status == true) {
                            renderTags(result.values);
                            toastr.success(result.msg);
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                    error: function(xhr, status, error){
                        console.error("❌ Error AJAX:", status, error);
                        console.log('status:', xhr.responseText);
                        toastr.error('Error en la peticion');
                    }
                    
                });

            });
        });


    </script>
@endsection
