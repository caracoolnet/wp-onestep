<?php
/**
 * Plugin Name: Caracool OneStep
 * Plugin URI:  https://caracool.net
 * Description: Desactiva los comentarios en todo el sitio, activa un modo de mantenimiento con página personalizable, inserta código personalizado en el head/footer, permite duplicar páginas y entradas y conecta la web con Claude para editar Elementor. Plugin ligero de Caracool, sin dependencias externas.
 * Version:     1.5.0
 * Author:      Caracool
 * Author URI:  https://caracool.net
 * Text Domain: caracool-onestep
 */

// ── Bloquear acceso directo al archivo ────────────────────────
if ( ! defined( 'ABSPATH' ) ) exit;

define( 'CARACOOL_ONESTEP_VERSION', '1.5.0' );
define( 'CARACOOL_ONESTEP_PATH',    plugin_dir_path( __FILE__ ) );
define( 'CARACOOL_ONESTEP_URL',     plugin_dir_url( __FILE__ ) );
define( 'CARACOOL_ONESTEP_SLUG',    'caracool-onestep' );

// Menú compartido "Caracool" de todos los plugins de la casa (Motion, Carta,
// OneStep, Churra): archivo idéntico en cada uno, ver inc/caracool-menu.php.
require_once CARACOOL_ONESTEP_PATH . 'inc/caracool-menu.php';
require_once CARACOOL_ONESTEP_PATH . 'inc/caracool-actualizador.php';

// ─────────────────────────────────────────────────────────────
// CLASE PRINCIPAL
// ─────────────────────────────────────────────────────────────
class Caracool_OneStep {

    const OPTION_KEY = 'caracool_onestep_settings';

    /** Conexión con Claude: espacio de rutas REST y registro de cambios. */
    const CLAUDE_NS      = 'caracool/v1';
    const CLAUDE_LOG_KEY = 'caracool_onestep_claude_log';

    /** Roles WP ordenados de menor a mayor peso, para el bypass de mantenimiento. */
    const ROLE_ORDER = [ 'subscriber', 'contributor', 'author', 'editor', 'administrator' ];

    /**
     * Logotipo de Caracool, incrustado como SVG inline (sin fichero de imagen
     * aparte) para que la página de ajustes lleve la marca de la agencia.
     */
    private static function logo_svg() {
        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 325.72 100.01" style="width:130px;height:auto;display:block;fill:#1a1a1a;" aria-label="Caracool" role="img"><path d="M39.23,9.44c0,4.65-.8,7.71-2.13,9.98-1.6-1.2-3.99-2.26-6.92-2.26-7.58,0-14.5,6.92-14.5,35.91,0,23.14,2.93,30.06,10.37,30.06,4.12,0,7.85-.67,10.37-2,1.06,2.26,2,5.59,2,10.37,0,3.86-6.12,8.25-14.1,8.25-15.16,0-24.34-3.99-24.34-45.49C0,10.64,14.63,2.66,26.6,2.66c11.31,0,12.64,3.86,12.64,6.78"/><path d="M41.9,36.84c0-1.6.27-3.46,1.46-4.39,1.6-1.33,9.58-3.19,23.41-3.19,9.04,0,13.57,4.26,13.57,16.23v6.92c0,22.74-.67,43.09-.67,43.09-4.52,2.66-11.17,4.26-19.15,4.26-9.84,0-18.89-.66-18.89-21.41,0-18.22,7.45-21.94,14.76-21.94,2.53,0,6.65.4,9.04,1.99v-9.84c0-3.46-1.46-4.92-4.39-4.92-5.05,0-12.77,1.2-17.16,2.79-1.86-3.06-2-8.51-2-9.58M65.44,68.36c-.93-.93-2.39-1.06-3.46-1.06-2.93,0-4.65,2.13-4.65,10.64s.67,9.58,3.99,9.58c.93,0,3.06-.27,3.86-1.33,0,0,.27-8.51.27-17.82"/><path d="M88.31,35.24c6.12-4.26,11.04-5.98,18.49-5.98s8.91,1.33,8.91,5.98c0,2.39-.27,5.98-1.46,9.31-1.86-.93-3.59-1.06-4.92-1.06-1.6,0-4.12.8-5.72,3.06l-.13,48.54q0,2.66-15.16,2.66v-62.51Z"/><path d="M119.96,36.84c0-1.6.27-3.46,1.46-4.39,1.6-1.33,9.58-3.19,23.41-3.19,9.04,0,13.57,4.26,13.57,16.23v6.92c0,22.74-.67,43.09-.67,43.09-4.52,2.66-11.17,4.26-19.15,4.26-9.84,0-18.88-.66-18.88-21.41,0-18.22,7.45-21.94,14.76-21.94,2.53,0,6.65.4,9.04,1.99v-9.84c0-3.46-1.46-4.92-4.39-4.92-5.05,0-12.77,1.2-17.16,2.79-1.86-3.06-2-8.51-2-9.58M143.51,68.36c-.93-.93-2.39-1.06-3.46-1.06-2.93,0-4.65,2.13-4.65,10.64s.66,9.58,3.99,9.58c.93,0,3.06-.27,3.86-1.33,0,0,.27-8.51.27-17.82"/><path d="M202.69,36.97c-2.53-1.86-4.65-3.59-9.84-3.59-9.44,0-20.22,3.99-20.22,33.12,0,26.6,6.92,29.39,17.82,29.39,5.19,0,9.44-2.39,12.5-4.92.53.93.8,2.13.8,3.06,0,1.73-6.38,5.99-13.7,5.99-12.9,0-21.81-2.53-21.81-34.05,0-33.12,13.43-36.71,24.21-36.71,5.59,0,11.17,2.79,11.17,4.26,0,1.06-.27,2.53-.93,3.46"/><path d="M234.21,29.26c13.43,0,20.75,8.11,20.75,35.38,0,23.27-7.98,35.11-21.28,35.11s-21.41-4.92-21.41-34.98c0-24.87,8.25-35.51,21.94-35.51M233.01,95.62c10.64,0,17.42-10.37,17.42-31.39,0-24.47-6.12-30.85-16.23-30.85s-17.55,8.51-17.55,32.18,5.99,30.06,16.36,30.06"/><path d="M286.88,29.26c13.43,0,20.75,8.11,20.75,35.38,0,23.27-7.98,35.11-21.28,35.11s-21.41-4.92-21.41-34.98c0-24.87,8.25-35.51,21.94-35.51M285.68,95.62c10.64,0,17.42-10.37,17.42-31.39,0-24.47-6.12-30.85-16.23-30.85s-17.55,8.51-17.55,32.18,5.99,30.06,16.36,30.06"/><path d="M325.71,96.29c0,1.46.27,1.46-4.39,1.46V2.39c0-2.39,1.07-2.39,4.39-2.39v96.29Z"/></svg>';
    }

