<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - POS Geprek LFC</title>
</head>
<body style="font-family: sans-serif; background-color: #f7fafc; padding: 40px 20px;">
    <div style="max-width: 400px; margin: 0 auto; background: #ffffff; padding: 24px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <h2 style="margin-top: 0; text-align: center;">POS Geprek LFC</h2>
        <p style="text-align: center; color: #4a5568; margin-bottom: 20px;">Silakan masuk ke akun Anda</p>

        @if ($errors->any())
            <div style="background-color: #fed7d7; color: #9b2c2c; padding: 12px; border-radius: 4px; margin-bottom: 16px; font-size: 14px;">
                <ul style="margin: 0; padding-left: 20px;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('auth.login') }}" method="POST">
            @csrf
            <div style="margin-bottom: 16px;">
                <label for="username" style="display: block; font-size: 14px; font-weight: bold; margin-bottom: 6px;">Username</label>
                <input type="text" id="username" name="username" value="{{ old('username') }}" required autofocus style="width: 100%; box-sizing: border-box; padding: 10px; border: 1px solid #cbd5e0; border-radius: 4px;">
            </div>

            <div style="margin-bottom: 20px;">
                <label for="password" style="display: block; font-size: 14px; font-weight: bold; margin-bottom: 6px;">Password</label>
                <input type="password" id="password" name="password" required style="width: 100%; box-sizing: border-box; padding: 10px; border: 1px solid #cbd5e0; border-radius: 4px;">
            </div>

            <button type="submit" style="width: 100%; background-color: #e53e3e; color: white; border: none; padding: 12px; font-size: 16px; font-weight: bold; border-radius: 4px; cursor: pointer;">
                Masuk
            </button>
        </form>
    </div>
</body>
</html>
