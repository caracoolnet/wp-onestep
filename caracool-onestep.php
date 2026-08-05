<?php
/**
 * Plugin Name: Caracool OneStep
 * Plugin URI:  https://caracool.net
 * Description: Desactiva los comentarios en todo el sitio, activa un modo de mantenimiento con página personalizable y ayuda a mejorar el rendimiento del sitio. Plugin ligero de Caracool, sin dependencias externas.
 * Version:     1.1.0
 * Author:      Caracool
 * Author URI:  https://caracool.net
 * Text Domain: caracool-onestep
 */

// ── Bloquear acceso directo al archivo ────────────────────────
if ( ! defined( 'ABSPATH' ) ) exit;

define( 'CARACOOL_ONESTEP_VERSION', '1.1.0' );
define( 'CARACOOL_ONESTEP_PATH',    plugin_dir_path( __FILE__ ) );
define( 'CARACOOL_ONESTEP_URL',     plugin_dir_url( __FILE__ ) );
define( 'CARACOOL_ONESTEP_SLUG',    'caracool-onestep' );

// ─────────────────────────────────────────────────────────────
// CLASE PRINCIPAL
// ─────────────────────────────────────────────────────────────
class Caracool_OneStep {

    const OPTION_KEY = 'caracool_onestep_settings';

    /** Roles WP ordenados de menor a mayor peso, para el bypass de mantenimiento. */
    const ROLE_ORDER = [ 'subscriber', 'contributor', 'author', 'editor', 'administrator' ];

    /**
     * Logotipo de Caracool, incrustado como SVG inline (sin fichero de imagen
     * aparte) para que la página de ajustes lleve la marca de la agencia.
     */
    private static function logo_svg() {
        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 325.72 100.01" style="width:130px;height:auto;display:block;fill:#1a1a1a;" aria-label="Caracool" role="img"><path d="M39.23,9.44c0,4.65-.8,7.71-2.13,9.98-1.6-1.2-3.99-2.26-6.92-2.26-7.58,0-14.5,6.92-14.5,35.91,0,23.14,2.93,30.06,10.37,30.06,4.12,0,7.85-.67,10.37-2,1.06,2.26,2,5.59,2,10.37,0,3.86-6.12,8.25-14.1,8.25-15.16,0-24.34-3.99-24.34-45.49C0,10.64,14.63,2.66,26.6,2.66c11.31,0,12.64,3.86,12.64,6.78"/><path d="M41.9,36.84c0-1.6.27-3.46,1.46-4.39,1.6-1.33,9.58-3.19,23.41-3.19,9.04,0,13.57,4.26,13.57,16.23v6.92c0,22.74-.67,43.09-.67,43.09-4.52,2.66-11.17,4.26-19.15,4.26-9.84,0-18.89-.66-18.89-21.41,0-18.22,7.45-21.94,14.76-21.94,2.53,0,6.65.4,9.04,1.99v-9.84c0-3.46-1.46-4.92-4.39-4.92-5.05,0-12.77,1.2-17.16,2.79-1.86-3.06-2-8.51-2-9.58M65.44,68.36c-.93-.93-2.39-1.06-3.46-1.06-2.93,0-4.65,2.13-4.65,10.64s.67,9.58,3.99,9.58c.93,0,3.06-.27,3.86-1.33,0,0,.27-8.51.27-17.82"/><path d="M88.31,35.24c6.12-4.26,11.04-5.98,18.49-5.98s8.91,1.33,8.91,5.98c0,2.39-.27,5.98-1.46,9.31-1.86-.93-3.59-1.06-4.92-1.06-1.6,0-4.12.8-5.72,3.06l-.13,48.54q0,2.66-15.16,2.66v-62.51Z"/><path d="M119.96,36.84c0-1.6.27-3.46,1.46-4.39,1.6-1.33,9.58-3.19,23.41-3.19,9.04,0,13.57,4.26,13.57,16.23v6.92c0,22.74-.67,43.09-.67,43.09-4.52,2.66-11.17,4.26-19.15,4.26-9.84,0-18.88-.66-18.88-21.41,0-18.22,7.45-21.94,14.76-21.94,2.53,0,6.65.4,9.04,1.99v-9.84c0-3.46-1.46-4.92-4.39-4.92-5.05,0-12.77,1.2-17.16,2.79-1.86-3.06-2-8.51-2-9.58M143.51,68.36c-.93-.93-2.39-1.06-3.46-1.06-2.93,0-4.65,2.13-4.65,10.64s.66,9.58,3.99,9.58c.93,0,3.06-.27,3.86-1.33,0,0,.27-8.51.27-17.82"/><path d="M202.69,36.97c-2.53-1.86-4.65-3.59-9.84-3.59-9.44,0-20.22,3.99-20.22,33.12,0,26.6,6.92,29.39,17.82,29.39,5.19,0,9.44-2.39,12.5-4.92.53.93.8,2.13.8,3.06,0,1.73-6.38,5.99-13.7,5.99-12.9,0-21.81-2.53-21.81-34.05,0-33.12,13.43-36.71,24.21-36.71,5.59,0,11.17,2.79,11.17,4.26,0,1.06-.27,2.53-.93,3.46"/><path d="M234.21,29.26c13.43,0,20.75,8.11,20.75,35.38,0,23.27-7.98,35.11-21.28,35.11s-21.41-4.92-21.41-34.98c0-24.87,8.25-35.51,21.94-35.51M233.01,95.62c10.64,0,17.42-10.37,17.42-31.39,0-24.47-6.12-30.85-16.23-30.85s-17.55,8.51-17.55,32.18,5.99,30.06,16.36,30.06"/><path d="M286.88,29.26c13.43,0,20.75,8.11,20.75,35.38,0,23.27-7.98,35.11-21.28,35.11s-21.41-4.92-21.41-34.98c0-24.87,8.25-35.51,21.94-35.51M285.68,95.62c10.64,0,17.42-10.37,17.42-31.39,0-24.47-6.12-30.85-16.23-30.85s-17.55,8.51-17.55,32.18,5.99,30.06,16.36,30.06"/><path d="M325.71,96.29c0,1.46.27,1.46-4.39,1.46V2.39c0-2.39,1.07-2.39,4.39-2.39v96.29Z"/></svg>';
    }

    /** Nombre de la opción donde se guarda lo capturado por el escaneo de scripts/estilos. */
    const SCAN_OPTION_KEY = 'caracool_onestep_scan_data';

    /** Nombre de la opción donde se cachea el último resultado de PageSpeed. */
    const PAGESPEED_OPTION_KEY = 'caracool_onestep_pagespeed_cache';

    /** Reglas de rendimiento activas (handle => acción), cargadas en perf_bootstrap() para los filtros de tag. */
    private $perf_rules = [];

    /** Evita capturar el escaneo dos veces (wp_footer + shutdown) en la misma petición. */
    private $scan_captured = false;

