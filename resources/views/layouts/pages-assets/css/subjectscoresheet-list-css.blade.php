
<style>
       * {
       /* box-sizing: border-box;
       margin: 0;
       padding: 0; */
       }
       .fraction {
       display: inline-flex;
       flex-direction: column;
       align-items: center;
       font-family: Arial, sans-serif;
       font-size: 10px;
       }
       .fraction .numerator {
       border-bottom: 2px solid black;
       padding: 0 5px;
       }
       .fraction .denominator {
       padding-top: 5px;
       }
       tr.rt>th,
       tr.rt>td {
       text-align: center;
       }
       div.grade>span {
       font-family: Arial, Helvetica, sans-serif;
       font-size: 16px;
       font-weight: bold;
       }
       span.text-space-on-dots {
       position: relative;
       width: 500px;
       border-bottom-style: dotted;
       }
       span.text-dot-space2 {
       position: relative;
       width: 300px;
       border-bottom-style: dotted;
       }
       @media print {
       div.print-body {
       background-color: white;
       }
       @page {
       size: 940px;
       margin: 0px;
       }
       div.print-body {
       background-color: white;
       }
       html,
       body {
       width: 940px;
       }
       body {
       margin: 0;
       }
       nav {
       display: none;
       }
       }
       p.school-name1 {
       font-family: 'Times New Roman', Times, serif;
       font-size: 40px;
       font-weight: 500;
       }
       p.school-name2 {
       font-family: 'Times New Roman', Times, serif;
       font-size: 30px;
       font-weight: bolder;
       }
       div.school-logo {
       width: 80px;
       height: 60px;
       }
       div.header-divider {
       width: 100%;
       height: 3px;
       background-color: black;
       margin-bottom: 3px;
       }
       div.header-divider2 {
       width: 100%;
       height: 1px;
       background-color: black;
       }
       span.result-details {
       font-size: 16px;
       font-family: 'Times New Roman', Times, serif;
       font-weight: lighter;
       font-style: italic;
       }
       span.rd1 {
       position: relative;
       width: 86.1%;
       border-bottom-style: dotted;
       }
       span.rd2 {
       position: relative;
       width: 30%;
       border-bottom-style: dotted;
       }
       span.rd3 {
       position: relative;
       width: 30%;
       border-bottom-style: dotted;
       }
       span.rd4 {
       position: relative;
       width: 30%;
       border-bottom-style: dotted;
       }
       span.rd5 {
       position: relative;
       width: 25%;
       border-bottom-style: dotted;
       }
       span.rd6 {
       position: relative;
       width: 28%;
       border-bottom-style: dotted;
       }
       span.rd7 {
       position: relative;
       width: 17.2%;
       border-bottom-style: dotted;
       }
       span.rd8 {
       position: relative;
       width: 12%;
       border-bottom-style: dotted;
       }
       span.rd9 {
       position: relative;
       width: 11%;
       border-bottom-style: dotted;
       }
       span.rd10 {
       position: relative;
       width: 11%;
       border-bottom-style: dotted;
       }
</style>
<!-- Sweet Alert css-->
<link href="{{ asset('theme/layouts/assets/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css">

<!-- dropzone css -->
<link href="{{ asset('theme/layouts/assets/libs/dropzone/dropzone.css') }}" rel="stylesheet" type="text/css">

       <!-- Layout config Js -->
<script src="{{ asset('theme/layouts/assets/js/layout.js')}}"></script>
<!-- Bootstrap Css -->
<link href="{{ asset('theme/layouts/assets/css/bootstrap.min.css')}}" rel="stylesheet" type="text/css">
<!-- Icons Css -->
<link href="{{ asset('theme/layouts/assets/css/icons.min.css')}}" rel="stylesheet" type="text/css">
<!-- App Css-->
<link href="{{ asset('theme/layouts/assets/css/app.min.css')}}" rel="stylesheet" type="text/css">
<!-- custom Css-->
<link href="{{ asset('theme/layouts/assets/css/custom.min.css')}}" rel="stylesheet" type="text/css">