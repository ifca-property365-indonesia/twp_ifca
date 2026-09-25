{{-- Dimuat ke dalam #modal (header tenant -> View Profile). --}}
<style>
    .avatar-preview { width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 3px solid var(--cui-border-color); background: #fff; }
    .crop-box { width: 100%; max-width: 260px; aspect-ratio: 1 / 1; margin: 0 auto; background: #111; border-radius: .5rem; overflow: hidden; }
    .crop-box img { display: block; max-width: 100%; }
    .crop-box .cropper-view-box, .crop-box .cropper-face { border-radius: 50%; }
    .crop-box .cropper-view-box { outline: 2px solid #fff; box-shadow: 0 0 0 9999px rgba(0, 0, 0, .55); }
    .crop-zoom { width: 100%; max-width: 260px; margin: .75rem auto 0; }
    .crop-zoom input { width: 100%; }
    .profile-changed { box-shadow: 0 0 0 3px rgba(79, 91, 213, .35); }
</style>

<div class="row g-4">
    <div class="col-md-5">
        {{-- Tahap 1: preview foto --}}
        <div class="text-center" id="avatarStage">
            <img id="picturebox" class="avatar-preview mb-3 pictured" src="{{ url('images/default/defaultUser.png') }}" onerror="{{ \App\Support\ProfilePicture::onError() }}" alt="{{ __('tenant/account.profile_picture') }}">
            <div class="mb-2">
                <label for="userfile" class="btn btn-outline-primary btn-sm"><i class="cil-cloud-upload"></i><span>{{ __('tenant/account.change_picture') }}</span></label>
                <input type="file" id="userfile" name="userfile" class="d-none" accept="image/png,image/jpeg,image/gif">
            </div>
            <div class="form-note">{{ __('tenant/account.picture_note') }}</div>
            <div class="form-note text-primary d-none" id="pictureHint"><i class="cil-info"></i> {!! __('tenant/account.picture_hint') !!}</div>
            <input type="hidden" name="image" id="image" value="">
            <input type="hidden" name="labelimage" id="labelimage">
        </div>

        {{-- Tahap 2: geser / zoom foto di dalam frame --}}
        <div class="text-center d-none" id="cropStage">
            <div class="crop-box"><img id="cropImage" src="" alt=""></div>
            <div class="crop-zoom">
                <div class="d-flex align-items-center gap-2">
                    <i class="cil-minus"></i>
                    <input type="range" id="cropZoom" min="1" max="4" step="0.01" value="1" class="form-range">
                    <i class="cil-plus"></i>
                </div>
            </div>
            <div class="form-note mt-2">{{ __('tenant/account.crop_help') }}</div>
            <div class="d-flex justify-content-center gap-2 mt-3">
                <button type="button" class="btn btn-sm btn-secondary" id="btnCropCancel">{{ __('common.cancel') }}</button>
                <button type="button" class="btn btn-sm btn-primary" id="btnCropApply"><i class="cil-check"></i><span>{{ __('tenant/account.use_photo') }}</span></button>
            </div>
        </div>
    </div>

    <div class="col-md-7">
        <ul class="nav nav-underline-border mb-3" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" data-coreui-toggle="tab" href="#tabPersonal" role="tab"><i class="cil-user"></i> {{ __('tenant/account.tab_personal') }}</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-coreui-toggle="tab" href="#tabPassword" role="tab"><i class="cil-lock-locked"></i> {{ __('common.password') }}</a>
            </li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane active" id="tabPersonal" role="tabpanel">
                <form id="frmEditor" method="post" action="" novalidate>
                    @csrf
                    <div class="mb-3">
                        <label for="name" class="form-label">{{ __('common.name') }} <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name">
                    </div>
                    <div class="mb-3">
                        <label for="contact_name" class="form-label">{{ __('tenant/account.contact_name') }}</label>
                        <input type="text" class="form-control" id="contact_name" name="contact_name" maxlength="100">
                        <div class="form-note">{{ __('tenant/account.contact_name_note') }}</div>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">{{ __('common.email') }}</label>
                        <input type="text" class="form-control" id="email" name="email" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="handphone" class="form-label">{{ __('tenant/account.handphone') }} <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="handphone" name="handphone">
                        <div class="form-note">{{ __('tenant/account.phone_format') }}</div>
                    </div>
                    <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                        <button type="button" class="btn btn-secondary" data-coreui-dismiss="modal">{{ __('common.back') }}</button>
                        <button type="button" id="btnSave" class="btn btn-primary"><i class="cil-save"></i><span>{{ __('common.save') }}</span></button>
                    </div>
                </form>
            </div>

            <div class="tab-pane" id="tabPassword" role="tabpanel">
                <form id="frmchangepass" method="post" action="" novalidate>
                    @csrf
                    <div class="mb-3">
                        <label for="password1" class="form-label">{{ __('tenant/account.new_password') }} <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="password1" name="password1" required autocomplete="new-password">
                    </div>
                    <div class="mb-3">
                        <label for="password2" class="form-label">{{ __('tenant/account.confirm_password') }} <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="password2" name="password2" required autocomplete="new-password">
                    </div>
                    <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                        <button type="button" class="btn btn-secondary" data-coreui-dismiss="modal">{{ __('common.back') }}</button>
                        <button type="button" id="btnSavepass" class="btn btn-primary"><i class="cil-lock-locked"></i><span>{{ __('common.change') }}</span></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
(function ($) {
    var validateOpts = {
        ignore: '',
        errorElement: 'div',
        errorClass: 'invalid-feedback',
        highlight: function (el) { $(el).addClass('is-invalid'); },
        unhighlight: function (el) { $(el).removeClass('is-invalid'); },
        errorPlacement: function (error, element) { error.insertAfter(element); }
    };

    $.validator.addMethod('confirmpass', function () {
        return $('#password1').val() === $('#password2').val();
    }, @json(__('tenant/account.password_mismatch')));

    $('#frmEditor').validate($.extend({}, validateOpts, {
        rules: { name: { required: true }, handphone: { required: true } }
    }));

    $('#frmchangepass').validate($.extend({}, validateOpts, {
        rules: { password1: { required: true }, password2: { required: true, confirmpass: true } }
    }));

    // ------------------------------------------------------------------
    // Foto profil: pilih file -> geser/zoom di frame -> "Use Photo" -> preview
    // langsung berubah (belum tersimpan sampai klik Save)
    // ------------------------------------------------------------------
    var cropper = null;
    var baseZoom = 1;

    function showStage(crop) {
        $('#cropStage').toggleClass('d-none', !crop);
        $('#avatarStage').toggleClass('d-none', crop);
    }

    function destroyCropper() {
        if (cropper) { cropper.destroy(); cropper = null; }
        $('#cropImage').attr('src', '');
        $('#userfile').val('');
        showStage(false);
    }

    $('#userfile').on('change', function () {
        var file = this.files[0];
        if (!file) { return; }
        if (!/^image\/(png|jpe?g|gif)$/i.test(file.type)) {
            Swal.fire({ title: @json(__('common.information')), text: @json(__('tenant/account.only_image')), icon: 'warning' });
            this.value = '';
            return;
        }
        if (file.size > 5000000) {
            Swal.fire({ title: @json(__('common.information')), text: @json(__('tenant/account.max_size')), icon: 'warning' });
            this.value = '';
            return;
        }

        var reader = new FileReader();
        reader.onload = function (e) {
            if (cropper) { cropper.destroy(); cropper = null; }
            $('#cropImage').attr('src', e.target.result);
            showStage(true);

            cropper = new Cropper(document.getElementById('cropImage'), {
                aspectRatio: 1,
                viewMode: 3,
                dragMode: 'move',
                autoCropArea: 1,
                cropBoxMovable: false,
                cropBoxResizable: false,
                toggleDragModeOnDblclick: false,
                guides: false,
                center: false,
                highlight: false,
                background: false,
                responsive: true,
                ready: function () {
                    // frame = seluruh kotak; zoom slider relatif terhadap ukuran awal
                    var c = cropper.getContainerData();
                    cropper.setCropBoxData({ left: 0, top: 0, width: c.width, height: c.height });
                    baseZoom = cropper.getCanvasData().width / cropper.getImageData().naturalWidth;
                    $('#cropZoom').val(1);
                },
                zoom: function (e) {
                    var factor = e.detail.ratio / baseZoom;
                    if (factor < 1) { e.preventDefault(); $('#cropZoom').val(1); return; }
                    if (factor > 4) { e.preventDefault(); $('#cropZoom').val(4); return; }
                    $('#cropZoom').val(factor.toFixed(2));
                }
            });
        };
        reader.readAsDataURL(file);
    });

    $('#cropZoom').on('input', function () {
        if (cropper) { cropper.zoomTo(baseZoom * parseFloat(this.value)); }
    });

    $('#btnCropCancel').on('click', destroyCropper);

    $('#btnCropApply').on('click', function () {
        if (!cropper) { return; }
        var $btn = $(this).prop('disabled', true);
        var canvas = cropper.getCroppedCanvas({ width: 400, height: 400, imageSmoothingEnabled: true, imageSmoothingQuality: 'high' });
        var previewUrl = canvas.toDataURL('image/png');

        canvas.toBlob(function (blob) {
            var data = new FormData();
            data.append('userfile', blob, 'profile_' + Date.now() + '.png');

            $.ajax({
                url: "{{ url('tenant/account/savepic') }}",
                type: 'POST',
                data: data,
                processData: false,
                contentType: false,
                dataType: 'json'
            }).done(function (res) {
                if (res.status == 'OK') {
                    // preview langsung berubah, disimpan ke profil saat klik Save
                    $('#picturebox').attr('src', previewUrl).addClass('profile-changed');
                    $('#image').val(res.url);
                    $('#labelimage').val(res.picname);
                    $('#pictureHint').removeClass('d-none');
                    destroyCropper();
                } else {
                    Swal.fire({ title: @json(__('common.error')), text: res.pesan, icon: 'error' });
                }
            }).fail(function (xhr, textStatus, errorThrown) {
                Swal.fire({ title: @json(__('common.error')), text: textStatus + ' : ' + errorThrown, icon: 'error' });
            }).always(function () {
                $btn.prop('disabled', false);
            });
        }, 'image/png');
    });

    // ------------------------------------------------------------------
    // Simpan profil
    // ------------------------------------------------------------------
    $('#btnSave').on('click', function () {
        if (!$('#frmEditor').valid()) { return; }

        // Foto masih di tahap crop (belum "Use Photo") -> ingatkan dulu
        if (cropper) {
            Swal.fire({
                title: @json(__('tenant/account.picture_not_applied')),
                text: @json(__('tenant/account.picture_not_applied_text')),
                icon: 'warning',
                confirmButtonText: @json(__('common.ok'))
            });
            return;
        }

        var dataform = $('#frmEditor').serializeArray();
        dataform.push({ name: 'isFile', value: false }, { name: 'labelimage', value: $('#labelimage').val() });

        $.ajax({
            url: "{{ url('tenant/account/updateprofile') }}",
            type: 'POST',
            data: dataform,
            dataType: 'json'
        }).done(function (res) {
            if (res.status === 'OK') {
                // header ikut berubah tanpa reload
                $('.header .user-name, .dropdown-menu-user .user-card .lead-text').text($('#name').val());
                $('.header .user-role, .dropdown-menu-user .user-card .sub-text:first').text($.trim($('#contact_name').val()));
                $('.header .user-avatar img').attr('src', $('#picturebox').attr('src'));
                $('#picturebox').removeClass('profile-changed');
                $('#pictureHint').addClass('d-none');
            }
            Swal.fire({ title: @json(__('common.information')), text: res.pesan, icon: res.status === 'OK' ? 'success' : 'error' })
                .then(function () { if (res.status === 'OK') { $('#modal').modal('hide'); } });
        }).fail(function (xhr, textStatus, errorThrown) {
            Swal.fire({ title: @json(__('common.error')), text: textStatus + ' : ' + errorThrown, icon: 'error' });
        });
    });

    $('#btnSavepass').on('click', function () {
        if (!$('#frmchangepass').valid()) { return; }

        var dataform = $('#frmchangepass').serializeArray();
        dataform.push({ name: 'email', value: $('#email').val() }, { name: 'password', value: $('#password2').val() });

        $.ajax({
            url: "{{ url('tenant/account/changepass') }}",
            type: 'POST',
            data: dataform,
            dataType: 'json'
        }).done(function (res) {
            Swal.fire({ title: @json(__('common.information')), text: res.pesan, icon: res.status === 'OK' ? 'success' : 'error' })
                .then(function () { if (res.status === 'OK') { $('#modal').modal('hide'); } });
        }).fail(function (xhr, textStatus, errorThrown) {
            Swal.fire({ title: @json(__('common.error')), text: textStatus + ' : ' + errorThrown, icon: 'error' });
        });
    });

    // Bersihkan cropper kalau modal ditutup
    $('#modal').one('hidden.coreui.modal', destroyCropper);

    // Isi data profil
    (function () {
        var id = $('#modal').data('Id');
        if (!id) { return; }
        $.getJSON("{{ url('tenant/account/getbyemail') }}/" + id, function (data) {
            if (!data || !data.length) { return; }
            $('#name').val(data[0].name);
            $('#handphone').val(data[0].handphone);
            $('#contact_name').val(data[0].contact_name || '');
            $('#email').val(data[0].email);
            $('#image').val(data[0].pict);
            $('#labelimage').val(data[0].pict);
            if (data[0].pict) {
                $('.pictured').attr('src', data[0].pict);
            }
        });
    })();
})(jQuery);
</script>