    // ── Constructor ───────────────────────────────────────────
    public function __construct() {
        add_action( 'admin_menu',            [ $this, 'add_menu' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );
        add_action( 'admin_notices',         [ $this, 'admin_notices' ] );
        add_action( 'admin_post_caracool_onestep_save', [ $this, 'save_settings' ] );
        add_action( 'admin_post_caracool_onestep_run_pagespeed', [ $this, 'perf_run_pagespeed' ] );
        add_action( 'admin_post_caracool_onestep_run_scan', [ $this, 'perf_run_scan' ] );

        $settings = $this->get_settings();

        if ( ! empty( $settings['comments_disabled'] ) ) {
            $this->comments_bootstrap();
        }

        // Prioridad 0: que se ejecute antes que cualquier otra cosa enganchada
        // a template_redirect (redirecciones canónicas, SEO, cache, etc.).
        add_action( 'template_redirect', [ $this, 'maintenance_maybe_render' ], 0 );

        $this->perf_bootstrap( $settings );
    }

    // ── Ajustes por defecto ───────────────────────────────────
    public function get_settings() {
        return wp_parse_args( get_option( self::OPTION_KEY, [] ), [
            // Comentarios
            'comments_disabled'           => false,

            // Mantenimiento
            'maintenance_enabled'         => false,
            'maintenance_http_status'     => 503,
            'maintenance_redirect_url'    => '',
            'maintenance_title'           => '',
            'maintenance_heading'         => '',
            'maintenance_message'         => '',
            'maintenance_logo_url'        => '',
            'maintenance_bg_color'        => '#14141a',
            'maintenance_text_color'      => '#ffffff',
            'maintenance_use_custom_html' => false,
            'maintenance_custom_html'     => '',
            'maintenance_bypass_role'     => 'administrator',
            'maintenance_ip_whitelist'    => [],
            'maintenance_show_credit'     => true,

            // Rendimiento — quick wins
            'perf_disable_emojis'           => false,
            'perf_remove_jquery_migrate'    => false,
            'perf_remove_block_library_css' => false,
            'perf_disable_embeds'           => false,
            'perf_remove_dashicons_front'   => false,

            // Rendimiento — gestor de scripts/estilos (handle => acción)
            'perf_rules' => [],

            // Rendimiento — integración opcional con Google PageSpeed Insights
            'perf_pagespeed_api_key'  => '',
            'perf_pagespeed_strategy' => 'mobile',
        ] );
    }

    // ── Guardar ajustes ───────────────────────────────────────
    // Hay varios <form> en la página de ajustes (uno por pestaña, para no anidar
    // formularios en la de Rendimiento, que además tiene sus propios botones de
    // "Escanear" y "Analizar con PageSpeed"). Todos apuntan a esta misma acción,
    // así que partimos siempre de los ajustes actuales y solo sobrescribimos el
    // subconjunto de claves que corresponde al formulario enviado (marcado con
    // el campo oculto "co_form"), para no borrar sin querer las demás pestañas.
    public function save_settings() {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'No autorizado.' );
        check_admin_referer( 'caracool_onestep_save' );

        $current = $this->get_settings();
        $form    = sanitize_key( wp_unslash( $_POST['co_form'] ?? 'main' ) );

        if ( 'perf' === $form ) {
            $overrides = [
                'perf_disable_emojis'           => ! empty( $_POST['perf_disable_emojis'] ),
                'perf_remove_jquery_migrate'    => ! empty( $_POST['perf_remove_jquery_migrate'] ),
                'perf_remove_block_library_css' => ! empty( $_POST['perf_remove_block_library_css'] ),
                'perf_disable_embeds'           => ! empty( $_POST['perf_disable_embeds'] ),
                'perf_remove_dashicons_front'   => ! empty( $_POST['perf_remove_dashicons_front'] ),
                'perf_rules'                    => $this->perf_parse_rules_from_post(),
            ];
        } else {
            $ip_raw  = sanitize_textarea_field( wp_unslash( $_POST['maintenance_ip_whitelist'] ?? '' ) );
            $ip_list = array_filter( array_map( 'trim', preg_split( '/[\r\n,]+/', $ip_raw ) ) );
            $ip_list = array_values( array_unique( array_filter( $ip_list, function ( $ip ) {
                return (bool) filter_var( $ip, FILTER_VALIDATE_IP );
            } ) ) );

            $status = absint( $_POST['maintenance_http_status'] ?? 503 );
            if ( ! in_array( $status, [ 200, 503, 404, 301 ], true ) ) $status = 503;

            $bypass_role = sanitize_text_field( $_POST['maintenance_bypass_role'] ?? '' );
            if ( $bypass_role && ! in_array( $bypass_role, self::ROLE_ORDER, true ) ) $bypass_role = '';

            $overrides = [
                'comments_disabled'           => ! empty( $_POST['comments_disabled'] ),

                'maintenance_enabled'         => ! empty( $_POST['maintenance_enabled'] ),
                'maintenance_http_status'     => $status,
                'maintenance_redirect_url'    => esc_url_raw( $_POST['maintenance_redirect_url'] ?? '' ),
                'maintenance_title'           => sanitize_text_field( $_POST['maintenance_title'] ?? '' ),
                'maintenance_heading'         => sanitize_text_field( $_POST['maintenance_heading'] ?? '' ),
                'maintenance_message'         => sanitize_textarea_field( $_POST['maintenance_message'] ?? '' ),
                'maintenance_logo_url'        => esc_url_raw( $_POST['maintenance_logo_url'] ?? '' ),
                'maintenance_bg_color'        => sanitize_hex_color( $_POST['maintenance_bg_color'] ?? '' ) ?: '#14141a',
                'maintenance_text_color'      => sanitize_hex_color( $_POST['maintenance_text_color'] ?? '' ) ?: '#ffffff',
                'maintenance_use_custom_html' => ! empty( $_POST['maintenance_use_custom_html'] ),
                'maintenance_custom_html'     => wp_kses_post( $_POST['maintenance_custom_html'] ?? '' ),
                'maintenance_bypass_role'     => $bypass_role,
                'maintenance_ip_whitelist'    => $ip_list,
                'maintenance_show_credit'     => ! empty( $_POST['maintenance_show_credit'] ),
            ];
        }

        update_option( self::OPTION_KEY, array_merge( $current, $overrides ) );

        wp_safe_redirect( admin_url( 'admin.php?page=' . CARACOOL_ONESTEP_SLUG . '&saved=1' ) );
        exit;
    }

    /**
     * Lee los selects "perf_rule[tipo:handle]" del formulario de Rendimiento
     * y los convierte en el array de reglas que se guarda en 'perf_rules'.
     */
    private function perf_parse_rules_from_post() {
        $rules = [];
        $posted = (array) ( $_POST['perf_rule'] ?? [] );

        foreach ( $posted as $key => $action ) {
            $key    = sanitize_text_field( wp_unslash( $key ) );
            $action = sanitize_key( wp_unslash( $action ) );

            if ( 'none' === $action || '' === $action ) continue;
            if ( ! in_array( $action, [ 'defer', 'async', 'disable' ], true ) ) continue;

            $parts = explode( ':', $key, 2 );
            if ( count( $parts ) !== 2 ) continue;

            [ $type, $handle ] = $parts;
            $type   = in_array( $type, [ 'script', 'style' ], true ) ? $type : '';
            $handle = sanitize_key( $handle );

            // Las hojas de estilo no tienen "async": si llega por manipulación
            // manual del formulario, lo tratamos como "defer".
            if ( 'style' === $type && 'async' === $action ) $action = 'defer';

            if ( $type && $handle ) {
                $rules[] = [ 'type' => $type, 'handle' => $handle, 'action' => $action ];
            }
        }

        return $rules;
    }

    // ── Avisos admin ──────────────────────────────────────────
    public function admin_notices() {
        $screen = get_current_screen();
        if ( ! $screen || $screen->id !== 'toplevel_page_' . CARACOOL_ONESTEP_SLUG ) return;

        if ( isset( $_GET['saved'] ) ) {
            echo '<div class="notice notice-success is-dismissible"><p>✅ Configuración guardada.</p></div>';
        }
        if ( isset( $_GET['scanned'] ) ) {
            echo '<div class="notice notice-success is-dismissible"><p>🔍 Escaneo completado — revisa la tabla de scripts y estilos en la pestaña Rendimiento.</p></div>';
        }
        if ( isset( $_GET['pagespeed'] ) ) {
            $pg = get_option( self::PAGESPEED_OPTION_KEY, [] );
            if ( ! empty( $pg['error'] ) ) {
                echo '<div class="notice notice-error is-dismissible"><p>⚠️ PageSpeed: ' . esc_html( $pg['error'] ) . '</p></div>';
            } else {
                echo '<div class="notice notice-success is-dismissible"><p>⚡ Análisis de PageSpeed completado.</p></div>';
            }
        }

        $s = $this->get_settings();
        if ( ! empty( $s['maintenance_enabled'] ) ) {
            echo '<div class="notice notice-warning"><p>🚧 El <strong>modo mantenimiento</strong> está activo: solo lo saltan los usuarios/IPs autorizados en la pestaña "Mantenimiento".</p></div>';
        }
    }

    // ── Menú admin ────────────────────────────────────────────
    public function add_menu() {
        add_menu_page(
            'Caracool OneStep',
            'Caracool OneStep',
            'manage_options',
            CARACOOL_ONESTEP_SLUG,
            [ $this, 'render_settings_page' ],
            'dashicons-admin-generic',
            80
        );
    }

    public function enqueue_admin_scripts( $hook ) {
        if ( $hook !== 'toplevel_page_' . CARACOOL_ONESTEP_SLUG ) return;
        wp_enqueue_media();
        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_script( 'wp-color-picker' );
    }

