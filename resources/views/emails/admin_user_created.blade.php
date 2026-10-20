{{-- Email user administrator baru (App\Mail\AdminUserCreated) --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('admin/users.mail_subject', ['app' => config('app.name')]) }}</title>
</head>
<body style="margin:0; padding:24px; background:#f3f4f7; font-family:Arial, Helvetica, sans-serif; color:#1f2937;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px; margin:0 auto; background:#ffffff; border-radius:8px; overflow:hidden;">
        <tr>
            <td style="background:#0a0f22; padding:18px 24px;">
                <img src="{{ url('images/icons/icon-192.png') }}" alt="IFCA" width="40" height="40" style="vertical-align:middle; border-radius:6px;">
                <span style="color:#ffffff; font-size:16px; font-weight:bold; vertical-align:middle; margin-left:10px;">{{ config('app.name') }} — Admin Portal</span>
            </td>
        </tr>
        <tr>
            <td style="padding:24px;">
                <p style="margin:0 0 12px;">{{ __('admin/users.mail_hello', ['name' => $name]) }}</p>
                <p style="margin:0 0 16px;">{{ __('admin/users.mail_intro') }}</p>
                <table role="presentation" cellpadding="0" cellspacing="0" style="width:100%; border:1px solid #e3e6ef; border-radius:6px; font-size:14px;">
                    <tr><td style="padding:8px 12px; color:#6b7280; width:40%;">{{ __('admin/users.email') }}</td><td style="padding:8px 12px; font-weight:bold;">{{ $email }}</td></tr>
                    <tr><td style="padding:8px 12px; color:#6b7280; border-top:1px solid #e3e6ef;">{{ __('admin/users.group') }}</td><td style="padding:8px 12px; border-top:1px solid #e3e6ef;">{{ $group }}</td></tr>
                    <tr>
                        <td style="padding:8px 12px; color:#6b7280; border-top:1px solid #e3e6ef;">{{ __('admin/users.password') }}</td>
                        <td style="padding:8px 12px; border-top:1px solid #e3e6ef;">
                            @if ($password !== null)
                                <code style="font-size:14px;">{{ $password }}</code>
                            @else
                                {{ __('admin/users.mail_password_given') }}
                            @endif
                        </td>
                    </tr>
                </table>
                <p style="margin:20px 0;">
                    <a href="{{ $loginUrl }}" style="display:inline-block; background:#4f5bd5; color:#ffffff; text-decoration:none; padding:10px 18px; border-radius:6px; font-weight:bold;">{{ __('admin/users.mail_login') }}</a>
                </p>
                <p style="margin:0; font-size:12px; color:#6b7280;">{{ __('admin/users.mail_footer') }}</p>
            </td>
        </tr>
    </table>
</body>
</html>
