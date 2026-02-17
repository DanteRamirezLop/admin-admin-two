<div class="modal-dialog" role="document">
  <div class="modal-content">
    {!! Form::open(['url' => action([\App\Http\Controllers\ExpenseController::class, 'saveQuickDetail']), 'method' => 'post', 'id' => 'save_quick_detail_expense' ]) !!}
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      <h4 class="modal-title">Detalle de gastos</h4>
    </div>
    <div class="modal-body">
        <div class="row">
            <div class="col-sm-6">
              <div class="form-group">
                {!! Form::label('type_expense', 'Tipo de gato:') !!}
                {!! Form::select('type_expense', ['Hospedaje'=>'Hospedaje','Alimentacion'=>'Alimentación','Movilidad'=>'Movilidad','Peaje'=>'Peaje'], null, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'),'required']); !!}
              </div>
            </div>
            <div class="col-sm-6">
              <div class="form-group">
                {!! Form::label('final_total', __('sale.total_amount') . ':*') !!}
                {!! Form::number('final_total', null, ['class' => 'form-control input_number', 'placeholder' => __('sale.total_amount'), 'required']); !!}
              </div>
            </div>
            <div class="col-sm-6">
              <div class="form-group">
                {!! Form::label('start_date_expense', 'De:*') !!}
                <div class="input-group">
                  <span class="input-group-addon">
                    <i class="fa fa-calendar"></i>
                  </span>
                  {!! Form::text('start_date_expense', @format_datetime('now'), ['class' => 'form-control', 'readonly', 'id' => 'start_date_expense']); !!}
                </div>
              </div>
            </div>
            <div class="col-sm-6">
              <div class="form-group">
                {!! Form::label('end_date_expense', 'Hasta:*') !!}
                <div class="input-group">
                  <span class="input-group-addon">
                    <i class="fa fa-calendar"></i>
                  </span>
                  {!! Form::text('end_date_expense', @format_datetime('now'), ['class' => 'form-control', 'readonly', 'id' => 'end_date_expense']); !!}
                </div>
              </div>
            </div>
            <div class="col-sm-12">
                <div class="form-group">
                  {!! Form::label('detail', 'Detalle:*') !!}
                    {!! Form::text('detail', null, ['class' => 'form-control', 'required', 'placeholder' => 'Detalle del gasto']); !!}
                </div>
            </div>
            <div class="col-sm-12">
              <div class="form-group">
                {!! Form::label('supplier', 'Proveedor:*') !!}
                  {!! Form::text('supplier', null, ['class' => 'form-control', 'required', 'placeholder' => 'Nombre del proveedor']); !!}
              </div>
            </div>
             <div class="col-sm-4">
              <div class="form-group">
                {!! Form::label('type_invoice', 'Tipo Comprobante:') !!}
                {!! Form::select('type_invoice', ['Factura'=>'Factura','Boleta'=>'Boleta','Sin comprobante'=>'Sin Comprobante'], null, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'),'required']); !!}
                </div>
            </div>
             <div class="col-sm-4">
              <div class="form-group">
                {!! Form::label('serie_invoice', 'Serie Comprobante:') !!}
                {!! Form::number('serie_invoice', null, ['class' => 'form-control', 'placeholder' => '0001']); !!}
              </div>
            </div>
            <div class="col-sm-4">
              <div class="form-group">
                {!! Form::label('number_invoice', 'Número Comprobante:') !!}
                {!! Form::number('number_invoice', null, ['class' => 'form-control', 'placeholder' => '200043210']); !!}
              </div>
            </div>
            
        </div>
    </div>

    <div class="modal-footer">
      <button type="submit" class="btn btn-primary">@lang( 'messages.save' )</button>
      <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close' )</button>
    </div>

    {!! Form::close() !!}
  </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->

<script type="text/javascript">
  $(document).ready( function(){
      $('#start_date_expense').datetimepicker({
          format: moment_date_format,
          ignoreReadonly: true,
      });
      $('#end_date_expense').datetimepicker({
          format: moment_date_format,
          ignoreReadonly: true,
      });
  });

   $(document).one('submit', 'form#save_quick_detail_expense', function(e) {
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
                    // AGREGAR EL MONTO EN EL INPUT TOTAL_AMOUNT DE LA VISTA CREATE EXPENSE
                    var $number_row = parseInt($('#number_row').val()) + 1;
                    $('#number_row').val($number_row);
                    var current_total = parseFloat($('#final_total').val()) || 0;
                    var new_total = current_total + parseFloat(result.row.final_total);
                    $('#final_total').val(new_total.toFixed(2));
                    $('#expense_payment_table').append(
                      `<tr>
                        <td>
                            <input type="hidden" name="expense_details[${$number_row}][type_expense]" value='${result.row.type_expense}' />
                              ${result.row.type_expense}
                        </td>
                        <td>
                            <input type="hidden" name="expense_details[${$number_row}][start_date_expense]" value='${result.row.start_date_expense}' />
                              ${result.row.start_date_expense}
                        </td>
                        <td>
                            <input type="hidden" name="expense_details[${$number_row}][end_date_expense]" value='${result.row.end_date_expense}' />
                              ${result.row.end_date_expense}</td>
                        <td>
                            <input type="hidden" name="expense_details[${$number_row}][detail]" value='${result.row.detail}' />
                              ${result.row.detail}
                        </td>
                        <td>
                            <input type="hidden" name="expense_details[${$number_row}][supplier]" value='${result.row.supplier}' />
                              ${result.row.supplier}
                        </td>
                        <td>
                            <input type="hidden" name="expense_details[${$number_row}][type_invoice]" value='${result.row.type_invoice}' />
                              ${result.row.type_invoice}</td>
                        <td>
                            <input type="hidden" name="expense_details[${$number_row}][serie_invoice]" value='${result.row.serie_invoice}' />
                              ${result.row.serie_invoice}
                        </td>
                        <td>
                            <input type="hidden" name="expense_details[${$number_row}][number_invoice]" value='${result.row.number_invoice}' />
                              ${result.row.number_invoice}</td>
                        <td>
                            <input type="hidden" name="expense_details[${$number_row}][final_total]" value='${result.row.final_total}' />
                              $ ${result.row.final_total}
                          </td>
                        <td>
                            <button type="button" class="btn btn-danger btn-xs remove_expense_detail_button"> - </button>
                        </td>
                      </tr>
                      `
                    );
                    $('#save_quick_detail_expense')[0].reset();
                    $('.expense_detail_modal').modal('hide');
                    toastr.success(result.msg);
                } else {
                    toastr.error(result.msg);
                }
            },
          error: function (jqXHR, textStatus, errorThrown) {
              console.log("Error en AJAX");
              console.log("Estado:", textStatus);
              console.log("Error devuelto:", errorThrown);
              console.log("Respuesta del servidor:", jqXHR.responseText);
          }
        });
   });

   $(document).on('click', '.remove_expense_detail_button', function() {
        swal({
            title: LANG.sure,
            icon: 'warning',
            buttons: true,
            dangerMode: true,
        }).then(value => {
            if (value) {
                var amount_to_subtract = parseFloat($(this).closest('tr').find('td').eq(8).text()) || 0;
                var current_total = parseFloat($('#final_total').val()) || 0;
                var new_total = current_total - amount_to_subtract;
                $('#final_total').val(new_total.toFixed(2));
                $(this).closest('tr').remove();
                // update_table_total();
                // update_grand_total();
                // update_table_sr_number();
            }
        });
    });

</script>
