<?php
/**
 * Plugin Name: Caracool OneStep
 * Plugin URI:  https://caracool.net
 * Description: Desactiva los comentarios en todo el sitio, activa un modo de mantenimiento con página personalizable, inserta código personalizado en el head/footer, permite duplicar páginas y entradas y conecta la web con Claude para editar Elementor. Plugin ligero de Caracool, sin dependencias externas.
 * Version:     1.5.5
 * Author:      Caracool
 * Author URI:  https://caracool.net
 * Text Domain: caracool-onestep
 */

// ── Bloquear acceso directo al archivo ────────────────────────
if ( ! defined( 'ABSPATH' ) ) exit;

define( 'CARACOOL_ONESTEP_VERSION', '1.5.5' );
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
        // 1.5.1: un zip que manda Claude desde el Mac (plugins de pago, zips sueltos).
        register_rest_route( $ns, '/plugins/subir', [
            'methods' => \WP_REST_Server::CREATABLE, 'callback' => [ $this, 'claude_subir_zip' ], 'permission_callback' => $perm,
        ] );
        // 1.5.2: borrar un tema que no esté en uso (los plugins se borran por la REST de WordPress).
        register_rest_route( $ns, '/temas/(?P<tema>[A-Za-z0-9._-]+)', [
            'methods' => \WP_REST_Server::DELETABLE, 'callback' => [ $this, 'claude_borrar_tema' ], 'permission_callback' => $perm,
        ] );
        // 1.5.3: los códigos personalizados de la pestaña Código (head/footer, HTML o PHP).
        register_rest_route( $ns, '/codigos', [
            [ 'methods' => \WP_REST_Server::READABLE, 'callback' => [ $this, 'claude_codigos' ], 'permission_callback' => $perm ],
            [ 'methods' => \WP_REST_Server::CREATABLE, 'callback' => [ $this, 'claude_codigo_crear' ], 'permission_callback' => $perm ],
        ] );
        register_rest_route( $ns, '/codigos/(?P<i>\d+)', [
            [ 'methods' => \WP_REST_Server::CREATABLE, 'callback' => [ $this, 'claude_codigo_cambiar' ], 'permission_callback' => $perm ],
            [ 'methods' => \WP_REST_Server::DELETABLE, 'callback' => [ $this, 'claude_codigo_quitar' ], 'permission_callback' => $perm ],
        ] );
        register_rest_route( $ns, '/codigos/papelera', [
            [ 'methods' => \WP_REST_Server::READABLE, 'callback' => [ $this, 'claude_codigos_papelera' ], 'permission_callback' => $perm ],
        ] );
        // 1.5.4: menús de WordPress, conservando los ajustes extra de cada elemento.
        register_rest_route( $ns, '/menus', [
            [ 'methods' => \WP_REST_Server::READABLE, 'callback' => [ $this, 'claude_menus' ], 'permission_callback' => $perm ],
            [ 'methods' => \WP_REST_Server::CREATABLE, 'callback' => [ $this, 'claude_menu_crear' ], 'permission_callback' => $perm ],
        ] );
        register_rest_route( $ns, '/menus/(?P<menu>\d+)', [
            [ 'methods' => \WP_REST_Server::CREATABLE, 'callback' => [ $this, 'claude_menu_cambiar' ], 'permission_callback' => $perm ],
        ] );
        register_rest_route( $ns, '/menus/papelera', [
            [ 'methods' => \WP_REST_Server::READABLE, 'callback' => [ $this, 'claude_menus_papelera' ], 'permission_callback' => $perm ],
        ] );
        register_rest_route( $ns, '/menus/papelera/(?P<i>\d+)', [
            [ 'methods' => \WP_REST_Server::CREATABLE, 'callback' => [ $this, 'claude_menu_restaurar' ], 'permission_callback' => $perm ],
        ] );
        // 1.5.5: ajustes de WordPress (Generales, Escritura, Lectura, Comentarios, Medios, Enlaces permanentes, Privacidad).
        register_rest_route( $ns, '/ajustes', [
            [ 'methods' => \WP_REST_Server::READABLE, 'callback' => [ $this, 'claude_ajustes' ], 'permission_callback' => $perm ],
            [ 'methods' => \WP_REST_Server::CREATABLE, 'callback' => [ $this, 'claude_ajustes_guardar' ], 'permission_callback' => $perm ],
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
     * desde un zip por https (desde 1.5.2, de cualquier dominio público). Si ya existe, se reemplaza y conserva su
     * estado (activo o no).
     */
    public function claude_instalar_zip( \WP_REST_Request $request ) {
        $ok = $this->claude_puede_mantener( 'install_plugins' );
        if ( is_wp_error( $ok ) ) return $ok;
        $url = (string) $request->get_param( 'url' );
        if ( ! self::claude_zip_permitido( $url ) ) {
            return new \WP_Error( 'co_origen', 'La URL del zip tiene que ser https y de un dominio público.', [ 'status' => 400 ] );
        }
        return $this->claude_instalar_paquete( $url, (bool) $request->get_param( 'activar' ) );
    }

    /**
     * POST /plugins/subir {zip (base64), nombre?, activar?}: instala o
     * sustituye un plugin desde un zip que manda Claude desde el Mac de la
     * persona (plugins de pago, zips que no están en una release). Máximo
     * 25 MB. Mismo resultado que /plugins/instalar.
     */
    public function claude_subir_zip( \WP_REST_Request $request ) {
        $ok = $this->claude_puede_mantener( 'install_plugins' );
        if ( is_wp_error( $ok ) ) return $ok;
        $datos = base64_decode( (string) $request->get_param( 'zip' ), true );
        if ( false === $datos || '' === $datos ) {
            return new \WP_Error( 'co_zip', 'Falta el zip, o no viene en base64.', [ 'status' => 400 ] );
        }
        if ( strlen( $datos ) > 25 * 1048576 ) {
            return new \WP_Error( 'co_zip', 'El zip pasa de 25 MB.', [ 'status' => 413 ] );
        }
        if ( 0 !== strncmp( $datos, "PK\x03\x04", 4 ) ) {
            return new \WP_Error( 'co_zip', 'El archivo no es un zip.', [ 'status' => 400 ] );
        }
        $this->claude_cargar_actualizador();
        $nombre = sanitize_file_name( (string) ( $request->get_param( 'nombre' ) ?: 'plugin.zip' ) );
        if ( ! preg_match( '/\.zip$/i', $nombre ) ) $nombre .= '.zip';
        $tmp = wp_tempnam( $nombre );
        if ( ! $tmp || false === file_put_contents( $tmp, $datos ) ) {
            return new \WP_Error( 'co_zip', 'No se ha podido guardar el zip en la carpeta temporal.', [ 'status' => 500 ] );
        }
        $r = $this->claude_instalar_paquete( $tmp, (bool) $request->get_param( 'activar' ), $nombre );
        if ( file_exists( $tmp ) ) @unlink( $tmp );
        return $r;
    }

    /** Instala un paquete (URL permitida o archivo temporal) y deja el plugin como estaba, o activo si se pide. */
    private function claude_instalar_paquete( $paquete, $activar, $origen = '' ) {
        $this->claude_cargar_actualizador();
        $fs = $this->claude_sistema_archivos();
        if ( is_wp_error( $fs ) ) return $fs;

        $antes = get_plugins();
        $skin  = new \WP_Ajax_Upgrader_Skin();
        $up    = new \Plugin_Upgrader( $skin );
        $r     = $up->install( $paquete, [ 'overwrite_package' => true ] );
        if ( is_wp_error( $r ) ) return $r;
        if ( ! $r ) {
            $err = $skin->get_errors();
            return new \WP_Error( 'co_instalar', 'No se ha podido instalar: ' . implode( ' · ', $this->claude_mensajes( $skin ) ), [ 'status' => 500, 'errores' => is_wp_error( $err ) ? $err->get_error_messages() : [] ] );
        }
        wp_clean_plugins_cache();
        $file = $up->plugin_info();
        if ( ! $file ) return new \WP_Error( 'co_instalar', 'El zip no contiene un plugin reconocible.', [ 'status' => 500 ] );
        $era  = isset( $antes[ $file ] );
        if ( $activar && ! is_plugin_active( $file ) ) {
            $a = activate_plugin( $file, '', false, false );
            if ( is_wp_error( $a ) ) return new \WP_Error( 'co_activar', 'Instalado, pero no se ha podido activar: ' . $a->get_error_message(), [ 'status' => 500 ] );
        }
        $ahora = get_plugins()[ $file ] ?? [];
        $this->claude_registrar( 'instalar', 0, trim( $file . ' ' . ( $ahora['Version'] ?? '' ) . ( $origen ? ' (zip subido: ' . $origen . ')' : '' ) ) );
        return [
            'plugin'    => $file,
            'nombre'    => $ahora['Name'] ?? $file,
            'antes'     => $era ? $antes[ $file ]['Version'] : null,
            'ahora'     => $ahora['Version'] ?? null,
            'activo'    => is_plugin_active( $file ),
            'mensajes'  => $this->claude_mensajes( $skin ),
        ];
    }

    /**
     * 1.5.2: cualquier zip por https. La descarga va con wp_safe_remote_get
     * (download_url), que ya rechaza direcciones internas de la red.
     */
    public static function claude_zip_permitido( $url ) {
        $p = wp_parse_url( (string) $url );
        return $p && ( $p['scheme'] ?? '' ) === 'https' && ! empty( $p['host'] ) && false !== strpos( $p['host'], '.' );
    }

    /**
     * DELETE /temas/<carpeta>: borra un tema instalado que no sea el activo
     * ni el padre del activo. No se puede deshacer.
     */
    public function claude_borrar_tema( \WP_REST_Request $request ) {
        $ok = $this->claude_puede_mantener( 'delete_themes' );
        if ( is_wp_error( $ok ) ) return $ok;
        $tema = (string) $request->get_param( 'tema' );
        $t = wp_get_theme( $tema );
        if ( ! $t->exists() ) return new \WP_Error( 'co_tema', 'No hay ningún tema instalado en la carpeta «' . $tema . '».', [ 'status' => 404 ] );
        if ( in_array( $tema, [ get_stylesheet(), get_template() ], true ) ) {
            return new \WP_Error( 'co_tema_en_uso', '«' . $t->get( 'Name' ) . '» está en uso (es el tema activo o su padre). Primero hay que activar otro.', [ 'status' => 409 ] );
        }
        $this->claude_cargar_actualizador();
        $fs = $this->claude_sistema_archivos();
        if ( is_wp_error( $fs ) ) return $fs;
        $nombre  = $t->get( 'Name' );
        $version = $t->get( 'Version' );
        $r = delete_theme( $tema );
        if ( is_wp_error( $r ) ) return $r;
        if ( ! $r ) return new \WP_Error( 'co_tema', 'WordPress no ha podido borrar el tema.', [ 'status' => 500 ] );
        $this->claude_registrar( 'borrar_tema', 0, $tema . ' ' . $version );
        return [ 'borrado' => $tema, 'nombre' => $nombre, 'version' => $version ];
    }

    private function claude_mensajes( $skin ) {
        return array_slice( array_values( array_filter( array_map( 'wp_strip_all_tags', (array) $skin->get_upgrade_messages() ) ) ), -12 );
    }

    // ── Códigos personalizados (1.5.3) ────────────────────────
    // Los mismos snippets de la pestaña Código: se guardan en
    // settings['snippets'] y los pinta/ejecuta snippets_bootstrap(). Como un
    // snippet puede ser JavaScript o PHP que se ejecuta en el servidor, todo
    // pide manage_options (Administrador). Los que se quitan van a una
    // papelera (los últimos 20) para poder recuperarlos.

    const CODIGOS_PAPELERA = 'caracool_onestep_snippets_papelera';

    private function claude_puede_codigo() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return new \WP_Error( 'co_sin_codigos', 'Los códigos personalizados solo los toca un Administrador: el usuario de Claude tiene que tener ese rol.', [ 'status' => 403 ] );
        }
        return true;
    }

    private function claude_lista_codigos() {
        $s = $this->get_settings();
        return array_values( is_array( $s['snippets'] ?? null ) ? $s['snippets'] : [] );
    }

    private static function claude_huella_codigos( $lista ) {
        return substr( md5( (string) wp_json_encode( $lista ) ), 0, 12 );
    }

    private function claude_guardar_codigos( $lista ) {
        $s = $this->get_settings();
        $s['snippets'] = array_values( $lista );
        update_option( self::OPTION_KEY, $s );
    }

    private static function claude_codigo_salida( $i, $c ) {
        return [
            'indice'      => (int) $i,
            'nombre'      => (string) ( $c['name'] ?? '' ),
            'tipo'        => (string) ( $c['type'] ?? 'html' ),
            'ubicacion'   => (string) ( $c['location'] ?? 'footer' ),
            'activo'      => ! empty( $c['active'] ),
            'visibilidad' => (string) ( $c['visibility'] ?? 'all' ),
            'urls'        => array_values( (array) ( $c['urls'] ?? [] ) ),
            'codigo'      => (string) ( $c['code'] ?? '' ),
        ];
    }

    /**
     * Aplica los campos recibidos sobre un snippet (nuevo o existente) con las
     * mismas reglas que el formulario de la pestaña Código. Un PHP con error
     * de sintaxis no se guarda.
     */
    public static function claude_codigo_desde( $p, $base = [] ) {
        $c = wp_parse_args( $base, [ 'name' => '', 'code' => '', 'type' => 'html', 'location' => 'footer', 'active' => false, 'visibility' => 'all', 'urls' => [] ] );
        if ( array_key_exists( 'nombre', $p ) )    $c['name'] = sanitize_text_field( (string) $p['nombre'] );
        if ( array_key_exists( 'codigo', $p ) )    $c['code'] = (string) $p['codigo'];
        if ( array_key_exists( 'tipo', $p ) ) {
            if ( ! in_array( $p['tipo'], [ 'html', 'php' ], true ) ) return new \WP_Error( 'co_codigo', '«tipo» es html o php.', [ 'status' => 400 ] );
            $c['type'] = $p['tipo'];
        }
        if ( array_key_exists( 'ubicacion', $p ) ) {
            if ( ! in_array( $p['ubicacion'], [ 'head', 'footer' ], true ) ) return new \WP_Error( 'co_codigo', '«ubicacion» es head o footer.', [ 'status' => 400 ] );
            $c['location'] = $p['ubicacion'];
        }
        if ( array_key_exists( 'visibilidad', $p ) ) {
            if ( ! in_array( $p['visibilidad'], [ 'all', 'all_except', 'only' ], true ) ) return new \WP_Error( 'co_codigo', '«visibilidad» es all, all_except u only.', [ 'status' => 400 ] );
            $c['visibility'] = $p['visibilidad'];
        }
        if ( array_key_exists( 'urls', $p ) ) {
            $c['urls'] = array_values( array_filter( array_map( function ( $u ) { return trim( sanitize_text_field( (string) $u ) ); }, (array) $p['urls'] ) ) );
        }
        if ( array_key_exists( 'activo', $p ) ) $c['active'] = (bool) $p['activo'];

        if ( '' === trim( $c['name'] ) ) return new \WP_Error( 'co_codigo', 'El código necesita un «nombre».', [ 'status' => 400 ] );
        if ( '' === trim( $c['code'] ) ) return new \WP_Error( 'co_codigo', 'Falta el «codigo».', [ 'status' => 400 ] );
        if ( 'php' === $c['type'] ) {
            // eval() no lleva etiqueta de apertura: se quita si viene.
            $c['code'] = preg_replace( '/^\s*<\?php\b/i', '', $c['code'] );
            $c['code'] = preg_replace( '/\?>\s*$/', '', $c['code'] );
            try {
                token_get_all( '<?php ' . $c['code'], TOKEN_PARSE );
            } catch ( \ParseError $e ) {
                return new \WP_Error( 'co_codigo_php', 'El PHP tiene un error de sintaxis (línea ' . $e->getLine() . '): ' . $e->getMessage() . '. No se ha guardado.', [ 'status' => 400 ] );
            }
        }
        return $c;
    }

    /** GET /codigos: todos los códigos, con su índice, y la «version» de la lista. */
    public function claude_codigos( \WP_REST_Request $request ) {
        $ok = $this->claude_puede_codigo();
        if ( is_wp_error( $ok ) ) return $ok;
        $lista = $this->claude_lista_codigos();
        $out = [];
        foreach ( $lista as $i => $c ) $out[] = self::claude_codigo_salida( $i, $c );
        $error = get_option( 'caracool_onestep_php_error' );
        return [ 'version' => self::claude_huella_codigos( $lista ), 'codigos' => $out, 'ultimo_error_php' => $error ?: null ];
    }

    private function claude_codigos_comprobar_version( $request, $lista ) {
        $v = (string) $request->get_param( 'version' );
        if ( '' === $v ) return new \WP_Error( 'co_sin_version', 'Falta la «version» de la lista (la da GET /codigos).', [ 'status' => 428 ] );
        $actual = self::claude_huella_codigos( $lista );
        if ( $v !== $actual ) return new \WP_Error( 'co_cambiada', 'Los códigos han cambiado desde que se leyeron: vuelve a leerlos.', [ 'status' => 409, 'version' => $actual ] );
        return true;
    }

    /** POST /codigos {nombre, codigo, tipo, ubicacion, activo, visibilidad, urls, version}: añade uno al final. */
    public function claude_codigo_crear( \WP_REST_Request $request ) {
        $ok = $this->claude_puede_codigo();
        if ( is_wp_error( $ok ) ) return $ok;
        $lista = $this->claude_lista_codigos();
        $v = $this->claude_codigos_comprobar_version( $request, $lista );
        if ( is_wp_error( $v ) ) return $v;
        $c = self::claude_codigo_desde( (array) $request->get_json_params() );
        if ( is_wp_error( $c ) ) return $c;
        $lista[] = $c;
        $this->claude_guardar_codigos( $lista );
        $i = count( $lista ) - 1;
        $this->claude_registrar( 'codigo_crear', 0, '#' . $i . ' ' . $c['name'] . ' (' . $c['type'] . ', ' . ( $c['active'] ? 'activo' : 'inactivo' ) . ')' );
        return [ 'version' => self::claude_huella_codigos( $lista ), 'codigo' => self::claude_codigo_salida( $i, $c ) ];
    }

    /** POST /codigos/<i> {campos que cambian, version}: cambia uno (también activarlo o desactivarlo). */
    public function claude_codigo_cambiar( \WP_REST_Request $request ) {
        $ok = $this->claude_puede_codigo();
        if ( is_wp_error( $ok ) ) return $ok;
        $lista = $this->claude_lista_codigos();
        $i = (int) $request->get_param( 'i' );
        if ( ! isset( $lista[ $i ] ) ) return new \WP_Error( 'co_codigo', 'No hay ningún código con el índice ' . $i . '.', [ 'status' => 404 ] );
        $v = $this->claude_codigos_comprobar_version( $request, $lista );
        if ( is_wp_error( $v ) ) return $v;
        $c = self::claude_codigo_desde( (array) $request->get_json_params(), $lista[ $i ] );
        if ( is_wp_error( $c ) ) return $c;
        $lista[ $i ] = $c;
        $this->claude_guardar_codigos( $lista );
        $this->claude_registrar( 'codigo_cambiar', 0, '#' . $i . ' ' . $c['name'] . ' (' . ( $c['active'] ? 'activo' : 'inactivo' ) . ')' );
        return [ 'version' => self::claude_huella_codigos( $lista ), 'codigo' => self::claude_codigo_salida( $i, $c ) ];
    }

    /** DELETE /codigos/<i>?version=…: lo quita de la lista y lo guarda en la papelera de códigos. */
    public function claude_codigo_quitar( \WP_REST_Request $request ) {
        $ok = $this->claude_puede_codigo();
        if ( is_wp_error( $ok ) ) return $ok;
        $lista = $this->claude_lista_codigos();
        $i = (int) $request->get_param( 'i' );
        if ( ! isset( $lista[ $i ] ) ) return new \WP_Error( 'co_codigo', 'No hay ningún código con el índice ' . $i . '.', [ 'status' => 404 ] );
        $v = $this->claude_codigos_comprobar_version( $request, $lista );
        if ( is_wp_error( $v ) ) return $v;
        $c = $lista[ $i ];
        $papelera = (array) get_option( self::CODIGOS_PAPELERA, [] );
        array_unshift( $papelera, [ 'fecha' => current_time( 'mysql' ), 'codigo' => $c ] );
        update_option( self::CODIGOS_PAPELERA, array_slice( $papelera, 0, 20 ), false );
        array_splice( $lista, $i, 1 );
        $this->claude_guardar_codigos( $lista );
        $this->claude_registrar( 'codigo_quitar', 0, '#' . $i . ' ' . ( $c['name'] ?? '' ) . ' (a la papelera de códigos)' );
        return [ 'version' => self::claude_huella_codigos( $lista ), 'quitado' => self::claude_codigo_salida( $i, $c ) ];
    }

    /** GET /codigos/papelera: los últimos códigos quitados, para recuperarlos con POST /codigos. */
    public function claude_codigos_papelera( \WP_REST_Request $request ) {
        $ok = $this->claude_puede_codigo();
        if ( is_wp_error( $ok ) ) return $ok;
        $out = [];
        foreach ( (array) get_option( self::CODIGOS_PAPELERA, [] ) as $k => $e ) {
            $out[] = [ 'fecha' => $e['fecha'] ?? '' ] + self::claude_codigo_salida( $k, (array) ( $e['codigo'] ?? [] ) );
        }
        return $out;
    }

    // ── Menús de WordPress (1.5.4) ────────────────────────────
    // Leer, crear y cambiar menús sin perder lo que cada elemento lleva de
    // más: los ajustes que guardan el tema o los plugins en el meta del
    // elemento (iconos, megamenús, visibilidad…). La REST normal y el
    // editor de menús los pierden con facilidad cuando se rehace un menú;
    // aquí los elementos existentes se tocan campo a campo, nunca se borran
    // y recrean, y al final se compara su meta con la de antes y se
    // restaura lo que algún gancho haya cambiado. Los elementos quitados
    // van a la papelera de WordPress y, además, a una copia completa en la
    // papelera de menús de OneStep (los últimos 50), de donde se restauran.

    const MENUS_PAPELERA = 'caracool_onestep_menus_papelera';

    /** Meta que pertenece a WordPress; el resto de claves son ajustes extra. */
    const MENU_META_WP = [
        '_menu_item_type', '_menu_item_menu_item_parent', '_menu_item_object_id', '_menu_item_object',
        '_menu_item_target', '_menu_item_classes', '_menu_item_xfn', '_menu_item_url', '_menu_item_orphaned',
        '_edit_lock', '_edit_last', '_wp_old_date', '_wp_old_slug', '_wp_desired_post_slug',
        '_wp_trash_meta_status', '_wp_trash_meta_time',
    ];

    private function claude_puede_menus() {
        if ( ! current_user_can( 'edit_theme_options' ) ) {
            return new \WP_Error( 'co_sin_menus', 'Los menús solo los toca un usuario que pueda editar la apariencia (Administrador).', [ 'status' => 403 ] );
        }
        return true;
    }

    /** Todo el meta de un elemento: clave => lista de valores ya deserializados. */
    private static function claude_menu_metas( $id ) {
        $out = [];
        foreach ( (array) get_post_custom( $id ) as $k => $vals ) {
            $out[ $k ] = array_map( 'maybe_unserialize', array_values( (array) $vals ) );
        }
        ksort( $out );
        return $out;
    }

    private static function claude_menu_extra( $metas ) {
        $extra = array_diff_key( $metas, array_flip( self::MENU_META_WP ) );
        $out = [];
        foreach ( $extra as $k => $vals ) $out[ $k ] = 1 === count( $vals ) ? $vals[0] : $vals;
        return $out;
    }

    /** Elementos publicados de un menú, en orden, ya preparados por WordPress. */
    private function claude_menu_items( $menu_id ) {
        $posts = get_posts( [
            'post_type'        => 'nav_menu_item',
            'post_status'      => 'publish',
            'numberposts'      => -1,
            'orderby'          => 'menu_order',
            'order'            => 'ASC',
            'suppress_filters' => true,
            'tax_query'        => [ [ 'taxonomy' => 'nav_menu', 'field' => 'term_id', 'terms' => (int) $menu_id ] ],
        ] );
        $out = [];
        foreach ( (array) $posts as $p ) {
            $out[ (int) $p->ID ] = wp_setup_nav_menu_item( $p );
        }
        return $out;
    }

    private static function claude_menu_item_salida( $it, $metas ) {
        return [
            'id'             => (int) $it->ID,
            'titulo'         => (string) $it->title,
            'titulo_propio'  => (string) $it->post_title,
            'tipo'           => (string) $it->type,
            'objeto'         => (string) $it->object,
            'objeto_id'      => (int) $it->object_id,
            'url'            => (string) $it->url,
            'padre'          => (int) $it->menu_item_parent,
            'orden'          => (int) $it->menu_order,
            'destino'        => (string) $it->target,
            'clases'         => array_values( array_filter( (array) $it->classes ) ),
            'atributo_title' => (string) $it->attr_title,
            'descripcion'    => (string) $it->post_content,
            'roto'           => ! empty( $it->_invalid ),
            'ajustes_extra'  => (object) self::claude_menu_extra( $metas ),
        ];
    }

    private function claude_menu_ubicaciones_de( $menu_id ) {
        $out = [];
        foreach ( (array) get_nav_menu_locations() as $slug => $id ) {
            if ( (int) $id === (int) $menu_id ) $out[] = (string) $slug;
        }
        return $out;
    }

    /** Un menú completo: elementos, ubicaciones, ajustes extra y su «version». */
    private function claude_menu_leer( $menu ) {
        $items = $this->claude_menu_items( $menu->term_id );
        $salida = [];
        $para_huella = [];
        $con_extra = [];
        foreach ( $items as $id => $it ) {
            $metas = self::claude_menu_metas( $id );
            $s = self::claude_menu_item_salida( $it, $metas );
            $salida[] = $s;
            $extra = (array) $s['ajustes_extra'];
            if ( $extra ) $con_extra[] = [ 'id' => $id, 'titulo' => $s['titulo'], 'claves' => array_keys( $extra ) ];
            $para_huella[] = [ $id, (int) $it->menu_order, $it->post_title, $it->post_content, $it->post_excerpt, $metas ];
        }
        $ubic = $this->claude_menu_ubicaciones_de( $menu->term_id );
        $idioma = apply_filters( 'wpml_element_language_code', null, [ 'element_id' => (int) $menu->term_id, 'element_type' => 'nav_menu' ] );
        return [
            'id'          => (int) $menu->term_id,
            'nombre'      => (string) $menu->name,
            'slug'        => (string) $menu->slug,
            'idioma'      => $idioma ? (string) $idioma : null,
            'ubicaciones' => $ubic,
            'version'     => substr( md5( (string) wp_json_encode( [ $menu->name, $ubic, $para_huella ] ) ), 0, 12 ),
            'elementos'   => $salida,
            // Lo que este MCP no sabe interpretar: se conserva siempre, pero para guardar hace falta «forzar».
            'ajustes_desconocidos' => $con_extra,
        ];
    }

    private function claude_menu_objeto( $id ) {
        $menu = wp_get_nav_menu_object( (int) $id );
        if ( ! $menu ) return new \WP_Error( 'co_menu', 'No hay ningún menú con el id ' . (int) $id . '.', [ 'status' => 404 ] );
        return $menu;
    }

    /** GET /menus[?menu=id]: menús con sus elementos, las ubicaciones del tema y la papelera. */
    public function claude_menus( \WP_REST_Request $request ) {
        $ok = $this->claude_puede_menus();
        if ( is_wp_error( $ok ) ) return $ok;
        $solo = (int) $request->get_param( 'menu' );
        $locs = (array) get_nav_menu_locations();
        $ubicaciones = [];
        foreach ( (array) get_registered_nav_menus() as $slug => $nombre ) {
            $ubicaciones[ $slug ] = [ 'nombre' => (string) $nombre, 'menu' => ! empty( $locs[ $slug ] ) ? (int) $locs[ $slug ] : null ];
        }
        $menus = [];
        foreach ( (array) wp_get_nav_menus() as $m ) {
            if ( $solo && (int) $m->term_id !== $solo ) continue;
            $menus[] = $this->claude_menu_leer( $m );
        }
        if ( $solo && ! $menus ) return new \WP_Error( 'co_menu', 'No hay ningún menú con el id ' . $solo . '.', [ 'status' => 404 ] );
        return [
            'tema'        => get_stylesheet(),
            'ubicaciones' => (object) $ubicaciones,
            'menus'       => $menus,
            'en_papelera' => count( (array) get_option( self::MENUS_PAPELERA, [] ) ),
        ];
    }

    /**
     * Comprueba unas ubicaciones para un menú. Quitarle una ubicación a otro
     * menú cambia lo que se ve en la web: sin «sustituir» se para y lo dice.
     */
    private function claude_menu_ubicaciones_validas( $slugs, $menu_id, $sustituir ) {
        $registradas = (array) get_registered_nav_menus();
        $locs = (array) get_nav_menu_locations();
        $slugs = array_values( array_unique( array_map( 'strval', (array) $slugs ) ) );
        foreach ( $slugs as $s ) {
            if ( ! isset( $registradas[ $s ] ) ) {
                return new \WP_Error( 'co_menu', 'El tema no tiene la ubicación «' . $s . '». Tiene: ' . implode( ', ', array_keys( $registradas ) ) . '.', [ 'status' => 400 ] );
            }
            $otro = (int) ( $locs[ $s ] ?? 0 );
            if ( $otro && $otro !== (int) $menu_id && ! $sustituir && wp_get_nav_menu_object( $otro ) ) {
                $m = wp_get_nav_menu_object( $otro );
                return new \WP_Error( 'co_menu_ubicacion_ocupada', 'La ubicación «' . $s . '» ya muestra el menú «' . $m->name . '» (id ' . $otro . '). Para sustituirlo, «sustituir».', [ 'status' => 409 ] );
            }
        }
        return $slugs;
    }

    /** POST /menus {nombre, ubicaciones?, sustituir?}: crea un menú vacío y, si se pide, lo pone en esas ubicaciones. */
    public function claude_menu_crear( \WP_REST_Request $request ) {
        $ok = $this->claude_puede_menus();
        if ( is_wp_error( $ok ) ) return $ok;
        $p = (array) $request->get_json_params();
        $nombre = sanitize_text_field( (string) ( $p['nombre'] ?? '' ) );
        if ( '' === $nombre ) return new \WP_Error( 'co_menu', 'El menú necesita un «nombre».', [ 'status' => 400 ] );
        $ubic = isset( $p['ubicaciones'] ) ? (array) $p['ubicaciones'] : ( isset( $p['ubicacion'] ) ? [ $p['ubicacion'] ] : [] );
        $ubic = $this->claude_menu_ubicaciones_validas( $ubic, 0, ! empty( $p['sustituir'] ) );
        if ( is_wp_error( $ubic ) ) return $ubic;
        $id = wp_create_nav_menu( $nombre );
        if ( is_wp_error( $id ) ) return new \WP_Error( 'co_menu', 'No se ha podido crear el menú: ' . $id->get_error_message(), [ 'status' => 400 ] );
        if ( $ubic ) {
            $locs = (array) get_theme_mod( 'nav_menu_locations', [] );
            foreach ( $ubic as $s ) $locs[ $s ] = (int) $id;
            set_theme_mod( 'nav_menu_locations', $locs );
        }
        $this->claude_registrar( 'menu_crear', 0, $nombre . ( $ubic ? ' → ' . implode( ', ', $ubic ) : '' ) );
        return $this->claude_menu_leer( wp_get_nav_menu_object( (int) $id ) );
    }

    /** Clave interna de un elemento: existente «e123», nuevo «n<ref>». */
    private static function claude_menu_clave( $v ) {
        if ( is_int( $v ) || ( is_string( $v ) && ctype_digit( $v ) ) ) return (int) $v ? 'e' . (int) $v : 0;
        if ( is_string( $v ) && '' !== trim( $v ) ) return 'n' . trim( $v );
        return null;
    }

    private static function claude_menu_clases( $v ) {
        $v = is_array( $v ) ? $v : preg_split( '/\s+/', (string) $v );
        return array_values( array_filter( array_map( 'sanitize_html_class', $v ) ) );
    }

    /**
     * POST /menus/<id> {version, forzar?, operaciones:[…]}: cambia un menú.
     * Las operaciones se comprueban todas antes de escribir nada:
     *   añadir   {titulo?, objeto?, objeto_id?, url?, padre?, despues_de?, destino?, clases?, atributo_title?, descripcion?, ref?}
     *   cambiar  {id, titulo?, url?, destino?, clases?, atributo_title?, descripcion?, padre?, despues_de?}
     *   mover    {id, padre?, despues_de?}            (despues_de 0 = el primero de sus hermanos)
     *   ordenar  {elementos:[{id, padre}]}            (todos, en el orden nuevo)
     *   quitar   {id}                                 (sus hijos suben un nivel; va a la papelera)
     *   renombrar {nombre}
     *   ubicaciones {ubicaciones:[slug…], sustituir?} (las de este menú, exactas; sustituir para quitársela a otro menú)
     * id, padre y despues_de admiten el «ref» de un elemento añadido en la misma llamada.
     */
    public function claude_menu_cambiar( \WP_REST_Request $request ) {
        global $wpdb;
        $ok = $this->claude_puede_menus();
        if ( is_wp_error( $ok ) ) return $ok;
        $menu = $this->claude_menu_objeto( $request->get_param( 'menu' ) );
        if ( is_wp_error( $menu ) ) return $menu;
        $menu_id = (int) $menu->term_id;
        $p = (array) $request->get_json_params();
        $forzar = ! empty( $p['forzar'] );

        $antes = $this->claude_menu_leer( $menu );
        $v = (string) ( $p['version'] ?? '' );
        if ( '' === $v ) return new \WP_Error( 'co_sin_version', 'Falta la «version» del menú (la da GET /menus).', [ 'status' => 428 ] );
        if ( $v !== $antes['version'] ) return new \WP_Error( 'co_cambiada', 'El menú ha cambiado desde que se leyó: vuelve a leerlo.', [ 'status' => 409, 'version' => $antes['version'] ] );
        if ( $antes['ajustes_desconocidos'] && ! $forzar ) {
            $lista = array_map( function ( $x ) { return '«' . $x['titulo'] . '» (' . $x['id'] . '): ' . implode( ', ', $x['claves'] ); }, $antes['ajustes_desconocidos'] );
            return new \WP_Error( 'co_menu_ajustes_extra', 'Hay elementos con ajustes que esta herramienta no conoce (los pone el tema o un plugin): ' . implode( '; ', $lista ) . '. Se conservan igualmente, pero para guardar hay que decírselo a la persona y mandar «forzar».', [ 'status' => 412, 'ajustes_desconocidos' => $antes['ajustes_desconocidos'] ] );
        }
        $ops = $p['operaciones'] ?? [];
        if ( ! is_array( $ops ) || ! $ops ) return new \WP_Error( 'co_menu', 'Faltan las «operaciones».', [ 'status' => 400 ] );

        // Modelo en memoria: orden plano + padre de cada uno + campos.
        $items = $this->claude_menu_items( $menu_id );
        $orden = [];
        $padre = [];
        $campos = [];
        foreach ( $items as $id => $it ) {
            $k = 'e' . $id;
            $orden[] = $k;
            $padre[ $k ] = $it->menu_item_parent ? 'e' . (int) $it->menu_item_parent : 0;
            $campos[ $k ] = [];
        }
        foreach ( $padre as $k => $pa ) if ( $pa && ! isset( $padre[ $pa ] ) ) $padre[ $k ] = 0; // padre que ya no está
        $nuevos = [];
        $quitados = [];
        $nombre_nuevo = null;
        $ubic_nuevas = null;

        $err = function ( $n, $msg ) { return new \WP_Error( 'co_menu', 'Operación ' . ( $n + 1 ) . ': ' . $msg, [ 'status' => 400 ] ); };
        $existe = function ( $k ) use ( &$padre ) { return $k && isset( $padre[ $k ] ); };
        $desciende = function ( $k, $de ) use ( &$padre ) { // ¿$k está debajo de $de (o es él)?
            for ( $i = 0; $k && $i < 500; $i++ ) { if ( $k === $de ) return true; $k = $padre[ $k ] ?? 0; }
            return false;
        };
        $colocar = function ( $k, $o, $n ) use ( &$orden, &$padre, $existe, $desciende, $err ) {
            $tiene_padre = array_key_exists( 'padre', $o );
            $tiene_despues = array_key_exists( 'despues_de', $o );
            if ( $tiene_padre ) {
                $pa = self::claude_menu_clave( $o['padre'] );
                if ( null === $pa || ( $pa && ! $existe( $pa ) ) ) return $err( $n, 'el «padre» no es un elemento de este menú.' );
                if ( $pa && $desciende( $pa, $k ) ) return $err( $n, 'un elemento no puede colgar de sí mismo ni de uno de sus hijos.' );
            }
            if ( $tiene_despues ) {
                $de = self::claude_menu_clave( $o['despues_de'] );
                if ( null === $de || ( $de && ( ! $existe( $de ) || $de === $k ) ) ) return $err( $n, '«despues_de» no es otro elemento de este menú.' );
                if ( ! $tiene_padre ) $pa = $de ? $padre[ $de ] : ( $padre[ $k ] ?? 0 );
                if ( $de && $pa !== $padre[ $de ] ) return $err( $n, '«despues_de» tiene que ser un hermano (del mismo «padre»).' );
            }
            if ( $tiene_padre || $tiene_despues ) {
                $padre[ $k ] = $pa;
                $orden = array_values( array_diff( $orden, [ $k ] ) );
                if ( $tiene_despues && $de ) {
                    array_splice( $orden, array_search( $de, $orden, true ) + 1, 0, [ $k ] );
                } elseif ( $tiene_despues ) {
                    $pos = count( $orden );
                    foreach ( $orden as $i => $x ) if ( $padre[ $x ] === $pa ) { $pos = $i; break; }
                    array_splice( $orden, $pos, 0, [ $k ] );
                } else {
                    $orden[] = $k;
                }
            } elseif ( ! in_array( $k, $orden, true ) ) {
                $padre[ $k ] = $padre[ $k ] ?? 0;
                $orden[] = $k;
            }
            return true;
        };
        $leer_campos = function ( $o, $n, $es_enlace ) use ( $err ) {
            $c = [];
            if ( array_key_exists( 'titulo', $o ) ) $c['titulo'] = sanitize_text_field( (string) $o['titulo'] );
            if ( array_key_exists( 'url', $o ) ) {
                if ( ! $es_enlace ) return $err( $n, 'solo se cambia la «url» de un enlace personalizado; los demás la toman de su página o categoría.' );
                $c['url'] = esc_url_raw( (string) $o['url'] );
                if ( '' === $c['url'] ) return $err( $n, 'la «url» no es válida.' );
            }
            if ( array_key_exists( 'destino', $o ) ) {
                if ( ! in_array( $o['destino'], [ '', '_blank' ], true ) ) return $err( $n, '«destino» es "" o "_blank".' );
                $c['destino'] = $o['destino'];
            }
            if ( array_key_exists( 'clases', $o ) ) $c['clases'] = self::claude_menu_clases( $o['clases'] );
            if ( array_key_exists( 'atributo_title', $o ) ) $c['atributo_title'] = sanitize_text_field( (string) $o['atributo_title'] );
            if ( array_key_exists( 'descripcion', $o ) ) $c['descripcion'] = sanitize_text_field( (string) $o['descripcion'] );
            return $c;
        };

        foreach ( array_values( $ops ) as $n => $o ) {
            $o = (array) $o;
            $accion = (string) ( $o['accion'] ?? '' );
            if ( 'anadir' === $accion ) $accion = 'añadir';
            switch ( $accion ) {
                case 'añadir':
                    $ref = isset( $o['ref'] ) && '' !== (string) $o['ref'] ? (string) $o['ref'] : '#' . $n;
                    if ( ctype_digit( $ref ) ) return $err( $n, 'el «ref» no puede ser un número.' );
                    $k = 'n' . $ref;
                    if ( isset( $padre[ $k ] ) ) return $err( $n, 'el «ref» «' . $ref . '» está repetido.' );
                    $obj_id = (int) ( $o['objeto_id'] ?? 0 );
                    $objeto = (string) ( $o['objeto'] ?? ( $obj_id ? 'page' : 'custom' ) );
                    if ( $obj_id ) {
                        if ( post_type_exists( $objeto ) ) {
                            $post = get_post( $obj_id );
                            if ( ! $post || $post->post_type !== $objeto ) return $err( $n, 'no hay ningún ' . $objeto . ' con el id ' . $obj_id . '.' );
                            $tipo = 'post_type';
                        } elseif ( taxonomy_exists( $objeto ) ) {
                            $term = get_term( $obj_id, $objeto );
                            if ( ! $term || is_wp_error( $term ) ) return $err( $n, 'no hay ningún término de ' . $objeto . ' con el id ' . $obj_id . '.' );
                            $tipo = 'taxonomy';
                        } else {
                            return $err( $n, '«objeto» tiene que ser un tipo de contenido (page, post, product…) o una taxonomía (category, product_cat…).' );
                        }
                    } else {
                        if ( empty( $o['url'] ) ) return $err( $n, 'para añadir hace falta «objeto_id» (una página, entrada o categoría) o una «url».' );
                        if ( '' === trim( (string) ( $o['titulo'] ?? '' ) ) ) return $err( $n, 'un enlace personalizado necesita «titulo».' );
                        $objeto = 'custom';
                        $tipo = 'custom';
                    }
                    $c = $leer_campos( $o, $n, 'custom' === $tipo );
                    if ( is_wp_error( $c ) ) return $c;
                    $padre[ $k ] = 0;
                    $r = $colocar( $k, $o, $n );
                    if ( is_wp_error( $r ) ) { unset( $padre[ $k ] ); return $r; }
                    $nuevos[ $k ] = [ 'ref' => $ref, 'tipo' => $tipo, 'objeto' => $objeto, 'objeto_id' => $obj_id ];
                    $campos[ $k ] = $c;
                    break;

                case 'cambiar':
                case 'mover':
                    $k = self::claude_menu_clave( $o['id'] ?? null );
                    if ( ! $existe( $k ) ) return $err( $n, 'el «id» no es un elemento de este menú.' );
                    if ( 'cambiar' === $accion ) {
                        $es_enlace = isset( $nuevos[ $k ] ) ? 'custom' === $nuevos[ $k ]['tipo'] : 'custom' === $items[ (int) substr( $k, 1 ) ]->type;
                        $c = $leer_campos( $o, $n, $es_enlace );
                        if ( is_wp_error( $c ) ) return $c;
                        $campos[ $k ] = array_merge( $campos[ $k ], $c );
                    }
                    $r = $colocar( $k, $o, $n );
                    if ( is_wp_error( $r ) ) return $r;
                    break;

                case 'ordenar':
                    $lista = $o['elementos'] ?? null;
                    if ( ! is_array( $lista ) ) return $err( $n, '«elementos» es la lista completa [{id, padre}] en el orden nuevo.' );
                    $nuevo_orden = [];
                    $nuevo_padre = [];
                    foreach ( $lista as $x ) {
                        $x = (array) $x;
                        $k = self::claude_menu_clave( $x['id'] ?? null );
                        if ( ! $existe( $k ) || isset( $nuevo_padre[ $k ] ) ) return $err( $n, 'en «elementos» hay un id que no es de este menú o está repetido.' );
                        $pa = self::claude_menu_clave( $x['padre'] ?? 0 );
                        if ( null === $pa || ( $pa && ! $existe( $pa ) ) ) return $err( $n, 'en «elementos» hay un padre que no es de este menú.' );
                        $nuevo_orden[] = $k;
                        $nuevo_padre[ $k ] = $pa;
                    }
                    if ( count( $nuevo_orden ) !== count( $orden ) ) return $err( $n, '«elementos» tiene que traerlos todos (' . count( $orden ) . '), no ' . count( $nuevo_orden ) . '.' );
                    foreach ( $nuevo_padre as $k => $pa ) {
                        for ( $i = 0, $x = $pa; $x && $i < 500; $i++ ) { if ( $x === $k ) return $err( $n, 'en «elementos» hay un elemento que cuelga de sí mismo.' ); $x = $nuevo_padre[ $x ] ?? 0; }
                    }
                    $orden = $nuevo_orden;
                    $padre = $nuevo_padre;
                    break;

                case 'quitar':
                    $k = self::claude_menu_clave( $o['id'] ?? null );
                    if ( ! $existe( $k ) ) return $err( $n, 'el «id» no es un elemento de este menú.' );
                    foreach ( $padre as $h => $pa ) if ( $pa === $k ) $padre[ $h ] = $padre[ $k ];
                    $orden = array_values( array_diff( $orden, [ $k ] ) );
                    unset( $padre[ $k ] );
                    if ( isset( $nuevos[ $k ] ) ) { unset( $nuevos[ $k ], $campos[ $k ] ); } else { $quitados[] = (int) substr( $k, 1 ); }
                    break;

                case 'renombrar':
                    $nombre_nuevo = sanitize_text_field( (string) ( $o['nombre'] ?? '' ) );
                    if ( '' === $nombre_nuevo ) return $err( $n, 'falta el «nombre».' );
                    foreach ( (array) wp_get_nav_menus() as $m ) {
                        if ( (int) $m->term_id !== $menu_id && 0 === strcasecmp( $m->name, $nombre_nuevo ) ) return $err( $n, 'ya hay otro menú que se llama «' . $nombre_nuevo . '».' );
                    }
                    break;

                case 'ubicaciones':
                    $u = $this->claude_menu_ubicaciones_validas( $o['ubicaciones'] ?? [], $menu_id, ! empty( $o['sustituir'] ) );
                    if ( is_wp_error( $u ) ) return new \WP_Error( $u->get_error_code(), 'Operación ' . ( $n + 1 ) . ': ' . $u->get_error_message(), $u->data );
                    $ubic_nuevas = $u;
                    break;

                default:
                    return $err( $n, '«accion» es añadir, cambiar, mover, ordenar, quitar, renombrar o ubicaciones.' );
            }
        }

        // Orden final: en profundidad, cada padre seguido de sus hijos en el orden del modelo.
        $hijos = [];
        foreach ( $orden as $k ) $hijos[ $padre[ $k ] && isset( $padre[ $padre[ $k ] ] ) ? $padre[ $k ] : 0 ][] = $k;
        $final = [];
        $recorrer = function ( $pa ) use ( &$recorrer, &$hijos, &$final ) {
            foreach ( $hijos[ $pa ] ?? [] as $k ) { $final[] = $k; $recorrer( $k ); }
        };
        $recorrer( 0 );

        // ── A partir de aquí se escribe ──
        $metas_antes = [];
        foreach ( $items as $id => $it ) $metas_antes[ $id ] = self::claude_menu_metas( $id );

        // 1. Lo quitado: copia completa a la papelera de OneStep y el elemento a la papelera de WordPress.
        if ( $quitados ) {
            $papelera = (array) get_option( self::MENUS_PAPELERA, [] );
            foreach ( $quitados as $id ) {
                $it = $items[ $id ];
                array_unshift( $papelera, [
                    'fecha'       => current_time( 'mysql' ),
                    'menu'        => $menu_id,
                    'menu_nombre' => (string) $menu->name,
                    'id'          => $id,
                    'padre'       => (int) $it->menu_item_parent,
                    'post'        => [ 'post_title' => $it->post_title, 'post_content' => $it->post_content, 'post_excerpt' => $it->post_excerpt, 'menu_order' => (int) $it->menu_order ],
                    'metas'       => $metas_antes[ $id ],
                    'elemento'    => self::claude_menu_item_salida( $it, $metas_antes[ $id ] ),
                ] );
                $wpdb->update( $wpdb->posts, [ 'post_status' => 'trash' ], [ 'ID' => $id ] );
                update_post_meta( $id, '_wp_trash_meta_status', 'publish' );
                update_post_meta( $id, '_wp_trash_meta_time', time() );
                clean_post_cache( $id );
            }
            update_option( self::MENUS_PAPELERA, array_slice( $papelera, 0, 50 ), false );
        }

        // 2. Lo nuevo, con la función de WordPress (no tiene nada que perder).
        $ids = [];
        foreach ( $items as $id => $it ) $ids[ 'e' . $id ] = $id;
        foreach ( $final as $k ) {
            if ( ! isset( $nuevos[ $k ] ) ) continue;
            $nv = $nuevos[ $k ];
            $c = $campos[ $k ];
            $nid = wp_update_nav_menu_item( $menu_id, 0, [
                'menu-item-type'        => $nv['tipo'],
                'menu-item-object'      => $nv['objeto'],
                'menu-item-object-id'   => $nv['objeto_id'],
                'menu-item-title'       => $c['titulo'] ?? '',
                'menu-item-url'         => $c['url'] ?? '',
                'menu-item-target'      => $c['destino'] ?? '',
                'menu-item-classes'     => implode( ' ', $c['clases'] ?? [] ),
                'menu-item-attr-title'  => $c['atributo_title'] ?? '',
                'menu-item-description' => $c['descripcion'] ?? '',
                'menu-item-status'      => 'publish',
            ] );
            if ( is_wp_error( $nid ) || ! $nid ) {
                return new \WP_Error( 'co_menu', 'No se ha podido añadir «' . ( $c['titulo'] ?? $nv['ref'] ) . '»' . ( is_wp_error( $nid ) ? ': ' . $nid->get_error_message() : '' ) . '. Lo anterior a este paso sí se ha guardado: vuelve a leer el menú.', [ 'status' => 500 ] );
            }
            $ids[ $k ] = (int) $nid;
        }

        // 3. Orden, padre y campos, uno a uno y solo donde cambian (sin rehacer nada).
        foreach ( $final as $i => $k ) {
            $id = $ids[ $k ];
            $orden_nuevo = $i + 1;
            $pa = $padre[ $k ] && isset( $ids[ $padre[ $k ] ] ) ? $ids[ $padre[ $k ] ] : 0;
            $c = $campos[ $k ];
            $post = [];
            if ( isset( $nuevos[ $k ] ) ) {
                $post['menu_order'] = $orden_nuevo;
            } else {
                $it = $items[ $id ];
                if ( (int) $it->menu_order !== $orden_nuevo ) $post['menu_order'] = $orden_nuevo;
                if ( array_key_exists( 'titulo', $c ) && $c['titulo'] !== $it->post_title ) $post['post_title'] = $c['titulo'];
                if ( array_key_exists( 'atributo_title', $c ) ) $post['post_excerpt'] = $c['atributo_title'];
                if ( array_key_exists( 'descripcion', $c ) ) $post['post_content'] = $c['descripcion'];
                if ( array_key_exists( 'url', $c ) ) update_post_meta( $id, '_menu_item_url', $c['url'] );
                if ( array_key_exists( 'destino', $c ) ) update_post_meta( $id, '_menu_item_target', $c['destino'] );
                if ( array_key_exists( 'clases', $c ) ) update_post_meta( $id, '_menu_item_classes', $c['clases'] ?: [ '' ] );
            }
            if ( (string) $pa !== (string) get_post_meta( $id, '_menu_item_menu_item_parent', true ) ) update_post_meta( $id, '_menu_item_menu_item_parent', (string) $pa );
            if ( $post ) $wpdb->update( $wpdb->posts, $post, [ 'ID' => $id ] );
            clean_post_cache( $id );
        }

        // 4. Nombre y ubicaciones del menú.
        if ( null !== $nombre_nuevo && $nombre_nuevo !== $menu->name ) {
            $r = wp_update_nav_menu_object( $menu_id, [ 'menu-name' => $nombre_nuevo ] );
            if ( is_wp_error( $r ) ) return new \WP_Error( 'co_menu', 'Los elementos se han guardado, pero no el nombre: ' . $r->get_error_message(), [ 'status' => 500 ] );
        }
        if ( null !== $ubic_nuevas ) {
            $locs = (array) get_theme_mod( 'nav_menu_locations', [] );
            foreach ( $locs as $s => $mid ) if ( (int) $mid === $menu_id && ! in_array( $s, $ubic_nuevas, true ) ) $locs[ $s ] = 0;
            foreach ( $ubic_nuevas as $s ) $locs[ $s ] = $menu_id;
            set_theme_mod( 'nav_menu_locations', $locs );
        }

        // Aviso a plugins de caché y traducción, como hace el editor de menús.
        do_action( 'wp_update_nav_menu', $menu_id, [] );

        // 5. Los ajustes extra, como estaban: si algún gancho los ha cambiado o borrado, se reponen.
        $restaurados = [];
        $conservados = 0;
        foreach ( $metas_antes as $id => $metas ) {
            if ( in_array( $id, $quitados, true ) ) continue;
            $extra = array_diff_key( $metas, array_flip( self::MENU_META_WP ) );
            if ( ! $extra ) continue;
            $ahora = self::claude_menu_metas( $id );
            foreach ( $extra as $clave => $vals ) {
                if ( isset( $ahora[ $clave ] ) && serialize( $ahora[ $clave ] ) === serialize( $vals ) ) { $conservados++; continue; }
                delete_post_meta( $id, $clave );
                foreach ( $vals as $val ) add_post_meta( $id, $clave, wp_slash( $val ) );
                $restaurados[] = $id . ':' . $clave;
            }
        }

        $resumen = [];
        if ( $nuevos ) $resumen[] = count( $nuevos ) . ' añadidos';
        if ( $quitados ) $resumen[] = count( $quitados ) . ' quitados';
        if ( null !== $nombre_nuevo ) $resumen[] = 'renombrado';
        if ( null !== $ubic_nuevas ) $resumen[] = 'ubicaciones: ' . ( $ubic_nuevas ? implode( ', ', $ubic_nuevas ) : 'ninguna' );
        $this->claude_registrar( 'menu', 0, $menu->name . ' (' . $menu_id . '): ' . count( $ops ) . ' operaciones' . ( $resumen ? ', ' . implode( ', ', $resumen ) : '' ) . ( $restaurados ? ', ' . count( $restaurados ) . ' ajustes extra repuestos' : '' ) );

        $nuevos_ids = [];
        foreach ( $nuevos as $k => $nv ) $nuevos_ids[ $nv['ref'] ] = $ids[ $k ];
        $menu = wp_get_nav_menu_object( $menu_id );
        return $this->claude_menu_leer( $menu ) + [
            'nuevos'        => (object) $nuevos_ids,
            'quitados'      => $quitados,
            'ajustes_extra' => [ 'conservados' => $conservados, 'repuestos' => $restaurados ],
        ];
    }

    /** GET /menus/papelera: los últimos elementos quitados, con su índice para restaurarlos. */
    public function claude_menus_papelera( \WP_REST_Request $request ) {
        $ok = $this->claude_puede_menus();
        if ( is_wp_error( $ok ) ) return $ok;
        $out = [];
        foreach ( (array) get_option( self::MENUS_PAPELERA, [] ) as $i => $e ) {
            $el = (array) ( $e['elemento'] ?? [] );
            $out[] = [
                'indice'        => $i,
                'fecha'         => $e['fecha'] ?? '',
                'menu'          => (int) ( $e['menu'] ?? 0 ),
                'menu_nombre'   => (string) ( $e['menu_nombre'] ?? '' ),
                'id'            => (int) ( $e['id'] ?? 0 ),
                'titulo'        => (string) ( $el['titulo'] ?? '' ),
                'tipo'          => (string) ( $el['tipo'] ?? '' ),
                'url'           => (string) ( $el['url'] ?? '' ),
                'ajustes_extra' => $el['ajustes_extra'] ?? new \stdClass(),
            ];
        }
        return $out;
    }

    /**
     * POST /menus/papelera/<i> {menu?, padre?}: devuelve un elemento a su
     * menú (u otro), al final, con todos sus ajustes. Si WordPress ya lo ha
     * vaciado de su papelera, lo rehace desde la copia.
     */
    public function claude_menu_restaurar( \WP_REST_Request $request ) {
        global $wpdb;
        $ok = $this->claude_puede_menus();
        if ( is_wp_error( $ok ) ) return $ok;
        $papelera = (array) get_option( self::MENUS_PAPELERA, [] );
        $i = (int) $request->get_param( 'i' );
        if ( ! isset( $papelera[ $i ] ) ) return new \WP_Error( 'co_menu', 'No hay nada con el índice ' . $i . ' en la papelera de menús.', [ 'status' => 404 ] );
        $e = $papelera[ $i ];
        $menu = $this->claude_menu_objeto( $request->get_param( 'menu' ) ?: ( $e['menu'] ?? 0 ) );
        if ( is_wp_error( $menu ) ) return new \WP_Error( 'co_menu', 'El menú de este elemento ya no existe: indica otro con «menu».', [ 'status' => 404 ] );
        $menu_id = (int) $menu->term_id;
        $items = $this->claude_menu_items( $menu_id );
        $pa = null !== $request->get_param( 'padre' ) ? (int) $request->get_param( 'padre' ) : (int) ( $e['padre'] ?? 0 );
        if ( $pa && ! isset( $items[ $pa ] ) ) $pa = 0;
        $orden = 1;
        foreach ( $items as $it ) $orden = max( $orden, (int) $it->menu_order + 1 );
        $metas = (array) ( $e['metas'] ?? [] );
        $uno = function ( $k ) use ( $metas ) { return $metas[ $k ][0] ?? ''; };

        $id = (int) ( $e['id'] ?? 0 );
        $post = $id ? get_post( $id ) : null;
        if ( $post && 'nav_menu_item' === $post->post_type ) {
            $wpdb->update( $wpdb->posts, [ 'post_status' => 'publish', 'menu_order' => $orden ], [ 'ID' => $id ] );
            delete_post_meta( $id, '_wp_trash_meta_status' );
            delete_post_meta( $id, '_wp_trash_meta_time' );
            wp_set_object_terms( $id, [ $menu_id ], 'nav_menu' );
            update_post_meta( $id, '_menu_item_menu_item_parent', (string) $pa );
            $como = 'recuperado de la papelera de WordPress';
        } else {
            $p = (array) ( $e['post'] ?? [] );
            $id = wp_update_nav_menu_item( $menu_id, 0, [
                'menu-item-type'        => $uno( '_menu_item_type' ),
                'menu-item-object'      => $uno( '_menu_item_object' ),
                'menu-item-object-id'   => (int) $uno( '_menu_item_object_id' ),
                'menu-item-title'       => $p['post_title'] ?? '',
                'menu-item-url'         => $uno( '_menu_item_url' ),
                'menu-item-target'      => $uno( '_menu_item_target' ),
                'menu-item-classes'     => implode( ' ', (array) $uno( '_menu_item_classes' ) ),
                'menu-item-xfn'         => $uno( '_menu_item_xfn' ),
                'menu-item-attr-title'  => $p['post_excerpt'] ?? '',
                'menu-item-description' => $p['post_content'] ?? '',
                'menu-item-parent-id'   => $pa,
                'menu-item-position'    => $orden,
                'menu-item-status'      => 'publish',
            ] );
            if ( is_wp_error( $id ) || ! $id ) return new \WP_Error( 'co_menu', 'No se ha podido rehacer el elemento.', [ 'status' => 500 ] );
            $id = (int) $id;
            $como = 'rehecho desde la copia (WordPress ya lo había vaciado de su papelera)';
        }
        // Los ajustes extra, tal cual estaban.
        foreach ( array_diff_key( $metas, array_flip( self::MENU_META_WP ) ) as $clave => $vals ) {
            delete_post_meta( $id, $clave );
            foreach ( (array) $vals as $val ) add_post_meta( $id, $clave, wp_slash( $val ) );
        }
        clean_post_cache( $id );
        array_splice( $papelera, $i, 1 );
        update_option( self::MENUS_PAPELERA, $papelera, false );
        do_action( 'wp_update_nav_menu', $menu_id, [] );
        $this->claude_registrar( 'menu_restaurar', 0, ( $e['elemento']['titulo'] ?? '' ) . ' → ' . $menu->name . ' (' . $como . ')' );
        return [ 'restaurado' => $id, 'como' => $como ] + $this->claude_menu_leer( $menu );
    }

    // ── Ajustes de WordPress (1.5.5) ──────────────────────────
    // Las pantallas de Ajustes (General, Escritura, Lectura, Comentarios,
    // Medios, Enlaces permanentes y Privacidad) por una lista cerrada de
    // opciones, cada una con su comprobación. Lo que puede dejar la web
    // inaccesible (la dirección) o toca cuentas y permisos (email del
    // administrador, registro abierto, rol por defecto) no se toca. Lo que
    // afecta al SEO (enlaces permanentes, ocultar a buscadores) pide
    // «confirmar». Con «probar» dice qué cambiaría sin guardar nada.

    /** opción => [pantalla, tipo, extra]. Tipos: texto, textarea, entero, si_no, lista, pagina, categoria, zona, idioma, formato, enlace. */
    const AJUSTES = [
        'blogname'                     => [ 'general', 'texto' ],
        'blogdescription'              => [ 'general', 'texto' ],
        'timezone_string'              => [ 'general', 'zona' ],
        'gmt_offset'                   => [ 'general', 'decimal' ],
        'date_format'                  => [ 'general', 'texto' ],
        'time_format'                  => [ 'general', 'texto' ],
        'start_of_week'                => [ 'general', 'entero', [ 0, 6 ] ],
        'WPLANG'                       => [ 'general', 'idioma' ],
        'default_category'             => [ 'escritura', 'categoria' ],
        'default_post_format'          => [ 'escritura', 'formato' ],
        'show_on_front'                => [ 'lectura', 'lista', [ 'posts', 'page' ] ],
        'page_on_front'                => [ 'lectura', 'pagina' ],
        'page_for_posts'               => [ 'lectura', 'pagina' ],
        'posts_per_page'               => [ 'lectura', 'entero', [ 1, 100 ] ],
        'posts_per_rss'                => [ 'lectura', 'entero', [ 1, 100 ] ],
        'rss_use_excerpt'              => [ 'lectura', 'si_no' ],
        'blog_public'                  => [ 'lectura', 'si_no' ],
        'default_pingback_flag'        => [ 'comentarios', 'si_no' ],
        'default_ping_status'          => [ 'comentarios', 'lista', [ 'open', 'closed' ] ],
        'default_comment_status'       => [ 'comentarios', 'lista', [ 'open', 'closed' ] ],
        'require_name_email'           => [ 'comentarios', 'si_no' ],
        'comment_registration'         => [ 'comentarios', 'si_no' ],
        'close_comments_for_old_posts' => [ 'comentarios', 'si_no' ],
        'close_comments_days_old'      => [ 'comentarios', 'entero', [ 0, 36500 ] ],
        'show_comments_cookies_opt_in' => [ 'comentarios', 'si_no' ],
        'thread_comments'              => [ 'comentarios', 'si_no' ],
        'thread_comments_depth'        => [ 'comentarios', 'entero', [ 2, 10 ] ],
        'page_comments'                => [ 'comentarios', 'si_no' ],
        'comments_per_page'            => [ 'comentarios', 'entero', [ 1, 1000 ] ],
        'default_comments_page'        => [ 'comentarios', 'lista', [ 'newest', 'oldest' ] ],
        'comment_order'                => [ 'comentarios', 'lista', [ 'asc', 'desc' ] ],
        'comments_notify'              => [ 'comentarios', 'si_no' ],
        'moderation_notify'            => [ 'comentarios', 'si_no' ],
        'comment_moderation'           => [ 'comentarios', 'si_no' ],
        'comment_previously_approved'  => [ 'comentarios', 'si_no' ],
        'comment_max_links'            => [ 'comentarios', 'entero', [ 0, 100 ] ],
        'moderation_keys'              => [ 'comentarios', 'textarea' ],
        'disallowed_keys'              => [ 'comentarios', 'textarea' ],
        'show_avatars'                 => [ 'comentarios', 'si_no' ],
        'avatar_rating'                => [ 'comentarios', 'lista', [ 'G', 'PG', 'R', 'X' ] ],
        'avatar_default'               => [ 'comentarios', 'lista', [ 'mystery', 'blank', 'gravatar_default', 'identicon', 'wavatar', 'monsterid', 'retro' ] ],
        'thumbnail_size_w'             => [ 'medios', 'entero', [ 0, 10000 ] ],
        'thumbnail_size_h'             => [ 'medios', 'entero', [ 0, 10000 ] ],
        'thumbnail_crop'               => [ 'medios', 'si_no' ],
        'medium_size_w'                => [ 'medios', 'entero', [ 0, 10000 ] ],
        'medium_size_h'                => [ 'medios', 'entero', [ 0, 10000 ] ],
        'large_size_w'                 => [ 'medios', 'entero', [ 0, 10000 ] ],
        'large_size_h'                 => [ 'medios', 'entero', [ 0, 10000 ] ],
        'uploads_use_yearmonth_folders' => [ 'medios', 'si_no' ],
        'permalink_structure'          => [ 'enlaces', 'enlace' ],
        'category_base'                => [ 'enlaces', 'base' ],
        'tag_base'                     => [ 'enlaces', 'base' ],
        'wp_page_for_privacy_policy'   => [ 'privacidad', 'pagina' ],
    ];

    /** Lo que no se toca nunca por aquí, y por qué. */
    const AJUSTES_BLOQUEADOS = [
        'home'              => 'la dirección de la web: un error la deja inaccesible. Se cambia desde el escritorio o el panel del hosting.',
        'siteurl'           => 'la dirección de WordPress: un error deja la web inaccesible. Se cambia desde el escritorio o el panel del hosting.',
        'admin_email'       => 'el email del administrador: WordPress pide confirmarlo desde ese correo. Lo cambia la persona en Ajustes → Generales.',
        'new_admin_email'   => 'el email del administrador: lo cambia la persona en Ajustes → Generales.',
        'users_can_register' => 'abrir el registro de usuarios es un permiso de cuentas: lo decide y lo cambia la persona.',
        'default_role'      => 'el rol de los usuarios nuevos es un permiso de cuentas: lo decide y lo cambia la persona.',
    ];

    private function claude_puede_ajustes() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return new \WP_Error( 'co_sin_ajustes', 'Los ajustes de WordPress solo los toca un Administrador.', [ 'status' => 403 ] );
        }
        return true;
    }

    private static function claude_ajuste_valor( $clave ) {
        $v = get_option( $clave, '' );
        $tipo = self::AJUSTES[ $clave ][1];
        if ( 'si_no' === $tipo ) return (bool) $v;
        if ( in_array( $tipo, [ 'entero', 'pagina', 'categoria' ], true ) ) return (int) $v;
        if ( 'decimal' === $tipo ) return (float) $v;
        return is_scalar( $v ) ? (string) $v : '';
    }

    private static function claude_ajustes_todos() {
        $out = [];
        foreach ( array_keys( self::AJUSTES ) as $k ) $out[ $k ] = self::claude_ajuste_valor( $k );
        return $out;
    }

    private static function claude_ajustes_huella( $valores ) {
        return substr( md5( (string) wp_json_encode( $valores ) ), 0, 12 );
    }

    /** Cómo quedaría la dirección de la última entrada con una estructura de enlaces permanentes. */
    private static function claude_ajustes_ejemplo( $estructura ) {
        $p = get_posts( [ 'post_type' => 'post', 'post_status' => 'publish', 'numberposts' => 1, 'suppress_filters' => true ] );
        $p = $p ? $p[0] : null;
        $base = untrailingslashit( home_url() );
        if ( ! $p ) return null;
        if ( '' === $estructura ) return $base . '/?p=' . $p->ID;
        $t = strtotime( $p->post_date );
        $cats = get_the_category( $p->ID );
        $autor = get_userdata( $p->post_author );
        $rep = [
            '%year%' => date( 'Y', $t ), '%monthnum%' => date( 'm', $t ), '%day%' => date( 'd', $t ),
            '%hour%' => date( 'H', $t ), '%minute%' => date( 'i', $t ), '%second%' => date( 's', $t ),
            '%post_id%' => $p->ID, '%postname%' => $p->post_name,
            '%category%' => $cats ? $cats[0]->slug : 'sin-categoria',
            '%author%' => $autor && ! empty( $autor->user_nicename ) ? $autor->user_nicename : 'autor',
        ];
        return [ 'entrada' => $p->post_title, 'url' => $base . strtr( $estructura, $rep ) ];
    }

    /** GET /ajustes: las opciones por pantalla, la «version», lo bloqueado y un ejemplo de enlace permanente. */
    public function claude_ajustes( \WP_REST_Request $request ) {
        $ok = $this->claude_puede_ajustes();
        if ( is_wp_error( $ok ) ) return $ok;
        $valores = self::claude_ajustes_todos();
        $pantallas = [];
        foreach ( self::AJUSTES as $k => $def ) $pantallas[ $def[0] ][ $k ] = $valores[ $k ];
        return [
            'version'    => self::claude_ajustes_huella( $valores ),
            'ajustes'    => $pantallas,
            'direccion'  => [ 'home' => get_option( 'home' ), 'siteurl' => get_option( 'siteurl' ) ],
            'idiomas_instalados' => array_values( (array) get_available_languages() ),
            'enlace_ejemplo' => self::claude_ajustes_ejemplo( (string) $valores['permalink_structure'] ),
            'bloqueados' => self::AJUSTES_BLOQUEADOS,
        ];
    }

    /** Comprueba y normaliza un valor según su tipo. */
    private static function claude_ajuste_validar( $clave, $v ) {
        $def = self::AJUSTES[ $clave ];
        $mal = function ( $msg ) use ( $clave ) { return new \WP_Error( 'co_ajuste', '«' . $clave . '»: ' . $msg, [ 'status' => 400 ] ); };
        switch ( $def[1] ) {
            case 'texto':
                if ( ! is_scalar( $v ) ) return $mal( 'tiene que ser un texto.' );
                return sanitize_text_field( (string) $v );
            case 'textarea':
                if ( ! is_scalar( $v ) ) return $mal( 'tiene que ser un texto.' );
                return implode( "\n", array_map( 'sanitize_text_field', explode( "\n", (string) $v ) ) );
            case 'entero':
                if ( ! is_numeric( $v ) || (int) $v != $v ) return $mal( 'tiene que ser un número entero.' );
                if ( (int) $v < $def[2][0] || (int) $v > $def[2][1] ) return $mal( 'va de ' . $def[2][0] . ' a ' . $def[2][1] . '.' );
                return (int) $v;
            case 'decimal':
                if ( ! is_numeric( $v ) || abs( (float) $v ) > 14 ) return $mal( 'es un desfase horario en horas, de -12 a 14.' );
                return (float) $v;
            case 'si_no':
                if ( ! is_bool( $v ) && ! in_array( $v, [ 0, 1, '0', '1' ], true ) ) return $mal( 'es true o false.' );
                return $v ? 1 : 0;
            case 'lista':
                if ( ! in_array( $v, $def[2], true ) ) return $mal( 'vale ' . implode( ', ', $def[2] ) . '.' );
                return $v;
            case 'pagina':
                if ( ! is_numeric( $v ) ) return $mal( 'es el id de una página (0 para ninguna).' );
                if ( (int) $v ) {
                    $p = get_post( (int) $v );
                    if ( ! $p || 'page' !== $p->post_type || 'publish' !== $p->post_status ) return $mal( 'no hay ninguna página publicada con el id ' . (int) $v . '.' );
                }
                return (int) $v;
            case 'categoria':
                $t = get_term( (int) $v, 'category' );
                if ( ! $t || is_wp_error( $t ) ) return $mal( 'no hay ninguna categoría con el id ' . (int) $v . '.' );
                return (int) $v;
            case 'zona':
                if ( '' !== $v && ! in_array( $v, timezone_identifiers_list(), true ) ) return $mal( 'no es una zona horaria (p. ej. Europe/Madrid), o "" para usar gmt_offset.' );
                return (string) $v;
            case 'idioma':
                if ( '' !== $v && ! in_array( $v, (array) get_available_languages(), true ) ) return $mal( 'ese idioma no está instalado en la web («idiomas_instalados»; "" es inglés).' );
                return (string) $v;
            case 'formato':
                $f = get_post_format_strings();
                if ( '0' !== (string) $v && ! isset( $f[ $v ] ) ) return $mal( 'vale 0 (estándar) o ' . implode( ', ', array_keys( $f ) ) . '.' );
                return (string) $v;
            case 'enlace':
                $v = trim( (string) $v );
                if ( '' !== $v ) {
                    if ( '/' !== $v[0] ) $v = '/' . $v;
                    if ( ! preg_match( '/%(postname|post_id)%/', $v ) ) return $mal( 'tiene que llevar %postname% o %post_id% (o "" para los enlaces simples ?p=123).' );
                    if ( preg_match( '#[^A-Za-z0-9_%/.-]#', $v ) ) return $mal( 'solo letras, números, -, _, . y etiquetas como %postname%.' );
                }
                return $v;
            case 'base':
                $v = trim( (string) $v, " /" );
                if ( '' !== $v && ! preg_match( '#^[A-Za-z0-9_/-]+$#', $v ) ) return $mal( 'solo letras, números, - y _ (o "" para la de WordPress).' );
                return $v;
        }
        return $mal( 'tipo desconocido.' );
    }

    /**
     * POST /ajustes {version, ajustes:{opción: valor}, probar?, confirmar?}.
     * Se comprueban todos antes de guardar ninguno.
     */
    public function claude_ajustes_guardar( \WP_REST_Request $request ) {
        global $wp_rewrite;
        $ok = $this->claude_puede_ajustes();
        if ( is_wp_error( $ok ) ) return $ok;
        $p = (array) $request->get_json_params();
        $antes = self::claude_ajustes_todos();
        $v = (string) ( $p['version'] ?? '' );
        if ( '' === $v ) return new \WP_Error( 'co_sin_version', 'Falta la «version» de los ajustes (la da GET /ajustes).', [ 'status' => 428 ] );
        if ( $v !== self::claude_ajustes_huella( $antes ) ) return new \WP_Error( 'co_cambiada', 'Los ajustes han cambiado desde que se leyeron: vuelve a leerlos.', [ 'status' => 409 ] );
        $pedidos = $p['ajustes'] ?? null;
        if ( ! is_array( $pedidos ) || ! $pedidos ) return new \WP_Error( 'co_ajuste', 'Faltan los «ajustes» que cambiar: {opción: valor}.', [ 'status' => 400 ] );

        $nuevos = [];
        foreach ( $pedidos as $k => $val ) {
            if ( isset( self::AJUSTES_BLOQUEADOS[ $k ] ) ) return new \WP_Error( 'co_ajuste_bloqueado', '«' . $k . '» no se cambia por aquí: ' . self::AJUSTES_BLOQUEADOS[ $k ], [ 'status' => 403 ] );
            if ( ! isset( self::AJUSTES[ $k ] ) ) return new \WP_Error( 'co_ajuste', '«' . $k . '» no está entre los ajustes que se pueden cambiar (los da GET /ajustes).', [ 'status' => 400 ] );
            $n = self::claude_ajuste_validar( $k, $val );
            if ( is_wp_error( $n ) ) return $n;
            $nuevos[ $k ] = $n;
        }
        // Coherencia de la portada: una página estática necesita página (solo si se toca la portada).
        $toca_portada = (bool) array_intersect_key( $nuevos, array_flip( [ 'show_on_front', 'page_on_front', 'page_for_posts' ] ) );
        $mostrar = $nuevos['show_on_front'] ?? $antes['show_on_front'];
        $portada = $nuevos['page_on_front'] ?? $antes['page_on_front'];
        $blog    = $nuevos['page_for_posts'] ?? $antes['page_for_posts'];
        if ( $toca_portada && 'page' === $mostrar && ! $portada ) return new \WP_Error( 'co_ajuste', 'Con show_on_front = page hace falta «page_on_front» (la página de portada).', [ 'status' => 400 ] );
        if ( $toca_portada && 'page' === $mostrar && $portada && $portada === $blog ) return new \WP_Error( 'co_ajuste', 'La portada y la página de entradas no pueden ser la misma página.', [ 'status' => 400 ] );

        $cambios = [];
        foreach ( $nuevos as $k => $n ) {
            $a = $antes[ $k ];
            $mismo = 'si_no' === self::AJUSTES[ $k ][1] ? (bool) $a === (bool) $n : (string) $a === (string) $n;
            if ( ! $mismo ) $cambios[ $k ] = [ 'antes' => $a, 'despues' => 'si_no' === self::AJUSTES[ $k ][1] ? (bool) $n : $n ];
        }
        $enlaces = (bool) array_intersect_key( $cambios, array_flip( [ 'permalink_structure', 'category_base', 'tag_base' ] ) );
        $delicados = [];
        if ( $enlaces ) $delicados[] = 'cambian los enlaces permanentes: las direcciones que ya están en Google, redes o enlaces de otras webs dejan de llevar a su sitio si no hay redirecciones';
        if ( isset( $cambios['blog_public'] ) && ! $cambios['blog_public']['despues'] ) $delicados[] = 'la web pide a los buscadores que no la indexen';
        $respuesta = [
            'cambios'  => (object) $cambios,
            'avisos'   => $delicados,
            'enlace_ejemplo' => $enlaces ? [
                'antes'   => self::claude_ajustes_ejemplo( (string) $antes['permalink_structure'] ),
                'despues' => self::claude_ajustes_ejemplo( (string) ( $nuevos['permalink_structure'] ?? $antes['permalink_structure'] ) ),
            ] : null,
        ];
        if ( ! empty( $p['probar'] ) || ! $cambios ) {
            return $respuesta + [ 'guardado' => false, 'version' => self::claude_ajustes_huella( $antes ) ];
        }
        if ( $delicados && empty( $p['confirmar'] ) ) {
            return new \WP_Error( 'co_ajuste_confirmar', 'Este cambio necesita «confirmar»: ' . implode( '; ', $delicados ) . '. Enséñaselo a la persona (con «probar» sale el ejemplo) y repite con confirmar=true solo con su visto bueno.', [ 'status' => 412 ] );
        }

        foreach ( $cambios as $k => $c ) {
            if ( in_array( $k, [ 'permalink_structure', 'category_base', 'tag_base' ], true ) ) continue;
            update_option( $k, $nuevos[ $k ] );
        }
        if ( $enlaces ) {
            if ( isset( $cambios['permalink_structure'] ) ) $wp_rewrite->set_permalink_structure( $nuevos['permalink_structure'] );
            if ( isset( $cambios['category_base'] ) ) $wp_rewrite->set_category_base( $nuevos['category_base'] );
            if ( isset( $cambios['tag_base'] ) ) $wp_rewrite->set_tag_base( $nuevos['tag_base'] );
            flush_rewrite_rules();
        }
        $this->claude_registrar( 'ajustes', 0, implode( ', ', array_keys( $cambios ) ) );
        return $respuesta + [ 'guardado' => true, 'version' => self::claude_ajustes_huella( self::claude_ajustes_todos() ) ];
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
