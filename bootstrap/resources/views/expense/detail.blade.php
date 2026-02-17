<div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close no-print" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title no-print">
                Detalle de gastos  (<strong>{{__('purchase.ref_no')}}</strong> : #{{$expense->ref_no}})
            </h4>
            <h4 class="modal-title visible-print-block">
                Detalle de gastos  (<strong>{{__('purchase.ref_no')}}</strong> : #{{$expense->ref_no}})
            </h4>
        </div>     
        <div class="modal-body">
            <div class="row">
                <div class="col-sm-12">
                <p class="pull-right"><b>@lang('messages.date'):</b> {{ @format_date($expense->transaction_date) }}</p>
                </div>
            </div>
            <div class="row invoice-info">
                    <div class="col-sm-4 invoice-col">
                        <strong>{{__('expense.expense_for')}}:</strong> 
                        <div>
                             {{$expense->transaction_for->first_name}}  {{$expense->transaction_for->first_name}}
                        </div>
                        <strong> {{__('lang_v1.expense_for_contact')}}:</strong>
                        <address>
                            {!! $expense->contact->contact_address !!}
                            @if(!empty($expense->contact->tax_number))
                            <br>@lang('contact.tax_no'): {{$expense->contact->tax_number}}
                            @endif
                            @if(!empty($expense->contact->mobile))
                                ,@lang('contact.mobile'): {{$expense->contact->mobile}}
                            @endif
                            @if(!empty($expense->contact->email))
                            <br>@lang('business.email'): {{$expense->contact->email}}
                            @endif
                        </address>
                        
                        @if($expense->document_path)
                            <a href="{{$expense->document_path}}" 
                            download="{{$expense->document_name}}" class="btn btn-sm btn-success pull-left no-print">
                            <i class="fa fa-download"></i> 
                                &nbsp;{{ __('expense.download_document') }}
                            </a>
                        @endif
                    </div>

                    <div class="col-sm-4 invoice-col">
                        @lang('business.business'):
                        <address>
                            <strong>{{ $expense->business->name }}</strong>
                            {{ $expense->location->name }}
                            @if(!empty($expense->location->landmark))
                            <br>{{$expense->location->landmark}}
                            @endif
                            @if(!empty($expense->location->city) || !empty($expense->location->state) || !empty($expense->location->country))
                            <br>{{implode(',', array_filter([$expense->location->city, $expense->location->state, $expense->location->country]))}}
                            @endif
                            
                            @if(!empty($expense->business->tax_number_1))
                            <br>{{$expense->business->tax_label_1}}: {{$expense->business->tax_number_1}}
                            @endif

                            @if(!empty($expense->business->tax_number_2))
                            <br>{{$expense->business->tax_label_2}}: {{$expense->business->tax_number_2}}
                            @endif

                            @if(!empty($expense->location->mobile))
                            <br>@lang('contact.mobile'): {{$expense->location->mobile}}
                            @endif
                            @if(!empty($expense->location->email))
                            <br>@lang('business.email'): {{$expense->location->email}}
                            @endif
                        </address>
                    </div>

                    <div class="col-sm-4 invoice-col">
                        <b>@lang('purchase.ref_no'):</b> #{{ $expense->ref_no }}<br/>
                        <b>@lang('messages.date'):</b> {{ @format_date($expense->transaction_date) }}<br/>
                        @if(!empty($expense->status))
                            <b>@lang('expense.expense_status'):</b> @if($expense->type == 'expense_order'){{$po_statuses[$expense->status]['label'] ?? ''}} @else {{ __('lang_v1.' . $expense->status) }} @endif<br>
                        @endif
                        @if(!empty($expense->payment_status))
                        <b>@lang('purchase.payment_status'):</b> {{ __('lang_v1.' . $expense->payment_status) }}
                        @endif

                        @if(!empty($custom_labels['expense']['custom_field_1']))
                            <br><strong>{{$custom_labels['expense']['custom_field_1'] ?? ''}}: </strong> {{$expense->custom_field_1}}
                        @endif
                        @if(!empty($custom_labels['expense']['custom_field_2']))
                            <br><strong>{{$custom_labels['expense']['custom_field_2'] ?? ''}}: </strong> {{$expense->custom_field_2}}
                        @endif
                        @if(!empty($custom_labels['expense']['custom_field_3']))
                            <br><strong>{{$custom_labels['expense']['custom_field_3'] ?? ''}}: </strong> {{$expense->custom_field_3}}
                        @endif
                        @if(!empty($custom_labels['expense']['custom_field_4']))
                            <br><strong>{{$custom_labels['expense']['custom_field_4'] ?? ''}}: </strong> {{$expense->custom_field_4}}
                        @endif
                        @if(!empty($expense_order_nos))
                                <strong>@lang('restaurant.order_no'):</strong>
                                {{$expense_order_nos}}
                            @endif

                            @if(!empty($expense_order_dates))
                                <br>
                                <strong>@lang('lang_v1.order_dates'):</strong>
                                {{$expense_order_dates}}
                            @endif
                        @if($expense->type == 'expense_order')
                            @php
                            $custom_labels = json_decode(session('business.custom_labels'), true);
                            @endphp
                            <strong>@lang('sale.shipping'):</strong>
                            <span class="label @if(!empty($shipping_status_colors[$expense->shipping_status])) {{$shipping_status_colors[$expense->shipping_status]}} @else {{'bg-gray'}} @endif">{{$shipping_statuses[$expense->shipping_status] ?? '' }}</span><br>
                            @if(!empty($expense->shipping_address()))
                            {{$expense->shipping_address()}}
                            @else
                            {{$expense->shipping_address ?? '--'}}
                            @endif
                            @if(!empty($expense->delivered_to))
                            <br><strong>@lang('lang_v1.delivered_to'): </strong> {{$expense->delivered_to}}
                            @endif
                            @if(!empty($expense->shipping_custom_field_1))
                            <br><strong>{{$custom_labels['shipping']['custom_field_1'] ?? ''}}: </strong> {{$expense->shipping_custom_field_1}}
                            @endif
                            @if(!empty($expense->shipping_custom_field_2))
                            <br><strong>{{$custom_labels['shipping']['custom_field_2'] ?? ''}}: </strong> {{$expense->shipping_custom_field_2}}
                            @endif
                            @if(!empty($expense->shipping_custom_field_3))
                            <br><strong>{{$custom_labels['shipping']['custom_field_3'] ?? ''}}: </strong> {{$expense->shipping_custom_field_3}}
                            @endif
                            @if(!empty($expense->shipping_custom_field_4))
                            <br><strong>{{$custom_labels['shipping']['custom_field_4'] ?? ''}}: </strong> {{$expense->shipping_custom_field_4}}
                            @endif
                            @if(!empty($expense->shipping_custom_field_5))
                            <br><strong>{{$custom_labels['shipping']['custom_field_5'] ?? ''}}: </strong> {{$expense->shipping_custom_field_5}}
                            @endif
                            @php
                            $medias = $expense->media->where('model_media_type', 'shipping_document')->all();
                            @endphp
                            @if(count($medias))
                            @include('sell.partials.media_table', ['medias' => $medias])
                            @endif
                        @endif
                    </div>
            </div>
            <br>

            <div class="row">
                <div class="col-sm-12 col-xs-12">
                <div class="table-responsive">
                    @if($expense_details)
                        <table class="table text-center">
                            <thead>
                                <tr class="bg-green">
                                    <th>Tipo de Gasto</th>
                                    <th>Fecha inicio</th>
                                    <th>Fecha final</th>
                                    <th>Detalle</th>
                                    <th>Proveedor</th>
                                    <th>Tipo Comprobante</th>
                                    <th>Serie </th>
                                    <th>Número Comprobante</th>
                                    <th>Monto</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($expense_details as $expense_item)
                                <tr>
                                <td>{{$expense_item->type_expense}}</td>
                                <td>{{$expense_item->start_date_expense}}</td>
                                <td>{{$expense_item->end_date_expense}}</td>
                                <td>{{$expense_item->detail}}</td>
                                <td>{{$expense_item->supplier}}</td>
                                <td>{{$expense_item->type_invoice}}</td>
                                <td>{{$expense_item->serie_invoice}}</td>
                                <td>{{$expense_item->number_invoice}}</td>
                                <td>{{$expense_item->final_total}}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        @else
                            <p class="text-center"> <strong>No hay comprobantes registrados</strong> </p>
                        @endif
                    </div>
                </div>
            </div>

            <div class="row">
                @if(!empty($expense->type == 'expense'))
                <div class="col-sm-12 col-xs-12">
                    <h4>{{ __('sale.payment_info') }}:</h4>
                </div>
                <div class="col-md-6 col-sm-12 col-xs-12">
                <div class="table-responsive">
                    <table class="table">
                    <tr class="bg-green">
                        <th>#</th>
                        <th>{{ __('messages.date') }}</th>
                        <th>{{ __('expense.ref_no') }}</th>
                        <th>{{ __('sale.amount') }}</th>
                        <th>{{ __('sale.payment_mode') }}</th>
                        <th>{{ __('sale.payment_note') }}</th>
                    </tr>
                    @php
                        $total_paid = 0;
                    @endphp
                    @forelse($expense->payment_lines as $payment_line)
                        @php
                        $total_paid += $payment_line->amount;
                        @endphp
                        <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ @format_date($payment_line->paid_on) }}</td>
                        <td>{{ $payment_line->payment_ref_no }}</td>
                        <td><span class="display_currency" data-currency_symbol="true">{{ $payment_line->amount }}</span></td>
                        <td>{{ $payment_methods[$payment_line->method] ?? '' }}</td>
                        <td>@if($payment_line->note) 
                            {{ ucfirst($payment_line->note) }}
                            @else
                            --
                            @endif
                        </td>
                        </tr>
                        @empty
                        <tr>
                        <td colspan="5" class="text-center">
                            @lang('purchase.no_payments')
                        </td>
                        </tr>
                    @endforelse
                    </table>
                </div>
                </div>
                @endif
                <div class="col-md-6 col-sm-12 col-xs-12 @if($expense->type == 'expense_order') col-md-offset-6 @endif">
                    <div class="table-responsive">
                        <table class="table">
                        <tr>
                            <th>Importe total neto: </th>
                            <td></td>
                            <td><span class="display_currency pull-right" data-currency_symbol="true">$ {{ $expense->total_before_tax }}</span></td>
                        </tr>
                        <tr>
                            <th>Impuesto de gastos:</th>
                            <td><b>(+)</b></td>
                            <td class="text-right">
                               $ {{$expense->tax_amount}}
                            </td>
                        </tr>
                        <tr>
                            <th>Total gastado:</th>
                            <td></td>
                            <td><span class="display_currency pull-right" data-currency_symbol="true" >$ {{ $expense->final_total }}</span></td>
                        </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-primary no-print" 
              aria-label="Print" 
                onclick="$(this).closest('div.modal').printThis();">
                <i class="fa fa-print"></i> @lang( 'messages.print' )
            </button>
            <button type="button" class="btn btn-default no-print" data-dismiss="modal">@lang( 'messages.close' )</button>
        </div>
    </div>
</div>