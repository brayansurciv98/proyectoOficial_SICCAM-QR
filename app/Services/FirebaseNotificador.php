<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Envía notificaciones push con Firebase Cloud Messaging (API HTTP v1)
 * usando la cuenta de servicio descargada de la consola de Firebase.
 */
class FirebaseNotificador
{
    private const SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    private ?array $credenciales = null;

    public function __construct()
    {
        $ruta = config('services.firebase.credenciales');

        if ($ruta && is_file($ruta)) {
            $this->credenciales = json_decode(file_get_contents($ruta), true);
        }
    }

    public function configurado(): bool
    {
        return isset(
            $this->credenciales['project_id'],
            $this->credenciales['client_email'],
            $this->credenciales['private_key']
        );
    }

    /**
     * Devuelve: 'enviado', 'invalido' (el celular ya no existe en Firebase),
     * 'error' (sin internet o falla de Firebase) o 'sin_configurar'.
     */
    public function enviar(string $fcmToken, string $titulo, string $mensaje, array $datos = []): string
    {
        if (!$this->configurado()) {
            return 'sin_configurar';
        }

        // Solo "data": la app arma la notificación con el logo del colegio,
        // tanto con la app abierta como cerrada
        $contenido = [
            'token'   => $fcmToken,
            'data'    => array_map(fn ($valor) => (string) $valor, array_merge($datos, [
                'titulo'  => $titulo,
                'mensaje' => $mensaje,
            ])),
            'android' => ['priority' => 'high'],
        ];

        try {
            $respuesta = Http::withToken($this->accessToken())
                ->timeout(10)
                ->post('https://fcm.googleapis.com/v1/projects/' . $this->credenciales['project_id'] . '/messages:send', [
                    'message' => $contenido,
                ]);
        } catch (\Throwable $e) {
            Log::warning('FCM sin conexión: ' . $e->getMessage());
            return 'error';
        }

        if ($respuesta->successful()) {
            return 'enviado';
        }

        if ($respuesta->status() === 404 || $respuesta->json('error.status') === 'UNREGISTERED') {
            return 'invalido';
        }

        Log::warning('FCM respondió ' . $respuesta->status() . ': ' . $respuesta->body());
        return 'error';
    }

    private function accessToken(): string
    {
        return Cache::remember('fcm_access_token', now()->addMinutes(50), function () {
            $ahora = time();

            $cabecera = $this->base64url(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
            $datos = $this->base64url(json_encode([
                'iss'   => $this->credenciales['client_email'],
                'scope' => self::SCOPE,
                'aud'   => self::TOKEN_URL,
                'iat'   => $ahora,
                'exp'   => $ahora + 3600,
            ]));

            openssl_sign($cabecera . '.' . $datos, $firma, $this->credenciales['private_key'], OPENSSL_ALGO_SHA256);
            $jwt = $cabecera . '.' . $datos . '.' . $this->base64url($firma);

            return Http::asForm()->timeout(10)->post(self::TOKEN_URL, [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $jwt,
            ])->throw()->json('access_token');
        });
    }

    private function base64url(string $texto): string
    {
        return rtrim(strtr(base64_encode($texto), '+/', '-_'), '=');
    }
}