    // ── Constructor ───────────────────────────────────────────
    public function __construct() {
        add_action( 'admin_menu',            [ $this, 'add_menu' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );
        add_action( 'admin_notices',         [ $this, 'admin_notices' ] );
        add_action( 'admin_post_caracool_onestep_save', [ $this, 'save_settings' ] );
        add_filter( 'caracool_plugins',      [ $this, 'presentarse' ] );

        $settings = $this->get_settings();

        if ( ! empty( $settings['comments_disabled'] ) ) {
            $this->comments_bootstrap();
        }

        // Prioridad 0: que se ejecute antes que cualquier otra cosa enganchada
        // a template_redirect (redirecciones canónicas, SEO, cache, etc.).
        add_action( 'template_redirect', [ $this, 'maintenance_maybe_render' ], 0 );

        $this->snippets_bootstrap( $settings );

        if ( ! empty( $settings['duplicate_enabled'] ) ) {
            $this->duplicate_bootstrap();
        }

        if ( ! empty( $settings['claude_enabled'] ) ) {
            $this->claude_bootstrap();
        }
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

            // Código personalizado (snippets de head/footer)
            'snippets' => [],

            // Duplicar (páginas y entradas)
            'duplicate_enabled'      => false,
            'duplicate_title_prefix' => 'Copia de ',

            // Conexión con Claude
            'claude_enabled' => false,
            'claude_user_id' => 0,
        ] );
    }

    // ── Guardar ajustes ───────────────────────────────────────
    // Hay dos <form> en la página de ajustes: el principal (Comentarios +
    // Mantenimiento) y el de Código personalizado, que va aparte porque su
    // lista de snippets se edita como bloques repetibles. Ambos apuntan a
    // esta misma acción, así que partimos siempre de los ajustes actuales y
    // solo sobrescribimos el subconjunto de claves del formulario enviado
    // (marcado con el campo oculto "co_form"), para no borrar sin querer la
    // otra pestaña.
    public function save_settings() {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'No autorizado.' );
        check_admin_referer( 'caracool_onestep_save' );

        $current = $this->get_settings();
        $form    = sanitize_key( wp_unslash( $_POST['co_form'] ?? 'main' ) );

        if ( 'snippets' === $form ) {
            $overrides = [
                'snippets' => $this->snippets_parse_from_post(),
            ];
        } else {
            $ip_raw  = sanitize_textarea_field( wp_unslash( $_POST['maintenance_ip_whitelist'] ?? '' ) );
            $ip_list = array_filter( array_map( 'trim', preg_split( '/[\r\n,]+/', $ip_raw ) ) );
            $ip_list = array_values( array_unique( array_filter( $ip_list, function ( $ip ) {
                return (bool) filter_var( $ip, FILTER_VALIDATE_IP );
            } ) ) );

            $status = absint( $_POST['maintenance_http_status'] ?? 503 );
            if ( ! in_array( $status, [ 200, 503, 404, 301 ], true ) ) $status = 503;

            $claude_user = absint( $_POST['claude_user_id'] ?? 0 );
            if ( $claude_user && ! user_can( $claude_user, 'edit_pages' ) ) $claude_user = 0;

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

                'duplicate_enabled'           => ! empty( $_POST['duplicate_enabled'] ),
                'duplicate_title_prefix'      => sanitize_text_field( wp_unslash( $_POST['duplicate_title_prefix'] ?? '' ) ),

                'claude_enabled'              => ! empty( $_POST['claude_enabled'] ),
                'claude_user_id'              => $claude_user,
            ];
        }

        update_option( self::OPTION_KEY, array_merge( $current, $overrides ) );

        // Recordamos en qué pestaña estaba para no devolver siempre a
        // "Comentarios" tras guardar — ver el campo oculto "co_active_tab"
        // en el formulario principal, y el JS que lo mantiene actualizado.
        if ( 'snippets' === $form ) {
            $active_tab = 'tab-codigo';
        } else {
            $posted_tab = sanitize_key( wp_unslash( $_POST['co_active_tab'] ?? '' ) );
            $active_tab = in_array( $posted_tab, [ 'tab-comentarios', 'tab-mantenimiento', 'tab-duplicar', 'tab-claude' ], true ) ? $posted_tab : 'tab-comentarios';
        }

        wp_safe_redirect( admin_url( 'admin.php?page=' . CARACOOL_ONESTEP_SLUG . '&saved=1&tab=' . $active_tab ) );
        exit;
    }

    /**
     * Lee los bloques "snippets[IDX][campo]" del formulario de Código
     * personalizado y los convierte en el array que se guarda en 'snippets'.
     * Se descartan los bloques vacíos (sin nombre y sin código) para que
     * añadir una fila y no rellenarla no deje basura guardada.
     */
    private function snippets_parse_from_post() {
        $posted   = (array) ( $_POST['snippets'] ?? [] );
        $snippets = [];

        foreach ( $posted as $row ) {
            $name = sanitize_text_field( wp_unslash( $row['name'] ?? '' ) );
            $code = wp_unslash( $row['code'] ?? '' ); // no se sanea el HTML/JS a propósito, ver nota en snippets_bootstrap()

            if ( '' === trim( $name ) && '' === trim( $code ) ) continue; // fila vacía, se descarta

            $type       = in_array( $row['type'] ?? '', [ 'html', 'php' ], true ) ? $row['type'] : 'html';
            $location   = in_array( $row['location'] ?? '', [ 'head', 'footer' ], true ) ? $row['location'] : 'footer';
            $visibility = in_array( $row['visibility'] ?? '', [ 'all', 'all_except', 'only' ], true ) ? $row['visibility'] : 'all';

            $urls_raw = sanitize_textarea_field( wp_unslash( $row['urls'] ?? '' ) );
            $urls     = array_values( array_filter( array_map( 'trim', preg_split( '/[\r\n]+/', $urls_raw ) ) ) );

            $snippets[] = [
                'name'       => $name,
                'code'       => $code,
                'type'       => $type,
                'location'   => $location,
                'active'     => ! empty( $row['active'] ),
                'visibility' => $visibility,
                'urls'       => $urls,
            ];
        }

        return $snippets;
    }

    // ── Avisos admin ──────────────────────────────────────────
    public function admin_notices() {
        $screen = get_current_screen();
        if ( ! $screen || $screen->id !== CARACOOL_MENU_SLUG . '_page_' . CARACOOL_ONESTEP_SLUG ) return;

        if ( isset( $_GET['saved'] ) ) {
            echo '<div class="notice notice-success is-dismissible"><p>✅ Configuración guardada.</p></div>';
        }

        // Aviso de un solo uso: un snippet PHP falló y se desactivó solo
        // (ver snippet_disable_and_log()). Se borra al mostrarlo para que
        // no reaparezca en cada carga del admin.
        $php_error = get_option( 'caracool_onestep_php_error' );
        if ( $php_error ) {
            delete_option( 'caracool_onestep_php_error' );
            printf(
                '<div class="notice notice-error"><p>⚠️ El snippet PHP «%s» ha dado un error y se ha desactivado automáticamente para no dejar la web caída: <code>%s</code>. Corrígelo en la pestaña Código y vuelve a activarlo.</p></div>',
                esc_html( $php_error['name'] ?? 'sin nombre' ),
                esc_html( $php_error['message'] ?? '' )
            );
        }
    }

    // ── Menú admin ────────────────────────────────────────────
    // Submenú del menú compartido "Caracool" (ver inc/caracool-menu.php). El
    // slug de la página no cambia, así que ningún enlace guardado ni ningún
    // admin.php?page=caracool-onestep se rompe.
    public function add_menu() {
        add_submenu_page(
            CARACOOL_MENU_SLUG,
            'Caracool OneStep',
            'OneStep',
            'manage_options',
            CARACOOL_ONESTEP_SLUG,
            [ $this, 'render_settings_page' ]
        );
    }

    /**
     * Se presenta en la portada del menú compartido "Caracool" (filtro
     * `caracool_plugins`, ver inc/caracool-menu.php).
     */
    public function presentarse( $lista ) {
        $lista[] = [
            'nombre'  => 'OneStep',
            'pagina'  => CARACOOL_ONESTEP_SLUG,
            'version' => CARACOOL_ONESTEP_VERSION,
            'resumen' => 'Comentarios, mantenimiento, código personalizado y conexión con Claude',
        ];
        return $lista;
    }

    public function enqueue_admin_scripts( $hook ) {
        if ( $hook !== CARACOOL_MENU_SLUG . '_page_' . CARACOOL_ONESTEP_SLUG ) return;
        wp_enqueue_media();
        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_script( 'wp-color-picker' );
    }

    // ── Página de ajustes ─────────────────────────────────────
    public function render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) return;

        $s = $this->get_settings();

        // Qué pestaña mostrar activa al cargar: la que venga en la URL tras
        // guardar (ver save_settings()), o "Comentarios" por defecto.
        $co_valid_tabs = [ 'tab-comentarios', 'tab-mantenimiento', 'tab-codigo', 'tab-duplicar', 'tab-claude' ];
        $active_tab    = isset( $_GET['tab'] ) && in_array( $_GET['tab'], $co_valid_tabs, true ) ? sanitize_key( $_GET['tab'] ) : 'tab-comentarios';
        $co_main_tab   = in_array( $active_tab, [ 'tab-mantenimiento', 'tab-duplicar', 'tab-claude' ], true ) ? $active_tab : 'tab-comentarios'; // valor inicial del campo oculto del form principal
        ?>
        <div class="wrap co-wrap">
        <style>
            .co-wrap{
                --co-bg:#f6f5f2; --co-panel:#ffffff; --co-border:#e7e4de;
                --co-ink:#1c1b19; --co-ink-soft:#6b6660; --co-ink-faint:#a29c93;
                --co-accent:#c1502e; --co-accent-soft:#f4e3dc; --co-accent-ink:#7a3319;
                --co-ok:#2f7a4f; --co-ok-soft:#e3f1e8;
                --co-radius-lg:16px; --co-radius-md:10px; --co-radius-sm:7px;
                --co-shadow:0 1px 2px rgba(28,27,25,.04), 0 8px 24px -12px rgba(28,27,25,.12);
                --co-mono: ui-monospace,"SF Mono","Cascadia Code",Menlo,Consolas,monospace;
                max-width:900px;
            }
            .co-wrap *{ box-sizing:border-box; }
            .co-head{ display:flex; align-items:center; justify-content:space-between; gap:20px; margin:18px 0 26px; flex-wrap:wrap; }
            .co-head-id{ display:flex; align-items:center; gap:14px; }
            .co-logo{ width:100px; height:auto; display:block; fill:var(--co-ink); flex-shrink:0; }
            .co-head-titles h1{ margin:0; padding:0; font-size:20px; font-weight:650; letter-spacing:-.01em; line-height:1.3; }
            .co-ver-pill{ font-size:11px; font-weight:600; color:var(--co-ink-soft); background:var(--co-panel); border:1px solid var(--co-border); padding:3px 9px; border-radius:999px; letter-spacing:.02em; margin-left:6px; vertical-align:2px; }
            .co-head-sub{ margin:4px 0 0; font-size:13px; color:var(--co-ink-soft); }
            .co-status-chip{ display:inline-flex; align-items:center; gap:7px; font-size:12.5px; font-weight:600; color:var(--co-ok); background:var(--co-ok-soft); border-radius:999px; padding:6px 12px 6px 10px; white-space:nowrap; }
            .co-status-chip .co-dot{ width:6px; height:6px; border-radius:50%; background:var(--co-ok); }

            .co-tabs{ display:flex; gap:4px; background:var(--co-panel); border:1px solid var(--co-border); border-radius:var(--co-radius-md); padding:4px; margin-bottom:22px; width:max-content; }
            .co-tab{ display:flex; align-items:center; gap:7px; border:none; background:transparent; font:inherit; font-size:13.5px; font-weight:560; color:var(--co-ink-soft); padding:8px 14px; border-radius:7px; cursor:pointer; transition:background .12s,color .12s; }
            .co-tab svg{ width:16px; height:16px; opacity:.75; }
            .co-tab:hover{ background:#f1efeb; color:var(--co-ink); }
            .co-tab.active{ background:var(--co-ink); color:#fff; }
            .co-tab.active svg{ opacity:1; }

            .co-panel{ display:none; }
            .co-panel.active{ display:block; }

            .co-card{ background:var(--co-panel); border:1px solid var(--co-border); border-radius:var(--co-radius-lg); box-shadow:var(--co-shadow); padding:24px 26px; margin-bottom:16px; }
            .co-card-head{ display:flex; align-items:center; gap:11px; margin-bottom:4px; }
            .co-card-icon{ width:32px; height:32px; border-radius:9px; background:var(--co-accent-soft); color:var(--co-accent-ink); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
            .co-card-icon svg{ width:17px; height:17px; }
            .co-card-head h2{ margin:0; padding:0; border:0; font-size:14.5px; font-weight:640; letter-spacing:-.005em; }
            .co-card-desc{ margin:10px 0 0; font-size:13px; line-height:1.55; color:var(--co-ink-soft); }
            .co-card-desc code{ background:#f1efeb; border-radius:4px; padding:1px 5px; font-size:12px; font-family:var(--co-mono); }

            .co-toggle-row{ display:flex; align-items:flex-start; justify-content:space-between; gap:20px; padding-top:16px; }
            .co-toggle-row .co-label{ font-size:14px; font-weight:570; }
            .co-toggle-row .co-hint{ margin:4px 0 0; font-size:12.5px; color:var(--co-ink-soft); max-width:520px; line-height:1.5; }
            .co-switch{ position:relative; width:40px; height:24px; flex-shrink:0; margin-top:1px; display:block; cursor:pointer; }
            .co-switch.co-switch-sm{ width:32px; height:19px; margin-top:0; }
            .co-switch input{ opacity:0; width:0; height:0; position:absolute; }
            .co-switch .co-track{ position:absolute; inset:0; background:#dcd8d1; border-radius:999px; transition:.15s; }
            .co-switch .co-track::before{ content:""; position:absolute; width:18px; height:18px; left:3px; top:3px; background:#fff; border-radius:50%; transition:.15s; box-shadow:0 1px 2px rgba(0,0,0,.25); }
            .co-switch.co-switch-sm .co-track::before{ width:13px; height:13px; left:3px; top:3px; }
            .co-switch input:checked + .co-track{ background:var(--co-ink); }
            .co-switch input:checked + .co-track::before{ transform:translateX(16px); }
            .co-switch.co-switch-sm input:checked + .co-track::before{ transform:translateX(13px); }

            .co-field-grid{ display:grid; grid-template-columns:190px 1fr; gap:14px 18px; align-items:start; margin-top:18px; }
            .co-field-grid label{ font-size:13px; font-weight:570; padding-top:9px; }
            .co-field-grid .co-field-hint{ grid-column:2; margin:-6px 0 0; font-size:12px; color:var(--co-ink-faint); }
            .co-wrap input[type=text], .co-wrap input[type=url], .co-wrap select, .co-wrap textarea{
                width:100%; font:inherit; font-size:13.5px; padding:9px 11px;
                border:1px solid var(--co-border); border-radius:var(--co-radius-sm);
                background:#fdfcfb; color:var(--co-ink);
            }
            .co-wrap textarea.code{ font-family:var(--co-mono); font-size:12.5px; }
            .co-wrap input[type=text]:focus, .co-wrap input[type=url]:focus, .co-wrap select:focus, .co-wrap textarea:focus{
                outline:none; border-color:var(--co-ink); box-shadow:0 0 0 3px rgba(28,27,25,.08);
            }

            .co-pill-select{ display:inline-flex; flex-wrap:wrap; border:1px solid var(--co-border); border-radius:999px; padding:3px; gap:2px; background:#fdfcfb; }
            .co-pill-select label{ position:relative; font-size:12.5px; font-weight:560; color:var(--co-ink-soft); padding:6px 12px; border-radius:999px; cursor:pointer; }
            .co-pill-select label.on{ background:var(--co-ink); color:#fff; }
            .co-pill-select input{ position:absolute; opacity:0; width:0; height:0; }

            .co-snippet{ border:1px solid var(--co-border); border-radius:var(--co-radius-md); padding:16px 18px; margin-top:14px; }
            .co-snippet-top{ display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; }
            .co-snippet-top .co-name-input{ font-weight:600; max-width:280px; border:none; background:transparent; padding:6px 0; }
            .co-snippet-meta{ display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
            .co-snippet-toggle{ flex-shrink:0; }
            .co-snippet-toggle svg{ transition:transform .15s ease; }
            .co-snippet.co-collapsed .co-snippet-toggle svg{ transform:rotate(-90deg); }
            .co-snippet-body{ overflow:hidden; }
            .co-snippet.co-collapsed .co-snippet-body{ display:none; }
            .co-snippets-bulk-toggle{ background:none; border:none; padding:0; font-size:12.5px; font-weight:610; color:var(--co-ink-soft); cursor:pointer; text-decoration:underline; text-underline-offset:2px; }
            .co-snippets-bulk-toggle:hover{ color:var(--co-ink); }
            .co-badge{ font-size:11px; font-weight:650; padding:3px 8px; border-radius:999px; background:var(--co-accent-soft); color:var(--co-accent-ink); white-space:nowrap; }
            .co-badge.muted{ background:#efeceb; color:var(--co-ink-soft); }
            .co-badge-php{ background:#3b2f6b; color:#fff; }
            .co-icon-btn{ width:28px; height:28px; border-radius:7px; border:1px solid var(--co-border); background:#fff; display:flex; align-items:center; justify-content:center; cursor:pointer; color:var(--co-ink-soft); }
            .co-icon-btn:hover{ color:#b8382c; border-color:#e7c3bb; }
            .co-icon-btn svg{ width:14px; height:14px; }
            .co-snippet-type-row{ margin-top:12px; max-width:340px; }
            .co-snippet-type-row label{ display:block; font-size:11.5px; font-weight:610; color:var(--co-ink-soft); margin-bottom:4px; text-transform:uppercase; letter-spacing:.03em; }
            .co-warn-box{ margin-top:10px; padding:10px 14px; background:#fdf3e3; border:1px solid #eccf94; border-radius:var(--co-radius-sm); font-size:12.5px; line-height:1.5; color:#7a5a12; }
            .co-warn-box code{ background:rgba(122,90,18,.1); border-radius:4px; padding:1px 5px; font-size:11.5px; font-family:var(--co-mono); }
            .co-snippet-fields{ display:grid; grid-template-columns:1fr 1fr; gap:10px 14px; margin-top:12px; }
            .co-snippet-fields .co-full{ grid-column:1 / -1; }
            .co-snippet-fields label{ display:block; font-size:11.5px; font-weight:610; color:var(--co-ink-soft); margin-bottom:4px; text-transform:uppercase; letter-spacing:.03em; }
            .co-field-hint{ font-size:12px; color:var(--co-ink-faint); }
            .co-field-hint code{ background:#f1efeb; border-radius:4px; padding:1px 5px; font-size:11px; font-family:var(--co-mono); }

            .co-btn-row{ display:flex; align-items:center; gap:10px; margin-top:22px; flex-wrap:wrap; }
            .co-btn{ font:inherit; font-size:13.5px; font-weight:610; padding:10px 18px; border-radius:999px; border:1px solid transparent; cursor:pointer; display:inline-flex; align-items:center; gap:7px; text-decoration:none; }
            .co-btn-primary{ background:var(--co-ink); color:#fff; }
            .co-btn-primary:hover{ background:#000; color:#fff; }
            .co-btn-ghost{ background:transparent; border-color:var(--co-border); color:var(--co-ink); }
            .co-btn-ghost:hover{ background:#f1efeb; color:var(--co-ink); }
            .co-btn svg{ width:14px; height:14px; }

            .co-foot-credit{ text-align:center; font-size:11.5px; color:var(--co-ink-faint); margin-top:34px; }
        </style>

        <div class="co-head">
            <div class="co-head-id">
                <?php echo self::logo_svg(); // phpcs:ignore -- markup fijo, sin datos de usuario ?>
                <div class="co-head-titles">
                    <h1>OneStep <span class="co-ver-pill">v<?php echo esc_html( CARACOOL_ONESTEP_VERSION ); ?></span></h1>
                    <p class="co-head-sub">Comentarios, mantenimiento, código personalizado y conexión con Claude — un plugin, sin dependencias</p>
                </div>
            </div>
            <div class="co-status-chip"><span class="co-dot"></span>Activo</div>
        </div>

        <div class="co-tabs">
            <button type="button" class="co-tab <?php echo 'tab-comentarios' === $active_tab ? 'active' : ''; ?>" data-co-tab="tab-comentarios">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Comentarios
            </button>
            <button type="button" class="co-tab <?php echo 'tab-mantenimiento' === $active_tab ? 'active' : ''; ?>" data-co-tab="tab-mantenimiento">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Mantenimiento
            </button>
            <button type="button" class="co-tab <?php echo 'tab-codigo' === $active_tab ? 'active' : ''; ?>" data-co-tab="tab-codigo">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m16 18 6-6-6-6M8 6l-6 6 6 6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Código
            </button>
            <button type="button" class="co-tab <?php echo 'tab-duplicar' === $active_tab ? 'active' : ''; ?>" data-co-tab="tab-duplicar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Duplicar
            </button>
            <button type="button" class="co-tab <?php echo 'tab-claude' === $active_tab ? 'active' : ''; ?>" data-co-tab="tab-claude">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71" stroke-linecap="round" stroke-linejoin="round"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Claude
            </button>
        </div>

            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <?php wp_nonce_field( 'caracool_onestep_save' ); ?>
                <input type="hidden" name="action" value="caracool_onestep_save">
                <input type="hidden" name="co_form" value="main">
                <input type="hidden" name="co_active_tab" id="co-active-tab-input" value="<?php echo esc_attr( $co_main_tab ); ?>">

                <!-- ── TAB: COMENTARIOS ── -->
                <div id="tab-comentarios" class="co-tab-panel co-panel <?php echo 'tab-comentarios' === $active_tab ? 'active' : ''; ?>">
                    <div class="co-card">
                        <div class="co-card-head">
                            <div class="co-card-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </div>
                            <h2>Comentarios</h2>
                        </div>
                        <div class="co-toggle-row">
                            <div>
                                <div class="co-label">Desactivar comentarios en todo WordPress</div>
                                <p class="co-hint">Incluye REST y XML-RPC. Se cierran comentarios y pingbacks en todos los tipos de contenido, se ocultan del menú de administración, del panel de Ajustes → Comentarios, de la barra de admin y del escritorio, se bloquea el endpoint REST (<code>/wp/v2/comments</code>) y el método XML-RPC (<code>wp.newComment</code>), y se elimina el formulario de comentarios del tema.</p>
                            </div>
                            <label class="co-switch">
                                <input type="checkbox" name="comments_disabled" value="1" <?php checked( $s['comments_disabled'] ); ?>>
                                <span class="co-track"></span>
                            </label>
                        </div>
                    </div>

                    <div class="co-btn-row">
                        <button type="submit" class="co-btn co-btn-primary">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            Guardar configuración
                        </button>
                    </div>
                </div>

                <!-- ── TAB: MANTENIMIENTO ── -->
                <div id="tab-mantenimiento" class="co-tab-panel co-panel <?php echo 'tab-mantenimiento' === $active_tab ? 'active' : ''; ?>">

                    <div class="co-card">
                        <div class="co-card-head">
                            <div class="co-card-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </div>
                            <h2>Activación</h2>
                        </div>
                        <div class="co-toggle-row">
                            <div>
                                <div class="co-label">Poner toda la web en construcción</div>
                                <p class="co-hint">Solo el administrador logueado ve el sitio real; el resto ve la página de mantenimiento. wp-admin sigue siendo siempre accesible. El "quién puede ver el sitio" se ajusta más abajo.</p>
                            </div>
                            <label class="co-switch">
                                <input type="checkbox" name="maintenance_enabled" value="1" <?php checked( $s['maintenance_enabled'] ); ?>>
                                <span class="co-track"></span>
                            </label>
                        </div>

                        <div class="co-field-grid">
                            <label>Código HTTP</label>
                            <div class="co-pill-select" data-co-pill-group="maintenance_http_status">
                                <label class="<?php echo 503 == $s['maintenance_http_status'] ? 'on' : ''; ?>"><input type="radio" name="maintenance_http_status" value="503" <?php checked( $s['maintenance_http_status'], 503 ); ?>>503</label>
                                <label class="<?php echo 200 == $s['maintenance_http_status'] ? 'on' : ''; ?>"><input type="radio" name="maintenance_http_status" value="200" <?php checked( $s['maintenance_http_status'], 200 ); ?>>200</label>
                                <label class="<?php echo 404 == $s['maintenance_http_status'] ? 'on' : ''; ?>"><input type="radio" name="maintenance_http_status" value="404" <?php checked( $s['maintenance_http_status'], 404 ); ?>>404</label>
                                <label class="<?php echo 301 == $s['maintenance_http_status'] ? 'on' : ''; ?>"><input type="radio" name="maintenance_http_status" value="301" <?php checked( $s['maintenance_http_status'], 301 ); ?>>301</label>
                            </div>
                            <p class="co-field-hint">503 le dice a Google que vuelva más tarde sin des-indexar el sitio. 301 redirige a otra URL.</p>
                        </div>
                        <div class="co-field-grid" id="co-redirect-row" style="<?php echo $s['maintenance_http_status'] == 301 ? '' : 'display:none;'; ?>">
                            <label for="maintenance_redirect_url">URL de redirección</label>
                            <input type="url" name="maintenance_redirect_url" id="maintenance_redirect_url" value="<?php echo esc_attr( $s['maintenance_redirect_url'] ); ?>" placeholder="https://...">
                        </div>
                    </div>

                    <div class="co-card">
                        <div class="co-card-head">
                            <div class="co-card-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                            </div>
                            <h2>Quién puede ver el sitio</h2>
                        </div>
                        <div class="co-field-grid">
                            <label for="maintenance_bypass_role">Rol mínimo</label>
                            <select name="maintenance_bypass_role" id="maintenance_bypass_role">
                                <option value="administrator" <?php selected( $s['maintenance_bypass_role'], 'administrator' ); ?>>Solo administrador (recomendado)</option>
                                <option value="editor" <?php selected( $s['maintenance_bypass_role'], 'editor' ); ?>>Editor o superior</option>
                                <option value="author" <?php selected( $s['maintenance_bypass_role'], 'author' ); ?>>Autor o superior</option>
                                <option value="contributor" <?php selected( $s['maintenance_bypass_role'], 'contributor' ); ?>>Colaborador o superior</option>
                                <option value="subscriber" <?php selected( $s['maintenance_bypass_role'], 'subscriber' ); ?>>Suscriptor o superior</option>
                                <option value="" <?php selected( $s['maintenance_bypass_role'], '' ); ?>>Cualquier usuario que haya iniciado sesión</option>
                            </select>
                            <p class="co-field-hint">Por defecto solo el administrador logueado ve la web real; el resto de visitantes (incluidos usuarios sin sesión) ven la página de mantenimiento.</p>

                            <label for="maintenance_ip_whitelist">IPs permitidas <span style="font-weight:normal;">(opcional)</span></label>
                            <textarea name="maintenance_ip_whitelist" id="maintenance_ip_whitelist" rows="3" placeholder="Una IP por línea"><?php echo esc_textarea( implode( "\n", (array) $s['maintenance_ip_whitelist'] ) ); ?></textarea>
                            <p class="co-field-hint">Estas IPs ven el sitio normal aunque no hayan iniciado sesión (ej. la oficina de Caracool). Déjalo vacío si no lo necesitas.</p>
                        </div>
                    </div>

                    <div class="co-card">
                        <div class="co-card-head">
                            <div class="co-card-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>
                            </div>
                            <h2>Página de mantenimiento</h2>
                        </div>

                        <div class="co-toggle-row" style="padding-top:12px;">
                            <div>
                                <div class="co-label">Usar HTML personalizado</div>
                                <p class="co-hint">En lugar de la plantilla por defecto (logo, colores y texto de abajo).</p>
                            </div>
                            <label class="co-switch">
                                <input type="checkbox" name="maintenance_use_custom_html" id="maintenance_use_custom_html" value="1" <?php checked( $s['maintenance_use_custom_html'] ); ?>>
                                <span class="co-track"></span>
                            </label>
                        </div>

                        <div id="co-default-page-fields" class="co-field-grid" style="<?php echo $s['maintenance_use_custom_html'] ? 'display:none;' : ''; ?>">
                            <label for="maintenance_title">Título de la pestaña</label>
                            <input type="text" name="maintenance_title" id="maintenance_title" value="<?php echo esc_attr( $s['maintenance_title'] ); ?>" placeholder="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">

                            <label for="maintenance_heading">Titular</label>
                            <input type="text" name="maintenance_heading" id="maintenance_heading" value="<?php echo esc_attr( $s['maintenance_heading'] ); ?>" placeholder="Estamos preparando algo nuevo">

                            <label for="maintenance_message">Mensaje</label>
                            <textarea name="maintenance_message" id="maintenance_message" rows="3" placeholder="Volvemos enseguida. Gracias por tu paciencia."><?php echo esc_textarea( $s['maintenance_message'] ); ?></textarea>

                            <label for="maintenance_logo_id">Logo</label>
                            <div>
                                <input type="hidden" name="maintenance_logo_url" id="maintenance_logo_url" value="<?php echo esc_attr( $s['maintenance_logo_url'] ); ?>">
                                <button type="button" class="co-btn co-btn-ghost" id="co-select-logo" style="padding:7px 14px;font-size:12.5px;"><?php echo $s['maintenance_logo_url'] ? 'Cambiar logo' : 'Seleccionar logo'; ?></button>
                                <img id="co-logo-preview" src="<?php echo esc_url( $s['maintenance_logo_url'] ); ?>" style="max-height:40px;vertical-align:middle;margin-left:10px;border-radius:6px;<?php echo $s['maintenance_logo_url'] ? '' : 'display:none;'; ?>">
                            </div>

                            <label for="maintenance_bg_color">Color de fondo</label>
                            <input type="text" name="maintenance_bg_color" id="maintenance_bg_color" value="<?php echo esc_attr( $s['maintenance_bg_color'] ); ?>" class="co-color-picker">

                            <label for="maintenance_text_color">Color de texto</label>
                            <input type="text" name="maintenance_text_color" id="maintenance_text_color" value="<?php echo esc_attr( $s['maintenance_text_color'] ); ?>" class="co-color-picker">

                            <label for="maintenance_show_credit">Crédito Caracool</label>
                            <label style="display:flex;align-items:center;gap:8px;font-weight:400;padding-top:0;">
                                <input type="checkbox" name="maintenance_show_credit" value="1" <?php checked( $s['maintenance_show_credit'] ); ?> style="width:auto;"> Mostrar "Hecho con ❤️ por Caracool" al pie de la página
                            </label>
                        </div>

                        <div id="co-custom-html-field" style="<?php echo $s['maintenance_use_custom_html'] ? '' : 'display:none;'; ?>margin-top:14px;">
                            <textarea name="maintenance_custom_html" id="maintenance_custom_html" rows="12" class="code" placeholder="&lt;h1&gt;Volvemos pronto&lt;/h1&gt;"><?php echo esc_textarea( $s['maintenance_custom_html'] ); ?></textarea>
                            <p class="co-field-hint" style="margin-top:6px;">HTML completo de la página. Se sanea con las mismas reglas que el contenido de una entrada.</p>
                        </div>
                    </div>

                    <div class="co-btn-row">
                        <button type="submit" class="co-btn co-btn-primary">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            Guardar configuración
                        </button>
                        <a href="<?php echo esc_url( wp_nonce_url( home_url( '/' ), 'co_preview_maintenance', 'co_preview_maintenance' ) ); ?>" target="_blank" class="co-btn co-btn-ghost">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            Ver página de mantenimiento
                        </a>
                        <p class="co-field-hint" style="width:100%;margin-top:8px;">Se abre en una pestaña nueva, mostrando la página tal cual la vería un visitante — aunque tú, como administrador, normalmente la saltarías.</p>
                    </div>

                </div>

                <!-- ── TAB: DUPLICAR ── -->
                <div id="tab-duplicar" class="co-tab-panel co-panel <?php echo 'tab-duplicar' === $active_tab ? 'active' : ''; ?>">
                    <div class="co-card">
                        <div class="co-card-head">
                            <div class="co-card-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </div>
                            <h2>Duplicar</h2>
                        </div>
                        <div class="co-toggle-row">
                            <div>
                                <div class="co-label">Añadir enlace "Duplicar" a páginas y entradas</div>
                                <p class="co-hint">Aparece junto a Editar/Papelera en el listado de Páginas y Entradas. Crea una copia como borrador (título, contenido, extracto, imagen destacada, plantilla, taxonomías y campos personalizados) y te lleva directamente a editarla. No copia adjuntos, hijos ni comentarios, y no incluye la función de "republicar programado" — solo duplicar y editar.</p>
                            </div>
                            <label class="co-switch">
                                <input type="checkbox" name="duplicate_enabled" value="1" <?php checked( $s['duplicate_enabled'] ); ?>>
                                <span class="co-track"></span>
                            </label>
                        </div>

                        <div class="co-field-grid">
                            <label for="duplicate_title_prefix">Prefijo del título</label>
                            <input type="text" name="duplicate_title_prefix" id="duplicate_title_prefix" value="<?php echo esc_attr( $s['duplicate_title_prefix'] ); ?>" placeholder="Copia de ">
                            <p class="co-field-hint">Se añade delante del título original en la copia. Déjalo vacío para no añadir nada.</p>
                        </div>

                        <?php if ( defined( 'ICL_SITEPRESS_VERSION' ) ) : ?>
                        <p class="co-field-hint" style="margin-top:16px;">WPML detectado: la copia se etiqueta con el mismo idioma que el original, para que no aparezca en el idioma por defecto del sitio por error. No se duplican las demás traducciones — solo el idioma que estás viendo.</p>
                        <?php endif; ?>
                    </div>

                    <div class="co-btn-row">
                        <button type="submit" class="co-btn co-btn-primary">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            Guardar configuración
                        </button>
                    </div>
                </div>

                <!-- ── TAB: CLAUDE ── -->
                <?php
                $co_claude_users = get_users( [ 'capability' => 'edit_pages', 'orderby' => 'login', 'fields' => [ 'ID', 'user_login', 'display_name' ] ] );
                $co_claude_user  = $s['claude_user_id'] ? get_userdata( (int) $s['claude_user_id'] ) : null;
                $co_claude_log   = array_slice( (array) get_option( self::CLAUDE_LOG_KEY, [] ), 0, 10 );
                $co_app_pw_ok    = function_exists( 'wp_is_application_passwords_available' ) && wp_is_application_passwords_available();
                ?>
                <div id="tab-claude" class="co-tab-panel co-panel <?php echo 'tab-claude' === $active_tab ? 'active' : ''; ?>">
                    <div class="co-card">
                        <div class="co-card-head">
                            <div class="co-card-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71" stroke-linecap="round" stroke-linejoin="round"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </div>
                            <h2>Conexión con Claude</h2>
                        </div>
                        <div class="co-toggle-row">
                            <div>
                                <div class="co-label">Dejar que Claude edite las páginas de Elementor</div>
                                <p class="co-hint">Claude lee y guarda páginas de Elementor por la API de WordPress, sin que nadie tenga que tener la sesión abierta. Entra con una contraseña de aplicación de un único usuario, el que elijas abajo, y solo toca lo que ese usuario ya puede editar. Antes de cada guardado deja una revisión, y no guarda si alguien ha cambiado la página después de que Claude la leyera o si la tiene abierta en el editor. Apagado, no añade nada a la web.</p>
                            </div>
                            <label class="co-switch">
                                <input type="checkbox" name="claude_enabled" value="1" <?php checked( $s['claude_enabled'] ); ?>>
                                <span class="co-track"></span>
                            </label>
                        </div>

                        <div class="co-field-grid">
                            <label for="claude_user_id">Usuario de Claude</label>
                            <select name="claude_user_id" id="claude_user_id">
                                <option value="0">— Elige un usuario —</option>
                                <?php foreach ( $co_claude_users as $co_u ) : ?>
                                    <option value="<?php echo (int) $co_u->ID; ?>" <?php selected( (int) $s['claude_user_id'], (int) $co_u->ID ); ?>><?php echo esc_html( $co_u->display_name . ' (' . $co_u->user_login . ')' ); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="co-field-hint">Mejor un usuario solo para esto (por ejemplo «claude»), para que en el historial se vea qué cambió Claude y poder cortarle el acceso sin tocar a nadie más. Con rol Editor solo edita páginas; como Administrador también puede actualizar plugins, temas y WordPress.</p>
                        </div>

                        <?php if ( ! $co_app_pw_ok ) : ?>
                            <p class="co-field-hint" style="margin-top:16px;color:#b42318;">⚠️ Las contraseñas de aplicación están desactivadas en esta web (las desactiva algún plugin de seguridad, o la web no va por HTTPS). Sin ellas Claude no puede conectarse.</p>
                        <?php elseif ( $co_claude_user ) : ?>
                            <p class="co-field-hint" style="margin-top:16px;">
                                Siguiente paso: <a href="<?php echo esc_url( admin_url( 'user-edit.php?user_id=' . (int) $co_claude_user->ID ) . '#application-passwords-section' ); ?>">crea una contraseña de aplicación para <?php echo esc_html( $co_claude_user->user_login ); ?></a> (llámala «Claude») y guárdala en el llavero del Mac. Se puede revocar desde ahí en cualquier momento.
                            </p>
                        <?php endif; ?>

                        <p class="co-field-hint" style="margin-top:12px;">Dirección de la conexión: <code><?php echo esc_html( rest_url( self::CLAUDE_NS . '/' ) ); ?></code>. Si al probarla responde 401 con la contraseña bien puesta, el hosting está quitando la cabecera <code>Authorization</code>: hay que dejarla pasar en el <code>.htaccess</code>.</p>
                        <p class="co-field-hint" style="margin-top:12px;"><?php echo defined( 'CARACOOL_MOTION_VERSION' )
                            ? 'Caracool Motion está activo (v' . esc_html( CARACOOL_MOTION_VERSION ) . '): Claude puede usar sus efectos de movimiento.'
                            : 'Esta web no tiene Caracool Motion: Claude no usará efectos de movimiento ni ajustes de Motion.'; ?></p>
                    </div>

                    <?php if ( $co_claude_log ) : ?>
                    <div class="co-card">
                        <div class="co-card-head"><h2>Últimos cambios de Claude</h2></div>
                        <table class="widefat striped" style="margin-top:8px;">
                            <thead><tr><th>Fecha</th><th>Qué</th><th>Página</th><th>Detalle</th></tr></thead>
                            <tbody>
                            <?php foreach ( $co_claude_log as $co_l ) : ?>
                                <tr>
                                    <td><?php echo esc_html( $co_l['fecha'] ?? '' ); ?></td>
                                    <td><?php echo esc_html( $co_l['accion'] ?? '' ); ?></td>
                                    <td><?php if ( ! empty( $co_l['post'] ) ) : ?><a href="<?php echo esc_url( get_edit_post_link( (int) $co_l['post'] ) ?: '#' ); ?>"><?php echo esc_html( ( $co_l['titulo'] ?? '' ) ?: '#' . (int) $co_l['post'] ); ?></a><?php endif; ?></td>
                                    <td><?php echo esc_html( $co_l['detalle'] ?? '' ); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>

                    <div class="co-btn-row">
                        <button type="submit" class="co-btn co-btn-primary">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            Guardar configuración
                        </button>
                    </div>
                </div>
            </form>

            <!-- ── TAB: CÓDIGO PERSONALIZADO ── -->
            <!-- Formulario aparte a propósito: la lista de snippets es de
                 tamaño variable y usa sus propios bloques repetibles. -->
            <div id="tab-codigo" class="co-tab-panel co-panel <?php echo 'tab-codigo' === $active_tab ? 'active' : ''; ?>">
                <div class="co-card">
                    <div class="co-card-head">
                        <div class="co-card-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m16 18 6-6-6-6M8 6l-6 6 6 6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </div>
                        <h2>Código personalizado</h2>
                    </div>
                    <p class="co-card-desc">
                        Inserta HTML, CSS o JavaScript (por ejemplo, el código de Google Analytics, Meta Pixel o Google Tag Manager) directamente en el <code>&lt;head&gt;</code> o antes de cerrar el <code>&lt;/body&gt;</code> del sitio, sin tocar el tema.
                        Un snippet inactivo, o si no hay ninguno guardado, no añade absolutamente nada al sitio — el módulo no engancha nada hasta que hay al menos uno activo.
                        También puedes marcar un snippet como tipo <strong>PHP</strong> para que se ejecute de verdad en el servidor (hooks de WooCommerce, campos personalizados, etc.) en vez de imprimirse en la página.
                    </p>
                    <p class="co-card-desc">
                        El código se pega tal cual, sin filtrar — igual que editar el tema directamente. Solo un administrador puede llegar hasta aquí, así que el riesgo es el mismo que el de cualquier cambio en el tema: revísalo antes de guardar.
                    </p>

                    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                        <?php wp_nonce_field( 'caracool_onestep_save' ); ?>
                        <input type="hidden" name="action" value="caracool_onestep_save">
                        <input type="hidden" name="co_form" value="snippets">

                        <div id="co-snippets-list">
                            <?php
                            $snippets = (array) $s['snippets'];
                            if ( ! $snippets ) $snippets = [ [] ]; // al menos un bloque vacío para empezar
                            foreach ( $snippets as $i => $snippet ) :
                                $snippet = wp_parse_args( $snippet, [
                                    'name' => '', 'code' => '', 'type' => 'html', 'location' => 'footer',
                                    'active' => false, 'visibility' => 'all', 'urls' => [],
                                ] );
                                $co_url_count = count( (array) $snippet['urls'] );
                                $co_is_php    = 'php' === $snippet['type'];
                                // Los snippets que ya traen código guardado arrancan
                                // plegados (para no saturar la pantalla si hay varios);
                                // un bloque vacío se muestra abierto, listo para escribir.
                                $co_start_collapsed = '' !== trim( $snippet['code'] );
                                ?>
                                <div class="co-snippet co-snippet-block<?php echo $co_start_collapsed ? ' co-collapsed' : ''; ?>">
                                    <div class="co-snippet-top">
                                        <button type="button" class="co-icon-btn co-snippet-toggle" title="Mostrar/ocultar código">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                        </button>
                                        <input type="text" name="snippets[<?php echo (int) $i; ?>][name]" value="<?php echo esc_attr( $snippet['name'] ); ?>" class="co-name-input" placeholder="Ej. Google Analytics">
                                        <div class="co-snippet-meta">
                                            <label class="co-switch co-switch-sm" title="Activo">
                                                <input type="checkbox" class="co-snippet-active" name="snippets[<?php echo (int) $i; ?>][active]" value="1" <?php checked( $snippet['active'] ); ?>>
                                                <span class="co-track"></span>
                                            </label>
                                            <span class="co-badge co-badge-active <?php echo $snippet['active'] ? '' : 'muted'; ?>"><?php echo $snippet['active'] ? 'Activo' : 'Inactivo'; ?></span>
                                            <span class="co-badge co-badge-type <?php echo $co_is_php ? 'co-badge-php' : 'muted'; ?>">
                                                <?php echo $co_is_php ? 'PHP' : ( 'head' === $snippet['location'] ? 'Head' : 'Footer' ); ?>
                                            </span>
                                            <span class="co-badge muted co-snippet-vis-badge" style="<?php echo $co_is_php ? 'display:none;' : ''; ?>">
                                                <?php
                                                if ( 'all_except' === $snippet['visibility'] ) echo 'Excluye ' . (int) $co_url_count . ' URL(s)';
                                                elseif ( 'only' === $snippet['visibility'] ) echo 'Solo ' . (int) $co_url_count . ' URL(s)';
                                                else echo 'Todo el sitio';
                                                ?>
                                            </span>
                                            <button type="button" class="co-icon-btn co-remove-snippet" title="Quitar este snippet">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0-1 14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2L4 6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="co-snippet-body">
                                        <div class="co-snippet-type-row">
                                            <label>Tipo</label>
                                            <select class="co-snippet-type" name="snippets[<?php echo (int) $i; ?>][type]">
                                                <option value="html" <?php selected( $snippet['type'], 'html' ); ?>>HTML / CSS / JS — se imprime en la página</option>
                                                <option value="php" <?php selected( $snippet['type'], 'php' ); ?>>PHP — se ejecuta en el servidor</option>
                                            </select>
                                        </div>

                                        <div class="co-warn-box co-snippet-php-warning" style="<?php echo $co_is_php ? '' : 'display:none;'; ?>">
                                            ⚠️ Este código se ejecuta como PHP real en el servidor, en cada carga de página — un error aquí es más delicado que en un snippet HTML/JS. No incluyas las etiquetas <code>&lt;?php</code> ni <code>?&gt;</code>, pega solo el código PHP. Si el snippet falla, OneStep lo desactiva solo para no dejar la web caída y te avisa arriba con el error — pero revísalo bien antes de guardar.
                                        </div>

                                        <textarea name="snippets[<?php echo (int) $i; ?>][code]" rows="5" class="code co-snippet-code" placeholder="<?php echo $co_is_php ? esc_attr( "add_action( 'init', function () {\n    // tu código PHP aquí (sin las etiquetas de apertura/cierre de PHP)\n} );" ) : esc_attr( "<div class=\"mi-bloque\">\n    ...\n</div>" ); ?>" style="margin-top:12px;"><?php echo esc_textarea( $snippet['code'] ); ?></textarea>

                                        <div class="co-snippet-fields co-snippet-html-fields" style="<?php echo $co_is_php ? 'display:none;' : ''; ?>">
                                            <div>
                                                <label>Ubicación</label>
                                                <select name="snippets[<?php echo (int) $i; ?>][location]">
                                                    <option value="head" <?php selected( $snippet['location'], 'head' ); ?>>Head (antes de &lt;/head&gt;)</option>
                                                    <option value="footer" <?php selected( $snippet['location'], 'footer' ); ?>>Footer (antes de &lt;/body&gt;)</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label>Dónde se muestra</label>
                                                <select class="co-snippet-visibility" name="snippets[<?php echo (int) $i; ?>][visibility]">
                                                    <option value="all" <?php selected( $snippet['visibility'], 'all' ); ?>>Todo el sitio</option>
                                                    <option value="all_except" <?php selected( $snippet['visibility'], 'all_except' ); ?>>Todo el sitio, excepto estas URLs</option>
                                                    <option value="only" <?php selected( $snippet['visibility'], 'only' ); ?>>Solo estas URLs</option>
                                                </select>
                                            </div>
                                            <div class="co-full co-snippet-urls-row" style="<?php echo 'all' === $snippet['visibility'] ? 'display:none;' : ''; ?>">
                                                <label>URLs (una por línea)</label>
                                                <textarea name="snippets[<?php echo (int) $i; ?>][urls]" rows="2" placeholder="/contacto/&#10;/blog/*"><?php echo esc_textarea( implode( "\n", (array) $snippet['urls'] ) ); ?></textarea>
                                                <p class="co-field-hint" style="margin-top:4px;">Ruta exacta (<code>/contacto/</code>) o con <code>*</code> al final para un grupo (<code>/blog/*</code>).</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="co-btn-row">
                            <button type="button" class="co-btn co-btn-ghost" id="co-add-snippet">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                Añadir snippet
                            </button>
                            <?php if ( count( $snippets ) > 1 ) : ?>
                                <button type="button" class="co-snippets-bulk-toggle" id="co-snippets-bulk-toggle" data-mode="expand">Expandir todos</button>
                            <?php endif; ?>
                        </div>

                        <div class="co-btn-row">
                            <button type="submit" class="co-btn co-btn-primary">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                Guardar código personalizado
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <p class="co-foot-credit">Hecho con ❤️ por Caracool</p>
        </div>

        <template id="co-snippet-template">
            <div class="co-snippet co-snippet-block">
                <div class="co-snippet-top">
                    <button type="button" class="co-icon-btn co-snippet-toggle" title="Mostrar/ocultar código">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                    <input type="text" name="snippets[__I__][name]" value="" class="co-name-input" placeholder="Ej. Google Analytics">
                    <div class="co-snippet-meta">
                        <label class="co-switch co-switch-sm" title="Activo">
                            <input type="checkbox" class="co-snippet-active" name="snippets[__I__][active]" value="1">
                            <span class="co-track"></span>
                        </label>
                        <span class="co-badge co-badge-active muted">Inactivo</span>
                        <span class="co-badge co-badge-type muted">Footer</span>
                        <span class="co-badge muted co-snippet-vis-badge">Todo el sitio</span>
                        <button type="button" class="co-icon-btn co-remove-snippet" title="Quitar este snippet">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0-1 14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2L4 6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                    </div>
                </div>

                <div class="co-snippet-body">
                    <div class="co-snippet-type-row">
                        <label>Tipo</label>
                        <select class="co-snippet-type" name="snippets[__I__][type]">
                            <option value="html" selected>HTML / CSS / JS — se imprime en la página</option>
                            <option value="php">PHP — se ejecuta en el servidor</option>
                        </select>
                    </div>

                    <div class="co-warn-box co-snippet-php-warning" style="display:none;">
                        ⚠️ Este código se ejecuta como PHP real en el servidor, en cada carga de página — un error aquí es más delicado que en un snippet HTML/JS. No incluyas las etiquetas <code>&lt;?php</code> ni <code>?&gt;</code>, pega solo el código PHP. Si el snippet falla, OneStep lo desactiva solo para no dejar la web caída y te avisa arriba con el error — pero revísalo bien antes de guardar.
                    </div>

                    <textarea name="snippets[__I__][code]" rows="5" class="code co-snippet-code" placeholder="&lt;div class=&quot;mi-bloque&quot;&gt;...&lt;/div&gt;" style="margin-top:12px;"></textarea>

                    <div class="co-snippet-fields co-snippet-html-fields">
                        <div>
                            <label>Ubicación</label>
                            <select name="snippets[__I__][location]">
                                <option value="head">Head (antes de &lt;/head&gt;)</option>
                                <option value="footer" selected>Footer (antes de &lt;/body&gt;)</option>
                            </select>
                        </div>
                        <div>
                            <label>Dónde se muestra</label>
                            <select class="co-snippet-visibility" name="snippets[__I__][visibility]">
                                <option value="all" selected>Todo el sitio</option>
                                <option value="all_except">Todo el sitio, excepto estas URLs</option>
                                <option value="only">Solo estas URLs</option>
                            </select>
                        </div>
                        <div class="co-full co-snippet-urls-row" style="display:none;">
                            <label>URLs (una por línea)</label>
                            <textarea name="snippets[__I__][urls]" rows="2" placeholder="/contacto/&#10;/blog/*"></textarea>
                            <p class="co-field-hint" style="margin-top:4px;">Ruta exacta (<code>/contacto/</code>) o con <code>*</code> al final para un grupo (<code>/blog/*</code>).</p>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <script>
        (function () {
            // ── Tabs (vanilla JS, sin dependencias) ────────────
            var tabs        = document.querySelectorAll('.co-tab');
            var panels      = document.querySelectorAll('.co-panel');
            var activeInput = document.getElementById('co-active-tab-input');
            tabs.forEach(function (tab) {
                tab.addEventListener('click', function () {
                    tabs.forEach(function (t) { t.classList.remove('active'); });
                    panels.forEach(function (p) { p.classList.remove('active'); });
                    tab.classList.add('active');
                    document.getElementById(tab.dataset.coTab).classList.add('active');
                    // Recuerda la pestaña en el campo oculto del formulario
                    // principal, para volver aquí tras guardar (solo aplica
                    // a Comentarios/Mantenimiento, que comparten ese <form>).
                    if (activeInput && tab.dataset.coTab !== 'tab-codigo') {
                        activeInput.value = tab.dataset.coTab;
                    }
                });
            });

            // ── Grupos de pill-select (radios) ──────────────────
            document.querySelectorAll('.co-pill-select').forEach(function (group) {
                var labels = group.querySelectorAll('label');
                group.querySelectorAll('input[type=radio]').forEach(function (input) {
                    input.addEventListener('change', function () {
                        labels.forEach(function (l) { l.classList.remove('on'); });
                        input.closest('label').classList.add('on');
                        if (group.dataset.coPillGroup === 'maintenance_http_status') {
                            var redirectRow = document.getElementById('co-redirect-row');
                            if (redirectRow) redirectRow.style.display = (input.value === '301') ? '' : 'none';
                        }
                    });
                });
            });

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

            // ── Código personalizado: mostrar/ocultar el textarea de URLs
            // según la visibilidad elegida, badge de activo/inactivo, y
            // bloques de snippet repetibles ──
            function coBindSnippetBlock(block) {
                var toggleBtn = block.querySelector('.co-snippet-toggle');
                if (toggleBtn) {
                    toggleBtn.addEventListener('click', function () {
                        block.classList.toggle('co-collapsed');
                    });
                }

                var visSelect = block.querySelector('.co-snippet-visibility');
                var urlsRow   = block.querySelector('.co-snippet-urls-row');
                if (visSelect && urlsRow) {
                    visSelect.addEventListener('change', function () {
                        urlsRow.style.display = (this.value === 'all') ? 'none' : '';
                    });
                }
                var activeInput = block.querySelector('.co-snippet-active');
                var activeBadge = block.querySelector('.co-badge-active');
                if (activeInput && activeBadge) {
                    activeInput.addEventListener('change', function () {
                        activeBadge.textContent = this.checked ? 'Activo' : 'Inactivo';
                        activeBadge.classList.toggle('muted', ! this.checked);
                    });
                }

                // ── Tipo de snippet: HTML/JS (se imprime) vs PHP (se ejecuta) ──
                var typeSelect  = block.querySelector('.co-snippet-type');
                var typeBadge   = block.querySelector('.co-badge-type');
                var visBadge    = block.querySelector('.co-snippet-vis-badge');
                var phpWarning  = block.querySelector('.co-snippet-php-warning');
                var htmlFields  = block.querySelector('.co-snippet-html-fields');
                var codeField   = block.querySelector('.co-snippet-code');
                var locationSel = block.querySelector('select[name$="[location]"]');
                if (typeSelect) {
                    typeSelect.addEventListener('change', function () {
                        var isPhp = (this.value === 'php');
                        if (phpWarning) phpWarning.style.display = isPhp ? '' : 'none';
                        if (htmlFields) htmlFields.style.display = isPhp ? 'none' : '';
                        if (visBadge)   visBadge.style.display   = isPhp ? 'none' : '';
                        if (typeBadge) {
                            typeBadge.classList.toggle('co-badge-php', isPhp);
                            if (isPhp) {
                                typeBadge.textContent = 'PHP';
                            } else {
                                typeBadge.textContent = (locationSel && locationSel.value === 'head') ? 'Head' : 'Footer';
                            }
                        }
                        if (codeField) {
                            codeField.placeholder = isPhp
                                ? "add_action( 'init', function () {\n    // tu código PHP aquí (sin las etiquetas de apertura/cierre de PHP)\n} );"
                                : "<div class=\"mi-bloque\">\n    ...\n</div>";
                        }
                    });
                }
                if (locationSel && typeBadge) {
                    locationSel.addEventListener('change', function () {
                        if (! typeBadge.classList.contains('co-badge-php')) {
                            typeBadge.textContent = (this.value === 'head') ? 'Head' : 'Footer';
                        }
                    });
                }

                var removeBtn = block.querySelector('.co-remove-snippet');
                if (removeBtn) {
                    removeBtn.addEventListener('click', function () {
                        var list = document.getElementById('co-snippets-list');
                        // Deja siempre al menos un bloque, para que el usuario no
                        // se quede sin ningún campo donde escribir.
                        if (list.querySelectorAll('.co-snippet-block').length > 1) {
                            block.remove();
                        } else {
                            block.querySelectorAll('input[type=text], textarea').forEach(function (f) { f.value = ''; });
                            block.querySelectorAll('input[type=checkbox]').forEach(function (f) { f.checked = false; });
                            if (typeSelect) { typeSelect.value = 'html'; typeSelect.dispatchEvent(new Event('change')); }
                        }
                    });
                }
            }
            document.querySelectorAll('#co-snippets-list .co-snippet-block').forEach(coBindSnippetBlock);

            // ── Expandir/Contraer todos los snippets a la vez ──────
            function coBindBulkToggle(btn) {
                btn.addEventListener('click', function () {
                    var list = document.getElementById('co-snippets-list');
                    var blocks = list.querySelectorAll('.co-snippet-block');
                    var expand = btn.dataset.mode === 'expand';
                    blocks.forEach(function (b) { b.classList.toggle('co-collapsed', ! expand); });
                    btn.dataset.mode = expand ? 'collapse' : 'expand';
                    btn.textContent = expand ? 'Contraer todos' : 'Expandir todos';
                });
            }
            var bulkToggleBtn = document.getElementById('co-snippets-bulk-toggle');
            if (bulkToggleBtn) coBindBulkToggle(bulkToggleBtn);

            function coEnsureBulkToggle() {
                var list = document.getElementById('co-snippets-list');
                if (list.querySelectorAll('.co-snippet-block').length <= 1) return;
                bulkToggleBtn = document.getElementById('co-snippets-bulk-toggle');
                if (bulkToggleBtn) return;
                bulkToggleBtn = document.createElement('button');
                bulkToggleBtn.type = 'button';
                bulkToggleBtn.id = 'co-snippets-bulk-toggle';
                bulkToggleBtn.className = 'co-snippets-bulk-toggle';
                bulkToggleBtn.dataset.mode = 'expand';
                bulkToggleBtn.textContent = 'Expandir todos';
                var addBtn = document.getElementById('co-add-snippet');
                if (addBtn && addBtn.parentNode) addBtn.parentNode.appendChild(bulkToggleBtn);
                coBindBulkToggle(bulkToggleBtn);
            }

            var addSnippetBtn = document.getElementById('co-add-snippet');
            var snippetTemplate = document.getElementById('co-snippet-template');
            if (addSnippetBtn && snippetTemplate) {
                addSnippetBtn.addEventListener('click', function () {
                    var list = document.getElementById('co-snippets-list');
                    var idx  = list.querySelectorAll('.co-snippet-block').length;
                    var html = snippetTemplate.innerHTML.split('__I__').join(idx);
                    var wrapper = document.createElement('div');
                    wrapper.innerHTML = html.trim();
                    var block = wrapper.firstElementChild;
                    list.appendChild(block);
                    coBindSnippetBlock(block);
                    coEnsureBulkToggle();
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
                    $('#co-select-logo').text('Cambiar logo');
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

        // Vista previa: el botón "Ver página de mantenimiento" del admin
        // añade este nonce a la URL para forzar la página de mantenimiento
        // aunque el administrador normalmente la saltaría — así se puede
        // comprobar cómo queda (logo, colores, texto) sin cerrar sesión.
        if (
            isset( $_GET['co_preview_maintenance'] )
            && current_user_can( 'manage_options' )
            && wp_verify_nonce( wp_unslash( $_GET['co_preview_maintenance'] ), 'co_preview_maintenance' )
        ) {
            $this->maintenance_render_page( $s );
            exit;
        }

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
    // MÓDULO: CÓDIGO PERSONALIZADO
    // Dos tipos de snippet, guardados por el admin:
    //  - "html": se imprime tal cual en el head o el footer (sin wp_kses_post
    //    ni similar, igual que el HTML personalizado de mantenimiento —
    //    filtrarlo rompería el propio propósito del módulo).
    //  - "php": se ejecuta de verdad en el servidor vía eval(), envuelto en
    //    un try/catch para que un error en el snippet no tire toda la web
    //    abajo (ver snippets_run_php más abajo).
    // Coste cero si no hay ningún snippet activo: no se engancha nada en
    // wp_head/wp_footer/plugins_loaded a menos que exista al menos uno del
    // tipo correspondiente. Solo un administrador (manage_options) puede
    // llegar hasta aquí, así que el nivel de confianza es el mismo que
    // editar el tema directamente — no se expone a roles inferiores.
    // ─────────────────────────────────────────────────────────
    private $active_snippets = [];

    private function snippets_bootstrap( $settings ) {
        $snippets = is_array( $settings['snippets'] ?? null ) ? $settings['snippets'] : [];

        // Se preservan las claves originales (sin array_values) para poder
        // desactivar exactamente el snippet que falle, por índice, si un
        // snippet PHP lanza un error — ver snippet_disable_and_log().
        $active = array_filter( $snippets, function ( $snippet ) {
            return ! empty( $snippet['active'] ) && '' !== trim( $snippet['code'] ?? '' );
        } );

        if ( ! $active ) return; // nada activo → no se engancha nada, coste cero

        $this->active_snippets = $active;

        $has_html = false;
        $has_php  = false;
        foreach ( $active as $snippet ) {
            if ( 'php' === ( $snippet['type'] ?? 'html' ) ) $has_php = true;
            else $has_html = true;
        }

        if ( $has_html ) {
            add_action( 'wp_head', [ $this, 'snippets_print_head' ], 999 );
            add_action( 'wp_footer', [ $this, 'snippets_print_footer' ], 999 );
        }
        if ( $has_php ) {
            // 'plugins_loaded' → después de que WordPress y el resto de
            // plugins (WooCommerce incluido) ya se hayan cargado, para que
            // add_action/add_filter/WC() estén disponibles en el snippet.
            add_action( 'plugins_loaded', [ $this, 'snippets_run_php' ] );
        }
    }

    public function snippets_print_head() {
        $this->snippets_print_for_location( 'head' );
    }

    public function snippets_print_footer() {
        $this->snippets_print_for_location( 'footer' );
    }

    private function snippets_print_for_location( $location ) {
        foreach ( $this->active_snippets as $snippet ) {
            if ( 'php' === ( $snippet['type'] ?? 'html' ) ) continue; // los PHP no se imprimen, se ejecutan
            if ( ( $snippet['location'] ?? 'footer' ) !== $location ) continue;
            if ( ! $this->snippet_applies_to_current_url( $snippet ) ) continue;

            $label = $snippet['name'] ? $snippet['name'] : 'sin nombre';
            echo "\n<!-- Caracool OneStep · " . esc_html( $label ) . " -->\n" . $snippet['code'] . "\n";
        }
    }

    /**
     * Ejecuta los snippets de tipo "php". Cada uno se envuelve en un
     * try/catch(\Throwable): en PHP 7+ esto captura tanto errores de
     * sintaxis (ParseError) como la mayoría de errores en tiempo de
     * ejecución (TypeError, Error de función no definida, etc.), así que
     * un snippet mal escrito no debería poder tirar la web entera.
     *
     * Como red de seguridad adicional para el puñado de fallos que ni
     * siquiera un try/catch puede interceptar, se guarda en una option
     * temporal qué snippet se está ejecutando ahora mismo; si la petición
     * muere ahí sin que lleguemos a limpiarla, una shutdown function lo
     * detecta y desactiva ese snippet en concreto, para que la web se
     * recupere sola en la siguiente carga de página. Ver
     * snippets_shutdown_check().
     */
    public function snippets_run_php() {
        $php_indexes = [];
        foreach ( $this->active_snippets as $index => $snippet ) {
            if ( 'php' === ( $snippet['type'] ?? 'html' ) ) $php_indexes[] = $index;
        }
        if ( ! $php_indexes ) return;

        register_shutdown_function( [ $this, 'snippets_shutdown_check' ] );

        foreach ( $php_indexes as $index ) {
            $snippet = $this->active_snippets[ $index ];
            update_option( 'caracool_onestep_php_running', $index, false );
            try {
                eval( $snippet['code'] );
            } catch ( \Throwable $e ) {
                $this->snippet_disable_and_log( $index, $snippet, $e->getMessage() );
            }
        }

        delete_option( 'caracool_onestep_php_running' );
    }

    /**
     * Red de seguridad: si la petición murió a mitad de un eval() sin
     * pasar por el catch (un fallo no capturable como Throwable), la
     * option "caracool_onestep_php_running" se queda con el índice del
     * snippet que estaba en marcha. Se desactiva ese snippet y se guarda
     * el error para avisar en el admin.
     */
    public function snippets_shutdown_check() {
        $running = get_option( 'caracool_onestep_php_running', null );
        if ( null === $running || '' === $running ) return; // terminó bien, nada a medias

        $error = error_get_last();
        if ( $error && in_array( $error['type'], [ E_ERROR, E_PARSE, E_COMPILE_ERROR, E_USER_ERROR ], true ) ) {
            $snippet = $this->active_snippets[ $running ] ?? [ 'name' => '', 'code' => '' ];
            $this->snippet_disable_and_log( $running, $snippet, $error['message'] );
        }

        delete_option( 'caracool_onestep_php_running' );
    }

    /**
     * Desactiva (por índice) el snippet PHP que ha fallado y guarda el
     * error en una option de un solo uso para mostrarlo como aviso en el
     * admin (ver admin_notices()).
     */
    private function snippet_disable_and_log( $index, $snippet, $error_message ) {
        $settings = $this->get_settings();
        $snippets = (array) ( $settings['snippets'] ?? [] );

        if ( isset( $snippets[ $index ] ) ) {
            $snippets[ $index ]['active'] = false;
            update_option( self::OPTION_KEY, array_merge( $settings, [ 'snippets' => $snippets ] ) );
        }

        update_option( 'caracool_onestep_php_error', [
            'name'    => $snippet['name'] ? $snippet['name'] : 'sin nombre',
            'message' => $error_message,
        ] );
    }

    private function snippet_applies_to_current_url( $snippet ) {
        $visibility = $snippet['visibility'] ?? 'all';
        if ( 'all' === $visibility ) return true;

        $matches = $this->snippet_url_matches( (array) ( $snippet['urls'] ?? [] ) );

        if ( 'only' === $visibility ) return $matches;
        if ( 'all_except' === $visibility ) return ! $matches;
        return true;
    }

    /**
     * Compara la URL actual contra una lista de rutas. Cada patrón puede ser
     * una ruta exacta ("/contacto/") o terminar en "*" para un grupo entero
     * ("/blog/*"). Comparación simple a propósito — es lo que cubre el caso
     * de uso real (excluir o limitar a un puñado de páginas conocidas), no
     * pretende ser un sistema de reglas de condición avanzado.
     */
    private function snippet_url_matches( $patterns ) {
        $request_path = wp_parse_url( $_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH );
        $path = untrailingslashit( $request_path ?: '/' );
        if ( '' === $path ) $path = '/';

        foreach ( $patterns as $pattern ) {
            $pattern = trim( $pattern );
            if ( '' === $pattern ) continue;

            if ( '*' === substr( $pattern, -1 ) ) {
                $prefix = untrailingslashit( substr( $pattern, 0, -1 ) );
                if ( '' === $prefix || 0 === strpos( $path, $prefix ) ) return true;
            } elseif ( untrailingslashit( $pattern ) === $path ) {
                return true;
            }
        }

        return false;
    }

    // ─────────────────────────────────────────────────────────
    // MÓDULO: DUPLICAR
    // Un enlace "Duplicar" en el listado de Páginas y Entradas: crea una
    // copia en borrador y lleva directamente a editarla. Deliberadamente
    // simple — sin "republicar programado", sin copiar adjuntos/hijos/
    // comentarios, sin capability propia (usa el mismo edit_post de
    // siempre). Coste cero si el interruptor está desactivado.
    // ─────────────────────────────────────────────────────────
    const DUPLICATE_POST_TYPES = [ 'post', 'page' ];

    private function duplicate_bootstrap() {
        add_filter( 'post_row_actions', [ $this, 'duplicate_add_row_action' ], 10, 2 );
        add_filter( 'page_row_actions', [ $this, 'duplicate_add_row_action' ], 10, 2 );
        add_action( 'admin_action_caracool_onestep_duplicate', [ $this, 'duplicate_handle_action' ] );
    }

    public function duplicate_add_row_action( $actions, $post ) {
        if ( ! $post instanceof WP_Post ) return $actions;
        if ( ! in_array( $post->post_type, self::DUPLICATE_POST_TYPES, true ) ) return $actions;
        if ( ! current_user_can( 'edit_post', $post->ID ) ) return $actions;

        $url = wp_nonce_url(
            admin_url( 'admin.php?action=caracool_onestep_duplicate&post=' . $post->ID ),
            'caracool_onestep_duplicate_' . $post->ID
        );

        $actions['co_duplicate'] = '<a href="' . esc_url( $url ) . '">Duplicar</a>';

        return $actions;
    }

    public function duplicate_handle_action() {
        $post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;
        if ( ! $post_id ) wp_die( 'Falta el contenido a duplicar.' );

        check_admin_referer( 'caracool_onestep_duplicate_' . $post_id );

        if ( ! current_user_can( 'edit_post', $post_id ) ) wp_die( 'No autorizado.' );

        $original = get_post( $post_id );
        if ( ! $original || ! in_array( $original->post_type, self::DUPLICATE_POST_TYPES, true ) ) {
            wp_die( 'Contenido no válido para duplicar.' );
        }

        $new_id = $this->duplicate_create_copy( $original );
        if ( is_wp_error( $new_id ) ) {
            wp_die( esc_html( $new_id->get_error_message() ) );
        }

        // Si la página original se edita con Elementor ('_elementor_edit_mode'
        // = 'builder'), hay que llevar a la copia al editor de Elementor
        // (post.php?...&action=elementor), no al editor de bloques de
        // WordPress. Si se abre una página de Elementor en el editor de
        // bloques, WordPress no reconoce ninguna de las marcas de Gutenberg
        // en el HTML (Elementor no las usa) y mete todo el contenido en un
        // único bloque de texto/HTML clásico — el contenido en sí está
        // bien, pero se ve y se edita mal. Con esta comprobación la copia
        // se abre igual que se abriría el original, con sus secciones,
        // columnas y contenedores intactos. Coste cero si Elementor no está
        // instalado o la página no se hizo con él (get_post_meta devuelve
        // una cadena vacía y se usa el editor normal, como hasta ahora).
        if ( 'builder' === get_post_meta( $new_id, '_elementor_edit_mode', true ) ) {
            wp_safe_redirect( admin_url( 'post.php?post=' . $new_id . '&action=elementor' ) );
        } else {
            wp_safe_redirect( admin_url( 'post.php?action=edit&post=' . $new_id ) );
        }
        exit;
    }

    /**
     * Crea la copia: título (con prefijo), contenido, extracto, imagen
     * destacada, plantilla de página, taxonomías y post meta (salvo un
     * puñado de claves internas de WordPress que no tiene sentido
     * arrastrar). No copia adjuntos físicos, hijos ni comentarios — a
     * propósito, no es el caso de uso real de la agencia.
     */
    private function duplicate_create_copy( $original ) {
        $s      = $this->get_settings();
        $prefix = $s['duplicate_title_prefix'];

        // sanitize_text_field() recorta los espacios al guardar el ajuste,
        // así que "Copia de " se queda en "Copia de " -> "Copia de" y el
        // título resultante pegaría las dos palabras ("Copia deEjemplo").
        // Nos aseguramos aquí de que siempre haya exactamente un espacio
        // entre el prefijo y el título, sin depender de cómo se guardó.
        if ( '' !== $prefix && ' ' !== substr( $prefix, -1 ) ) {
            $prefix .= ' ';
        }

        // El contenido que vamos a copiar ya estaba guardado tal cual en el
        // original — no es entrada de un formulario que haya que sanear de
        // nuevo. Si dejamos que wp_insert_post() lo pase otra vez por KSES
        // (content_save_pre), KSES elimina los comentarios HTML del tipo
        // <!-- wp:group --> / <!-- /wp:columns --> que usa Gutenberg para
        // delimitar cada bloque/contenedor — el resultado visible es que la
        // copia se abre con todo el contenido metido en un único bloque de
        // texto en vez de conservar los bloques y contenedores originales.
        // kses_remove_filters() quita esos filtros solo para esta inserción
        // y kses_init_filters() los repone justo después, así que el resto
        // del sitio (formularios, comentarios, etc.) sigue tan protegido
        // como siempre.
        kses_remove_filters();

        $new_id = wp_insert_post( [
            'post_title'     => $prefix . $original->post_title,
            'post_content'   => $original->post_content,
            'post_excerpt'   => $original->post_excerpt,
            'post_status'    => 'draft',
            'post_type'      => $original->post_type,
            'post_parent'    => $original->post_parent,
            'menu_order'     => $original->menu_order,
            'post_author'    => $original->post_author,
            'comment_status' => $original->comment_status,
            'ping_status'    => $original->ping_status,
        ], true );

        kses_init_filters();

        if ( is_wp_error( $new_id ) ) return $new_id;

        // Imagen destacada y plantilla de página.
        $thumbnail_id = get_post_thumbnail_id( $original->ID );
        if ( $thumbnail_id ) set_post_thumbnail( $new_id, $thumbnail_id );

        $template = get_post_meta( $original->ID, '_wp_page_template', true );
        if ( $template ) update_post_meta( $new_id, '_wp_page_template', $template );

        // Taxonomías propias del tipo de contenido (categorías, etiquetas...).
        foreach ( get_object_taxonomies( $original->post_type ) as $taxonomy ) {
            $terms = wp_get_object_terms( $original->ID, $taxonomy, [ 'fields' => 'ids' ] );
            if ( ! is_wp_error( $terms ) && $terms ) {
                wp_set_object_terms( $new_id, $terms, $taxonomy );
            }
        }

        // Post meta, salvo claves internas ya gestionadas arriba o que no
        // tiene sentido copiar (bloqueo de edición, slug antiguo...).
        // '_elementor_css' se excluye a propósito: es la caché de Elementor
        // (qué fichero CSS y qué "huella" de datos ya tiene generados), y
        // apunta al fichero del post ORIGINAL (post-{ID}.css). Si se copia
        // tal cual, Elementor cree que la copia ya tiene su CSS listo y no
        // lo regenera — la copia se ve sin estilos (contenedores/columnas
        // amontonados como texto plano) hasta que se pulsa "Regenerar CSS"
        // a mano. Sin esta clave, Elementor genera su propio CSS la primera
        // vez que se abre o se visita la copia, como con cualquier página
        // nueva.
        //
        // Copiamos el post meta LEYENDO Y ESCRIBIENDO DIRECTAMENTE en la
        // tabla de la base de datos, en vez de con get_post_meta()/
        // add_post_meta(). Caso real detectado en molinadetapas.es: el
        // _elementor_data de una página de Elementor (la estructura interna
        // de secciones/columnas/widgets que usa Elementor) medía 49790
        // caracteres en la base de datos, pero get_post_meta() devolvía una
        // versión recortada a 49087 caracteres — casi con toda seguridad
        // por algún filtro de terceros de este hosting que toca las
        // lecturas de metadatos grandes a través de la API de WordPress
        // (el mismo tipo de "hardening" ya mencionado más arriba para el
        // contenido). Esa versión recortada es la que se copiaba a la
        // duplicada, dejando el JSON incompleto — Elementor no podía
        // interpretarlo y mostraba todo el contenido como un único bloque
        // de texto de repuesto. Leyendo y escribiendo el valor tal cual
        // está en la tabla, sin pasar por esa API, evitamos que cualquier
        // filtro de este tipo toque el contenido por el camino.
        global $wpdb;
        $skip_meta   = [ '_edit_lock', '_edit_last', '_wp_old_slug', '_wp_page_template', '_thumbnail_id', '_elementor_css' ];
        $meta_rows   = $wpdb->get_results( $wpdb->prepare(
            "SELECT meta_key, meta_value FROM {$wpdb->postmeta} WHERE post_id = %d",
            $original->ID
        ) );
        foreach ( $meta_rows as $meta_row ) {
            if ( in_array( $meta_row->meta_key, $skip_meta, true ) ) continue;
            $wpdb->insert( $wpdb->postmeta, [
                'post_id'    => $new_id,
                'meta_key'   => $meta_row->meta_key,
                'meta_value' => $meta_row->meta_value,
            ] );
        }
        // Como hemos escrito directamente en la tabla (sin pasar por
        // add_post_meta()), hay que invalidar la caché de metadatos de la
        // copia a mano — si no, una lectura posterior en esta misma carga
        // (por ejemplo, el aviso de Elementor al redirigir tras duplicar)
        // podría no ver todavía lo que acabamos de insertar.
        wp_cache_delete( $new_id, 'post_meta' );

        update_post_meta( $new_id, '_caracool_onestep_duplicate_of', $original->ID );

        $this->duplicate_wpml_tag_language( $new_id, $original->ID, $original->post_type );

        return $new_id;
    }

    /**
     * Si WPML está activo, etiqueta la copia con el mismo idioma que el
     * original — para que no aparezca en el idioma por defecto del sitio
     * por error. Deliberadamente NO duplica las demás traducciones (eso
     * es mucho más código y no es el caso de uso real de la agencia): la
     * copia queda como una entrada nueva e independiente, en el mismo
     * idioma que el original, sin formar parte de su grupo de traducción.
     * Usa la API pública documentada de WPML (filtro/acción), no accede a
     * sus internals — coste cero si WPML no está activo.
     */
    private function duplicate_wpml_tag_language( $new_id, $original_id, $post_type ) {
        if ( ! defined( 'ICL_SITEPRESS_VERSION' ) ) return;

        $element_type = 'post_' . $post_type;

        $original_lang = apply_filters( 'wpml_element_language_details', null, [
            'element_id'   => $original_id,
            'element_type' => $element_type,
        ] );

        if ( ! $original_lang || empty( $original_lang->language_code ) ) return;

        do_action( 'wpml_set_element_language_details', [
            'element_id'           => $new_id,
            'element_type'         => $element_type,
            'trid'                 => null, // grupo de traducción propio, no el del original
            'language_code'        => $original_lang->language_code,
            'source_language_code' => null,
        ] );
    }

    // ─────────────────────────────────────────────────────────
    // MÓDULO: CONEXIÓN CON CLAUDE (nuevo en 1.4.0)
    // ─────────────────────────────────────────────────────────
    // Endpoints REST bajo /wp-json/caracool/v1/ para que Claude lea y edite
    // páginas de Elementor sin tener la sesión de nadie abierta en un
    // navegador. Se autentica con una contraseña de aplicación de WordPress
    // (Usuarios → Perfil), de un único usuario elegido en la pestaña Claude.
    //
    // Reglas que el módulo hace cumplir por código, no por buena voluntad:
    // - Solo el usuario elegido puede usarlo, y solo en lo que ese usuario
    //   ya puede editar (edit_post).
    // - Para guardar hay que mandar la "version" (huella del _elementor_data)
    //   que se leyó. Si alguien ha guardado después, se rechaza con 409 en
    //   vez de pisarle el trabajo.
    // - Si alguien tiene la página abierta en el editor ahora mismo, se
    //   rechaza con 423 salvo que se pida "forzar".
    // - Antes de cada guardado se crea una revisión, que se puede restaurar.
    // - Se guarda con Document::save() de Elementor, igual que el botón
    //   Guardar del editor: regenera el CSS y vacía la caché de elementos.
    // Con el módulo apagado no se registra ni una ruta.

    private function claude_bootstrap() {
        self::claude_cabecera_alternativa();
        add_action( 'rest_api_init', [ $this, 'claude_register_routes' ] );
    }

    /**
     * Muchos hostings (Plesk, FastCGI) no pasan la cabecera Authorization a
     * PHP, y entonces WordPress no ve la contraseña de aplicación: responde
     * «no estás conectado» aunque sea correcta. Caso real: caracool.net.
     * Como no hay acceso al .htaccess, el MCP manda la misma credencial
     * también en X-Caracool-Auth, que sí llega, y aquí se copia donde
     * WordPress la busca. Solo en las rutas caracool/v1, solo con el módulo
     * encendido y solo si Authorization no ha llegado. La credencial la
     * sigue comprobando WordPress como siempre.
     */
    public static function claude_cabecera_alternativa() {
        if ( ! empty( $_SERVER['PHP_AUTH_USER'] ) || ! empty( $_SERVER['HTTP_AUTHORIZATION'] ) ) return;
        $h = isset( $_SERVER['HTTP_X_CARACOOL_AUTH'] ) ? (string) $_SERVER['HTTP_X_CARACOOL_AUTH'] : '';
        if ( 0 !== stripos( $h, 'Basic ' ) ) return;
        $uri = rawurldecode( (string) ( $_SERVER['REQUEST_URI'] ?? '' ) );
        if ( false === strpos( $uri, '/' . self::CLAUDE_NS . '/' ) ) return;
        $dec = base64_decode( substr( $h, 6 ), true );
        if ( false === $dec || false === strpos( $dec, ':' ) ) return;
        list( $usuario, $clave ) = explode( ':', $dec, 2 );
        $_SERVER['PHP_AUTH_USER'] = $usuario;
        $_SERVER['PHP_AUTH_PW']   = $clave;
    }

    public function claude_register_routes() {
        $ns   = self::CLAUDE_NS;
        $perm = [ $this, 'claude_permission' ];

        register_rest_route( $ns, '/estado', [
            'methods' => \WP_REST_Server::READABLE, 'callback' => [ $this, 'claude_estado' ], 'permission_callback' => $perm,
        ] );
        register_rest_route( $ns, '/registro', [
            'methods' => \WP_REST_Server::READABLE, 'callback' => [ $this, 'claude_ver_registro' ], 'permission_callback' => $perm,
        ] );
        register_rest_route( $ns, '/paginas', [
            'methods' => \WP_REST_Server::CREATABLE, 'callback' => [ $this, 'claude_crear_pagina' ], 'permission_callback' => $perm,
        ] );
        register_rest_route( $ns, '/motion', [
            'methods' => \WP_REST_Server::READABLE, 'callback' => [ $this, 'claude_motion' ], 'permission_callback' => $perm,
        ] );
        register_rest_route( $ns, '/elementor/kit', [
            'methods' => \WP_REST_Server::READABLE, 'callback' => [ $this, 'claude_kit' ], 'permission_callback' => $perm,
        ] );
        register_rest_route( $ns, '/elementor/cache', [
            'methods' => \WP_REST_Server::CREATABLE, 'callback' => [ $this, 'claude_limpiar_cache' ], 'permission_callback' => $perm,
        ] );
        register_rest_route( $ns, '/elementor/(?P<id>\d+)', [
            [ 'methods' => \WP_REST_Server::READABLE,  'callback' => [ $this, 'claude_leer' ],    'permission_callback' => $perm ],
            [ 'methods' => \WP_REST_Server::CREATABLE, 'callback' => [ $this, 'claude_guardar' ], 'permission_callback' => $perm ],
        ] );
        register_rest_route( $ns, '/elementor/(?P<id>\d+)/elemento/(?P<el>[A-Za-z0-9]+)', [
            [ 'methods' => \WP_REST_Server::READABLE,  'callback' => [ $this, 'claude_leer_elemento' ],    'permission_callback' => $perm ],
            [ 'methods' => \WP_REST_Server::CREATABLE, 'callback' => [ $this, 'claude_ajustar_elemento' ], 'permission_callback' => $perm ],
        ] );
        register_rest_route( $ns, '/elementor/(?P<id>\d+)/revisiones', [
            'methods' => \WP_REST_Server::READABLE, 'callback' => [ $this, 'claude_revisiones' ], 'permission_callback' => $perm,
        ] );
        register_rest_route( $ns, '/elementor/(?P<id>\d+)/revisiones/(?P<rev>\d+)', [
            'methods' => \WP_REST_Server::CREATABLE, 'callback' => [ $this, 'claude_restaurar' ], 'permission_callback' => $perm,
        ] );
        // Mantenimiento (1.5.0): solo si el usuario de Claude es Administrador.
        register_rest_route( $ns, '/actualizaciones', [
            'methods' => \WP_REST_Server::READABLE, 'callback' => [ $this, 'claude_actualizaciones' ], 'permission_callback' => $perm,
        ] );
        register_rest_route( $ns, '/actualizar', [
            'methods' => \WP_REST_Server::CREATABLE, 'callback' => [ $this, 'claude_actualizar' ], 'permission_callback' => $perm,
        ] );
        register_rest_route( $ns, '/plugins/instalar', [
            'methods' => \WP_REST_Server::CREATABLE, 'callback' => [ $this, 'claude_instalar_zip' ], 'permission_callback' => $perm,
        ] );
    }

    /**
     * Comprueba la contraseña de aplicación aquí mismo, con la función de
     * WordPress, si WordPress no lo ha hecho ya. Caso real (caracool.net,
     * 1.4.1): ni con una contraseña falsa respondía «contraseña incorrecta»,
     * señal de que el usuario actual se había decidido antes de que
     * WordPress supiera que era una petición REST (algún plugin pregunta
     * por el usuario demasiado pronto) y la contraseña no se llegaba a
     * mirar. Aquí, ya dentro de la REST, se valida y se fija el usuario.
     * Lee Authorization (ya traducida por PHP o en HTTP_AUTHORIZATION) o,
     * si el hosting la quita, X-Caracool-Auth.
     */
    public static function claude_autenticar() {
        $usuario = isset( $_SERVER['PHP_AUTH_USER'] ) ? (string) $_SERVER['PHP_AUTH_USER'] : '';
        $clave   = isset( $_SERVER['PHP_AUTH_PW'] ) ? (string) $_SERVER['PHP_AUTH_PW'] : '';
        if ( '' === $usuario ) {
            foreach ( [ 'HTTP_AUTHORIZATION', 'REDIRECT_HTTP_AUTHORIZATION', 'HTTP_X_CARACOOL_AUTH' ] as $k ) {
                $h = isset( $_SERVER[ $k ] ) ? (string) $_SERVER[ $k ] : '';
                if ( 0 !== stripos( $h, 'Basic ' ) ) continue;
                $dec = base64_decode( substr( $h, 6 ), true );
                if ( false === $dec || false === strpos( $dec, ':' ) ) continue;
                list( $usuario, $clave ) = explode( ':', $dec, 2 );
                break;
            }
        }
        if ( '' === $usuario ) {
            return new \WP_Error( 'co_sin_credencial', 'No ha llegado ninguna credencial a WordPress (ni Authorization ni X-Caracool-Auth).', [ 'status' => 401 ] );
        }
        if ( ! function_exists( 'wp_authenticate_application_password' ) ) {
            return new \WP_Error( 'co_sin_app_passwords', 'Este WordPress no tiene contraseñas de aplicación.', [ 'status' => 401 ] );
        }
        $user = wp_authenticate_application_password( null, $usuario, $clave );
        if ( $user instanceof \WP_User ) {
            wp_set_current_user( $user->ID );
            return $user;
        }
        if ( is_wp_error( $user ) ) {
            return new \WP_Error( 'co_credencial', 'WordPress rechaza la contraseña de aplicación de «' . $usuario . '» (' . $user->get_error_code() . ').', [ 'status' => 401 ] );
        }
        return new \WP_Error( 'co_credencial', 'WordPress no ha validado la contraseña de aplicación (¿están desactivadas para este usuario o para la API?).', [ 'status' => 401 ] );
    }

    /** Solo el usuario elegido en la pestaña Claude, y con permiso de editar páginas. */
    public function claude_permission() {
        if ( ! is_user_logged_in() ) {
            $autenticado = self::claude_autenticar();
            if ( is_wp_error( $autenticado ) ) return $autenticado;
        }
        $uid = (int) $this->get_settings()['claude_user_id'];
        if ( ! $uid ) {
            return new \WP_Error( 'co_sin_usuario', 'Falta elegir el usuario de Claude en Caracool → OneStep → Claude.', [ 'status' => 403 ] );
        }
        if ( get_current_user_id() !== $uid ) {
            return new \WP_Error( 'co_usuario', 'Este usuario no tiene permitida la conexión con Claude.', [ 'status' => 403 ] );
        }
        if ( ! current_user_can( 'edit_pages' ) ) {
            return new \WP_Error( 'co_permiso', 'El usuario de Claude no puede editar páginas.', [ 'status' => 403 ] );
        }
        return true;
    }

    // ── Lecturas ──────────────────────────────────────────────

    public function claude_estado() {
        $idiomas = null;
        if ( defined( 'ICL_SITEPRESS_VERSION' ) ) {
            $idiomas = array_keys( (array) apply_filters( 'wpml_active_languages', null, [ 'skip_missing' => 0 ] ) );
        }
        return [
            'web'           => home_url( '/' ),
            'onestep'       => CARACOOL_ONESTEP_VERSION,
            'wordpress'     => get_bloginfo( 'version' ),
            'elementor'     => defined( 'ELEMENTOR_VERSION' ) ? ELEMENTOR_VERSION : null,
            'elementor_pro' => defined( 'ELEMENTOR_PRO_VERSION' ) ? ELEMENTOR_PRO_VERSION : null,
            'wpml'          => defined( 'ICL_SITEPRESS_VERSION' ) ? ICL_SITEPRESS_VERSION : null,
            // Caracool Motion no está en todas las webs: null = no hay efectos de movimiento.
            'motion'        => $this->claude_hay_motion() ? CARACOOL_MOTION_VERSION : null,
            'idiomas'       => $idiomas,
            'idioma_defecto'=> $idiomas ? apply_filters( 'wpml_default_language', null ) : null,
            'usuario'       => wp_get_current_user()->user_login,
            // Actualizar plugins, temas y WordPress pide un usuario Administrador.
            'mantenimiento' => $this->claude_puede_mantener( 'update_plugins' ) === true,
        ];
    }

    public function claude_ver_registro() {
        return array_slice( (array) get_option( self::CLAUDE_LOG_KEY, [] ), 0, 50 );
    }

    /** GET /elementor/{id}: resumen del árbol (o el árbol entero con ?completo=1) y su "version". */
    public function claude_leer( \WP_REST_Request $request ) {
        $id   = (int) $request['id'];
        $post = $this->claude_post_editable( $id );
        if ( is_wp_error( $post ) ) return $post;

        $raw   = $this->claude_datos_crudos( $id );
        $arbol = $this->claude_arbol( $raw );
        $lock  = $this->claude_bloqueo( $id );

        return [
            'id'            => $id,
            'titulo'        => $post->post_title,
            'tipo'          => $post->post_type,
            'estado'        => $post->post_status,
            'url'           => get_permalink( $id ),
            'idioma'        => $this->claude_idioma( $id, $post->post_type ),
            'modificada'    => $post->post_modified,
            'con_elementor' => 'builder' === get_post_meta( $id, '_elementor_edit_mode', true ),
            'bloqueada_por' => $lock ? $lock->display_name : null,
            'version'       => $this->claude_huella( $raw ),
            'motion'        => $this->claude_hay_motion(),
            'avisos'        => self::claude_avisos( $arbol, $this->claude_hay_motion() ),
            'elementos'     => $request->get_param( 'completo' ) ? $arbol : self::claude_resumen( $arbol ),
        ];
    }

    /**
     * GET /motion: catálogo de Caracool Motion EN ESTA WEB (efectos de
     * contenedor con sus controles cm_, y variantes de botón). Motion no
     * está en todas las webs: si no está, lo dice y no hay que usar ningún
     * ajuste cm_ ni proponer efectos de movimiento.
     */
    public function claude_motion() {
        if ( ! $this->claude_hay_motion() ) {
            return [
                'motion' => null,
                'aviso'  => 'Esta web no tiene Caracool Motion. No hay efectos de movimiento: no uses ajustes cm_ ni propongas cortinas, entradas ni parallax.',
            ];
        }
        $out = [ 'motion' => CARACOOL_MOTION_VERSION, 'efectos' => null, 'botones' => null ];

        if ( class_exists( 'Caracool_Motion_Scroll' ) && method_exists( 'Caracool_Motion_Scroll', 'efectos' ) ) {
            $opciones = method_exists( 'Caracool_Motion_Scroll', 'opciones' ) ? (array) Caracool_Motion_Scroll::opciones() : [];
            $efectos  = [];
            foreach ( (array) Caracool_Motion_Scroll::efectos() as $clave => $fx ) {
                $ops = [];
                foreach ( (array) ( $fx['opciones'] ?? [] ) as $op ) {
                    $def   = (array) ( $opciones[ $op ] ?? [] );
                    $ops[] = array_filter( [
                        'control'  => 'cm_' . $op,
                        'etiqueta' => $def['etiqueta'] ?? $op,
                        'tipo'     => $def['tipo'] ?? 'select',
                        'valores'  => isset( $def['valores'] ) ? array_keys( (array) $def['valores'] ) : null,
                        'defecto'  => $def['defecto'] ?? null,
                        'min'      => $def['min'] ?? null,
                        'max'      => $def['max'] ?? null,
                    ], function ( $v ) { return null !== $v; } );
                }
                $efectos[] = [
                    'clave'    => $clave,
                    'etiqueta' => $fx['etiqueta'] ?? $clave,
                    'ayuda'    => $fx['ayuda'] ?? '',
                    'scroll'   => ! empty( $fx['scroll'] ),
                    'opciones' => $ops,
                ];
            }
            $out['efectos'] = [
                'donde'   => 'Solo en contenedores: ajuste "cm_efecto" con la clave del efecto, y sus opciones con el control indicado. Los colores, enlazados a globales en __globals__.',
                'lista'   => $efectos,
            ];
        }
        if ( class_exists( 'Caracool_Motion_Botones' ) && method_exists( 'Caracool_Motion_Botones', 'variantes' ) ) {
            $out['botones'] = [
                'donde'     => 'El barrido de color de los botones se enciende para toda la web en Caracool → Motion, no botón a botón. Cada botón necesita sus dos colores enlazados a globales.',
                'variantes' => array_keys( (array) Caracool_Motion_Botones::variantes() ),
            ];
        }
        return $out;
    }

    /** Si esta web tiene Caracool Motion activo. */
    private function claude_hay_motion() {
        return defined( 'CARACOOL_MOTION_VERSION' );
    }

    /** GET /elementor/{id}/elemento/{el}: un elemento con todos sus ajustes. */
    public function claude_leer_elemento( \WP_REST_Request $request ) {
        $id   = (int) $request['id'];
        $post = $this->claude_post_editable( $id );
        if ( is_wp_error( $post ) ) return $post;

        $raw = $this->claude_datos_crudos( $id );
        $el  = self::claude_encontrar( $this->claude_arbol( $raw ), (string) $request['el'] );
        if ( null === $el ) {
            return new \WP_Error( 'co_no_elemento', 'No hay ningún elemento con ese id en la página.', [ 'status' => 404 ] );
        }
        $hijos = [];
        foreach ( (array) ( $el['elements'] ?? [] ) as $hijo ) $hijos[] = $hijo['id'] ?? '';
        unset( $el['elements'] );
        $el['hijos'] = $hijos;

        return [ 'version' => $this->claude_huella( $raw ), 'elemento' => $el ];
    }

    /** GET /elementor/kit: colores, tipografías y puntos de interrupción del Kit activo. */
    public function claude_kit() {
        if ( ! $this->claude_elementor_listo() ) return $this->claude_sin_elementor();

        $km  = \Elementor\Plugin::$instance->kits_manager;
        $kit = method_exists( $km, 'get_active_kit_for_frontend' ) ? $km->get_active_kit_for_frontend() : $km->get_active_kit();
        if ( ! $kit ) return new \WP_Error( 'co_sin_kit', 'No hay Kit activo.', [ 'status' => 404 ] );

        $s = (array) $kit->get_settings();
        $colores = [];
        foreach ( array_merge( (array) ( $s['system_colors'] ?? [] ), (array) ( $s['custom_colors'] ?? [] ) ) as $c ) {
            $colores[] = [ 'id' => $c['_id'] ?? '', 'nombre' => $c['title'] ?? '', 'color' => $c['color'] ?? '' ];
        }
        $tipos = [];
        foreach ( array_merge( (array) ( $s['system_typography'] ?? [] ), (array) ( $s['custom_typography'] ?? [] ) ) as $t ) {
            $tipos[] = [
                'id'     => $t['_id'] ?? '',
                'nombre' => $t['title'] ?? '',
                'fuente' => $t['typography_font_family'] ?? '',
                'peso'   => $t['typography_font_weight'] ?? '',
            ];
        }
        return [
            'kit'         => $kit->get_id(),
            'colores'     => $colores,
            'tipografias' => $tipos,
            'puntos'      => $s['active_breakpoints'] ?? null,
            'como_enlazar'=> 'En __globals__ de un elemento: "globals/colors?id=<id>" y, para tipografía, la clave typography_typography con "globals/typography?id=<id>".',
        ];
    }

    public function claude_revisiones( \WP_REST_Request $request ) {
        $id   = (int) $request['id'];
        $post = $this->claude_post_editable( $id );
        if ( is_wp_error( $post ) ) return $post;

        $out = [];
        foreach ( wp_get_post_revisions( $id, [ 'posts_per_page' => 20 ] ) as $rev ) {
            $autor = get_userdata( $rev->post_author );
            $out[] = [ 'id' => $rev->ID, 'fecha' => $rev->post_modified, 'autor' => $autor ? $autor->display_name : '' ];
        }
        return $out;
    }

    // ── Escrituras ────────────────────────────────────────────

    /** POST /elementor/{id}: guarda el árbol entero. Body: elements, version, [estado], [forzar]. */
    public function claude_guardar( \WP_REST_Request $request ) {
        $id  = (int) $request['id'];
        $raw = $this->claude_comprobar_escritura( $id, $request );
        if ( is_wp_error( $raw ) ) return $raw;

        $elements = $request->get_param( 'elements' );
        $error    = is_array( $elements ) ? self::claude_validar_arbol( $elements ) : 'Falta "elements" (el árbol de Elementor, un array).';
        if ( $error ) return new \WP_Error( 'co_arbol', $error, [ 'status' => 400 ] );

        $estado = (string) $request->get_param( 'estado' );
        if ( $estado && ! in_array( $estado, [ 'publish', 'draft', 'private', 'pending' ], true ) ) {
            return new \WP_Error( 'co_estado', 'Estado no válido: publish, draft, private o pending.', [ 'status' => 400 ] );
        }

        $resultado = $this->claude_guardar_documento( $id, $elements, $estado );
        if ( is_wp_error( $resultado ) ) return $resultado;

        $this->claude_registrar( 'guardar', $id, count( $elements ) . ' elementos de primer nivel' );
        return $resultado;
    }

    /** POST /elementor/{id}/elemento/{el}: cambia ajustes de un elemento. Un valor null borra la clave. */
    public function claude_ajustar_elemento( \WP_REST_Request $request ) {
        $id  = (int) $request['id'];
        $raw = $this->claude_comprobar_escritura( $id, $request );
        if ( is_wp_error( $raw ) ) return $raw;

        $settings = $request->get_param( 'settings' );
        if ( ! is_array( $settings ) || ! $settings ) {
            return new \WP_Error( 'co_settings', 'Falta "settings" (objeto con los ajustes que cambian).', [ 'status' => 400 ] );
        }

        $el_id = (string) $request['el'];
        $hecho = false;
        $arbol = self::claude_modificar( $this->claude_arbol( $raw ), $el_id, function ( $el ) use ( $settings ) {
            $el['settings'] = self::claude_fusionar_ajustes( (array) ( $el['settings'] ?? [] ), $settings );
            return $el;
        }, $hecho );
        if ( ! $hecho ) {
            return new \WP_Error( 'co_no_elemento', 'No hay ningún elemento con ese id en la página.', [ 'status' => 404 ] );
        }

        $resultado = $this->claude_guardar_documento( $id, $arbol, '' );
        if ( is_wp_error( $resultado ) ) return $resultado;

        $this->claude_registrar( 'ajustar', $id, $el_id . ': ' . implode( ', ', array_keys( $settings ) ) );
        return $resultado;
    }

    /**
     * POST /paginas: crea una página ya lista para Elementor y, con WPML,
     * con su idioma puesto (una página creada por la REST normal se queda
     * sin idioma, da 404 y el editor de Elementor no carga nunca).
     * Body: titulo, [slug], [estado=draft], [padre], [plantilla], [idioma],
     * [traduccion_de] (id de la página original), [elements].
     */
    public function claude_crear_pagina( \WP_REST_Request $request ) {
        if ( ! current_user_can( 'publish_pages' ) && 'draft' !== ( $request->get_param( 'estado' ) ?: 'draft' ) ) {
            return new \WP_Error( 'co_permiso', 'Este usuario solo puede crear borradores.', [ 'status' => 403 ] );
        }
        $titulo = sanitize_text_field( (string) $request->get_param( 'titulo' ) );
        if ( '' === $titulo ) return new \WP_Error( 'co_titulo', 'Falta "titulo".', [ 'status' => 400 ] );

        $estado = (string) ( $request->get_param( 'estado' ) ?: 'draft' );
        if ( ! in_array( $estado, [ 'publish', 'draft', 'private', 'pending' ], true ) ) $estado = 'draft';

        $elements = $request->get_param( 'elements' );
        if ( null !== $elements ) {
            $error = is_array( $elements ) ? self::claude_validar_arbol( $elements ) : '"elements" tiene que ser un array.';
            if ( $error ) return new \WP_Error( 'co_arbol', $error, [ 'status' => 400 ] );
        }

        $original = (int) $request->get_param( 'traduccion_de' );
        if ( $original && ! current_user_can( 'edit_post', $original ) ) {
            return new \WP_Error( 'co_permiso', 'No se puede leer la página original.', [ 'status' => 403 ] );
        }

        $id = wp_insert_post( [
            'post_type'   => 'page',
            'post_title'  => $titulo,
            'post_name'   => sanitize_title( (string) $request->get_param( 'slug' ) ),
            'post_status' => $estado,
            'post_parent' => (int) $request->get_param( 'padre' ),
        ], true );
        if ( is_wp_error( $id ) ) return $id;

        $plantilla = sanitize_text_field( (string) $request->get_param( 'plantilla' ) );
        if ( $plantilla ) update_post_meta( $id, '_wp_page_template', $plantilla );

        // Idioma de WPML, antes de que nadie abra la página.
        $idioma = null;
        if ( defined( 'ICL_SITEPRESS_VERSION' ) ) {
            $idioma = sanitize_key( (string) $request->get_param( 'idioma' ) ) ?: apply_filters( 'wpml_default_language', null );
            $trid   = null;
            $origen = null;
            if ( $original ) {
                $det = apply_filters( 'wpml_element_language_details', null, [ 'element_id' => $original, 'element_type' => 'post_page' ] );
                if ( $det && ! empty( $det->trid ) ) {
                    $trid   = $det->trid;
                    $origen = $det->language_code;
                }
            }
            do_action( 'wpml_set_element_language_details', [
                'element_id'           => $id,
                'element_type'         => 'post_page',
                'trid'                 => $trid,
                'language_code'        => $idioma,
                'source_language_code' => $origen,
            ] );
        }

        $version = null;
        $avisos  = [];
        if ( $this->claude_elementor_listo() ) {
            update_post_meta( $id, '_elementor_edit_mode', 'builder' );
            update_post_meta( $id, '_elementor_template_type', 'wp-page' );
            update_post_meta( $id, '_elementor_version', ELEMENTOR_VERSION );
            if ( is_array( $elements ) && $elements ) {
                $guardado = $this->claude_guardar_documento( $id, $elements, '', false );
                if ( is_wp_error( $guardado ) ) return $guardado;
                $version = $guardado['version'];
                $avisos  = $guardado['avisos'];
            } else {
                update_post_meta( $id, '_elementor_data', '[]' );
                $version = $this->claude_huella( '[]' );
            }
        }

        $this->claude_registrar( 'crear', $id, $titulo . ( $idioma ? ' (' . $idioma . ')' : '' ) );
        return [
            'id'      => $id,
            'url'     => get_permalink( $id ),
            'estado'  => get_post_status( $id ),
            'idioma'  => $idioma,
            'version' => $version,
            'avisos'  => $avisos,
        ];
    }

    /**
     * POST /elementor/{id}/revisiones/{rev}: vuelve los datos de Elementor
     * de la página a los de una revisión. Body: version, [forzar].
     *
     * No usa wp_restore_post_revision(): en caracool.net (1.4.2) daba un
     * error crítico junto con el gancho de restaurar de Elementor. Se lee el
     * _elementor_data de la revisión directo de la tabla y se guarda por el
     * mismo camino que cualquier cambio (revisión previa, CSS, caché, y las
     * mismas comprobaciones de version y de editor abierto). Solo vuelve el
     * diseño de Elementor: título, slug y estado de la página no cambian.
     */
    public function claude_restaurar( \WP_REST_Request $request ) {
        $id  = (int) $request['id'];
        $raw = $this->claude_comprobar_escritura( $id, $request );
        if ( is_wp_error( $raw ) ) return $raw;

        // wp_get_post_revision() recibe el id POR REFERENCIA: pasarle una
        // expresión es un error fatal en PHP 8 (caso real, 1.4.2 y 1.4.3).
        $rev_id = (int) $request['rev'];
        $rev    = wp_get_post_revision( $rev_id );
        if ( ! $rev || (int) $rev->post_parent !== $id ) {
            return new \WP_Error( 'co_revision', 'Esa revisión no es de esta página.', [ 'status' => 404 ] );
        }
        $datos = $this->claude_arbol( $this->claude_datos_crudos( $rev->ID ) );
        if ( ! $datos ) {
            return new \WP_Error( 'co_revision', 'Esa revisión no guarda datos de Elementor.', [ 'status' => 404 ] );
        }
        $error = self::claude_validar_arbol( $datos );
        if ( $error ) {
            return new \WP_Error( 'co_revision', 'Los datos de esa revisión no son válidos: ' . $error, [ 'status' => 409 ] );
        }

        $resultado = $this->claude_guardar_documento( $id, $datos, '' );
        if ( is_wp_error( $resultado ) ) return $resultado;

        $this->claude_registrar( 'restaurar', $id, 'revisión ' . $rev->ID );
        return $resultado;
    }

    /** POST /elementor/cache: lo mismo que Elementor → Herramientas → Regenerar archivos y datos. */
    public function claude_limpiar_cache() {
        if ( ! $this->claude_elementor_listo() ) return $this->claude_sin_elementor();
        \Elementor\Plugin::$instance->files_manager->clear_cache();
        delete_post_meta_by_key( '_elementor_element_cache' );
        $this->claude_registrar( 'cache', 0, '' );
        return [ 'ok' => true ];
    }

    // ── Mantenimiento: actualizaciones e instalación (1.5.0) ──
    // Lo mismo que Escritorio → Actualizaciones, sin sesión. Pide que el
    // usuario de Claude tenga el permiso de WordPress correspondiente
    // (Administrador) y que la web permita cambiar archivos.

    /** true, o el WP_Error que explica por qué no se puede. */
    private function claude_puede_mantener( $cap ) {
        if ( defined( 'DISALLOW_FILE_MODS' ) && DISALLOW_FILE_MODS ) {
            return new \WP_Error( 'co_sin_cambios_archivos', 'Esta web tiene DISALLOW_FILE_MODS: no deja instalar ni actualizar nada desde WordPress.', [ 'status' => 403 ] );
        }
        if ( ! current_user_can( $cap ) ) {
            return new \WP_Error( 'co_sin_mantenimiento', 'El usuario de Claude no tiene el permiso «' . $cap . '». Para actualizar o instalar, tiene que ser Administrador.', [ 'status' => 403 ] );
        }
        return true;
    }

    private function claude_cargar_actualizador() {
        foreach ( [ 'file.php', 'misc.php', 'plugin.php', 'theme.php', 'update.php', 'class-wp-upgrader.php', 'translation-install.php' ] as $f ) {
            require_once ABSPATH . 'wp-admin/includes/' . $f;
        }
    }

    /** Sin credenciales FTP: si el hosting no deja escribir directamente, se dice. */
    private function claude_sistema_archivos() {
        if ( 'direct' !== get_filesystem_method() ) {
            return new \WP_Error( 'co_sin_escritura', 'WordPress no puede escribir los archivos directamente en este hosting (pide FTP). Hay que actualizar desde el escritorio.', [ 'status' => 500 ] );
        }
        WP_Filesystem();
        return true;
    }

    /** GET /actualizaciones: lo que WordPress ofrece ahora. ?refrescar=1 vuelve a preguntar. */
    public function claude_actualizaciones( \WP_REST_Request $request ) {
        $ok = $this->claude_puede_mantener( 'update_plugins' );
        if ( is_wp_error( $ok ) ) return $ok;
        $this->claude_cargar_actualizador();
        if ( $request->get_param( 'refrescar' ) ) {
            delete_site_transient( 'update_plugins' );
            delete_site_transient( 'update_themes' );
            wp_version_check( [], true );
        }
        wp_update_plugins();
        wp_update_themes();

        $plugins = [];
        $todos   = get_plugins();
        $up      = get_site_transient( 'update_plugins' );
        foreach ( (array) ( $up->response ?? [] ) as $file => $info ) {
            $plugins[] = [
                'plugin'  => $file,
                'nombre'  => $todos[ $file ]['Name'] ?? $file,
                'version' => $todos[ $file ]['Version'] ?? null,
                'nueva'   => $info->new_version ?? null,
                'activo'  => is_plugin_active( $file ),
            ];
        }
        $temas = [];
        $ut    = get_site_transient( 'update_themes' );
        foreach ( (array) ( $ut->response ?? [] ) as $slug => $info ) {
            $t = wp_get_theme( $slug );
            $temas[] = [ 'tema' => $slug, 'nombre' => $t->get( 'Name' ), 'version' => $t->get( 'Version' ), 'nueva' => $info['new_version'] ?? null ];
        }
        $core = null;
        foreach ( (array) get_core_updates() as $u ) {
            if ( isset( $u->response ) && 'upgrade' === $u->response ) {
                $core = [ 'version' => get_bloginfo( 'version' ), 'nueva' => $u->current, 'menor' => $this->claude_es_menor( $u->current ) ];
                break;
            }
        }
        return [
            'plugins'      => $plugins,
            'temas'        => $temas,
            'wordpress'    => $core,
            'traducciones' => count( wp_get_translation_updates() ),
            'instalados'   => array_map( function ( $file ) use ( $todos ) {
                return [ 'plugin' => $file, 'nombre' => $todos[ $file ]['Name'], 'version' => $todos[ $file ]['Version'], 'activo' => is_plugin_active( $file ) ];
            }, array_keys( $todos ) ),
        ];
    }

    /** ¿La versión nueva es de la misma rama (7.1.x → 7.1.y)? */
    private function claude_es_menor( $nueva ) {
        $a = explode( '.', get_bloginfo( 'version' ) );
        $b = explode( '.', (string) $nueva );
        return ( $a[0] ?? '' ) === ( $b[0] ?? '' ) && ( $a[1] ?? '0' ) === ( $b[1] ?? '0' );
    }

    /**
     * POST /actualizar {tipo: plugins|temas|traducciones|wordpress, items?: [...], mayor?: bool}
     * Sin items, actualiza todo lo que haya de ese tipo. WordPress solo sube
     * de rama (7.1 → 7.2) con mayor:true. Los plugins activos siguen activos.
     */
    public function claude_actualizar( \WP_REST_Request $request ) {
        $tipo  = (string) $request->get_param( 'tipo' );
        $caps  = [ 'plugins' => 'update_plugins', 'temas' => 'update_themes', 'traducciones' => 'update_languages', 'wordpress' => 'update_core' ];
        if ( ! isset( $caps[ $tipo ] ) ) {
            return new \WP_Error( 'co_tipo', 'tipo tiene que ser plugins, temas, traducciones o wordpress.', [ 'status' => 400 ] );
        }
        $ok = $this->claude_puede_mantener( $caps[ $tipo ] );
        if ( is_wp_error( $ok ) ) return $ok;
        $this->claude_cargar_actualizador();
        $fs = $this->claude_sistema_archivos();
        if ( is_wp_error( $fs ) ) return $fs;

        $items = array_values( array_filter( array_map( 'strval', (array) $request->get_param( 'items' ) ) ) );
        $skin  = new \WP_Ajax_Upgrader_Skin();
        $res   = [];

        if ( 'plugins' === $tipo ) {
            wp_update_plugins();
            $ofrecidos = array_keys( (array) ( get_site_transient( 'update_plugins' )->response ?? [] ) );
            $lista     = $items ? array_values( array_intersect( $items, $ofrecidos ) ) : $ofrecidos;
            $sin       = array_values( array_diff( $items, $ofrecidos ) );
            if ( ! $lista ) return [ 'actualizados' => [], 'sin_actualizacion' => $sin ];
            $activos = array_filter( $lista, 'is_plugin_active' );
            $antes   = get_plugins();
            $r       = ( new \Plugin_Upgrader( $skin ) )->bulk_upgrade( $lista );
            wp_clean_plugins_cache();
            $despues = get_plugins();
            foreach ( $lista as $file ) {
                if ( in_array( $file, $activos, true ) && ! is_plugin_active( $file ) ) activate_plugin( $file, '', false, true );
                $res[] = [
                    'plugin'  => $file,
                    'antes'   => $antes[ $file ]['Version'] ?? null,
                    'ahora'   => $despues[ $file ]['Version'] ?? null,
                    'ok'      => ! empty( $r[ $file ] ) && ! is_wp_error( $r[ $file ] ),
                    'activo'  => is_plugin_active( $file ),
                ];
            }
            $this->claude_registrar( 'actualizar', 0, 'plugins: ' . implode( ', ', $lista ) );
            return [ 'actualizados' => $res, 'sin_actualizacion' => $sin, 'mensajes' => $this->claude_mensajes( $skin ) ];
        }

        if ( 'temas' === $tipo ) {
            wp_update_themes();
            $ofrecidos = array_keys( (array) ( get_site_transient( 'update_themes' )->response ?? [] ) );
            $lista     = $items ? array_values( array_intersect( $items, $ofrecidos ) ) : $ofrecidos;
            if ( ! $lista ) return [ 'actualizados' => [], 'sin_actualizacion' => $items ];
            $r = ( new \Theme_Upgrader( $skin ) )->bulk_upgrade( $lista );
            foreach ( $lista as $slug ) {
                $res[] = [ 'tema' => $slug, 'ahora' => wp_get_theme( $slug )->get( 'Version' ), 'ok' => ! empty( $r[ $slug ] ) && ! is_wp_error( $r[ $slug ] ) ];
            }
            $this->claude_registrar( 'actualizar', 0, 'temas: ' . implode( ', ', $lista ) );
            return [ 'actualizados' => $res, 'mensajes' => $this->claude_mensajes( $skin ) ];
        }

        if ( 'traducciones' === $tipo ) {
            $packs = wp_get_translation_updates();
            if ( ! $packs ) return [ 'actualizados' => 0 ];
            $r = ( new \Language_Pack_Upgrader( $skin ) )->bulk_upgrade( $packs );
            $this->claude_registrar( 'actualizar', 0, 'traducciones: ' . count( $packs ) );
            return [ 'actualizados' => is_array( $r ) ? count( array_filter( $r ) ) : 0, 'de' => count( $packs ), 'mensajes' => $this->claude_mensajes( $skin ) ];
        }

        // wordpress
        wp_version_check( [], true );
        $oferta = null;
        foreach ( (array) get_core_updates() as $u ) {
            if ( isset( $u->response ) && 'upgrade' === $u->response ) { $oferta = $u; break; }
        }
        if ( ! $oferta ) return [ 'actualizado' => false, 'detalle' => 'WordPress ya está al día (' . get_bloginfo( 'version' ) . ').' ];
        if ( ! $this->claude_es_menor( $oferta->current ) && ! $request->get_param( 'mayor' ) ) {
            return new \WP_Error( 'co_mayor', 'La actualización ' . get_bloginfo( 'version' ) . ' → ' . $oferta->current . ' cambia de rama. Repite con mayor:true si la persona lo ha aprobado.', [ 'status' => 409 ] );
        }
        $antes = get_bloginfo( 'version' );
        $r     = ( new \Core_Upgrader( $skin ) )->upgrade( $oferta );
        if ( is_wp_error( $r ) ) return $r;
        $this->claude_registrar( 'actualizar', 0, 'WordPress ' . $antes . ' → ' . $oferta->current );
        return [ 'actualizado' => true, 'antes' => $antes, 'ahora' => $oferta->current, 'mensajes' => $this->claude_mensajes( $skin ) ];
    }

    /**
     * POST /plugins/instalar {url, activar?}: instala o sustituye un plugin
     * desde un zip. Solo de releases de github.com/caracoolnet o de
     * downloads.wordpress.org. Si ya existe, se reemplaza y conserva su
     * estado (activo o no).
     */
    public function claude_instalar_zip( \WP_REST_Request $request ) {
        $ok = $this->claude_puede_mantener( 'install_plugins' );
        if ( is_wp_error( $ok ) ) return $ok;
        $url = (string) $request->get_param( 'url' );
        if ( ! self::claude_zip_permitido( $url ) ) {
            return new \WP_Error( 'co_origen', 'Solo se instalan zips de https://github.com/caracoolnet/… o de https://downloads.wordpress.org/plugin/….', [ 'status' => 400 ] );
        }
        $this->claude_cargar_actualizador();
        $fs = $this->claude_sistema_archivos();
        if ( is_wp_error( $fs ) ) return $fs;

        $antes = get_plugins();
        $skin  = new \WP_Ajax_Upgrader_Skin();
        $up    = new \Plugin_Upgrader( $skin );
        $r     = $up->install( $url, [ 'overwrite_package' => true ] );
        if ( is_wp_error( $r ) ) return $r;
        if ( ! $r ) {
            $err = $skin->get_errors();
            return new \WP_Error( 'co_instalar', 'No se ha podido instalar: ' . implode( ' · ', $this->claude_mensajes( $skin ) ), [ 'status' => 500, 'errores' => is_wp_error( $err ) ? $err->get_error_messages() : [] ] );
        }
        wp_clean_plugins_cache();
        $file = $up->plugin_info();
        if ( ! $file ) return new \WP_Error( 'co_instalar', 'El zip no contiene un plugin reconocible.', [ 'status' => 500 ] );
        $era  = isset( $antes[ $file ] );
        $activar = $request->get_param( 'activar' );
        if ( $activar && ! is_plugin_active( $file ) ) {
            $a = activate_plugin( $file, '', false, false );
            if ( is_wp_error( $a ) ) return new \WP_Error( 'co_activar', 'Instalado, pero no se ha podido activar: ' . $a->get_error_message(), [ 'status' => 500 ] );
        }
        $ahora = get_plugins()[ $file ] ?? [];
        $this->claude_registrar( 'instalar', 0, $file . ' ' . ( $ahora['Version'] ?? '' ) );
        return [
            'plugin'    => $file,
            'nombre'    => $ahora['Name'] ?? $file,
            'antes'     => $era ? $antes[ $file ]['Version'] : null,
            'ahora'     => $ahora['Version'] ?? null,
            'activo'    => is_plugin_active( $file ),
            'mensajes'  => $this->claude_mensajes( $skin ),
        ];
    }

    public static function claude_zip_permitido( $url ) {
        $p = wp_parse_url( (string) $url );
        if ( ! $p || ( $p['scheme'] ?? '' ) !== 'https' || empty( $p['host'] ) ) return false;
        $ruta = (string) ( $p['path'] ?? '' );
        if ( 'github.com' === $p['host'] ) return 0 === stripos( $ruta, '/caracoolnet/' ) && false !== strpos( $ruta, '/releases/' );
        if ( 'downloads.wordpress.org' === $p['host'] ) return 0 === strpos( $ruta, '/plugin/' );
        return false;
    }

    private function claude_mensajes( $skin ) {
        return array_slice( array_values( array_filter( array_map( 'wp_strip_all_tags', (array) $skin->get_upgrade_messages() ) ) ), -12 );
    }

    // ── Piezas internas ───────────────────────────────────────

    private function claude_elementor_listo() {
        return class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance ) && defined( 'ELEMENTOR_VERSION' );
    }

    private function claude_sin_elementor() {
        return new \WP_Error( 'co_sin_elementor', 'Elementor no está activo en esta web.', [ 'status' => 503 ] );
    }

    private function claude_post_editable( $id ) {
        $post = get_post( $id );
        if ( ! $post || 'revision' === $post->post_type ) {
            return new \WP_Error( 'co_no_existe', 'No existe ningún contenido con ese id.', [ 'status' => 404 ] );
        }
        if ( ! current_user_can( 'edit_post', $id ) ) {
            return new \WP_Error( 'co_permiso', 'El usuario de Claude no puede editar este contenido.', [ 'status' => 403 ] );
        }
        return $post;
    }

    /**
     * Comprobaciones de cualquier guardado. Devuelve el _elementor_data
     * actual (crudo) si se puede escribir, o el WP_Error que toque.
     */
    private function claude_comprobar_escritura( $id, \WP_REST_Request $request ) {
        $post = $this->claude_post_editable( $id );
        if ( is_wp_error( $post ) ) return $post;
        if ( ! $this->claude_elementor_listo() ) return $this->claude_sin_elementor();

        $lock = $this->claude_bloqueo( $id );
        if ( $lock && ! $request->get_param( 'forzar' ) ) {
            return new \WP_Error( 'co_bloqueada', sprintf( '%s tiene la página abierta en el editor ahora mismo. Espera a que la cierre.', $lock->display_name ), [ 'status' => 423 ] );
        }

        $raw      = $this->claude_datos_crudos( $id );
        $actual   = $this->claude_huella( $raw );
        $esperada = (string) $request->get_param( 'version' );
        if ( '' === $esperada ) {
            return new \WP_Error( 'co_sin_version', 'Falta "version": lee la página antes de guardar y manda la version que te dio.', [ 'status' => 428 ] );
        }
        if ( ! hash_equals( $actual, $esperada ) ) {
            return new \WP_Error( 'co_cambiada', 'La página ha cambiado desde que la leíste (alguien ha guardado). Vuelve a leerla antes de guardar.', [ 'status' => 409, 'version' => $actual ] );
        }
        return $raw;
    }

    /** Usuario que tiene la página abierta en el editor ahora mismo (que no sea Claude), o null. */
    private function claude_bloqueo( $id ) {
        if ( ! function_exists( 'wp_check_post_lock' ) ) {
            require_once ABSPATH . 'wp-admin/includes/post.php';
        }
        $uid = wp_check_post_lock( $id );
        return $uid ? get_userdata( $uid ) : null;
    }

    /**
     * El _elementor_data tal cual está en la tabla. Se lee sin get_post_meta()
     * por lo mismo que Duplicar (ver duplicate_create_copy()): en algún
     * hosting la API devolvía el JSON recortado.
     */
    private function claude_datos_crudos( $id ) {
        global $wpdb;
        return (string) $wpdb->get_var( $wpdb->prepare(
            "SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = '_elementor_data' ORDER BY meta_id DESC LIMIT 1",
            $id
        ) );
    }

    private function claude_arbol( $raw ) {
        $arbol = json_decode( (string) $raw, true );
        return is_array( $arbol ) ? $arbol : [];
    }

    /** Huella corta del _elementor_data: es la "version" que se manda al guardar. */
    private function claude_huella( $raw ) {
        return substr( md5( (string) $raw ), 0, 16 );
    }

    private function claude_idioma( $id, $post_type ) {
        if ( ! defined( 'ICL_SITEPRESS_VERSION' ) ) return null;
        $det = apply_filters( 'wpml_element_language_details', null, [ 'element_id' => $id, 'element_type' => 'post_' . $post_type ] );
        return $det && ! empty( $det->language_code ) ? $det->language_code : null;
    }

    /** Revisión de cómo está la página ahora, aunque WordPress crea que no ha cambiado nada. */
    private function claude_crear_revision( $id ) {
        if ( ! wp_revisions_enabled( get_post( $id ) ) ) return null;
        add_filter( 'wp_save_post_revision_check_for_changes', '__return_false' );
        $rev = wp_save_post_revision( $id );
        remove_filter( 'wp_save_post_revision_check_for_changes', '__return_false' );
        return $rev && ! is_wp_error( $rev ) ? (int) $rev : null;
    }

    /**
     * Guarda con Document::save() de Elementor, como el botón Guardar del
     * editor, y después vacía la caché de elementos y rehace el CSS de la
     * página por si la versión de Elementor no lo hace sola.
     */
    private function claude_guardar_documento( $id, array $elements, $estado, $con_revision = true ) {
        $doc = \Elementor\Plugin::$instance->documents->get( $id, false );
        if ( ! $doc ) {
            return new \WP_Error( 'co_documento', 'Elementor no reconoce este contenido como documento.', [ 'status' => 500 ] );
        }
        if ( ! $doc->is_editable_by_current_user() ) {
            return new \WP_Error( 'co_permiso', 'Elementor no deja editar este contenido al usuario de Claude (revisa Elementor → Gestor de roles).', [ 'status' => 403 ] );
        }

        $revision = $con_revision ? $this->claude_crear_revision( $id ) : null;

        $data = [ 'elements' => $elements ];
        if ( $estado ) $data['settings'] = [ 'post_status' => $estado ];

        try {
            $ok = $doc->save( $data );
        } catch ( \Throwable $e ) {
            return new \WP_Error( 'co_guardar', 'Elementor ha dado un error al guardar: ' . $e->getMessage(), [ 'status' => 500 ] );
        }
        if ( ! $ok ) {
            return new \WP_Error( 'co_guardar', 'Elementor no ha guardado la página.', [ 'status' => 500 ] );
        }

        $this->claude_refrescar( $id );

        return [
            'id'       => $id,
            'version'  => $this->claude_huella( $this->claude_datos_crudos( $id ) ),
            'avisos'   => self::claude_avisos( $elements, $this->claude_hay_motion() ),
            'revision' => $revision,
            'estado'   => get_post_status( $id ),
            'url'      => get_permalink( $id ),
        ];
    }

    /** Vacía la caché de elementos de la página y rehace su CSS. */
    private function claude_refrescar( $id ) {
        delete_post_meta( $id, '_elementor_element_cache' );
        delete_post_meta( $id, '_elementor_css' );
        wp_cache_delete( $id, 'post_meta' );
        if ( class_exists( '\Elementor\Core\Files\CSS\Post' ) ) {
            try {
                ( new \Elementor\Core\Files\CSS\Post( $id ) )->update();
            } catch ( \Throwable $e ) {
                // Si falla, Elementor lo regenera solo en la primera visita.
            }
        }
    }

    private function claude_registrar( $accion, $post_id, $detalle ) {
        $log = (array) get_option( self::CLAUDE_LOG_KEY, [] );
        array_unshift( $log, [
            'fecha'   => current_time( 'mysql' ),
            'accion'  => $accion,
            'post'    => (int) $post_id,
            'titulo'  => $post_id ? get_the_title( $post_id ) : '',
            'detalle' => mb_substr( (string) $detalle, 0, 200 ),
        ] );
        update_option( self::CLAUDE_LOG_KEY, array_slice( $log, 0, 50 ), false );
    }

    // ── Avisos de las reglas de construcción de la casa ──
    // No bloquean el guardado: vuelven en la respuesta para que se corrijan.

    /** Lista de avisos (frases cortas) sobre un árbol de Elementor. */
    public static function claude_avisos( array $elements, $hay_motion ) {
        $avisos = [];
        $cm     = [];
        $crece  = 0;
        self::claude_revisar( $elements, $avisos, $cm, $crece );

        if ( $cm && ! $hay_motion ) {
            $avisos[] = 'Esta web no tiene Caracool Motion: los ajustes cm_ de ' . implode( ', ', array_slice( array_unique( $cm ), 0, 8 ) ) . ' no hacen nada. Quítalos.';
        }
        if ( $hay_motion && $crece > 1 ) {
            $avisos[] = 'El efecto «crece» de Motion va una sola vez por página y aquí hay ' . $crece . '.';
        }
        $avisos = array_values( array_unique( $avisos ) );
        if ( count( $avisos ) > 30 ) {
            $resto  = count( $avisos ) - 30;
            $avisos = array_slice( $avisos, 0, 30 );
            $avisos[] = "… y $resto avisos más.";
        }
        return $avisos;
    }

    private static function claude_revisar( array $elements, array &$avisos, array &$cm, &$crece ) {
        foreach ( $elements as $el ) {
            if ( ! is_array( $el ) ) continue;
            $id      = (string) ( $el['id'] ?? '?' );
            $tipo    = $el['elType'] ?? '';
            $widget  = $el['widgetType'] ?? '';
            $s       = is_array( $el['settings'] ?? null ) ? $el['settings'] : [];
            $globals = is_array( $s['__globals__'] ?? null ) ? $s['__globals__'] : [];

            if ( 'section' === $tipo || 'column' === $tipo ) {
                $avisos[] = "$id es una " . ( 'section' === $tipo ? 'sección' : 'columna' ) . ' antigua: la casa monta todo con contenedores flexbox.';
            }
            if ( 'html' === $widget ) {
                $avisos[] = "$id es un widget HTML: no se usa para montar estructura.";
            }
            if ( ! empty( $s['custom_css'] ) ) {
                $avisos[] = "$id lleva CSS personalizado: la casa no usa CSS a mano (colores y fuentes globales, Estilo del tema y ajustes del widget).";
            }
            if ( isset( $globals['typography'] ) ) {
                $avisos[] = "$id enlaza la tipografía con la clave «typography»: en __globals__ es «typography_typography».";
            }

            foreach ( $s as $clave => $valor ) {
                if ( 0 === strpos( (string) $clave, 'cm_' ) && '' !== $valor && null !== $valor ) {
                    $cm[] = $id;
                }
                if ( preg_match( '/_color(_hover|_tablet|_mobile)?$/', (string) $clave ) && is_string( $valor ) && '' !== $valor && empty( $globals[ $clave ] ) ) {
                    $avisos[] = "$id.$clave tiene un color escrito a mano ($valor): enlázalo a un color global del Kit.";
                }
                if ( preg_match( '/^(width|_element_custom_width)(_[a-z_]+)?$/', (string) $clave ) && is_array( $valor ) && ( $valor['unit'] ?? '' ) === 'px' && '' !== (string) ( $valor['size'] ?? '' ) ) {
                    $avisos[] = "$id.$clave está en píxeles: la casa pone los anchos en porcentaje.";
                }
            }

            if ( 'container' === $tipo && isset( $s['width'] ) && 'full' !== ( $s['content_width'] ?? 'boxed' ) ) {
                $avisos[] = "$id es un contenedor «boxed» con ancho (width): ese ancho no se aplica. Ponlo a ancho completo (content_width: full).";
            }
            if ( 'button' === $widget ) {
                $faltan = [];
                foreach ( [ 'background_color' => 'fondo', 'button_text_color' => 'texto' ] as $c => $nombre ) {
                    if ( empty( $globals[ $c ] ) ) $faltan[] = $nombre;
                }
                if ( $faltan ) {
                    $avisos[] = "El botón $id no tiene enlazado a un global el color de " . implode( ' ni el de ', $faltan ) . ': si no, sale con el color de Énfasis y no hace caso al Kit.';
                }
            }
            if ( 'crece' === ( $s['cm_efecto'] ?? '' ) ) {
                $crece++;
            }

            if ( ! empty( $el['elements'] ) && is_array( $el['elements'] ) ) {
                self::claude_revisar( $el['elements'], $avisos, $cm, $crece );
            }
        }
    }

    // ── Funciones puras sobre el árbol (probadas en pruebas/claude-arbol.php) ──

    /** Árbol corto: id, tipo, widget y un trozo del texto, sin los ajustes. */
    public static function claude_resumen( array $elements ) {
        $out = [];
        foreach ( $elements as $el ) {
            if ( ! is_array( $el ) ) continue;
            $item = [ 'id' => $el['id'] ?? '', 'tipo' => $el['elType'] ?? '' ];
            if ( ! empty( $el['widgetType'] ) ) $item['widget'] = $el['widgetType'];
            $texto = self::claude_texto_corto( (array) ( $el['settings'] ?? [] ) );
            if ( '' !== $texto ) $item['texto'] = $texto;
            if ( ! empty( $el['elements'] ) ) $item['hijos'] = self::claude_resumen( (array) $el['elements'] );
            $out[] = $item;
        }
        return $out;
    }

    public static function claude_texto_corto( array $settings ) {
        foreach ( [ 'title', 'editor', 'text', 'heading_title', 'title_text', 'description_text', 'button_text' ] as $k ) {
            if ( isset( $settings[ $k ] ) && is_string( $settings[ $k ] ) && '' !== trim( $settings[ $k ] ) ) {
                $t = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $settings[ $k ] ) ) );
                return mb_strlen( $t ) > 60 ? mb_substr( $t, 0, 60 ) . '…' : $t;
            }
        }
        if ( ! empty( $settings['image']['url'] ) && is_string( $settings['image']['url'] ) ) {
            return basename( $settings['image']['url'] );
        }
        return '';
    }

    public static function claude_encontrar( array $elements, $el_id ) {
        foreach ( $elements as $el ) {
            if ( ! is_array( $el ) ) continue;
            if ( ( $el['id'] ?? '' ) === $el_id ) return $el;
            if ( ! empty( $el['elements'] ) ) {
                $hallado = self::claude_encontrar( (array) $el['elements'], $el_id );
                if ( null !== $hallado ) return $hallado;
            }
        }
        return null;
    }

    public static function claude_modificar( array $elements, $el_id, callable $fn, &$hecho ) {
        foreach ( $elements as $i => $el ) {
            if ( ! is_array( $el ) ) continue;
            if ( ( $el['id'] ?? '' ) === $el_id ) {
                $elements[ $i ] = $fn( $el );
                $hecho = true;
                return $elements;
            }
            if ( ! empty( $el['elements'] ) ) {
                $elements[ $i ]['elements'] = self::claude_modificar( (array) $el['elements'], $el_id, $fn, $hecho );
                if ( $hecho ) return $elements;
            }
        }
        return $elements;
    }

    /**
     * Mezcla ajustes: cada clave nueva sustituye a la vieja y un null la
     * borra. __globals__ se mezcla clave a clave, para enlazar un color
     * global sin perder los demás enlaces del elemento.
     */
    public static function claude_fusionar_ajustes( array $actuales, array $nuevos ) {
        foreach ( $nuevos as $clave => $valor ) {
            if ( null === $valor ) {
                unset( $actuales[ $clave ] );
            } elseif ( '__globals__' === $clave && is_array( $valor ) ) {
                $globales = (array) ( $actuales['__globals__'] ?? [] );
                foreach ( $valor as $g => $v ) {
                    if ( null === $v ) unset( $globales[ $g ] ); else $globales[ $g ] = $v;
                }
                $actuales['__globals__'] = $globales;
            } else {
                $actuales[ $clave ] = $valor;
            }
        }
        return $actuales;
    }

    /** Devuelve '' si el árbol es válido, o el motivo si no. */
    public static function claude_validar_arbol( array $elements, array &$vistos = [], $nivel = 0 ) {
        if ( $nivel > 30 ) return 'El árbol tiene demasiados niveles.';
        foreach ( $elements as $i => $el ) {
            if ( ! is_array( $el ) ) return "El elemento $i no es un objeto.";
            $id = $el['id'] ?? '';
            if ( ! is_string( $id ) || ! preg_match( '/^[A-Za-z0-9]{1,20}$/', $id ) ) return "Un elemento no tiene un id válido (posición $i, nivel $nivel).";
            if ( isset( $vistos[ $id ] ) ) return "El id $id está repetido.";
            $vistos[ $id ] = true;
            $tipo = $el['elType'] ?? '';
            if ( ! in_array( $tipo, [ 'container', 'section', 'column', 'widget' ], true ) ) return "El elemento $id tiene un elType desconocido.";
            if ( 'widget' === $tipo && empty( $el['widgetType'] ) ) return "El widget $id no dice qué widget es (widgetType).";
            if ( isset( $el['settings'] ) && ! is_array( $el['settings'] ) ) return "Los ajustes de $id no son un objeto.";
            if ( ! empty( $el['elements'] ) ) {
                if ( ! is_array( $el['elements'] ) ) return "Los hijos de $id no son un array.";
                $error = self::claude_validar_arbol( $el['elements'], $vistos, $nivel + 1 );
                if ( $error ) return $error;
            }
        }
        return '';
    }
}

new Caracool_OneStep();

// ── Enlace "Ajustes" en la lista de Plugins ────────────────────
// Aparece junto a Activar/Desactivar/Borrar, y lleva directo a la página
// de ajustes de OneStep en vez de tener que buscarla en el menú lateral.
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), function ( $links ) {
    $settings_link = '<a href="' . esc_url( admin_url( 'admin.php?page=' . CARACOOL_ONESTEP_SLUG ) ) . '">Ajustes</a>';
    array_unshift( $links, $settings_link );
    return $links;
} );

// ── Actualizaciones: las da Caracool Central con la licencia de la web ──
// (inc/caracool-actualizador.php, común a todos los plugins de la casa).
// Sin licencia, pregunta a GitHub como antes.
caracool_actualizador_v2(
    array(
        'archivo' => __FILE__,
        'slug'    => 'onestep',
        'version' => CARACOOL_ONESTEP_VERSION,
        'repo'    => 'caracoolnet/wp-onestep',
        'nombre'  => 'Caracool OneStep',
        'resumen' => 'Desactiva comentarios, modo mantenimiento, código personalizado en el head o el footer y duplicar páginas y entradas, en un único plugin ligero.',
    )
);
