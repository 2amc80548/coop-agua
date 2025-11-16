<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Contraseña</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f0f7fa; margin: 0; padding: 20px; color: #333; }
        .container { max-width: 600px; margin: 40px auto; background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #06b6d4, #14b8a6); padding: 30px; text-align: center; color: white; }
        .header img { width: 80px; height: 80px; border-radius: 50%; margin-bottom: 15px; border: 4px solid rgba(255,255,255,0.3); }
        .header h1 { margin: 0; font-size: 24px; font-weight: 600; }
        .content { padding: 30px; text-align: center; }
        .content p { font-size: 16px; line-height: 1.6; margin: 15px 0; }
        .button { display: inline-block; background: linear-gradient(135deg, #06b6d4, #14b8a6); color: white; font-weight: bold; font-size: 18px; padding: 14px 32px; border-radius: 50px; text-decoration: none; margin: 20px 0; box-shadow: 0 4px 15px rgba(6,182,212,0.3); }
        .expire { background: #fff3cd; color: #856404; padding: 10px; border-radius: 8px; font-size: 14px; margin: 15px 0; display: inline-block; }
        .footer { background: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #6c757d; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="{{ url('/storage/img/AGUA CABEZAS.png') }}" alt="Logo">
            <h1>Cooperativa de Agua Cabezas</h1>
        </div>
        <div class="content">
            <h2>Restablecer Contraseña</h2>
            <p>Hola,</p>
            <p>Recibiste este correo porque solicitaste cambiar tu contraseña.</p>
            <p>Haz clic en el botón para crear una nueva:</p>
            <a href="{{ $url }}" class="button">Cambiar Contraseña</a>
            <div class="expire">
                Este enlace <strong>expira en {{ $expire }} minutos</strong>
            </div>
            <p>Si no solicitaste esto, ignora este mensaje.</p>
        </div>
        <div class="footer">
            © {{ date('Y') }} Cooperativa de Agua Cabezas - Bolivia<br>
            <small>Este es un correo automático, no respondas.</small>
        </div>
    </div>
</body>
</html>