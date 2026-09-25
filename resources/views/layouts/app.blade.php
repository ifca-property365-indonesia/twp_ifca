{{--
    Layout bersama portal admin & tenant (CoreUI 5).
    Dipakai lewat resources/views/admin/template/layout2/base.blade.php dan
    resources/views/tenant/template/base.blade.php yang mengisi section 'sidebar' & 'header'.

    Section : title, sidebar, header, content
    Stack   : styles (di <head>), scripts (sebelum </body>)
    Variabel: $appTitle (judul tab), $portal ('admin'|'tenant')
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Tenant Web Portal">
    <link rel="shortcut icon" href="{{ url('images/logo/IFCA.png') }}">
    <title>@hasSection('title')@yield('title') - @endif{{ $appTitle ?? 'IFCA' }}</title>

    <!-- CoreUI -->
    <link rel="stylesheet" href="{{ url('assets/coreui/css/coreui.min.css') }}">
    <link rel="stylesheet" href="{{ url('assets/coreui/icons/css/free.min.css') }}">
    <!-- Plugin -->
    <link rel="stylesheet" href="{{ url('assets/vendor/datatables/dataTables.bootstrap5.min.css') }}">
    <link rel="stylesheet" href="{{ url('assets/vendor/datatables/buttons.bootstrap5.min.css') }}">
    <link rel="stylesheet" href="{{ url('assets/vendor/select2/select2.min.css') }}">
    <link rel="stylesheet" href="{{ url('assets/vendor/select2/select2-bootstrap-5-theme.min.css') }}">
    <link rel="stylesheet" href="{{ url('assets/vendor/bootstrap-datepicker/bootstrap-datepicker.min.css') }}">
    <link rel="stylesheet" href="{{ url('assets/vendor/cropperjs/cropper.min.css') }}">
    <!-- App -->
    <link rel="stylesheet" href="{{ url('assets/app/css/app.css?ver=1.0.9') }}">
    @stack('styles')

    {{-- Script dimuat di <head> karena banyak halaman memakai jQuery/plugin langsung di dalam @section('content') --}}
    <script src="{{ url('assets/vendor/jquery/jquery.min.js') }}"></script>
    <script src="{{ url('assets/vendor/jquery/jquery.validate.min.js') }}"></script>
    <script src="{{ url('assets/coreui/js/coreui.bundle.min.js') }}"></script>
    <script src="{{ url('assets/vendor/moment/moment.min.js') }}"></script>
    <script src="{{ url('assets/vendor/datatables/dataTables.min.js') }}"></script>
    <script src="{{ url('assets/vendor/datatables/dataTables.bootstrap5.min.js') }}"></script>
    <script src="{{ url('assets/vendor/datatables/dataTables.buttons.min.js') }}"></script>
    <script src="{{ url('assets/vendor/datatables/buttons.bootstrap5.min.js') }}"></script>
    <script src="{{ url('assets/vendor/jszip/jszip.min.js') }}"></script>
    <script src="{{ url('assets/vendor/pdfmake/pdfmake.min.js') }}"></script>
    <script src="{{ url('assets/vendor/pdfmake/vfs_fonts.js') }}"></script>
    <script src="{{ url('assets/vendor/datatables/buttons.html5.min.js') }}"></script>
    <script src="{{ url('assets/app/js/pdf-export.js?ver=1.0.2') }}"></script>
    <script src="{{ url('assets/vendor/select2/select2.full.min.js') }}"></script>
    <script src="{{ url('assets/vendor/sweetalert2/sweetalert2.all.min.js') }}"></script>
    <script src="{{ url('assets/vendor/bootstrap-datepicker/bootstrap-datepicker.min.js') }}"></script>
    <script src="{{ url('assets/vendor/chartjs/chart.umd.min.js') }}"></script>
    <script src="{{ url('assets/vendor/cropperjs/cropper.min.js') }}"></script>
    <script src="{{ url('assets/app/js/app.js?ver=1.0.0') }}"></script>
    <script src="{{ url('assets/app/js/timepicker.js?ver=1.0.0') }}"></script>
    @include('partials.i18n_plugins')
    <script src="{{ url('assets/app/js/file-input.js?ver=1.0.0') }}"></script>
    @stack('head-scripts')
</head>

<body>
    @yield('sidebar')

    <div class="wrapper d-flex flex-column min-vh-100">
        @yield('header')

        <div class="body flex-grow-1 py-4">
            <div class="container-fluid px-3 px-lg-4">
                @yield('content')
            </div>
        </div>

        <footer class="footer">
            <div>&copy; {{ date('Y') }} IFCA &middot; {{ __('common.footer') }}</div>
        </footer>
    </div>

    {{-- Modal bersama (isi dimuat via AJAX oleh halaman) --}}
    <div class="modal fade" tabindex="-1" id="modal">
        <div class="modal-dialog modal-lg" id="modaldialog">
            <div class="modal-content">
                <button type="button" class="btn-close" data-coreui-dismiss="modal" aria-label="{{ __('common.close') }}"></button>
                <div class="modal-header" id="modalheader">
                    <h5 class="modal-title" id="modaltitle">{{ __('common.modal_title') }}</h5>
                </div>
                <div class="modal-body" id="modalbody"></div>
                <div class="modal-footer bg-body-tertiary" id="modalfooter">
                    <button type="button" class="btn btn-primary" id="savefrm">{{ __('common.save') }}</button>
                    <button type="button" class="btn btn-secondary" data-coreui-dismiss="modal">{{ __('common.close') }}</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" tabindex="-1" id="modalsm">
        <div class="modal-dialog modal-sm" id="modaldialogsm">
            <div class="modal-content">
                <button type="button" class="btn-close" data-coreui-dismiss="modal" aria-label="{{ __('common.close') }}"></button>
                <div class="modal-header" id="modalheadersm">
                    <h5 class="modal-title" id="modaltitlesm">{{ __('common.modal_title') }}</h5>
                </div>
                <div class="modal-body" id="modalbodysm"></div>
                <div class="modal-footer bg-body-tertiary" id="modalfootersm">
                    <button type="button" class="btn btn-primary" id="savefrm-sm">{{ __('common.save') }}</button>
                    <button type="button" class="btn btn-secondary" data-coreui-dismiss="modal">{{ __('common.close') }}</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" tabindex="-1" id="modallg">
        <div class="modal-dialog modal-lg" id="modaldialoglg">
            <div class="modal-content">
                <button type="button" class="btn-close" data-coreui-dismiss="modal" aria-label="{{ __('common.close') }}"></button>
                <div class="modal-header" id="modalheaderlg">
                    <h5 class="modal-title" id="modaltitlelg"></h5>
                </div>
                <div class="modal-body" id="modalbodylg"></div>
                <div class="modal-footer bg-body-tertiary" id="modalfooterlg"></div>
            </div>
        </div>
    </div>

    <div class="modal fade" tabindex="-1" id="modalxl">
        <div class="modal-dialog modal-xl" id="modaldialogxl">
            <div class="modal-content">
                <button type="button" class="btn-close" data-coreui-dismiss="modal" aria-label="{{ __('common.close') }}"></button>
                <div class="modal-header" id="modalheaderxl">
                    <h5 class="modal-title" id="modaltitlexl">{{ __('common.modal_title') }}</h5>
                </div>
                <div class="modal-body" id="modalbodyxl"></div>
                <div class="modal-footer bg-body-tertiary" id="modalfooterxl">
                    <button type="button" class="btn btn-primary" id="savefrmxl">{{ __('common.save') }}</button>
                    <button type="button" class="btn btn-secondary" data-coreui-dismiss="modal">{{ __('common.close') }}</button>
                </div>
            </div>
        </div>
    </div>

    <div id="overlaySpinner" class="spinner-overlay">
        <div class="spinner-box">
            <div class="spinner"></div>
            <div class="loading-text" id="overlaySpinnerText">{{ __('common.processing') }}</div>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