    // ── Página de ajustes ─────────────────────────────────────
    public function render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) return;

        $s         = $this->get_settings();
        $scan      = get_option( self::SCAN_OPTION_KEY, [] );
        $pagespeed = get_option( self::PAGESPEED_OPTION_KEY, [] );

        // Une lo detectado por el último escaneo con las reglas ya guardadas
        // (para que una regla siga visible aunque ese script no cargara en el
        // escaneo más reciente, p.ej. porque solo se usa en ciertas páginas).
        $detected     = [];
        $rules_by_key = [];

        foreach ( (array) ( $scan['scripts'] ?? [] ) as $row ) {
            $detected[ 'script:' . $row['handle'] ] = [ 'type' => 'script', 'handle' => $row['handle'], 'src' => $row['src'] ];
        }
        foreach ( (array) ( $scan['styles'] ?? [] ) as $row ) {
            $detected[ 'style:' . $row['handle'] ] = [ 'type' => 'style', 'handle' => $row['handle'], 'src' => $row['src'] ];
        }
        foreach ( (array) $s['perf_rules'] as $rule ) {
            $key = $rule['type'] . ':' . $rule['handle'];
            $rules_by_key[ $key ] = $rule['action'];
            if ( ! isset( $detected[ $key ] ) ) {
                $detected[ $key ] = [ 'type' => $rule['type'], 'handle' => $rule['handle'], 'src' => '' ];
            }
        }
        ksort( $detected );
        ?>
        <div class="wrap" style="max-width:1100px;">
            <div style="display:flex;align-items:center;gap:16px;margin-top:10px;">
                <?php echo self::logo_svg(); // phpcs:ignore -- markup fijo, sin datos de usuario ?>
                <h1 style="margin:0;padding:0;line-height:1.3;">
                    OneStep <span style="font-size:12px;color:#888;font-weight:normal;">v<?php echo esc_html( CARACOOL_ONESTEP_VERSION ); ?></span>
                </h1>
            </div>
            <p class="description" style="margin-top:14px;">Comentarios, modo mantenimiento y ayudas de rendimiento en un único plugin ligero, sin dependencias externas.</p>

            <h2 class="nav-tab-wrapper" style="margin-top:16px;">
                <a href="#tab-comentarios" class="nav-tab nav-tab-active" data-co-tab="tab-comentarios">💬 Comentarios</a>
                <a href="#tab-mantenimiento" class="nav-tab" data-co-tab="tab-mantenimiento">🚧 Mantenimiento</a>
                <a href="#tab-rendimiento" class="nav-tab" data-co-tab="tab-rendimiento">⚡ Rendimiento</a>
            </h2>

            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <?php wp_nonce_field( 'caracool_onestep_save' ); ?>
                <input type="hidden" name="action" value="caracool_onestep_save">
                <input type="hidden" name="co_form" value="main">

                <!-- ── TAB: COMENTARIOS ── -->
                <div id="tab-comentarios" class="co-tab-panel">
                    <div style="background:#fff;border:1px solid #c3c4c7;border-radius:6px;padding:20px 24px;margin-top:16px;">
                        <h2 style="margin-top:0;border-bottom:1px solid #f0f0f1;padding-bottom:10px;font-size:14px;">💬 Comentarios</h2>
                        <label style="font-size:14px;">
                            <input type="checkbox" name="comments_disabled" value="1" <?php checked( $s['comments_disabled'] ); ?>>
                            Desactivar comentarios en todo WordPress, incluido a través de REST y XML-RPC
                        </label>
                        <p class="description" style="margin-top:10px;">
                            Al activarlo se cierran los comentarios y pingbacks en todos los tipos de contenido,
                            se ocultan del menú de administración, del panel de Ajustes → Comentarios, de la barra
                            de admin y del escritorio, se bloquea por completo el endpoint REST (<code>/wp/v2/comments</code>)
                            y el método XML-RPC (<code>wp.newComment</code>), y se elimina el formulario de comentarios
                            de las plantillas del tema.
                        </p>
                    </div>
                </div>

                <!-- ── TAB: MANTENIMIENTO ── -->
                <div id="tab-mantenimiento" class="co-tab-panel" style="display:none;">

                    <div style="background:#fff;border:1px solid #c3c4c7;border-radius:6px;padding:20px 24px;margin-top:16px;">
                        <h2 style="margin-top:0;border-bottom:1px solid #f0f0f1;padding-bottom:10px;font-size:14px;">🚧 Activación</h2>
                        <label style="font-size:14px;">
                            <input type="checkbox" name="maintenance_enabled" value="1" <?php checked( $s['maintenance_enabled'] ); ?>>
                            Poner toda la web en construcción — solo el administrador logueado ve el sitio real, el resto ve esta página
                        </label>
                        <p class="description" style="margin-top:6px;">wp-admin sigue siendo siempre accesible, para poder desactivarlo desde aquí. El "quién puede ver el sitio" se ajusta más abajo.</p>

                        <table class="form-table">
                            <tr>
                                <th style="width:200px;"><label for="maintenance_http_status">Código HTTP</label></th>
                                <td>
                                    <select name="maintenance_http_status" id="maintenance_http_status">
                                        <option value="503" <?php selected( $s['maintenance_http_status'], 503 ); ?>>503 — Servicio no disponible (recomendado, SEO-friendly)</option>
                                        <option value="200" <?php selected( $s['maintenance_http_status'], 200 ); ?>>200 — OK</option>
                                        <option value="404" <?php selected( $s['maintenance_http_status'], 404 ); ?>>404 — No encontrado</option>
                                        <option value="301" <?php selected( $s['maintenance_http_status'], 301 ); ?>>301 — Redirección permanente</option>
                                    </select>
                                    <p class="description">503 le dice a Google que vuelva más tarde sin des-indexar el sitio.</p>
                                </td>
                            </tr>
                            <tr id="co-redirect-row" style="<?php echo $s['maintenance_http_status'] == 301 ? '' : 'display:none;'; ?>">
                                <th><label for="maintenance_redirect_url">URL de redirección</label></th>
                                <td><input type="url" name="maintenance_redirect_url" id="maintenance_redirect_url" value="<?php echo esc_attr( $s['maintenance_redirect_url'] ); ?>" class="regular-text" placeholder="https://..."></td>
                            </tr>
                        </table>
                    </div>

                    <div style="background:#fff;border:1px solid #c3c4c7;border-radius:6px;padding:20px 24px;margin-top:16px;">
                        <h2 style="margin-top:0;border-bottom:1px solid #f0f0f1;padding-bottom:10px;font-size:14px;">🔓 Quién puede ver el sitio</h2>
                        <table class="form-table">
                            <tr>
                                <th style="width:200px;"><label for="maintenance_bypass_role">Rol mínimo</label></th>
                                <td>
                                    <select name="maintenance_bypass_role" id="maintenance_bypass_role">
                                        <option value="administrator" <?php selected( $s['maintenance_bypass_role'], 'administrator' ); ?>>Solo administrador (recomendado)</option>
                                        <option value="editor" <?php selected( $s['maintenance_bypass_role'], 'editor' ); ?>>Editor o superior</option>
                                        <option value="author" <?php selected( $s['maintenance_bypass_role'], 'author' ); ?>>Autor o superior</option>
                                        <option value="contributor" <?php selected( $s['maintenance_bypass_role'], 'contributor' ); ?>>Colaborador o superior</option>
                                        <option value="subscriber" <?php selected( $s['maintenance_bypass_role'], 'subscriber' ); ?>>Suscriptor o superior</option>
                                        <option value="" <?php selected( $s['maintenance_bypass_role'], '' ); ?>>Cualquier usuario que haya iniciado sesión</option>
                                    </select>
                                    <p class="description">Por defecto solo el administrador logueado ve la web real; el resto de visitantes (incluidos usuarios sin sesión) ven la página de mantenimiento.</p>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="maintenance_ip_whitelist">IPs permitidas <span style="font-weight:normal;color:#888;">(opcional)</span></label></th>
                                <td>
                                    <textarea name="maintenance_ip_whitelist" id="maintenance_ip_whitelist" rows="3" class="large-text" placeholder="Una IP por línea"><?php echo esc_textarea( implode( "\n", (array) $s['maintenance_ip_whitelist'] ) ); ?></textarea>
                                    <p class="description">Estas IPs ven el sitio normal aunque no hayan iniciado sesión (ej. la oficina de Caracool). Déjalo vacío si no lo necesitas.</p>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <div style="background:#fff;border:1px solid #c3c4c7;border-radius:6px;padding:20px 24px;margin-top:16px;">
                        <h2 style="margin-top:0;border-bottom:1px solid #f0f0f1;padding-bottom:10px;font-size:14px;">🎨 Página de mantenimiento</h2>

                        <label>
                            <input type="checkbox" name="maintenance_use_custom_html" id="maintenance_use_custom_html" value="1" <?php checked( $s['maintenance_use_custom_html'] ); ?>>
                            Usar HTML personalizado en lugar de la plantilla por defecto
                        </label>

                        <div id="co-default-page-fields" style="<?php echo $s['maintenance_use_custom_html'] ? 'display:none;' : ''; ?>margin-top:14px;">
                            <table class="form-table" style="margin-top:0;">
                                <tr>
                                    <th style="width:200px;"><label for="maintenance_title">Título de la pestaña</label></th>
                                    <td><input type="text" name="maintenance_title" id="maintenance_title" value="<?php echo esc_attr( $s['maintenance_title'] ); ?>" class="regular-text" placeholder="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>"></td>
                                </tr>
                                <tr>
                                    <th><label for="maintenance_heading">Titular</label></th>
                                    <td><input type="text" name="maintenance_heading" id="maintenance_heading" value="<?php echo esc_attr( $s['maintenance_heading'] ); ?>" class="regular-text" placeholder="Estamos preparando algo nuevo"></td>
                                </tr>
                                <tr>
                                    <th><label for="maintenance_message">Mensaje</label></th>
                                    <td><textarea name="maintenance_message" id="maintenance_message" rows="3" class="large-text" placeholder="Volvemos enseguida. Gracias por tu paciencia."><?php echo esc_textarea( $s['maintenance_message'] ); ?></textarea></td>
                                </tr>
                                <tr>
                                    <th><label for="maintenance_logo_id">Logo</label></th>
                                    <td>
                                        <input type="hidden" name="maintenance_logo_url" id="maintenance_logo_url" value="<?php echo esc_attr( $s['maintenance_logo_url'] ); ?>">
                                        <button type="button" class="button button-secondary" id="co-select-logo"><?php echo $s['maintenance_logo_url'] ? '🔄 Cambiar logo' : '📁 Seleccionar logo'; ?></button>
                                        <img id="co-logo-preview" src="<?php echo esc_url( $s['maintenance_logo_url'] ); ?>" style="max-height:48px;vertical-align:middle;margin-left:10px;<?php echo $s['maintenance_logo_url'] ? '' : 'display:none;'; ?>">
                                    </td>
                                </tr>
                                <tr>
                                    <th><label for="maintenance_bg_color">Color de fondo</label></th>
                                    <td><input type="text" name="maintenance_bg_color" id="maintenance_bg_color" value="<?php echo esc_attr( $s['maintenance_bg_color'] ); ?>" class="co-color-picker"></td>
                                </tr>
                                <tr>
                                    <th><label for="maintenance_text_color">Color de texto</label></th>
                                    <td><input type="text" name="maintenance_text_color" id="maintenance_text_color" value="<?php echo esc_attr( $s['maintenance_text_color'] ); ?>" class="co-color-picker"></td>
                                </tr>
                                <tr>
                                    <th><label for="maintenance_show_credit">Crédito Caracool</label></th>
                                    <td>
                                        <label><input type="checkbox" name="maintenance_show_credit" value="1" <?php checked( $s['maintenance_show_credit'] ); ?>> Mostrar "Hecho con ❤️ por Caracool" al pie de la página</label>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <div id="co-custom-html-field" style="<?php echo $s['maintenance_use_custom_html'] ? '' : 'display:none;'; ?>margin-top:14px;">
                            <textarea name="maintenance_custom_html" id="maintenance_custom_html" rows="12" class="large-text" placeholder="&lt;h1&gt;Volvemos pronto&lt;/h1&gt;"><?php echo esc_textarea( $s['maintenance_custom_html'] ); ?></textarea>
                            <p class="description">HTML completo de la página. Se sanea con las mismas reglas que el contenido de una entrada.</p>
                        </div>
                    </div>

                    <?php if ( $s['maintenance_enabled'] ) : ?>
                    <p><a href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank" class="button">👁 Ver página de mantenimiento</a></p>
                    <?php endif; ?>

                </div>

                <p class="submit">
                    <?php submit_button( '💾 Guardar configuración', 'primary', 'submit', false ); ?>
                </p>
            </form>

            <!-- ── TAB: RENDIMIENTO ── -->
            <!-- Fuera del <form> de arriba a propósito: esta pestaña tiene sus
                 propios formularios (guardar reglas, escanear, analizar con
                 PageSpeed) y HTML no puede anidar <form> dentro de <form>. -->
            <div id="tab-rendimiento" class="co-tab-panel" style="display:none;">

                <div style="background:#fff;border:1px solid #c3c4c7;border-radius:6px;padding:20px 24px;margin-top:16px;">
                    <h2 style="margin-top:0;border-bottom:1px solid #f0f0f1;padding-bottom:10px;font-size:14px;">⚡ Mejoras rápidas</h2>
                    <p class="description" style="margin-top:0;">Cada una es independiente: pruébalas por separado y revisa que el sitio se siga viendo bien antes de activar la siguiente.</p>

                    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                        <?php wp_nonce_field( 'caracool_onestep_save' ); ?>
                        <input type="hidden" name="action" value="caracool_onestep_save">
                        <input type="hidden" name="co_form" value="perf">

                        <table class="form-table" style="margin-top:0;">
                            <tr>
                                <td>
                                    <label style="font-size:14px;">
                                        <input type="checkbox" name="perf_disable_emojis" value="1" <?php checked( $s['perf_disable_emojis'] ); ?>>
                                        Quitar el script y los estilos de emojis nativos de WordPress
                                    </label>
                                    <p class="description">WordPress carga un script en cada página para dar soporte a emojis en navegadores antiguos. La mayoría de navegadores actuales ya no lo necesitan.</p>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <label style="font-size:14px;">
                                        <input type="checkbox" name="perf_remove_jquery_migrate" value="1" <?php checked( $s['perf_remove_jquery_migrate'] ); ?>>
                                        Quitar jQuery Migrate
                                    </label>
                                    <p class="description">Librería de compatibilidad con versiones muy antiguas de jQuery. Si el tema o los plugins no la necesitan, es peso muerto en cada página.</p>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <label style="font-size:14px;">
                                        <input type="checkbox" name="perf_remove_block_library_css" value="1" <?php checked( $s['perf_remove_block_library_css'] ); ?>>
                                        Quitar el CSS de bloques de Gutenberg en todo el sitio
                                    </label>
                                    <p class="description">⚠️ Solo si el sitio no usa el editor de bloques (o el tema ya trae sus propios estilos). Si algo se desmaqueta al activarlo, desactívalo de nuevo.</p>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <label style="font-size:14px;">
                                        <input type="checkbox" name="perf_disable_embeds" value="1" <?php checked( $s['perf_disable_embeds'] ); ?>>
                                        Desactivar el sistema de embebidos (oEmbed) de WordPress
                                    </label>
                                    <p class="description">Evita que otros sitios puedan embeber automáticamente tus entradas y quita el script <code>wp-embed</code> del front-end.</p>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <label style="font-size:14px;">
                                        <input type="checkbox" name="perf_remove_dashicons_front" value="1" <?php checked( $s['perf_remove_dashicons_front'] ); ?>>
                                        Quitar Dashicons para visitantes sin sesión
                                    </label>
                                    <p class="description">Los iconos de wp-admin no hacen falta en el front-end para quien no tiene la barra de administración.</p>
                                </td>
                            </tr>
                        </table>

                        <h2 style="margin-top:24px;border-bottom:1px solid #f0f0f1;padding-bottom:10px;font-size:14px;">🧰 Gestor de scripts y estilos</h2>
                        <p class="description" style="margin-top:0;">
                            <?php if ( ! empty( $scan['scanned_at'] ) ) : ?>
                                Último escaneo: <?php echo esc_html( wp_date( 'd/m/Y H:i', $scan['scanned_at'] ) ); ?> sobre <code><?php echo esc_html( $scan['url'] ); ?></code>.
                            <?php else : ?>
                                Todavía no se ha escaneado el sitio — pulsa "Escanear ahora" más abajo para detectar qué scripts y estilos cargan en la portada.
                            <?php endif; ?>
                        </p>

                        <?php if ( $detected ) : ?>
                        <table class="widefat striped" style="margin-top:10px;max-width:820px;">
                            <thead>
                                <tr>
                                    <th style="width:60px;">Tipo</th>
                                    <th>Handle</th>
                                    <th>Origen</th>
                                    <th style="width:220px;">Acción</th>
                                </tr>
                            </thead>
                            <tbody id="co-perf-rules-body">
                                <?php foreach ( $detected as $key => $row ) :
                                    $current_action = $rules_by_key[ $key ] ?? 'none';
                                    ?>
                                    <tr>
                                        <td><?php echo 'script' === $row['type'] ? '🧩 JS' : '🎨 CSS'; ?></td>
                                        <td><code><?php echo esc_html( $row['handle'] ); ?></code></td>
                                        <td style="max-width:320px;overflow-wrap:anywhere;color:#666;font-size:12px;"><?php echo esc_html( $row['src'] ); ?></td>
                                        <td>
                                            <select name="perf_rule[<?php echo esc_attr( $key ); ?>]">
                                                <option value="none" <?php selected( $current_action, 'none' ); ?>>Sin cambios</option>
                                                <option value="defer" <?php selected( $current_action, 'defer' ); ?>>Retrasar carga (defer)</option>
                                                <?php if ( 'script' === $row['type'] ) : ?>
                                                <option value="async" <?php selected( $current_action, 'async' ); ?>>Cargar en paralelo (async)</option>
                                                <?php endif; ?>
                                                <option value="disable" <?php selected( $current_action, 'disable' ); ?>>Desactivar por completo</option>
                                            </select>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php endif; ?>

                        <p style="margin-top:14px;">
                            <button type="button" class="button" id="co-add-manual-rule">➕ Añadir regla manual</button>
                            <span class="description">Para un handle que no haya aparecido en el escaneo (p. ej. porque solo carga en ciertas páginas).</span>
                        </p>
                        <table class="widefat" id="co-manual-rules-table" style="margin-top:6px;max-width:820px;display:none;">
                            <tbody id="co-manual-rules-body"></tbody>
                        </table>

                        <p class="submit">
                            <?php submit_button( '💾 Guardar reglas de rendimiento', 'primary', 'submit', false ); ?>
                        </p>
                    </form>

                    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:4px;">
                        <?php wp_nonce_field( 'caracool_onestep_run_scan' ); ?>
                        <input type="hidden" name="action" value="caracool_onestep_run_scan">
                        <?php submit_button( '🔍 Escanear ahora', 'secondary', 'submit', false ); ?>
                        <p class="description">Hace una petición interna a la portada del sitio y anota qué scripts y estilos cargan realmente, para rellenar la tabla de arriba.</p>
                    </form>
                </div>

                <div style="background:#fff;border:1px solid #c3c4c7;border-radius:6px;padding:20px 24px;margin-top:16px;">
                    <h2 style="margin-top:0;border-bottom:1px solid #f0f0f1;padding-bottom:10px;font-size:14px;">📊 Análisis con Google PageSpeed Insights</h2>
                    <p class="description" style="margin-top:0;">Opcional. Consulta la API pública de Google para obtener una puntuación real y la lista de recursos que bloquean el renderizado.</p>

                    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                        <?php wp_nonce_field( 'caracool_onestep_run_pagespeed' ); ?>
                        <input type="hidden" name="action" value="caracool_onestep_run_pagespeed">

                        <table class="form-table" style="margin-top:0;">
                            <tr>
                                <th style="width:200px;"><label for="perf_pagespeed_strategy">Dispositivo</label></th>
                                <td>
                                    <select name="perf_pagespeed_strategy" id="perf_pagespeed_strategy">
                                        <option value="mobile" <?php selected( $s['perf_pagespeed_strategy'], 'mobile' ); ?>>Móvil</option>
                                        <option value="desktop" <?php selected( $s['perf_pagespeed_strategy'], 'desktop' ); ?>>Escritorio</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="perf_pagespeed_api_key">Clave de API de Google <span style="font-weight:normal;color:#888;">(opcional)</span></label></th>
                                <td>
                                    <input type="text" name="perf_pagespeed_api_key" id="perf_pagespeed_api_key" value="<?php echo esc_attr( $s['perf_pagespeed_api_key'] ); ?>" class="regular-text" placeholder="Sin clave funciona igual, pero con más límite de peticiones">
                                    <p class="description">Se puede crear gratis en <a href="https://console.cloud.google.com/apis/credentials" target="_blank" rel="noopener">Google Cloud Console</a>, habilitando la "PageSpeed Insights API".</p>
                                </td>
                            </tr>
                        </table>

                        <p class="submit">
                            <?php submit_button( '⚡ Analizar ahora', 'primary', 'submit', false ); ?>
                        </p>
                    </form>

                    <?php if ( ! empty( $pagespeed['checked_at'] ) ) : ?>
                        <hr>
                        <p class="description">Último análisis: <?php echo esc_html( wp_date( 'd/m/Y H:i', $pagespeed['checked_at'] ) ); ?> (<?php echo esc_html( 'mobile' === $pagespeed['strategy'] ? 'móvil' : 'escritorio' ); ?>)</p>

                        <?php if ( ! empty( $pagespeed['error'] ) ) : ?>
                            <p style="color:#b32d2e;">⚠️ <?php echo esc_html( $pagespeed['error'] ); ?></p>
                        <?php else : ?>
                            <?php if ( null !== $pagespeed['score'] ) :
                                $score = (int) $pagespeed['score'];
                                $color = $score >= 90 ? '#1a7f37' : ( $score >= 50 ? '#b8860b' : '#b32d2e' );
                                ?>
                                <p style="font-size:15px;">Puntuación de rendimiento: <strong style="color:<?php echo esc_attr( $color ); ?>;font-size:22px;"><?php echo esc_html( $score ); ?></strong> / 100</p>
                            <?php endif; ?>

                            <?php if ( ! empty( $pagespeed['items'] ) ) : ?>
                                <p><strong>Recursos que bloquean el renderizado:</strong></p>
                                <table class="widefat striped" style="max-width:820px;">
                                    <thead><tr><th>URL</th><th style="width:110px;">Peso</th><th style="width:110px;">Tiempo estimado</th></tr></thead>
                                    <tbody>
                                        <?php foreach ( $pagespeed['items'] as $item ) : ?>
                                        <tr>
                                            <td style="overflow-wrap:anywhere;font-size:12px;"><?php echo esc_html( $item['url'] ); ?></td>
                                            <td><?php echo esc_html( size_format( $item['totalBytes'] ) ); ?></td>
                                            <td><?php echo esc_html( $item['wastedMs'] ); ?> ms</td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                <p class="description">Añade esos handles en el "Gestor de scripts y estilos" de arriba (o escanea primero) para retrasarlos o desactivarlos.</p>
                            <?php else : ?>
                                <p class="description">Google no ha detectado recursos bloqueantes relevantes en este análisis. 🎉</p>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

            </div>
        </div>

        <script>
        (function () {
            // ── Tabs (vanilla JS, sin dependencias) ────────────
            var tabs   = document.querySelectorAll('.nav-tab-wrapper .nav-tab');
            var panels = document.querySelectorAll('.co-tab-panel');
            tabs.forEach(function (tab) {
                tab.addEventListener('click', function (e) {
                    e.preventDefault();
                    tabs.forEach(function (t) { t.classList.remove('nav-tab-active'); });
                    panels.forEach(function (p) { p.style.display = 'none'; });
                    tab.classList.add('nav-tab-active');
                    document.getElementById(tab.dataset.coTab).style.display = '';
                });
            });

            // ── Mostrar/ocultar campo de redirección ───────────
            var statusSelect = document.getElementById('maintenance_http_status');
            var redirectRow  = document.getElementById('co-redirect-row');
            if (statusSelect) {
                statusSelect.addEventListener('change', function () {
                    redirectRow.style.display = (this.value === '301') ? '' : 'none';
                });
            }

            // ── HTML personalizado vs plantilla por defecto ────
            var customToggle = document.getElementById('maintenance_use_custom_html');
            var defaultFields = document.getElementById('co-default-page-fields');
            var customField   = document.getElementById('co-custom-html-field');
            if (customToggle) {
                customToggle.addEventListener('change', function () {
                    defaultFields.style.display = this.checked ? 'none' : '';
                    customField.style.display   = this.checked ? '' : 'none';
                });
            }

            // ── Rendimiento: añadir una regla manual (handle no detectado) ──
            // El name del <select> se rellena al vuelo con "perf_rule[tipo:handle]"
            // según lo que se escriba en el campo de handle, para reutilizar
            // exactamente el mismo formato que las filas generadas por PHP.
            var addRuleBtn   = document.getElementById('co-add-manual-rule');
            var manualTable  = document.getElementById('co-manual-rules-table');
            var manualBody   = document.getElementById('co-manual-rules-body');
            if (addRuleBtn) {
                addRuleBtn.addEventListener('click', function () {
                    manualTable.style.display = '';
                    var tr = document.createElement('tr');
                    tr.innerHTML =
                        '<td style="width:70px;">' +
                            '<select class="co-manual-type"><option value="script">🧩 JS</option><option value="style">🎨 CSS</option></select>' +
                        '</td>' +
                        '<td><input type="text" class="co-manual-handle regular-text" placeholder="handle exacto del script/estilo"></td>' +
                        '<td style="width:220px;">' +
                            '<select class="co-manual-action" disabled>' +
                                '<option value="none">Sin cambios</option>' +
                                '<option value="defer">Retrasar carga (defer)</option>' +
                                '<option value="async">Cargar en paralelo (async)</option>' +
                                '<option value="disable">Desactivar por completo</option>' +
                            '</select>' +
                        '</td>' +
                        '<td style="width:40px;"><button type="button" class="button-link co-manual-remove" title="Quitar">✕</button></td>';
                    manualBody.appendChild(tr);

                    var handleInput  = tr.querySelector('.co-manual-handle');
                    var typeSelect   = tr.querySelector('.co-manual-type');
                    var actionSelect = tr.querySelector('.co-manual-action');

                    function syncName() {
                        var handle = handleInput.value.trim().toLowerCase().replace(/[^a-z0-9_-]/g, '');
                        if (handle) {
                            actionSelect.name = 'perf_rule[' + typeSelect.value + ':' + handle + ']';
                            actionSelect.disabled = false;
                        } else {
                            actionSelect.removeAttribute('name');
                            actionSelect.disabled = true;
                        }
                    }
                    handleInput.addEventListener('input', syncName);
                    typeSelect.addEventListener('change', syncName);

                    tr.querySelector('.co-manual-remove').addEventListener('click', function () {
                        tr.remove();
                    });
                });
            }
        })();

        jQuery(function ($) {
            // ── Selector de logo (media library) ───────────────
            var uploader;
            $('#co-select-logo').on('click', function (e) {
                e.preventDefault();
                if (uploader) { uploader.open(); return; }
                uploader = wp.media({
                    title:    'Selecciona el logo',
                    button:   { text: 'Usar este logo' },
                    multiple: false,
                    library:  { type: 'image' }
                });
                uploader.on('select', function () {
                    var att = uploader.state().get('selection').first().toJSON();
                    $('#maintenance_logo_url').val(att.url);
                    $('#co-logo-preview').attr('src', att.url).show();
                    $('#co-select-logo').text('🔄 Cambiar logo');
                });
                uploader.open();
            });

            // ── Selectores de color ─────────────────────────────
            $('.co-color-picker').wpColorPicker();
        });
        </script>
        <?php
    }

    // ─────────────────────────────────────────────────────────
    // MÓDULO: COMENTARIOS
    // Un único interruptor "desactivar en todo el sitio", sin
    // ajustes por tipo de contenido ni por rol, que es lo único
    // que usamos en los sitios de Caracool.
    // ─────────────────────────────────────────────────────────
    private function comments_bootstrap() {
        add_action( 'wp_loaded', [ $this, 'comments_remove_post_type_support' ] );

        add_filter( 'comments_open', '__return_false' );
        add_filter( 'pings_open', '__return_false' );
        add_filter( 'comments_array', '__return_empty_array', 20 );
        add_filter( 'get_comments_number', '__return_zero' );
        add_filter( 'feed_links_show_comments_feed', '__return_false' );
        add_filter( 'wp_headers', [ $this, 'comments_remove_pingback_header' ] );
        add_filter( 'xmlrpc_methods', [ $this, 'comments_remove_xmlrpc_method' ] );
        add_filter( 'rest_endpoints', [ $this, 'comments_remove_rest_endpoints' ] );

        add_action( 'template_redirect', [ $this, 'comments_kill_feed_and_template' ], 9 );
        add_action( 'widgets_init', [ $this, 'comments_disable_recent_comments_widget' ], 20 );
        add_action( 'wp_dashboard_setup', [ $this, 'comments_remove_dashboard_widget' ] );
        add_action( 'wp_before_admin_bar_render', [ $this, 'comments_remove_admin_bar_menu' ] );

        add_action( 'admin_init', [ $this, 'comments_block_admin_pages' ] );
        add_action( 'admin_menu', [ $this, 'comments_remove_admin_menu' ], 999 );
    }

    public function comments_remove_post_type_support() {
        foreach ( get_post_types() as $post_type ) {
            if ( post_type_supports( $post_type, 'comments' ) ) {
                remove_post_type_support( $post_type, 'comments' );
                remove_post_type_support( $post_type, 'trackbacks' );
            }
        }
    }

    public function comments_remove_pingback_header( $headers ) {
        unset( $headers['X-Pingback'] );
        return $headers;
    }

    public function comments_remove_xmlrpc_method( $methods ) {
        unset( $methods['wp.newComment'] );
        return $methods;
    }

    public function comments_remove_rest_endpoints( $endpoints ) {
        foreach ( $endpoints as $route => $handlers ) {
            if ( strpos( $route, '/wp/v2/comments' ) === 0 ) {
                unset( $endpoints[ $route ] );
            }
        }
        return $endpoints;
    }

    public function comments_kill_feed_and_template() {
        if ( is_comment_feed() ) {
            wp_die( esc_html__( 'Los comentarios están desactivados.', 'caracool-onestep' ), '', [ 'response' => 403 ] );
        }

        if ( is_singular() ) {
            add_filter( 'comments_template', [ $this, 'comments_blank_template' ], 20 );
            wp_deregister_script( 'comment-reply' );
            remove_action( 'wp_head', 'feed_links_extra', 3 );
        }
    }

    public function comments_blank_template() {
        return CARACOOL_ONESTEP_PATH . 'templates/blank-comments.php';
    }

    public function comments_disable_recent_comments_widget() {
        unregister_widget( 'WP_Widget_Recent_Comments' );
    }

    public function comments_remove_dashboard_widget() {
        remove_meta_box( 'dashboard_recent_comments', 'dashboard', 'normal' );
    }

    public function comments_remove_admin_bar_menu() {
        global $wp_admin_bar;
        if ( $wp_admin_bar ) $wp_admin_bar->remove_menu( 'comments' );
    }

    public function comments_block_admin_pages() {
        global $pagenow;
        if ( in_array( $pagenow, [ 'edit-comments.php', 'comment.php' ], true ) ) {
            wp_safe_redirect( admin_url() );
            exit;
        }
    }

    public function comments_remove_admin_menu() {
        remove_menu_page( 'edit-comments.php' );
        remove_submenu_page( 'options-general.php', 'options-discussion.php' );
    }

    // ─────────────────────────────────────────────────────────
    // MÓDULO: MANTENIMIENTO
    // ─────────────────────────────────────────────────────────
    public function maintenance_maybe_render() {
        $s = $this->get_settings();
        if ( empty( $s['maintenance_enabled'] ) ) return;
        if ( is_admin() ) return; // wp-admin siempre accesible, para poder desactivarlo

        if ( $this->maintenance_user_bypasses( $s ) ) return;

        $status = (int) $s['maintenance_http_status'];

        if ( 301 === $status && ! empty( $s['maintenance_redirect_url'] ) ) {
            wp_redirect( esc_url_raw( $s['maintenance_redirect_url'] ), 301 );
            exit;
        }

        if ( in_array( $status, [ 503, 404 ], true ) ) {
            status_header( $status );
            if ( 503 === $status ) {
                header( 'Retry-After: 3600' );
            }
        }

        $this->maintenance_render_page( $s );
        exit;
    }

    private function maintenance_user_bypasses( $s ) {
        if ( $this->maintenance_ip_is_whitelisted( $s ) ) return true;
        if ( ! is_user_logged_in() ) return false;

        $min_role = $s['maintenance_bypass_role'];
        if ( empty( $min_role ) ) return true; // cualquier usuario conectado pasa

        return $this->user_meets_min_role( wp_get_current_user(), $min_role );
    }

    private function user_meets_min_role( $user, $min_role ) {
        $user_max = -1;
        foreach ( (array) $user->roles as $role ) {
            $idx = array_search( $role, self::ROLE_ORDER, true );
            if ( false !== $idx ) $user_max = max( $user_max, $idx );
        }
        $min_idx = array_search( $min_role, self::ROLE_ORDER, true );
        if ( false === $min_idx ) return true;

        return $user_max >= $min_idx;
    }

    private function maintenance_ip_is_whitelisted( $s ) {
        $ip = $this->get_client_ip();
        if ( ! $ip ) return false;
        return in_array( $ip, (array) $s['maintenance_ip_whitelist'], true );
    }

    /**
     * IP del visitante desde REMOTE_ADDR. Si el sitio va detrás de un proxy o
     * CDN (Cloudflare, etc.), REMOTE_ADDR será la IP del proxy, no la del
     * visitante: en ese caso habría que leer la cabecera correspondiente
     * (p.ej. CF-Connecting-IP) según cómo esté montado el hosting.
     */
    private function get_client_ip() {
        $ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
        return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '';
    }

    private function maintenance_render_page( $s ) {
        nocache_headers();

        if ( ! empty( $s['maintenance_use_custom_html'] ) && ! empty( $s['maintenance_custom_html'] ) ) {
            echo wp_kses_post( $s['maintenance_custom_html'] );
            return;
        }

        $title   = $s['maintenance_title']   ?: get_bloginfo( 'name' );
        $heading = $s['maintenance_heading'] ?: __( 'Estamos preparando algo nuevo', 'caracool-onestep' );
        $message = $s['maintenance_message'] ?: __( 'Volvemos enseguida. Gracias por tu paciencia.', 'caracool-onestep' );
        ?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo esc_html( $title ); ?></title>
    <style>
        :root { --co-bg: <?php echo esc_html( $s['maintenance_bg_color'] ); ?>; --co-fg: <?php echo esc_html( $s['maintenance_text_color'] ); ?>; }
        html, body { height: 100%; margin: 0; }
        body {
            background: var(--co-bg);
            color: var(--co-fg);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 24px;
            box-sizing: border-box;
        }
        .co-wrap { max-width: 560px; }
        .co-logo { max-width: 180px; max-height: 100px; margin-bottom: 28px; }
        h1 { font-size: clamp(22px, 4vw, 34px); font-weight: 600; margin: 0 0 14px; }
        p { font-size: 16px; line-height: 1.6; opacity: .85; margin: 0; }
        .co-credit { position: fixed; bottom: 16px; left: 0; right: 0; font-size: 12px; opacity: .45; }
        .co-credit a { color: inherit; }
    </style>
</head>
<body>
    <div class="co-wrap">
        <?php if ( ! empty( $s['maintenance_logo_url'] ) ) : ?>
        <img class="co-logo" src="<?php echo esc_url( $s['maintenance_logo_url'] ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
        <?php endif; ?>
        <h1><?php echo esc_html( $heading ); ?></h1>
        <p><?php echo nl2br( esc_html( $message ) ); ?></p>
    </div>
    <?php if ( ! empty( $s['maintenance_show_credit'] ) ) : ?>
    <div class="co-credit">Hecho con ❤️ por <a href="https://caracool.net" target="_blank" rel="noopener">Caracool</a></div>
    <?php endif; ?>
</body>
</html>
        <?php
    }

    // ─────────────────────────────────────────────────────────
    // MÓDULO: RENDIMIENTO
    //
    // Tres piezas independientes:
    // 1) "Mejoras rápidas" — técnicas puntuales, cada una detrás de su propio
    //    checkbox (emojis, jQuery Migrate, CSS de bloques, embeds, dashicons).
    // 2) "Gestor de scripts y estilos" — un escaneo real de la portada (vía una
    //    petición interna con wp_remote_get) que lee $wp_scripts/$wp_styles ya
    //    procesados para ver qué ha cargado de verdad, y unas reglas por handle
    //    (retrasar/async/desactivar) aplicadas con script_loader_tag/
    //    style_loader_tag y wp_dequeue_*.
    // 3) Integración opcional con la API pública de Google PageSpeed Insights
    //    (v5, https://pagespeedonline.googleapis.com/pagespeedonline/v5/runPagespeed),
    //    solo bajo demanda desde el botón "Analizar ahora" (nunca en cada
    //    visita, para no gastar cuota ni ralentizar el sitio).
    // ─────────────────────────────────────────────────────────
    private function perf_bootstrap( $settings ) {
        // El escaneo se comprueba en cada petición (init), pero solo hace algo
        // si la URL trae el token de un escaneo recién solicitado desde el admin.
        add_action( 'init', [ $this, 'perf_maybe_start_scan_capture' ] );

        if ( ! empty( $settings['perf_disable_emojis'] ) ) {
            // Igual que el snippet de referencia de WordPress: hay que
            // engancharlo en 'init', porque los hooks de emojis ya están
            // registrados por el core antes de que se cargue el plugin.
            add_action( 'init', [ $this, 'perf_disable_emojis' ] );
        }

        if ( ! empty( $settings['perf_remove_jquery_migrate'] ) ) {
            add_action( 'wp_default_scripts', [ $this, 'perf_remove_jquery_migrate' ], 150 );
        }

        if ( ! empty( $settings['perf_remove_block_library_css'] ) ) {
            add_action( 'wp_enqueue_scripts', [ $this, 'perf_remove_block_library_css' ], 100 );
        }

        if ( ! empty( $settings['perf_disable_embeds'] ) ) {
            add_action( 'init', [ $this, 'perf_disable_embeds' ], 9999 );
        }

        if ( ! empty( $settings['perf_remove_dashicons_front'] ) ) {
            add_action( 'wp_enqueue_scripts', [ $this, 'perf_remove_dashicons_front' ], 100 );
        }

        $rules = is_array( $settings['perf_rules'] ?? null ) ? $settings['perf_rules'] : [];
        if ( $rules ) {
            $this->perf_rules = $rules;

            add_filter( 'script_loader_tag', [ $this, 'perf_apply_script_rule' ], 10, 2 );
            add_filter( 'style_loader_tag',  [ $this, 'perf_apply_style_rule' ], 10, 2 );

            add_action( 'wp_enqueue_scripts', [ $this, 'perf_dequeue_disabled_handles' ], 999 );
        }
    }

    // ── Mejoras rápidas ───────────────────────────────────────
    public function perf_disable_emojis() {
        remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
        remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
        remove_action( 'wp_print_styles', 'print_emoji_styles' );
        remove_action( 'admin_print_styles', 'print_emoji_styles' );
        remove_action( 'wp_mail', 'wp_staticize_emoji_for_email' );
        remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
        remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
        remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
        add_filter( 'tiny_mce_plugins', [ $this, 'perf_disable_emojis_tinymce' ] );
        add_filter( 'wp_resource_hints', [ $this, 'perf_disable_emojis_dns_prefetch' ], 10, 2 );
    }

    public function perf_disable_emojis_tinymce( $plugins ) {
        return is_array( $plugins ) ? array_diff( $plugins, [ 'wpemoji' ] ) : [];
    }

    public function perf_disable_emojis_dns_prefetch( $urls, $relation_type ) {
        if ( 'dns-prefetch' === $relation_type ) {
            $emoji_svg_url = apply_filters( 'emoji_svg_url', 'https://s.w.org/images/core/emoji/2/svg/' );
            $urls = array_diff( $urls, [ $emoji_svg_url ] );
        }
        return $urls;
    }

    public function perf_remove_jquery_migrate( $scripts ) {
        if ( is_admin() ) return;
        if ( empty( $scripts->registered['jquery'] ) ) return;

        $jquery = $scripts->registered['jquery'];
        if ( ! empty( $jquery->deps ) ) {
            $jquery->deps = array_diff( $jquery->deps, [ 'jquery-migrate' ] );
        }
    }

    public function perf_remove_block_library_css() {
        wp_dequeue_style( 'wp-block-library' );
        wp_dequeue_style( 'wp-block-library-theme' );
        wp_dequeue_style( 'classic-theme-styles' );
        wp_dequeue_style( 'global-styles' );
        wp_dequeue_style( 'wc-blocks-style' );
    }

    public function perf_disable_embeds() {
        remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
        remove_action( 'wp_head', 'wp_oembed_add_host_js' );
        remove_action( 'rest_api_init', 'wp_oembed_register_route' );
        remove_filter( 'oembed_dataparse', 'wp_filter_oembed_result', 10 );
        add_filter( 'embed_oembed_discover', '__return_false' );
        add_action( 'wp_enqueue_scripts', [ $this, 'perf_deregister_embed_script' ], 999 );
    }

    public function perf_deregister_embed_script() {
        wp_deregister_script( 'wp-embed' );
    }

    public function perf_remove_dashicons_front() {
        if ( is_admin_bar_showing() ) return; // lo siguen necesitando los usuarios logueados con barra de admin
        wp_dequeue_style( 'dashicons' );
        wp_deregister_style( 'dashicons' );
    }

    // ── Gestor de scripts y estilos: aplicar reglas ────────────
    public function perf_apply_script_rule( $tag, $handle ) {
        foreach ( $this->perf_rules as $rule ) {
            if ( 'script' !== $rule['type'] || $rule['handle'] !== $handle ) continue;

            if ( 'defer' === $rule['action'] && false === strpos( $tag, ' defer' ) && false === strpos( $tag, 'type="module"' ) ) {
                $tag = str_replace( ' src=', ' defer src=', $tag );
            } elseif ( 'async' === $rule['action'] && false === strpos( $tag, ' async' ) ) {
                $tag = str_replace( ' src=', ' async src=', $tag );
            }
            break;
        }
        return $tag;
    }

    public function perf_apply_style_rule( $tag, $handle ) {
        foreach ( $this->perf_rules as $rule ) {
            if ( 'style' !== $rule['type'] || $rule['handle'] !== $handle ) continue;

            if ( 'defer' === $rule['action'] && preg_match( "/href=(['\"])(.*?)\\1/", $tag, $href_m ) ) {
                $href  = $href_m[2];
                $media = 'all';
                if ( preg_match( "/media=(['\"])(.*?)\\1/", $tag, $media_m ) && $media_m[2] ) {
                    $media = $media_m[2];
                }
                // Carga no bloqueante mediante el patrón preload + onload
                // recomendado por web.dev para CSS no crítico, con fallback
                // <noscript> para navegadores sin JavaScript.
                $tag = sprintf(
                    '<link rel="preload" id="%1$s-css" href="%2$s" as="style" media="%3$s" onload="this.onload=null;this.rel=\'stylesheet\'" />' .
                    '<noscript><link rel="stylesheet" id="%1$s-css-fallback" href="%2$s" media="%3$s" /></noscript>' . "\n",
                    esc_attr( $handle ),
                    esc_url( $href ),
                    esc_attr( $media )
                );
            }
            break;
        }
        return $tag;
    }

    public function perf_dequeue_disabled_handles() {
        foreach ( $this->perf_rules as $rule ) {
            if ( 'disable' !== $rule['action'] ) continue;

            if ( 'style' === $rule['type'] ) {
                wp_dequeue_style( $rule['handle'] );
                wp_deregister_style( $rule['handle'] );
            } else {
                wp_dequeue_script( $rule['handle'] );
                wp_deregister_script( $rule['handle'] );
            }
        }
    }

    // ── Escaneo real de la portada ──────────────────────────────
    // El botón "Escanear ahora" del admin dispara una petición HTTP interna
    // (loopback) a la portada con un token de un solo uso. Esa petición, al
    // llegar aquí de nuevo, detecta el token válido y engancha la captura en
    // wp_footer (con 'shutdown' como red de seguridad si wp_footer no llega a
    // dispararse). Así el escaneo refleja lo que carga de verdad, sin añadir
    // overhead a las visitas normales.
    public function perf_run_scan() {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'No autorizado.' );
        check_admin_referer( 'caracool_onestep_run_scan' );

        $token = wp_generate_password( 20, false, false );
        set_transient( 'caracool_onestep_scan_token', $token, 60 );

        $url = add_query_arg( 'caracool_onestep_scan', $token, home_url( '/' ) );
        wp_remote_get( $url, [
            'timeout'   => 20,
            'sslverify' => apply_filters( 'https_local_ssl_verify', false ),
            'headers'   => [ 'Cache-Control' => 'no-cache' ],
        ] );

        wp_safe_redirect( admin_url( 'admin.php?page=' . CARACOOL_ONESTEP_SLUG . '&scanned=1' ) );
        exit;
    }

    public function perf_maybe_start_scan_capture() {
        if ( is_admin() ) return;
        if ( empty( $_GET['caracool_onestep_scan'] ) ) return;

        $token = sanitize_text_field( wp_unslash( $_GET['caracool_onestep_scan'] ) );
        if ( ! $token || $token !== get_transient( 'caracool_onestep_scan_token' ) ) return;

        delete_transient( 'caracool_onestep_scan_token' );

        add_action( 'wp_footer', [ $this, 'perf_capture_scan' ], 999 );
        add_action( 'shutdown', [ $this, 'perf_capture_scan' ] );
    }

    public function perf_capture_scan() {
        if ( $this->scan_captured ) return;
        $this->scan_captured = true;

        global $wp_scripts, $wp_styles;

        $scripts = [];
        if ( $wp_scripts instanceof WP_Scripts ) {
            foreach ( (array) $wp_scripts->done as $handle ) {
                if ( empty( $wp_scripts->registered[ $handle ] ) ) continue;
                $scripts[] = [
                    'handle' => $handle,
                    'src'    => $wp_scripts->registered[ $handle ]->src ?: '(inline)',
                ];
            }
        }

        $styles = [];
        if ( $wp_styles instanceof WP_Styles ) {
            foreach ( (array) $wp_styles->done as $handle ) {
                if ( empty( $wp_styles->registered[ $handle ] ) ) continue;
                $styles[] = [
                    'handle' => $handle,
                    'src'    => $wp_styles->registered[ $handle ]->src ?: '(inline)',
                ];
            }
        }

        update_option( self::SCAN_OPTION_KEY, [
            'scanned_at' => time(),
            'url'        => home_url( '/' ),
            'scripts'    => $scripts,
            'styles'     => $styles,
        ], false );
    }

    // ── Integración con Google PageSpeed Insights (opcional, bajo demanda) ──
    public function perf_run_pagespeed() {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'No autorizado.' );
        check_admin_referer( 'caracool_onestep_run_pagespeed' );

        $strategy = in_array( $_POST['perf_pagespeed_strategy'] ?? '', [ 'mobile', 'desktop' ], true )
            ? $_POST['perf_pagespeed_strategy']
            : 'mobile';
        $api_key = sanitize_text_field( wp_unslash( $_POST['perf_pagespeed_api_key'] ?? '' ) );

        // Recordamos la clave y la estrategia elegidas, para no tener que
        // volver a escribirlas cada vez.
        $current = $this->get_settings();
        update_option( self::OPTION_KEY, array_merge( $current, [
            'perf_pagespeed_api_key'  => $api_key,
            'perf_pagespeed_strategy' => $strategy,
        ] ) );

        $args = [
            'url'      => home_url( '/' ),
            'strategy' => $strategy,
            'category' => 'performance',
        ];
        if ( $api_key ) $args['key'] = $api_key;

        $endpoint = add_query_arg( $args, 'https://pagespeedonline.googleapis.com/pagespeedonline/v5/runPagespeed' );
        $response = wp_remote_get( $endpoint, [ 'timeout' => 60 ] );

        $result = [
            'checked_at' => time(),
            'strategy'   => $strategy,
            'score'      => null,
            'items'      => [],
            'error'      => '',
        ];

        if ( is_wp_error( $response ) ) {
            $result['error'] = $response->get_error_message();
        } else {
            $code = wp_remote_retrieve_response_code( $response );
            $body = json_decode( wp_remote_retrieve_body( $response ), true );

            if ( 200 !== $code ) {
                $result['error'] = $body['error']['message'] ?? sprintf( 'Error HTTP %d al consultar la API de PageSpeed.', $code );
            } else {
                if ( isset( $body['lighthouseResult']['categories']['performance']['score'] ) ) {
                    $result['score'] = (int) round( $body['lighthouseResult']['categories']['performance']['score'] * 100 );
                }

                $audit = $body['lighthouseResult']['audits']['render-blocking-resources'] ?? null;
                if ( ! empty( $audit['details']['items'] ) ) {
                    foreach ( $audit['details']['items'] as $item ) {
                        $result['items'][] = [
                            'url'        => $item['url'] ?? '',
                            'totalBytes' => (int) ( $item['totalBytes'] ?? 0 ),
                            'wastedMs'   => (int) ( $item['wastedMs'] ?? 0 ),
                        ];
                    }
                }
            }
        }

        update_option( self::PAGESPEED_OPTION_KEY, $result, false );

        wp_safe_redirect( admin_url( 'admin.php?page=' . CARACOOL_ONESTEP_SLUG . '&pagespeed=1' ) );
        exit;
    }
}

