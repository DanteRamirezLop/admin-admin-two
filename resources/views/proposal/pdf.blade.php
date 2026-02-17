<html>
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <style>
    body{
      font-family: sans-serif;
    }
    @page {
      margin: 0;
    }
    /* header { 
      left: -50px;
      top: -150px;
      right: -50px;
      height: 200px;      
    } */
    
    .cabecera {
      background-color: #C3A42F;
    }
    header h1{
      margin: 0;
    }
    header h2{
      margin: 0 0 10px 0;
    }
    footer {
      position: fixed;
      left: 100px;
      bottom: 50px;
      right: 100px;
      height: 50px;
      /* border-bottom: 2px solid #ddd; */
    }
    footer .page:after {
      content: counter(page);
    }
    footer table {
      width: 100%;
    }
    footer p {
      text-align: right;
    }
    footer .izq {
      text-align: left;
    }
  </style>
</head>
<body>
  <header>
    <div style="padding-top: 15px;">
        <div style="align-items: start;">
            <img style="padding-left: 25px; align-items: start;" src="{{public_path('/img/comerser.png')}}" width="220" height="140">
            
        </div>
        <div style="padding-left: 550px;">
            <p style="font-size: 14px;color: #3F6EA1;font-weight: bold;">DATE: {{$date}} </p>
        </div>
    </div>    
  </header>
  
  <div id="content" style="padding-top: 20px;">
    <h1 style="text-align: center;color:#3F6EA1;font-size: 18px;"><b>PREQUALIFICATION QUOTES</b></h1>
    <p style="text-align: center;font-size: 16px;color:#3F6EA1;">DBA: Model T Inn</p>    
    <p style="padding-left: 550px;font-size: 14px;color:#3F6EA1;">OWNER: {{$proposal->owner}} </p>  
    <p style="padding-left: 550px;font-size: 14px;color:#000000;">REFERENCE # {{$proposal->id}} </p> 
    <p style="padding: 0 100px 0 100px;text-align: justify;color:#3F6EA1;">{{$proposal->content}}</p>
    <p style="padding: 0 100px 10px 100px;color:#3F6EA1;">Prequalification quotes:</p>
    <table style="text-align: center;padding-left: 80px;">
      <tr style="background-color: #3F6EA1;">
        <th style="height: 20px;color: #fff;text-align: start; padding: 10px;"> #</th>
        <th style="height: 20px;color: #fff;text-align: center;padding: 10px;"><b style="font-size: 13px;">PRODUCT TYPE</b> </th>
        <th style="height: 20px;color: #fff;text-align: center;padding: 10px;"><b style="font-size: 13px;">MULTIPLIER</b> </th>
        <th style="height: 20px;color: #fff;text-align: center;padding: 10px;"><b style="font-size: 13px;">RATE</b> </th>
        <th style="height: 20px;color: #fff;text-align: center;padding: 10px;"><b style="font-size: 13px;">AMOUNT</b> </th>
        <th style="height: 20px;color: #fff;text-align: center;padding: 10px;"><b style="font-size: 13px;">PAYBACK AMOUNT</b> </th>
        <th style="height: 20px;color: #fff;text-align: center;padding: 10px;"><b style="font-size: 13px;">ADMIN FEE</b> </th>
      </tr>
      @foreach($quotes as $key=>$item)
      <tr style="background-color: #F2F2F2;">      
        <td style="padding: 10px;color: #3F6EA1;font-size: 12px;">{{$key + 1}} </td>
        <td style="padding: 10px;color: #3F6EA1;font-size: 12px;">MCA </td>
        <td style="padding: 10px;color: #3F6EA1;font-size: 12px;">{{$item->multiplayer}} </td>
        <td style="padding: 10px;color: #3F6EA1;font-size: 12px;">{{$item->rate}}% </td>
        <td style="padding: 10px;color: #3F6EA1;font-size: 12px;">${{ number_format($item->amount,2)}} </td>
        <td style="padding: 10px;color: #3F6EA1;font-size: 12px;">${{number_format($item->admin_fee,2)}} </td>
        <td style="padding: 10px;color: #3F6EA1;font-size: 12px;">${{number_format(($item->amount*0.04),2)}} </td>
      </tr>
      @endforeach
    </table>           
  </div>

  <footer>
        <p style="font-size: 14px;text-align: justify;color: #77c6de;font-style: italic;">
        Note: {{$proposal->note}}
        </p>
  </footer>

</body>
</html>