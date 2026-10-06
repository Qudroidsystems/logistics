<!DOCTYPE html>
<html lang="en" >
  <!--begin::Head-->
  <head>
    <title>ViteeSchool</title>
    <meta charset="utf-8"/>
    <meta name="description" content=" "/>
    <meta name="keywords" content=""/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <link rel="shortcut icon" href="{{ asset('html/assets/assets/media/logos/favicon.ico')}}"/>
    <!--begin::Fonts(mandatory for all pages)-->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700"/>
    <!--end::Fonts-->
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
    @if (Route::is('dashboard'))
    @include('layouts.pages-assets.css.dashboard-css')
    @endif
    @if (Route::is('users.*'))
    @include('layouts.pages-assets.css.users-list-css')
    @endif
    @if (Route::is('user.overview'))
    @include('layouts.pages-assets.css.users-list-css')
    @endif
    @if (Route::is('user.settings'))
    @include('layouts.pages-assets.css.users-list-css')
    @endif
    @if (Route::is('roles.*'))
    @include('layouts.pages-assets.css.users-list-css')
    @endif
    @if (Route::is('permissions.*'))
    @include('layouts.pages-assets.css.permission-list-css')
    @endif
    @if (Route::is('student.*'))
    @include('layouts.pages-assets.css.users-list-css')
    @endif
    @if (Route::is('studentImageUpload.*'))
    @include('layouts.pages-assets.css.users-list-css')
    @endif
    @if (Route::is('parent.*'))
    @include('layouts.pages-assets.css.users-list-css')
    @endif
    @if (Route::is('session.*'))
    @include('layouts.pages-assets.css.users-list-css')
    @endif
    @if (Route::is('term.*'))
    @include('layouts.pages-assets.css.users-list-css')
    @endif
    @if (Route::is('schoolarm.*'))
    @include('layouts.pages-assets.css.users-list-css')
    @endif
    @if (Route::is('schoolhouse.*'))
    @include('layouts.pages-assets.css.users-list-css')
    @endif
    @if (Route::is('schoolclass.*'))
    @include('layouts.pages-assets.css.users-list-css')
    @endif
    @if (Route::is('classcategories.*'))
    @include('layouts.pages-assets.css.users-list-css')
    @endif
    @if (Route::is('classteacher.*'))
    @include('layouts.pages-assets.css.users-list-css')
    @endif
    @if (Route::is('subject.*'))
    @include('layouts.pages-assets.css.users-list-css')
    @endif
    @if (Route::is('subjectteacher.*'))
    @include('layouts.pages-assets.css.users-list-css')
    @endif
    @if (Route::is('subjectclass.*'))
    @include('layouts.pages-assets.css.users-list-css')
    @endif
    @if (Route::is('subjectoperation.*'))
    @include('layouts.pages-assets.css.users-list-css')
    @endif
    @if (Route::is('subjectinfo.*'))
    @include('layouts.pages-assets.css.users-list-css')
    @endif
    @if (Route::is('myclass.*'))
    @include('layouts.pages-assets.css.users-list-css')
    @endif
    @if (Route::is('mysubject.*'))
    @include('layouts.pages-assets.css.users-list-css')
    @endif
    @if (Route::is('viewstudent*'))
    @include('layouts.pages-assets.css.users-list-css')
    @endif
    @if (Route::is('myresultroom*'))
    @include('layouts.pages-assets.css.users-list-css')
    @endif
    @if (Route::is('subjectscoresheet*'))
    @include('layouts.pages-assets.css.users-list-css')
    @endif
    @if (Route::is('studentresults*'))
    @include('layouts.pages-assets.css.users-list-css')
    @endif
    @if (Route::is('schoolbill*'))
        @include('layouts.pages-assets.css.users-list-css')
    @endif
    @if (Route::is('schoolpayment*'))
        @include('layouts.pages-assets.css.users-list-css')
    @endif
    @if (Route::is('analysis*'))
        @include('layouts.pages-assets.css.users-list-css')
    @endif
    @if (Route::is('exams*'))
        @include('layouts.pages-assets.css.users-list-css')
    @endif
    @if (Route::is('questions*'))
        @include('layouts.pages-assets.css.users-list-css')
    @endif
    @if (Route::is('cbt*'))
        @include('layouts.pages-assets.css.users-list-css')
    @endif
    <!--begin::Body-->
  <body  id="kt_app_body" data-kt-app-layout="dark-sidebar" data-kt-app-header-fixed="true" data-kt-app-sidebar-enabled="true" data-kt-app-sidebar-fixed="true" data-kt-app-sidebar-hoverable="true" data-kt-app-sidebar-push-header="true" data-kt-app-sidebar-push-toolbar="true" data-kt-app-sidebar-push-footer="true" data-kt-app-toolbar-enabled="true"  class="app-default" >
    <!--begin::Theme mode setup on page load-->
    <script>
      var defaultThemeMode = "light";
      var themeMode;
      
      if ( document.documentElement ) {
      	if ( document.documentElement.hasAttribute("data-bs-theme-mode")) {
      		themeMode = document.documentElement.getAttribute("data-bs-theme-mode");
      	} else {
      		if ( localStorage.getItem("data-bs-theme") !== null ) {
      			themeMode = localStorage.getItem("data-bs-theme");
      		} else {
      			themeMode = defaultThemeMode;
      		}
      	}
      
      	if (themeMode === "system") {
      		themeMode = window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
      	}
      
      	document.documentElement.setAttribute("data-bs-theme", themeMode);
      }
    </script>
    <!--begin::App-->
    <div class="d-flex flex-column flex-root app-root" id="kt_app_root">
    <!--begin::Page-->
    <div class="app-page  flex-column flex-column-fluid " id="kt_app_page">
      <!--begin::Header-->
      <div id="kt_app_header" class="app-header ">
        <!--begin::Header container-->
        <div class="app-container  container-fluid d-flex align-items-stretch justify-content-between " id="kt_app_header_container">
          <!--begin::Sidebar mobile toggle-->
          <div class="d-flex align-items-center d-lg-none ms-n3 me-1 me-md-2" title="Show sidebar menu">
            <div class="btn btn-icon btn-active-color-primary w-35px h-35px" id="kt_app_sidebar_mobile_toggle">
              <i class="ki-duotone ki-abstract-14 fs-2 fs-md-1"><span class="path1"></span><span class="path2"></span></i>		
            </div>
          </div>
          <!--end::Sidebar mobile toggle-->
          <!--begin::Mobile logo-->
          <div class="d-flex align-items-center flex-grow-1 flex-lg-grow-0">
            <a href="assets/index.html')}}" class="d-lg-none">
            <img alt="Logo" src="{{ asset('html/assets/assets/media/logos/default-small.svg')}}" class="h-30px"/>
            </a>
          </div>
          <!--end::Mobile logo-->
          <!--begin::Header wrapper-->
          <div class="d-flex align-items-stretch justify-content-between flex-lg-grow-1" id="kt_app_header_wrapper">
            <!--begin::Menu wrapper-->
            <div
              class="
              app-header-menu
              app-header-mobile-drawer
              align-items-stretch
              "
              data-kt-drawer="true"
              data-kt-drawer-name="app-header-menu"
              data-kt-drawer-activate="{default: true, lg: false}"
              data-kt-drawer-overlay="true"
              data-kt-drawer-width="250px"
              data-kt-drawer-direction="end"
              data-kt-drawer-toggle="#kt_app_header_menu_toggle"
              data-kt-swapper="true"
              data-kt-swapper-mode="{default: 'append', lg: 'prepend'}"
              data-kt-swapper-parent="{default: '#kt_app_body', lg: '#kt_app_header_wrapper'}"
              >
            </div>
            <!--end::Menu wrapper-->
            @include('layouts.inc.header')
            <!--begin::Wrapper-->
            <div class="app-wrapper  flex-column flex-row-fluid " id="kt_app_wrapper">
              @include('layouts.inc.sidebar')
              @yield('content')
            </div>
            <!--end::Content wrapper-->
            <!--begin::Footer-->
            <div id="kt_app_footer" class="app-footer " >
              <!--begin::Footer container-->
              <div class="app-container  container-fluid d-flex flex-column flex-md-row flex-center flex-md-stack py-3 ">
                <!--begin::Copyright-->
                <div class="text-dark order-2 order-md-1">
                  <span class="text-muted fw-semibold me-1">2023&copy;</span>
                  <a href="#" target="_blank" class="text-gray-800 text-hover-primary">Powered By Qudroid Systems</a>
                </div>
                <!--end::Copyright-->
                <!--begin::Menu-->
                <ul class="menu menu-gray-600 menu-hover-primary fw-semibold order-1">
                  <li class="menu-item">
                    <a href="https://keenthemes.com/" target="_blank" class="menu-link px-2">
                    About
                    </a>
                  <li>
                </ul>
                <!--end::Menu-->
              </div>
              <!--end::Footer container-->
            </div>
            <!--end::Footer-->
          </div>
          <!--end:::Main-->
        </div>
        <!--end::Wrapper-->
      </div>
      <!--end::Page-->
    </div>
    <!--end::App-->
    <!--begin::Scrolltop-->
    <div id="kt_scrolltop" class="scrolltop" data-kt-scrolltop="true">
      <i class="ki-duotone ki-arrow-up"><span class="path1"></span><span class="path2"></span></i>
    </div>
    <!--end::Scrolltop-->
    <!--begin::Javascript-->
    <script>
      // var hostUrl = "{{ asset('html/assets/assets/index.html')}}";        
    </script>
    @if (Route::is('dashboard'))
    @include('layouts.pages-assets.js.dashboard-js')
    @endif
    @if (Route::is('users.*'))
    @include('layouts.pages-assets.js.users-list-js')
    @endif
    @if (Route::is('user.overview'))
    @include('layouts.pages-assets.js.users-list-js')
    @endif
    @if (Route::is('user.settings'))
    @include('layouts.pages-assets.js.users-list-js')
    @endif
    @if (Route::is('roles.*'))
    @include('layouts.pages-assets.js.role-list-js')
    @endif
    @if (Route::is('permissions.*'))
    @include('layouts.pages-assets.js.permissions-list-js')
    @endif
    @if (Route::is('session.*'))
    @include('layouts.pages-assets.js.session-list-js')
    @endif
    @if (Route::is('term.*'))
    @include('layouts.pages-assets.js.term-list-js')
    @endif
    @if (Route::is('schoolhouse.*'))
    @include('layouts.pages-assets.js.house-list-js')
    @endif
    @if (Route::is('schoolclass.*'))
    @include('layouts.pages-assets.js.schoolclass-list-js')
    @endif
    @if (Route::is('schoolarm.*'))
    @include('layouts.pages-assets.js.arm-list-js')
    @endif
    @if (Route::is('classcategories.*'))
    @include('layouts.pages-assets.js.classcategory-list-js')
    @endif
    @if (Route::is('classteacher.*'))
    @include('layouts.pages-assets.js.classteacher-list-js')
    @endif
    @if (Route::is('subject.*'))
    @include('layouts.pages-assets.js.subject-list-js')
    @endif
    @if (Route::is('subjectteacher.*'))
    @include('layouts.pages-assets.js.subjectteacher-list-js')
    @endif
    @if (Route::is('subjectclass.*'))
    @include('layouts.pages-assets.js.subjectclass-list-js')
    @endif
    @if (Route::is('student.*'))
    @include('layouts.pages-assets.js.student-list-js')
    @endif
    @if (Route::is('batchindex.*'))
    @include('layouts.pages-assets.js.studentbatch-list-js')
    @endif
    @if (Route::is('subjectoperation.*'))
    @include('layouts.pages-assets.js.subjectoperation-list-js')
    @endif
    @if (Route::is('subjectinfo.*'))
    @include('layouts.pages-assets.js.subjectinfo-list-js')
    @endif
    @if (Route::is('myclass.*'))
    @include('layouts.pages-assets.js.myclass-list-js')
    @endif
    @if (Route::is('mysubject.*'))
    @include('layouts.pages-assets.js.mysubject-list-js')
    @endif
    @if (Route::is('viewstudent*'))
    @include('layouts.pages-assets.js.viewstudent-list-js')
    @endif
    @if (Route::is('myresultroom*'))
    @include('layouts.pages-assets.js.myresultroom-list-js')
    @endif
    @if (Route::is('subjectscoresheet*'))
    @include('layouts.pages-assets.js.subjectscoresheet-list-js')
    @endif
    @if (Route::is('studentresults*'))
    @include('layouts.pages-assets.js.subjectscoresheet-list-js')
    @endif
    @if (Route::is('schoolbill*'))
    @include('layouts.pages-assets.js.schoolbill-list-js')
    @endif
    @if (Route::is('schoolbilltermsession.*'))
    @include('layouts.pages-assets.js.schoolbilltermsession-list-js')
    @endif
    @if (Route::is('schoolpayment.*'))
    @include('layouts.pages-assets.js.schoolpayment-list-js')
    @endif
    @if (Route::is('analysis.*'))
    @include('layouts.pages-assets.js.analysis-list-js')
    @endif
    @if (Route::is('exams.*'))
         @include('layouts.pages-assets.js.analysis-list-js')
    @endif
    @if (Route::is('questions.*'))
         @include('layouts.pages-assets.js.analysis-list-js')
    @endif
    @if (Route::is('cbt.*'))
         @include('layouts.pages-assets.js.analysis-list-js')
    @endif
    <!--end::Custom Javascript-->
  </body>
</html>