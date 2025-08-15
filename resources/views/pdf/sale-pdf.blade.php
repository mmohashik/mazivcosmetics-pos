<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "//www.w3.org/TR/html4/strict.dtd">
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
    <title>Sale Invoice</title>
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon.ico') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        * {
            font-family: 'DejaVu Sans', Arial, Helvetica, sans-serif;
            font-size: 10px;
            line-height: 1.3;
        }
        
        body {
            margin: 0;
            padding: 10px;
            color: #333;
        }
        
        .header {
            margin-bottom: 20px;
        }
        
        .invoice-title {
            font-size: 18px;
            font-weight: bold;
            color: #2c3e50;
            text-align: center;
        }
        
        .invoice-number {
            font-size: 14px;
            font-weight: bold;
            color: #3498db;
            text-align: center;
        }
        
        .info-box {
            border: 1px solid #eee;
            border-radius: 4px;
            margin-bottom: 15px;
            overflow: hidden;
        }
        
        .info-header {
            background-color: #3498db;
            color: white;
            padding: 8px 10px;
            font-size: 11px;
            font-weight: bold;
        }
        
        .info-body {
            padding: 10px;
            background-color: #f9f9f9;
        }
        
        .info-row {
            margin-bottom: 5px;
        }
        
        .label {
            font-weight: bold;
            display: inline-block;
            width: 100px;
        }
        
        .value {
            color: #555;
        }
        
        .company-name {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 5px;
            color: #2c3e50;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        
        th {
            background-color: #3498db;
            color: white;
            padding: 8px;
            text-align: left;
            font-size: 10px;
            font-weight: bold;
        }
        
        td {
            padding: 8px;
            border-bottom: 1px solid #eee;
        }
        
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .summary-table {
            width: 50%;
            float: right;
            margin-top: 20px;
        }
        
        .summary-table td {
            border-bottom: 1px solid #eee;
        }
        
        .total-row {
            font-weight: bold;
            background-color: #f2f2f2;
        }
        
        .logo {
            height: 60px;
        }
        
        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }
    </style>
</head>
<body>
    <div class="header clearfix">
        <div style="float: left; width: 20%;">
            <img src="{{$companyLogo}}" alt="Company Logo" class="logo">
        </div>
        <div style="float: left; width: 60%; text-align: center;">
            <div class="invoice-title">{{ getSettingValue('company_name') }}</div>
            <div class="invoice-number">INVOICE #{{ $sale->reference_code }}</div>
        </div>
        <div style="float: right; width: 20%; text-align: right;">
            <div><strong>Date:</strong> {{ \Carbon\Carbon::parse($sale->created_at)->format('Y-m-d') }}</div>
            <div><strong>Status:</strong> 
                {{ $sale->payment_status == \App\Models\Sale::PAID ? 'Paid' : 'Unpaid' }}
            </div>
        </div>
    </div>
    
    <div class="clearfix" style="margin-top: 20px;">
        <div style="float: left; width: 48%; margin-right: 2%;">
            <div class="info-box">
                <div class="info-header">CUSTOMER INFORMATION</div>
                <div class="info-body">
                    <div class="info-row">
                        <span class="label">Name:</span>
                        <span class="value">{{ $sale->customer->name ?? 'N/A' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="label">Phone:</span>
                        <span class="value">{{ $sale->customer->phone ?? 'N/A' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="label">Address:</span>
                        <span class="value">
                            {{ $sale->customer->address ?? '' }}
                            {{ $sale->customer->city ?? '' }}
                            {{ $sale->customer->country ?? '' }}
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="label">Email:</span>
                        <span class="value">{{ $sale->customer->email ?? '' }}</span>
                    </div>
                </div>
            </div>
        </div>
        
        <div style="float: right; width: 48%; margin-left: 2%;">
            <div class="info-box">
                <div class="info-header">COMPANY INFORMATION</div>
                <div class="info-body">
                    <div class="company-name">{{ getSettingValue('company_name') }}</div>
                    <div class="info-row">
                        <span class="label">Address:</span>
                        <span class="value">{{ getSettingValue('address') }}</span>
                    </div>
                    <div class="info-row">
                        <span class="label">Phone:</span>
                        <span class="value">{{ getSettingValue('phone') }}</span>
                    </div>
                    <div class="info-row">
                        <span class="label">Email:</span>
                        <span class="value">{{ getSettingValue('email') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>PRODUCT</th>
                <th class="text-right">UNIT PRICE</th>
                <th class="text-center">QTY</th>
        
            
                <th class="text-right">TOTAL</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sale->saleItems as $saleItem)
            <tr>
                <td>{{ $saleItem->product->name }}</td>
                <td class="text-right">{{ currencyAlignment(number_format((float)$saleItem->net_unit_price, 2)) }}</td>
                <td class="text-center">{{ $saleItem->quantity }}</td>
            
                <td class="text-right">{{ currencyAlignment(number_format((float)$saleItem->sub_total, 2)) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    
    <div class="clearfix">
        <table class="summary-table">
            
            <tr class="total-row">
                <td>GRAND TOTAL:</td>
                <td class="text-right">{{ currencyAlignment(number_format((float)$sale->grand_total, 2)) }}</td>
            </tr>
            <tr>
                <td>Paid Amount:</td>
                <td class="text-right">{{ currencyAlignment(number_format((float)$sale->payments->sum('amount'), 2)) }}</td>
            </tr>
            @if($sale->payment_status == \App\Models\Sale::UNPAID)
            <tr class="total-row">
                <td>DUE AMOUNT:</td>
                <td class="text-right">{{ currencyAlignment(number_format((float)($sale->grand_total - $sale->payments->sum('amount')), 2)) }}</td>
            </tr>
            @endif
        </table>
    </div>
    
    <div style="margin-top: 30px; text-align: center; font-size: 9px; color: #777;">
        Thank you for your business!
    </div>
</body>
</html>