new Caracool_OneStep();

// ── Update checker — GitHub Releases ──────────────────────────
// Comprueba si hay nueva versión disponible en el repo público,
// sin librerías externas. Ajustar el nombre del repo si cambia.
add_filter( 'pre_set_site_transient_update_plugins', function ( $transient ) {
    if ( empty( $transient->checked ) ) return $transient;

    $plugin_file = plugin_basename( __FILE__ );
    if ( ! isset( $transient->checked[ $plugin_file ] ) ) return $transient;

    $cache_key = 'caracool_onestep_gh_release';
    $release   = get_transient( $cache_key );

    if ( false === $release ) {
        $response = wp_remote_get(
            'https://api.github.com/repos/caracoolnet/wp-onestep/releases/latest',
            [
                'timeout' => 10,
                'headers' => [
                    'Accept'     => 'application/vnd.github+json',
                    'User-Agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . home_url(),
                ],
            ]
        );

        if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
            set_transient( $cache_key, [], 6 * HOUR_IN_SECONDS );
            return $transient;
        }

        $release = json_decode( wp_remote_retrieve_body( $response ), true );
        set_transient( $cache_key, $release ?: [], 6 * HOUR_IN_SECONDS );
    }

    if ( empty( $release['tag_name'] ) ) return $transient;

    $latest = ltrim( $release['tag_name'], 'v' );
    if ( ! version_compare( $latest, CARACOOL_ONESTEP_VERSION, '>' ) ) return $transient;

    $zip_url = '';
    foreach ( $release['assets'] ?? [] as $asset ) {
        if ( str_ends_with( $asset['name'], '.zip' ) ) {
            $zip_url = $asset['browser_download_url'];
            break;
        }
    }
    if ( ! $zip_url ) {
        $zip_url = $release['zipball_url'] ?? '';
    }

    $transient->response[ $plugin_file ] = (object) [
        'id'           => 'github.com/caracoolnet/wp-onestep',
        'slug'         => 'caracool-onestep',
        'plugin'       => $plugin_file,
        'new_version'  => $latest,
        'url'          => 'https://github.com/caracoolnet/wp-onestep',
        'package'      => $zip_url,
        'requires'     => '6.0',
        'tested'       => '6.8',
        'requires_php' => '7.4',
    ];

    return $transient;
} );

