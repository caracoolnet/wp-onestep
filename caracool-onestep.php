<?php
/**
 * Plugin Name: Caracool OneStep
 * Plugin URI:  https://caracool.net
 * Description: Desactiva los comentarios en todo el sitio y activa un modo de mantenimiento con página personalizable. Plugin ligero de Caracool, sin dependencias externas.
 * Version:     1.0.0
 * Author:      Caracool
 * Author URI:  https://caracool.net
 * Text Domain: caracool-onestep
 */

// ── Bloquear acceso directo al archivo ────────────────────────
if ( ! defined( 'ABSPATH' ) ) exit;

define( 'CARACOOL_ONESTEP_VERSION', '1.0.0' );
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
        ] );
    }

    // ── Guardar ajustes ───────────────────────────────────────
    public function save_settings() {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'No autorizado.' );
        check_admin_referer( 'caracool_onestep_save' );

        $ip_raw  = sanitize_textarea_field( wp_unslash( $_POST['maintenance_ip_whitelist'] ?? '' ) );
        $ip_list = array_filter( array_map( 'trim', preg_split( '/[\r\n,]+/', $ip_raw ) ) );
        $ip_list = array_values( array_unique( array_filter( $ip_list, function ( $ip ) {
            return (bool) filter_var( $ip, FILTER_VALIDATE_IP );
        } ) ) );

        $status = absint( $_POST['maintenance_http_status'] ?? 503 );
        if ( ! in_array( $status, [ 200, 503, 404, 301 ], true ) ) $status = 503;

        $bypass_role = sanitize_text_field( $_POST['maintenance_bypass_role'] ?? '' );
        if ( $bypass_role && ! in_array( $bypass_role, self::ROLE_ORDER, true ) ) $bypass_role = '';

        update_option( self::OPTION_KEY, [
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
        ] );

        wp_safe_redirect( admin_url( 'admin.php?page=' . CARACOOL_ONESTEP_SLUG . '&saved=1' ) );
        exit;
    }

    // ── Avisos admin ──────────────────────────────────────────
    public function admin_notices() {
        $screen = get_current_screen();
        if ( ! $screen || $screen->id !== 'toplevel_page_' . CARACOOL_ONESTEP_SLUG ) return;

        if ( isset( $_GET['saved'] ) ) {
            echo '<div class="notice notice-success is-dismissible"><p>✅ Configuración guardada.</p></div>';
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
            'dashicons-shield',
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
        ?>
        <div class="wrap" style="max-width:900px;">
            <div style="display:flex;align-items:center;gap:16px;margin-top:10px;">
                <?php echo self::logo_svg(); // phpcs:ignore -- markup fijo, sin datos de usuario ?>
                <h1 style="margin:0;padding:0;line-height:1.3;">
                    OneStep <span style="font-size:12px;color:#888;font-weight:normal;">v<?php echo esc_html( CARACOOL_ONESTEP_VERSION ); ?></span>
                </h1>
            </div>
            <p class="description" style="margin-top:14px;">Comentarios y modo mantenimiento en un único plugin ligero, sin dependencias externas.</p>

            <h2 class="nav-tab-wrapper" style="margin-top:16px;">
                <a href="#tab-comentarios" class="nav-tab nav-tab-active" data-co-tab="tab-comentarios">💬 Comentarios</a>
                <a href="#tab-mantenimiento" class="nav-tab" data-co-tab="tab-mantenimiento">🚧 Mantenimiento</a>
            </h2>

            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <?php wp_nonce_field( 'caracool_onestep_save' ); ?>
                <input type="hidden" name="action" value="caracool_onestep_save">

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
            'description' => 'Desactiva comentarios en todo el sitio y activa un modo de mantenimiento con página personalizable, en un único plugin ligero, sin dependencias externas.',
            'changelog'   => '<h4>1.0.0</h4><p>Versión inicial: desactivación de comentarios en todo el sitio + modo mantenimiento con página personalizable, whitelist de IPs y bypass por rol.</p>',
        ],
    ];
}, 10, 3 );
