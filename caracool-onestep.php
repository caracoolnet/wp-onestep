<?php
/**
 * Plugin Name: Caracool OneStep
 * Plugin URI:  https://caracool.net
 * Description: Desactiva los comentarios en todo el sitio, activa un modo de mantenimiento con página personalizable e inserta código personalizado en el head/footer. Plugin ligero de Caracool, sin dependencias externas.
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

    // ── Constructor ───────────────────────────────────────────
    public function __construct() {
        add_action( 'admin_menu',            [ $this, 'add_menu' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );
        add_action( 'admin_notices',         [ $this, 'admin_notices' ] );
        add_action( 'admin_post_caracool_onestep_save', [ $this, 'save_settings' ] );

        $settings = $this->get_settings();

        if ( ! empty( $settings['comments_disabled'] ) ) {
            $this->comments_bootstrap();
        }

        // Prioridad 0: que se ejecute antes que cualquier otra cosa enganchada
        // a template_redirect (redirecciones canónicas, SEO, cache, etc.).
        add_action( 'template_redirect', [ $this, 'maintenance_maybe_render' ], 0 );

        $this->snippets_bootstrap( $settings );
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

        // Recordamos en qué pestaña estaba para no devolver siempre a
        // "Comentarios" tras guardar — ver el campo oculto "co_active_tab"
        // en el formulario principal, y el JS que lo mantiene actualizado.
        if ( 'snippets' === $form ) {
            $active_tab = 'tab-codigo';
        } else {
            $posted_tab = sanitize_key( wp_unslash( $_POST['co_active_tab'] ?? '' ) );
            $active_tab = in_array( $posted_tab, [ 'tab-comentarios', 'tab-mantenimiento' ], true ) ? $posted_tab : 'tab-comentarios';
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

            $location = in_array( $row['location'] ?? '', [ 'head', 'footer' ], true ) ? $row['location'] : 'footer';
            $visibility = in_array( $row['visibility'] ?? '', [ 'all', 'all_except', 'only' ], true ) ? $row['visibility'] : 'all';

            $urls_raw = sanitize_textarea_field( wp_unslash( $row['urls'] ?? '' ) );
            $urls     = array_values( array_filter( array_map( 'trim', preg_split( '/[\r\n]+/', $urls_raw ) ) ) );

            $snippets[] = [
                'name'       => $name,
                'code'       => $code,
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
        if ( ! $screen || $screen->id !== 'toplevel_page_' . CARACOOL_ONESTEP_SLUG ) return;

        if ( isset( $_GET['saved'] ) ) {
            echo '<div class="notice notice-success is-dismissible"><p>✅ Configuración guardada.</p></div>';
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

        $s = $this->get_settings();

        // Qué pestaña mostrar activa al cargar: la que venga en la URL tras
        // guardar (ver save_settings()), o "Comentarios" por defecto.
        $co_valid_tabs = [ 'tab-comentarios', 'tab-mantenimiento', 'tab-codigo' ];
        $active_tab    = isset( $_GET['tab'] ) && in_array( $_GET['tab'], $co_valid_tabs, true ) ? sanitize_key( $_GET['tab'] ) : 'tab-comentarios';
        $co_main_tab   = 'tab-mantenimiento' === $active_tab ? 'tab-mantenimiento' : 'tab-comentarios'; // valor inicial del campo oculto del form principal
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
            .co-badge{ font-size:11px; font-weight:650; padding:3px 8px; border-radius:999px; background:var(--co-accent-soft); color:var(--co-accent-ink); white-space:nowrap; }
            .co-badge.muted{ background:#efeceb; color:var(--co-ink-soft); }
            .co-icon-btn{ width:28px; height:28px; border-radius:7px; border:1px solid var(--co-border); background:#fff; display:flex; align-items:center; justify-content:center; cursor:pointer; color:var(--co-ink-soft); }
            .co-icon-btn:hover{ color:#b8382c; border-color:#e7c3bb; }
            .co-icon-btn svg{ width:14px; height:14px; }
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
                    <p class="co-head-sub">Comentarios, mantenimiento y código personalizado — un plugin, sin dependencias</p>
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
                                    'name' => '', 'code' => '', 'location' => 'footer',
                                    'active' => false, 'visibility' => 'all', 'urls' => [],
                                ] );
                                $co_url_count = count( (array) $snippet['urls'] );
                                ?>
                                <div class="co-snippet co-snippet-block">
                                    <div class="co-snippet-top">
                                        <input type="text" name="snippets[<?php echo (int) $i; ?>][name]" value="<?php echo esc_attr( $snippet['name'] ); ?>" class="co-name-input" placeholder="Ej. Google Analytics">
                                        <div class="co-snippet-meta">
                                            <label class="co-switch co-switch-sm" title="Activo">
                                                <input type="checkbox" class="co-snippet-active" name="snippets[<?php echo (int) $i; ?>][active]" value="1" <?php checked( $snippet['active'] ); ?>>
                                                <span class="co-track"></span>
                                            </label>
                                            <span class="co-badge co-badge-active <?php echo $snippet['active'] ? '' : 'muted'; ?>"><?php echo $snippet['active'] ? 'Activo' : 'Inactivo'; ?></span>
                                            <span class="co-badge muted"><?php echo 'head' === $snippet['location'] ? 'Head' : 'Footer'; ?></span>
                                            <span class="co-badge muted">
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

                                    <textarea name="snippets[<?php echo (int) $i; ?>][code]" rows="5" class="code" placeholder="&lt;script&gt;...&lt;/script&gt;" style="margin-top:12px;"><?php echo esc_textarea( $snippet['code'] ); ?></textarea>

                                    <div class="co-snippet-fields">
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
                            <?php endforeach; ?>
                        </div>

                        <div class="co-btn-row">
                            <button type="button" class="co-btn co-btn-ghost" id="co-add-snippet">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                Añadir snippet
                            </button>
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

            <p class="co-foot-credit">Hecho por Caracool</p>
        </div>

        <template id="co-snippet-template">
            <div class="co-snippet co-snippet-block">
                <div class="co-snippet-top">
                    <input type="text" name="snippets[__I__][name]" value="" class="co-name-input" placeholder="Ej. Google Analytics">
                    <div class="co-snippet-meta">
                        <label class="co-switch co-switch-sm" title="Activo">
                            <input type="checkbox" class="co-snippet-active" name="snippets[__I__][active]" value="1">
                            <span class="co-track"></span>
                        </label>
                        <span class="co-badge co-badge-active muted">Inactivo</span>
                        <span class="co-badge muted">Footer</span>
                        <span class="co-badge muted">Todo el sitio</span>
                        <button type="button" class="co-icon-btn co-remove-snippet" title="Quitar este snippet">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0-1 14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2L4 6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                    </div>
                </div>

                <textarea name="snippets[__I__][code]" rows="5" class="code" placeholder="&lt;script&gt;...&lt;/script&gt;" style="margin-top:12px;"></textarea>

                <div class="co-snippet-fields">
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
                        }
                    });
                }
            }
            document.querySelectorAll('#co-snippets-list .co-snippet-block').forEach(coBindSnippetBlock);

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
    // Inserta snippets guardados por el admin en el head o el footer.
    // Coste cero si no hay ningún snippet activo: no se engancha nada en
    // wp_head/wp_footer a menos que exista al menos uno. El código se
    // imprime tal cual (sin wp_kses_post ni similar) porque, igual que con
    // el HTML personalizado de mantenimiento, filtrarlo rompería el propio
    // propósito del módulo (scripts de analítica, pixels, etc. no son HTML
    // "de contenido"). Solo un administrador (manage_options) puede llegar
    // hasta aquí, así que el nivel de confianza es el mismo que editar el
    // tema directamente — no se expone a roles inferiores.
    // ─────────────────────────────────────────────────────────
    private $active_snippets = [];

    private function snippets_bootstrap( $settings ) {
        $snippets = is_array( $settings['snippets'] ?? null ) ? $settings['snippets'] : [];

        $active = array_values( array_filter( $snippets, function ( $snippet ) {
            return ! empty( $snippet['active'] ) && '' !== trim( $snippet['code'] ?? '' );
        } ) );

        if ( ! $active ) return; // nada activo → no se engancha nada, coste cero

        $this->active_snippets = $active;

        add_action( 'wp_head', [ $this, 'snippets_print_head' ], 999 );
        add_action( 'wp_footer', [ $this, 'snippets_print_footer' ], 999 );
    }

    public function snippets_print_head() {
        $this->snippets_print_for_location( 'head' );
    }

    public function snippets_print_footer() {
        $this->snippets_print_for_location( 'footer' );
    }

    private function snippets_print_for_location( $location ) {
        foreach ( $this->active_snippets as $snippet ) {
            if ( ( $snippet['location'] ?? 'footer' ) !== $location ) continue;
            if ( ! $this->snippet_applies_to_current_url( $snippet ) ) continue;

            $label = $snippet['name'] ? $snippet['name'] : 'sin nombre';
            echo "\n<!-- Caracool OneStep · " . esc_html( $label ) . " -->\n" . $snippet['code'] . "\n";
        }
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
            'description' => 'Desactiva comentarios en todo el sitio, activa un modo de mantenimiento con página personalizable e inserta código personalizado (HTML/CSS/JS) en el head o el footer, en un único plugin ligero, sin dependencias externas.',
            'changelog'   => '<h4>1.1.0</h4><p>Nuevo módulo de Código personalizado: snippets de HTML/CSS/JS insertables en el head o el footer, con control de en qué URLs se muestran. Sin coste para el sitio si no hay ningún snippet activo.</p><h4>1.0.1</h4><p>Cambio del icono del menú de admin a "admin-generic".</p><h4>1.0.0</h4><p>Versión inicial: desactivación de comentarios en todo el sitio + modo mantenimiento con página personalizable, whitelist de IPs y bypass por rol.</p>',
        ],
    ];
}, 10, 3 );
