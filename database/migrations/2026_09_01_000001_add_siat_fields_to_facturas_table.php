<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FASE 1 - Fase 1.A: Campos de Facturación Electrónica SIAT (SIN Bolivia)
 *
 * Esta migración es INCREMENTAL y NO DESTRUCTIVA.
 * Agrega únicamente los campos tributarios requeridos por la normativa
 * RND 10-0021-16 (Facturación Electrónica en Línea) del SIN Bolivia.
 *
 * Todos los campos son nullable para preservar la compatibilidad con
 * las facturas ya existentes en el sistema.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('facturas', function (Blueprint $table) {

            // ---------------------------------------------------------------
            // BLOQUE 1: Identificadores Fiscales Únicos (SIAT)
            // ---------------------------------------------------------------

            // CUF: Código Único de Factura. Hash SHA-256 generado localmente
            // con la clave privada del certificado digital.
            $table->string('cuf', 100)
                ->nullable()
                ->unique()
                ->after('id')
                ->comment('Código Único de Factura SIAT (SHA-256, 64-90 chars)');

            // CUFD: Código Único de Facturación Diaria. Se obtiene del WebService
            // del SIN cada día hábil y tiene vigencia de 24 horas.
            $table->string('cufd', 150)
                ->nullable()
                ->after('cuf')
                ->comment('Código Único de Facturación Diaria, obtenido del SIN');

            // Número de Factura: Correlativo asignado localmente según la
            // dosificación/autorización vigente del SIN.
            $table->unsignedBigInteger('numero_factura')
                ->nullable()
                ->after('cufd')
                ->comment('Número correlativo de factura según dosificación SIN');

            // ---------------------------------------------------------------
            // BLOQUE 2: Datos del Cliente (Receptor del Documento Fiscal)
            // ---------------------------------------------------------------

            // Razón Social o nombre completo del cliente conforme a su documento.
            $table->string('razon_social', 200)
                ->nullable()
                ->after('conexion_id')
                ->comment('Razón social o nombre del comprador para el XML SIAT');

            // Número del Documento de Identidad del cliente.
            $table->string('numero_documento', 20)
                ->nullable()
                ->after('razon_social')
                ->comment('CI, NIT u otro documento del comprador');

            // Complemento del CI (1 o 2 letras, ej: "1A"), requerido cuando
            // hay dos personas con el mismo número de CI.
            $table->string('complemento_documento', 2)
                ->nullable()
                ->after('numero_documento')
                ->comment('Complemento del CI (ej: 1E), solo para CI boliviano');

            // Tipo de documento según catálogo del SIN:
            // 1=CI, 2=Cédula Extranjería, 3=Pasaporte, 4=OtroDoc, 5=NIT
            $table->unsignedTinyInteger('codigo_tipo_documento_identidad')
                ->default(1)
                ->after('complemento_documento')
                ->comment('Catálogo SIN: 1=CI, 2=CEX, 3=Pasaporte, 4=Otro, 5=NIT');

            // ---------------------------------------------------------------
            // BLOQUE 3: Estado de Sincronización con el SIN
            // ---------------------------------------------------------------

            // Estado de la factura ante el SIN. Diferente del estado interno
            // del sistema (impaga/pagado). Esta columna refleja el ciclo de vida
            // fiscal del documento.
            // Valores posibles: PENDIENTE, EN_PROCESO, VALIDADA, RECHAZADA,
            //                   ANULADA_SIAT, OBSERVADA, CONTINGENCIA
            $table->string('estado_siat', 30)
                ->default('PENDIENTE')
                ->after('estado')
                ->comment('Estado ante el SIN: PENDIENTE, VALIDADA, RECHAZADA, etc.');

            // Código de recepción devuelto por el WebService SOAP del SIN
            // después de enviar el paquete de facturas.
            $table->string('codigo_recepcion_siat', 100)
                ->nullable()
                ->after('estado_siat')
                ->comment('Código de recepción devuelto por WebService SIN');

            // ---------------------------------------------------------------
            // BLOQUE 4: Contenido Legal y XML del Documento Fiscal
            // ---------------------------------------------------------------

            // Leyenda legal aleatoria requerida obligatoriamente en el pie de
            // la factura según Art. 12 Resolución SIN.
            $table->text('leyenda_siat')
                ->nullable()
                ->after('codigo_recepcion_siat')
                ->comment('Leyenda oficial del SIN requerida en el documento impreso');

            // Ruta relativa al archivo XML firmado digitalmente con el
            // certificado digital de la cooperativa. Se guarda en storage/facturas_xml/
            $table->string('xml_path', 255)
                ->nullable()
                ->after('leyenda_siat')
                ->comment('Ruta relativa al XML firmado: storage/facturas_xml/AAAA-MM/cuf.xml');

            // ---------------------------------------------------------------
            // BLOQUE 5: Anulación ante el SIN
            // ---------------------------------------------------------------

            // Motivo de anulación según catálogo del SIN:
            // 1=Factura mal emitida, 2=Devolución, 3=otro según catálogo SIAT
            $table->unsignedTinyInteger('motivo_anulacion_siat_id')
                ->nullable()
                ->after('justificacion_modificacion')
                ->comment('Catálogo SIN para motivos de anulación de factura');

            // ---------------------------------------------------------------
            // BLOQUE 6: Índices de Rendimiento (Búsquedas Frecuentes)
            // ---------------------------------------------------------------
            // Estos índices corrigen el Hallazgo 4.2 de la auditoría:
            // columnas sin índice que sufren Full Table Scans en filtros.

            $table->index(['estado', 'periodo'], 'facturas_estado_periodo_index');
            $table->index(['fecha_emision'], 'facturas_fecha_emision_index');
            $table->index(['estado_siat'], 'facturas_estado_siat_index');
        });
    }

    public function down(): void
    {
        Schema::table('facturas', function (Blueprint $table) {
            // Eliminar índices primero para evitar errores de constraint
            $table->dropIndex('facturas_estado_periodo_index');
            $table->dropIndex('facturas_fecha_emision_index');
            $table->dropIndex('facturas_estado_siat_index');

            // Eliminar columnas SIAT
            $table->dropUnique(['cuf']);
            $table->dropColumn([
                'cuf',
                'cufd',
                'numero_factura',
                'razon_social',
                'numero_documento',
                'complemento_documento',
                'codigo_tipo_documento_identidad',
                'estado_siat',
                'codigo_recepcion_siat',
                'leyenda_siat',
                'xml_path',
                'motivo_anulacion_siat_id',
            ]);
        });
    }
};
