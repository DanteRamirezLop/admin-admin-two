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
    .cabecera {
      background-color: #C3A42F;
    }
    header{
        text-align: center;
        position: fixed;
    }
    header h1{
      margin: 0;
    }
    header h2{
      margin: 0 0 10px 0;
    }
    footer {
      position: fixed;
      left: 200px;
      bottom: 50px;
      right: 200px;
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
    <div>
        <div style="align-items: center;">
            <img style="align-items: center;" src="https://comerserfinancial.com/wp-content/uploads/2019/12/logo-comercer-ingles.png" width="280" height="120">
            
        </div>
        <div>
            <p style="font-size: 14px;color: #3F6EA1;font-weight: bold;">DATE: {{$date}} </p>
        </div>
    </div>    
  </header>
  
  <div id="content" style="padding-top: 20px;">
    <h1 style="text-align: center;color:#3F6EA1;font-size: 18px;"><b>PREQUALIFICATION QUOTES</b></h1>
    <p style="text-align: center;font-size: 16px;color:#3F6EA1;">DBA: Model T Inn</p>    
    <p style="text-align:center; font-size: 14px;color:#3F6EA1;">OWNER: {{$proposal->owner}} </p>  
    <p style="text-align:center; font-size: 14px;color:#000000;">REFERENCE # {{$proposal->id}} </p> 
    <p style="text-align: justify;color:#3F6EA1;">{{$proposal->content}}</p>
    <p style="color:#3F6EA1;">Prequalification quotes:</p>
    <!-- <table style="align-items: center; text-align:center;"> -->
    <table width='700' border='1' cellpadding='8' cellspacing='0' align='center'>
      <tr style="background-color: #3F6EA1;">
        <th style="height: 20px;color: #fff;text-align: start; padding: 10px;"> #</th>
        <th style="height: 20px;color: #fff;text-align: center;padding: 10px;"><b>PRODUCT TYPE</b> </th>
        <th style="height: 20px;color: #fff;text-align: center;padding: 10px;"><b>MULTIPLIER</b> </th>
        <th style="height: 20px;color: #fff;text-align: center;padding: 10px;"><b>RATE</b> </th>
        <th style="height: 20px;color: #fff;text-align: center;padding: 10px;"><b>AMOUNT</b> </th>
        <th style="height: 20px;color: #fff;text-align: center;padding: 10px;"><b>PAYBACK AMOUNT</b> </th>
        <th style="height: 20px;color: #fff;text-align: center;padding: 10px;"><b>ADMIN FEE</b> </th>
      </tr>
      @foreach($quotes as $key=>$item)
      <tr style="background-color: #F2F2F2;">      
        <td style="padding: 10px;color: #3F6EA1;">{{$key + 1}} </td>
        <td style="padding: 10px;color: #3F6EA1;">MCA </td>
        <td style="padding: 10px;color: #3F6EA1;">{{$item->multiplayer}} </td>
        <td style="padding: 10px;color: #3F6EA1;">{{$item->rate}}% </td>
        <td style="padding: 10px;color: #3F6EA1;">${{ number_format($item->amount,2)}} </td>
        <td style="padding: 10px;color: #3F6EA1;">${{number_format($item->admin_fee,2)}} </td>
        <td style="padding: 10px;color: #3F6EA1;">${{number_format(($item->amount*0.04),2)}} </td>
      </tr>
      @endforeach
    </table>           
  </div>

  <footer>
    <div>
        <p style="font-size: 14px;text-align: justify;color: #77c6de;font-style: italic;">
        Note: {{$proposal->note}}
        </p>
    </div>
  </footer>

</body>
</html>