// Información de plugin en el lightbox de "Ver detalles"
add_filter( 'plugins_api', function ( $result, $action, $args ) {
    if ( 'plugin_information' !== $action ) return $result;
    if ( ( $args->slug ?? '' ) !== 'caracool-onestep' ) return $result;

    return (object) [
        'name'         => 'Caracool OneStep',
        'slug'         => 'caracool-onestep',
        'version'      => CARACOOL_ONESTEP_VERSION,
        'author'       => '<a href="https://caracool.net">Caracool</a>',
        'homepage'     => 'https://github.com/caracoolnet/wp-onestep',
        'requires'     => '6.0',
        'tested'       => '6.8',
        'requires_php' => '7.4',
        'sections'     => [
            'description' => 'Desactiva comentarios en todo el sitio, activa un modo de mantenimiento con página personalizable y ayuda a mejorar el rendimiento (mejoras rápidas, gestor de scripts/estilos y análisis con Google PageSpeed Insights), en un único plugin ligero, sin dependencias externas.',
            'changelog'   => '<h4>1.1.0</h4><p>Nuevo módulo de Rendimiento: mejoras rápidas (emojis, jQuery Migrate, CSS de bloques, embeds, dashicons), escaneo real de scripts/estilos con reglas de retrasar/async/desactivar por handle, e integración opcional con la API de Google PageSpeed Insights.</p><h4>1.0.1</h4><p>Cambio del icono del menú de admin a "admin-generic".</p><h4>1.0.0</h4><p>Versión inicial: desactivación de comentarios en todo el sitio + modo mantenimiento con página personalizable, whitelist de IPs y bypass por rol.</p>',
        ],
    ];
}, 10, 3 );
