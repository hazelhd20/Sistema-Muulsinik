<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // Grupo: Empresa
            [
                'key' => 'company_name',
                'value' => 'Constructora Muulsinik S.A. de C.V.',
                'group' => 'empresa',
                'type' => 'string',
                'label' => 'Nombre de la empresa',
                'description' => 'Razón social completa de la constructora',
            ],
            [
                'key' => 'company_rfc',
                'value' => '',
                'group' => 'empresa',
                'type' => 'string',
                'label' => 'RFC',
                'description' => 'Registro Federal de Contribuyentes',
            ],
            [
                'key' => 'company_address',
                'value' => '',
                'group' => 'empresa',
                'type' => 'string',
                'label' => 'Dirección fiscal',
                'description' => 'Dirección completa de la empresa',
            ],
            [
                'key' => 'company_phone',
                'value' => '',
                'group' => 'empresa',
                'type' => 'string',
                'label' => 'Teléfono',
                'description' => 'Teléfono principal de contacto',
            ],
            [
                'key' => 'company_email',
                'value' => '',
                'group' => 'empresa',
                'type' => 'string',
                'label' => 'Correo electrónico',
                'description' => 'Email de contacto de la empresa',
            ],
            [
                'key' => 'company_logo',
                'value' => null,
                'group' => 'empresa',
                'type' => 'string',
                'label' => 'Logo',
                'description' => 'Logo de la empresa para documentos',
            ],

            // Grupo: Documentos
            [
                'key' => 'req_prefix',
                'value' => 'REQ-',
                'group' => 'documentos',
                'type' => 'string',
                'label' => 'Prefijo de requisiciones',
                'description' => 'Prefijo para números de requisición',
            ],
            [
                'key' => 'req_next_number',
                'value' => '1',
                'group' => 'documentos',
                'type' => 'number',
                'label' => 'Siguiente número de requisición',
                'description' => 'Número consecutivo para la próxima requisición',
            ],
            [
                'key' => 'currency_symbol',
                'value' => '$',
                'group' => 'documentos',
                'type' => 'string',
                'label' => 'Símbolo monetario',
                'description' => 'Símbolo de moneda para mostrar precios',
            ],
            [
                'key' => 'currency_position',
                'value' => 'before',
                'group' => 'documentos',
                'type' => 'string',
                'label' => 'Posición del símbolo',
                'description' => 'before=Antes, after=Después del monto',
            ],
            [
                'key' => 'decimal_places',
                'value' => '2',
                'group' => 'documentos',
                'type' => 'number',
                'label' => 'Decimales',
                'description' => 'Cantidad de decimales para mostrar',
            ],
            [
                'key' => 'terms_conditions',
                'value' => "Precios sujetos a cambio sin previo aviso.\nVigencia de cotización: 15 días naturales.\nEntrega sujeta a disponibilidad de inventario.",
                'group' => 'documentos',
                'type' => 'string',
                'label' => 'Términos y condiciones',
                'description' => 'Texto que aparece al final de cotizaciones',
            ],

            // Grupo: Integraciones
            [
                'key' => 'gemini_enabled',
                'value' => '0',
                'group' => 'integraciones',
                'type' => 'boolean',
                'label' => 'Habilitar Gemini AI',
                'description' => 'Activar procesamiento de cotizaciones con IA',
            ],
            [
                'key' => 'gemini_api_key',
                'value' => '',
                'group' => 'integraciones',
                'type' => 'string',
                'label' => 'API Key de Gemini',
                'description' => 'Clave de API de Google AI Studio',
            ],
            [
                'key' => 'gemini_model',
                'value' => 'gemini-1.5-flash',
                'group' => 'integraciones',
                'type' => 'string',
                'label' => 'Modelo Gemini',
                'description' => 'Versión del modelo a utilizar',
            ],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
