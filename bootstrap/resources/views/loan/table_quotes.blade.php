
            <table class="table table-bordered table-striped dataTable" id="loans_table">
                <thead>
                    <tr>
                        <th>&nbsp;N° Letra</th>
                        <th>Fecha vencimiento</th>
                        <th>Estado</th> 
                        <th>Saldo inicial</th>
                        <th>+Tramite</th> 
                        <th>+GPS</th> 
                        <th>+Seguro</th>     
                        <th>+Inicial</th>
                        <th>Pago</th> 
                        <th>Capital</th> 
                        <th>Intereses</th> 
                        <th>Saldo final</th> 
                        <th>@lang( 'messages.action' )</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($paymentSchedules as $key=>$item)
                        <tr>
                            <td>{{$item->number_letter}} </td>                            
                            <td>     
                                @php
                                    $fecha = Carbon::parse($item->sheduled_date);
                                    $date = $fecha->isoFormat('dddd MMMM D\, Y'); 
                                @endphp
                                {{$date}}
                            </td>
                            <td>
                                 @switch($item->status)
                                    @case("pending")
                                        <span class="label label-default">Pendiente</span>
                                        @break
                                    @case("overdue")
                                        <span class="label label-danger">Atrasado</span>
                                        @break
                                    @case("paid")
                                        <span class="label label-success">Pagado</span>
                                    @break
                                    @case("partial")
                                        <span class="label label-info">parcial</span>
                                    @break
                                @endswitch
                            </td>
                            
                            <td>${{number_format($item->opening_balance,2)}}</td>
                            <td>${{number_format($item->admin_fee_quota,2)}}</td>
                            <td>${{number_format($item->gps_quota,2)}}</td>
                            <td>${{number_format($item->sure_quota,2)}}</td>
                            <td>${{number_format($item->initial,2)}}</td>
                            <td>${{number_format(($item->mount_quota + $item->gps_quota + $item->sure_quota + $item->admin_fee_quota + $item->initial ),2)}}</td> 
                          
                            <td>${{number_format($item->capital,2)}}</td>
                            <td>
                                ${{number_format($item->interests,2)}}
                            </td>
                            <td>
                                ${{number_format($item->final_balance,2)}}
                            </td>
                             
                            <td>  
                                @if($item->status == "paid")
                                    <span class="label bg-light-green"><i class="fas fa-check"></i> @lang( 'loan.detail_pay' )</span>
                                @else
                                    <a href="{{route('add.pay.loan',$item->id)}}" class="btn btn-xs btn-success add_payment_modal"><i class="fas fa-money-bill-alt"></i> Agregar Pago  &nbsp;</a> <br> 
                                @endif

                                @if($item->delay)
                                   
                                    @if($item->delay->status == "late")
                                    <a href="{{route('delays.show',$item->id)}}" class="btn btn-xs btn-danger">
                                        <i class="fas fa fa-money-bill-alt"></i> Gestionar Mora
                                    </a> 
                                    @else
                                        <a href="{{route('delays.show',$item->id)}}" class="btn btn-xs btn-info">
                                        <i class="fa fa-eye"></i> Ver Mora
                                        </a> 
                                    @endif
                                    
                                @else
                                    <button type="button" class="btn btn-xs btn-info add-create-delay" 
                                        data-href="{{route('add.delay.loan',$item->id)}}" 
                                        data-container=".delay_modal">
                                        <i class="fa fa-plus"></i> Agregar Mora &nbsp;&nbsp;
                                    </button>
                                @endif
                                
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>