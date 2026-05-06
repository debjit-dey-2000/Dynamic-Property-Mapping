<?php
/**
 * Plugin Name: Dynamic Property Map
 * Description: Manage property locations from WP Admin and display them on an interactive map. Use shortcode [property_map] in any Elementor section.
 * Version: 2.0.0
 * Author: Debjit Dey
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ─────────────────────────────────────────────
// 1. REGISTER CUSTOM POST TYPE: Properties
// ─────────────────────────────────────────────
add_action( 'init', function () {
    register_post_type( 'property', [
        'labels' => [
            'name'               => 'Properties',
            'singular_name'      => 'Property',
            'add_new'            => 'Add New Property',
            'add_new_item'       => 'Add New Property',
            'edit_item'          => 'Edit Property',
            'new_item'           => 'New Property',
            'view_item'          => 'View Property',
            'search_items'       => 'Search Properties',
            'not_found'          => 'No properties found',
            'not_found_in_trash' => 'No properties found in Trash',
            'menu_name'          => 'Properties',
        ],
        'public'          => true,
        'show_in_menu'    => true,
        'menu_position'   => 20,
        'menu_icon'       => 'dashicons-location-alt',
        'supports'        => [ 'title', 'thumbnail' ],
        'show_in_rest'    => true,
        'has_archive'     => false,
    ]);
});

// ─────────────────────────────────────────────
// 2. META BOX
// ─────────────────────────────────────────────
add_action( 'add_meta_boxes', function () {
    add_meta_box(
        'property_details',
        '📍 Property Details & Location',
        'dpm_render_meta_box',
        'property',
        'normal',
        'high'
    );
});

function dpm_render_meta_box( $post ) {
    wp_nonce_field( 'dpm_save_meta', 'dpm_nonce' );

    $fields = [
        'dpm_address'       => [ 'label' => 'Address',          'type' => 'text',   'placeholder' => '189 Ten Mile Circle, Frisco, Colorado',  'full' => true ],
        'dpm_property_type' => [ 'label' => 'Property Type',    'type' => 'text',   'placeholder' => 'Retail, Office, Industrial…',             'full' => false ],
        'dpm_sqft'          => [ 'label' => 'Total Square Feet','type' => 'text',   'placeholder' => '101,506 SF',                              'full' => false ],
        'dpm_lat'           => [ 'label' => 'Latitude ★',       'type' => 'number', 'placeholder' => '39.4817',                                 'full' => false, 'step' => 'any' ],
        'dpm_lng'           => [ 'label' => 'Longitude ★',      'type' => 'number', 'placeholder' => '-106.0736',                               'full' => false, 'step' => 'any' ],
        'dpm_link'          => [ 'label' => 'Property URL (optional)', 'type' => 'url', 'placeholder' => 'https://…',                           'full' => true ],
    ];
    ?>
    <style>
        .dpm-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;padding:10px 0}
        .dpm-field{display:flex;flex-direction:column;gap:5px}
        .dpm-field.full{grid-column:1/-1}
        .dpm-field label{font-weight:600;font-size:12px;color:#1d2327;text-transform:uppercase;letter-spacing:.04em}
        .dpm-field input{padding:8px 10px;border:1px solid #c3c4c7;border-radius:4px;font-size:13px}
        .dpm-field input:focus{border-color:#2271b1;box-shadow:0 0 0 1px #2271b1;outline:none}
        .dpm-tip{background:#f0f6fc;border-left:4px solid #2271b1;padding:10px 14px;margin-bottom:14px;font-size:12.5px;line-height:1.6;border-radius:0 4px 4px 0}
        .dpm-tip a{color:#2271b1}
        .dpm-img-tip{background:#fef9e7;border-left:4px solid #f0ad00;padding:10px 14px;margin-bottom:14px;font-size:12.5px;line-height:1.6;border-radius:0 4px 4px 0}
    </style>
    <div class="dpm-img-tip">
        📷 <strong>Property Image:</strong> Set the <strong>Featured Image</strong> in the right-hand panel — this is the photo shown at the top of the map sidebar.
    </div>
    <div class="dpm-tip">
        ★ <strong>Coordinates:</strong> Right-click any spot in <a href="https://maps.google.com" target="_blank">Google Maps</a> and click the coordinates to copy them, or use <a href="https://www.latlong.net/" target="_blank">latlong.net</a>.
    </div>
    <div class="dpm-grid">
    <?php foreach ( $fields as $key => $field ) :
        $value = esc_attr( get_post_meta( $post->ID, $key, true ) );
        $extra = isset($field['step']) ? ' step="'.$field['step'].'"' : '';
        $cls   = $field['full'] ? ' full' : '';
    ?>
        <div class="dpm-field<?php echo $cls; ?>">
            <label for="<?php echo $key; ?>"><?php echo esc_html($field['label']); ?></label>
            <input type="<?php echo $field['type']; ?>" id="<?php echo $key; ?>" name="<?php echo $key; ?>"
                   value="<?php echo $value; ?>" placeholder="<?php echo esc_attr($field['placeholder']); ?>"<?php echo $extra; ?>>
        </div>
    <?php endforeach; ?>
    </div>
    <?php
}

// ─────────────────────────────────────────────
// 3. SAVE META
// ─────────────────────────────────────────────
add_action( 'save_post_property', function ( $post_id ) {
    if ( ! isset( $_POST['dpm_nonce'] ) || ! wp_verify_nonce( $_POST['dpm_nonce'], 'dpm_save_meta' ) ) return;
    if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;
    foreach ( [ 'dpm_address', 'dpm_property_type', 'dpm_sqft', 'dpm_lat', 'dpm_lng', 'dpm_link' ] as $key ) {
        if ( isset( $_POST[$key] ) ) update_post_meta( $post_id, $key, sanitize_text_field( $_POST[$key] ) );
    }
});

// ─────────────────────────────────────────────
// 4. ADMIN LIST COLUMNS
// ─────────────────────────────────────────────
add_filter( 'manage_property_posts_columns', function( $cols ) {
    return [
        'cb'          => $cols['cb'],
        'title'       => 'Property Name',
        'dpm_thumb'   => 'Photo',
        'dpm_type'    => 'Type',
        'dpm_address' => 'Address',
        'dpm_coords'  => 'Coordinates',
        'dpm_sqft'    => 'Sq Ft',
    ];
});

add_action( 'manage_property_posts_custom_column', function( $col, $post_id ) {
    switch ( $col ) {
        case 'dpm_thumb':
            $url = get_the_post_thumbnail_url($post_id, 'thumbnail');
            echo $url
                ? '<img src="'.esc_url($url).'" style="width:50px;height:38px;object-fit:cover;border-radius:3px">'
                : '<span style="color:#aaa;font-size:11px">No image</span>';
            break;
        case 'dpm_type':    echo esc_html( get_post_meta($post_id,'dpm_property_type',true) ?: '—' ); break;
        case 'dpm_address': echo esc_html( get_post_meta($post_id,'dpm_address',true) ?: '—' ); break;
        case 'dpm_sqft':    echo esc_html( get_post_meta($post_id,'dpm_sqft',true) ?: '—' ); break;
        case 'dpm_coords':
            $lat = get_post_meta($post_id,'dpm_lat',true);
            $lng = get_post_meta($post_id,'dpm_lng',true);
            echo ($lat && $lng)
                ? '<code style="font-size:11px">'.esc_html($lat).', '.esc_html($lng).'</code>'
                : '<span style="color:#cc1818">⚠ Missing</span>';
            break;
    }
}, 10, 2 );

// ─────────────────────────────────────────────
// 5. REST API — /wp-json/dpm/v1/properties
// ─────────────────────────────────────────────
add_action( 'rest_api_init', function () {
    register_rest_route( 'dpm/v1', '/properties', [
        'methods'             => 'GET',
        'callback'            => 'dpm_get_properties',
        'permission_callback' => '__return_true',
    ]);
});

function dpm_get_properties() {
    $query = new WP_Query([
        'post_type'      => 'property',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
    ]);
    $data = [];
    foreach ( $query->posts as $post ) {
        $lat = get_post_meta($post->ID,'dpm_lat',true);
        $lng = get_post_meta($post->ID,'dpm_lng',true);
        if (!$lat || !$lng) continue;
        $data[] = [
            'id'            => $post->ID,
            'name'          => $post->post_title,
            'address'       => get_post_meta($post->ID,'dpm_address',true),
            'property_type' => get_post_meta($post->ID,'dpm_property_type',true),
            'sqft'          => get_post_meta($post->ID,'dpm_sqft',true),
            'link'          => get_post_meta($post->ID,'dpm_link',true),
            'lat'           => (float)$lat,
            'lng'           => (float)$lng,
            'image'         => get_the_post_thumbnail_url($post->ID,'large') ?: '',
        ];
    }
    return rest_ensure_response($data);
}

// ─────────────────────────────────────────────
// 6. SHORTCODE [property_map]
// ─────────────────────────────────────────────
add_shortcode( 'property_map', function ( $atts ) {
    $atts = shortcode_atts([
        'height' => '540px',
        'zoom'   => '20',
    ], $atts, 'property_map');

    $uid     = 'dpm_' . uniqid();
    $api_url = esc_url( rest_url('dpm/v1/properties') );
    ob_start();
    ?>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>

    <div class="dpm-wrap" id="<?php echo $uid; ?>" style="height:<?php echo esc_attr($atts['height']); ?>">

        <!-- ── LEFT SIDEBAR ── -->
        <div class="dpm-sidebar">

            <!-- Property photo -->
            <div class="dpm-photo-wrap">
                <img class="dpm-photo" src="" alt="">
                <div class="dpm-photo-placeholder">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#4a6080" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                    <span>No image</span>
                </div>
            </div>

            <!-- Nav bar: ← NAME ◆ -->
            <div class="dpm-nav-bar">
                <button class="dpm-nav-btn dpm-prev" title="Previous property">
                    <svg width="12" height="8" viewBox="0 0 12 8" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M0.146446 3.33004C-0.0488157 3.5253 -0.0488157 3.84189 0.146446 4.03715L3.32843 7.21913C3.52369 7.41439 3.84027 7.41439 4.03553 7.21913C4.2308 7.02387 4.2308 6.70728 4.03553 6.51202L1.20711 3.68359L4.03553 0.855167C4.2308 0.659904 4.2308 0.343322 4.03553 0.14806C3.84027 -0.0472023 3.52369 -0.0472023 3.32843 0.14806L0.146446 3.33004ZM11.5 3.68359V3.18359L0.5 3.18359V3.68359V4.18359L11.5 4.18359V3.68359Z" fill="white"/>
</svg>

                </button>
                <span class="dpm-nav-name">Loading…</span>
                <button class="dpm-nav-btn dpm-next" title="Next property">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<rect y="12" width="16.9718" height="16.9718" transform="rotate(-45 0 12)" fill="white"/>
<path d="M15.3506 13.7666C15.3514 13.7659 15.3551 13.7639 15.3574 13.7617C15.3606 13.7588 15.3645 13.7531 15.3701 13.7471L17.9268 11.1914L17.9258 11.1914C18.2046 10.9148 18.2224 10.4757 17.9805 10.1787L17.9287 10.1211L15.373 7.56738L15.3721 7.56641C15.0752 7.27439 14.5977 7.27621 14.3047 7.57129C14.0122 7.8662 14.0122 8.33985 14.3047 8.63477L15.5713 9.90137L9.19141 9.90137C8.77455 9.90152 8.43555 10.2403 8.43555 10.6572L8.43555 16.8945C8.4357 17.3113 8.77465 17.6502 9.19141 17.6504C9.60829 17.6504 9.94712 17.3114 9.94727 16.8945L9.94727 11.4131L15.5684 11.4131L14.3027 12.6787L14.3027 12.6797C14.0032 12.968 13.9943 13.446 14.2813 13.7451C14.5513 14.0282 14.9901 14.0543 15.291 13.8193L15.3486 13.7686L15.3516 13.7666L15.3506 13.7666Z" fill="#1E2749" stroke="#1E2749" stroke-width="0.3"/>
</svg>

        </button>
            </div>

            <!-- Details -->
            <div class="dpm-details">
                <div class="dpm-field-group">
                    <div class="dpm-label">Name</div>
                    <div class="dpm-value dpm-val-name">—</div>
                </div>
                <div class="dpm-divider"></div>
                <div class="dpm-field-group">
                    <div class="dpm-label">Description</div>
                    <div class="dpm-value dpm-val-address">—</div>
                </div>
                <div class="dpm-divider"></div>
                <div class="dpm-property-row">
                    <div class="dpm-field-group property-type">
                        <div class="dpm-label property-type-label">Property Type: </div>
                        <div class="dpm-value dpm-val-type">—</div>
                    </div>
                    <div class="dpm-field-group sqft">
                        <div class="dpm-label sqft-label">Total Square Feet: </div>
                        <div class="dpm-value dpm-val-sqft">—</div>
                    </div>
                </div>
                <a class="dpm-cta" href="#" target="_blank" style="display:none">
                    View Property
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="7" y1="17" x2="17" y2="7"/><polyline points="7 7 17 7 17 17"/></svg>
                </a>
            </div>
        </div>

        <!-- ── MAP ── -->
        <div class="dpm-map-wrap">
            <div class="dpm-map" id="<?php echo $uid; ?>_map"></div>
        </div>
    </div>

    <style>
    /* ── Root ─────────────────────────────────── */
    .dpm-wrap {
        display: flex;
        overflow: hidden;
        /* font-family: 'Helvetica Neue', Arial, sans-serif; */
        /* box-shadow: 0 6px 32px rgba(0,0,0,.16); */
        border-radius: 0;
        background: #fff;
    }

    /* ── Sidebar ─────────────────────────────── */
    .dpm-sidebar {
        width: 373px;
        min-width: 373px;
        display: flex;
        flex-direction: column;
        background: #fff;
        overflow: hidden;
        position: relative;
        z-index: 10;
    }

    /* ── Property Photo ──────────────────────── */
    .dpm-photo-wrap {
        position: relative;
        width: 100%;
        height: auto;
        background: #e8edf3;
        flex-shrink: 0;
        overflow: hidden;
        aspect-ratio: 4 / 2;
        display: flex;
    }
    .dpm-photo {
        width: 100%;
        /* height: 100%; */
        object-fit: cover;
        display: block;
        transition: opacity .35s ease;
        opacity: 0;
    }
    .dpm-photo.loaded { 
        opacity: 1;
        aspect-ratio: 4 / 3;
    }
    .dpm-photo-placeholder {
        position: absolute;
        inset: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 8px;
        color: #7a8fa6;
        font-size: 12px;
        letter-spacing: .04em;
        pointer-events: none;
        transition: opacity .2s;
    }
    .dpm-photo-placeholder.hidden { opacity: 0; }

    /* ── Nav Bar ─────────────────────────────── */
    .dpm-nav-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: #1E2749;
        padding: 18px 15px;
        height: 48px;
        flex-shrink: 0;
        /* gap: 10px; */
    }
    .dpm-nav-btn {
        background: transparent !important;
        padding: 0 !important;
        border: none;
        color: rgba(255,255,255,.7);
        cursor: pointer;
        padding: 6px;
        border-radius: 3px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        transition: color .15s, background .15s;
        line-height: 1;
    }
    .dpm-nav-btn:hover { color: #fff; background: rgba(255,255,255,.1); }
    .dpm-nav-name {
        /* flex: 1; */
        font-family: 'Lora';
        font-style: normal;
        font-weight: 600;
        font-size: 16px;
        line-height: 34px;
        text-align: center;
        text-transform: uppercase;
        color: #fff;
        text-align: center;
        /* white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis; */
    }

    /* ── Details ─────────────────────────────── */
    .dpm-details {
        flex: 1;
        /* overflow-y: auto; */
        padding: 30px 22px 40px;
        background: #F6F6F6;
    }
    .dpm-field-group { margin-bottom: 0; }
    .dpm-label {
        /* font-size: 10.5px;
        font-weight: 600;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: #9aabb8; */
        margin-bottom: 10px;
        font-family: 'Poppins';
        font-style: normal;
        font-weight: 400;
        font-size: 16px;
        line-height: 15px;
        text-transform: capitalize;
        color: #464646;
    }
    .dpm-value {
        font-family: 'Poppins';
        font-style: normal;
        font-weight: 500;
        font-size: 16px;
        line-height: 20px;
        text-transform: capitalize;
        color: #1E2749;
    }
    .dpm-divider {
        border: none;
        border-top: 1px solid #E7E7E7;
        margin: 30px 0;
    }
    .dpm-property-row {
        display: flex;
        flex-direction: column;
        gap: 11px;
    }
    .property-type,
    .sqft{
        display: flex;
        justify-content: flex-start;
        align-items: center;
        gap: 7px;
    }
    .property-type-label,
    .sqft-label{
        margin: 0;
    }
    .sqft-label{
        font-family: 'Poppins';
        font-style: normal;
        font-weight: 500;
        font-size: 16px;
        line-height: 20px;
        text-transform: capitalize;
        color: #1E2749;
    }
    .dpm-val-type{
        font-family: 'Poppins';
        font-style: normal;
        font-weight: 400;
        font-size: 16px;
        line-height: 15px;
        text-transform: capitalize;
        color: #464646;
    }
    .dpm-cta {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        margin-top: 20px;
        background: #0f1f38;
        color: #fff !important;
        text-decoration: none !important;
        padding: 9px 18px;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: .08em;
        text-transform: uppercase;
        border-radius: 3px;
        transition: background .15s;
    }
    .dpm-cta:hover { background: #1a3358; }

    /* ── Map ─────────────────────────────────── */
    .dpm-map-wrap {
        flex: 1;
        min-width: 0;
        position: relative;
    }
    .dpm-map {
        width: 100%;
        height: 100%;
    }

    /* ── Marker ──────────────────────────────── */
    .dpm-pin {
        width: 40px;
        height: 40px;
        /* background: #0f1f38; */
        /* border: 2.5px solid #fff; */
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0px 0px 0px 0px #fff;
        cursor: pointer;
        transition: transform .15s, box-shadow .15s;
    }
    /* .dpm-pin::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 1);
        border-radius: 50%;
        transform: translate(-50%, -50%) scale(1);
        animation: pulse 1.8s infinite;
        z-index: -1;
        opacity: 0;
    }

    @keyframes pulse {
    0% {
        transform: translate(-50%, -50%) scale(1.8);
        opacity: 1;
    }
    70% {
        transform: translate(-50%, -50%) scale(2.2);
        opacity: 0;
    }
    100% {
        transform: translate(-50%, -50%) scale(2.2);
        opacity: 0;
    }
    } */
    .dpm-pin.active {
        box-shadow: 0px 0px 0px 10px #fff;
    }
    /* .dpm-pin.active svg { stroke: #0f1f38; } */

    /* ── Leaflet overrides ───────────────────── */
    .leaflet-control-attribution { font-size: 10px !important; }
    .leaflet-touch .leaflet-bar a { width: 28px; height: 28px; line-height: 28px; }

    /* ── Responsive ──────────────────────────── */
    @media only screen and (max-width: 1200px){
        .dpm-details{
            padding: 20px 15px;
        }c91gW5TQPQVzaeczCoi6
        .dpm-divider{
            margin: 20px 0;
        }
    }
    @media (max-width: 767px) {
        .dpm-wrap { flex-direction: column; height: 1800px !important; }
        .dpm-sidebar { width: 100%; min-width: 0; }
        .dpm-photo-wrap { height: auto; }
        .dpm-map-wrap { height: 800px; }
    }
    @media (max-width: 500px) {
        .dpm-wrap { flex-direction: column; height: 1000px !important; }
        .dpm-sidebar { width: 100%; min-width: 0; }
        .dpm-photo-wrap { height: auto; }
        .dpm-map-wrap { height: 800px; }
    }
    </style>

    <script>
    (function(){
        var ROOT  = document.getElementById('<?php echo $uid; ?>');
        var MAP_ID = '<?php echo $uid; ?>_map';

        function loadLeaflet(cb){
            if(window.L){ cb(); return; }
            var s=document.createElement('script');
            s.src='https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
            s.onload=cb;
            document.head.appendChild(s);
        }

        function init(){
            var props    = [];
            var current  = 0;
            var markers  = [];

            var map = L.map(MAP_ID, {
                zoomControl: true,
                scrollWheelZoom: true
            });

            L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png',{
                attribution:'&copy; OpenStreetMap &copy; CARTO',
                subdomains:'abcd',
                maxZoom:19
            }).addTo(map);

            // DOM refs
            var photo        = ROOT.querySelector('.dpm-photo');
            var photoWrap    = ROOT.querySelector('.dpm-photo-wrap');
            var placeholder  = ROOT.querySelector('.dpm-photo-placeholder');
            var navName      = ROOT.querySelector('.dpm-nav-name');
            var valName      = ROOT.querySelector('.dpm-val-name');
            var valAddress   = ROOT.querySelector('.dpm-val-address');
            var valType      = ROOT.querySelector('.dpm-val-type');
            var valSqft      = ROOT.querySelector('.dpm-val-sqft');
            var ctaBtn       = ROOT.querySelector('.dpm-cta');
            var btnPrev      = ROOT.querySelector('.dpm-prev');
            var btnNext      = ROOT.querySelector('.dpm-next');

            function makePin(active){
                return L.divIcon({
                    className:'',
                    html:'<div class="dpm-pin'+(active?' active':'')+'">'+
                         '<svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">'+
                         '<circle opacity="0.9" cx="20" cy="20" r="20" fill="#1E2749"/>'+
                         '<path d="M29.6491 18.8947L26.5047 15.7504V12.1181C26.5047 11.4613 25.9725 10.9291 25.3148 10.9291C24.6586 10.9291 24.1264 11.4613 24.1264 12.1181V13.372L21.7851 11.0307C20.6276 9.87381 18.6151 9.87586 17.4602 11.0328L9.59809 18.8947C9.13397 19.3598 9.13397 20.1122 9.59809 20.5765C10.0624 21.0415 10.8163 21.0415 11.2805 20.5765L19.1418 12.7144C19.3979 12.4597 19.8493 12.4597 20.1041 12.7137L27.9667 20.5765C28.0771 20.6871 28.2082 20.7747 28.3525 20.8344C28.4969 20.8942 28.6515 20.9248 28.8077 20.9246C29.1122 20.9246 29.4167 20.8089 29.6492 20.5765C30.1134 20.1123 30.1134 19.3598 29.6491 18.8947Z" fill="white"/>'+
                         '<path d="M20.0376 14.9681C19.8092 14.7398 19.4393 14.7398 19.2116 14.9681L12.2961 21.8815C12.1865 21.9913 12.125 22.1401 12.125 22.2952V27.3376C12.125 28.5209 13.0844 29.4802 14.2676 29.4802H17.6915V24.1778H21.557V29.4802H24.9809C26.164 29.4802 27.1234 28.5209 27.1234 27.3377V22.2952C27.1234 22.1397 27.0621 21.9907 26.9523 21.8815L20.0376 14.9681Z" fill="white"/>'+
                         '</svg></div>',
                    iconSize:[36,36],
                    iconAnchor:[18,18],
                });
            }

            function updateSidebar(p){
                // Photo
                if(p.image){
                    photo.classList.remove('loaded');
                    photo.onload = function(){ photo.classList.add('loaded'); };
                    photo.src = p.image;
                    placeholder.classList.add('hidden');
                } else {
                    photo.classList.remove('loaded');
                    photo.src = '';
                    placeholder.classList.remove('hidden');
                }

                // Text
                navName.textContent    = p.name || '';
                valName.textContent    = p.name || '—';
                valAddress.textContent = p.address || '—';
                valType.textContent    = p.property_type || '—';
                valSqft.textContent    = p.sqft ? p.sqft + (p.sqft.toString().toLowerCase().includes('sf') ? '' : ' SF') : '—';

                // CTA
                if(p.link){
                    ctaBtn.href = p.link;
                    ctaBtn.style.display = 'inline-flex';
                } else {
                    ctaBtn.style.display = 'none';
                }

                // Link button (next/arrow)
                // if(p.link){
                //     btnNext.href = p.link;
                //     btnNext.style.pointerEvents = 'auto';
                //     btnNext.style.opacity = '1';
                // } else {
                //     btnNext.href = '#';
                //     btnNext.style.pointerEvents = 'none';
                //     btnNext.style.opacity = '0.35';
                // }


                // Link button
                // currentLink = p.link || '';
                // if(currentLink){
                //     btnNext.setAttribute('href', currentLink);
                //     btnNext.style.pointerEvents = 'auto';
                //     btnNext.style.opacity = '1';
                // } else {
                //     btnNext.setAttribute('href', '#');
                //     btnNext.style.pointerEvents = 'none';
                //     btnNext.style.opacity = '0.35';
                // }
            }

            function selectProperty(idx){
                // reset old marker
                if(markers[current]){
                    markers[current].setIcon(makePin(false));
                }
                current = ((idx % props.length) + props.length) % props.length;
                var p = props[current];

                // highlight new marker
                if(markers[current]){
                    markers[current].setIcon(makePin(true));
                    map.panTo([p.lat, p.lng], {animate:true, duration:0.5});
                }

                updateSidebar(p);
            }

            // Prev / Next
            btnPrev.addEventListener('click', function(){ selectProperty(current - 1); });
            btnNext.addEventListener('click', function(){ selectProperty(current + 1); });

            // Fetch data
            fetch('<?php echo $api_url; ?>')
                .then(function(r){ return r.json(); })
                .then(function(data){
                    props = data;
                    if(!props.length){
                        navName.textContent = 'No properties found';
                        return;
                    }

                    // Create markers
                    props.forEach(function(p, i){
                        var m = L.marker([p.lat, p.lng], { icon: makePin(false) }).addTo(map);
                        markers.push(m);
                        m.on('click', function(){ selectProperty(i); });
                    });

                    // Fit bounds
                    var group = L.featureGroup(markers);
                    map.fitBounds(group.getBounds().pad(0.12));

                    // Show first
                    selectProperty(0);
                })
                .catch(function(e){
                    console.error('Property Map error:', e);
                    navName.textContent = 'Failed to load';
                });
        }

        if(document.readyState==='loading'){
            document.addEventListener('DOMContentLoaded', function(){ loadLeaflet(init); });
        } else {
            loadLeaflet(init);
        }
    })();
    </script>
    <?php
    return ob_get_clean();
});

// ─────────────────────────────────────────────
// 7. ACTIVATION NOTICE
// ─────────────────────────────────────────────
register_activation_hook( __FILE__, function(){
    set_transient('dpm_activated', true, 5);
});

add_action('admin_notices', function(){
    if(get_transient('dpm_activated')){
        delete_transient('dpm_activated');
        echo '<div class="notice notice-success is-dismissible">
            <p>✅ <strong>Dynamic Property Map</strong> activated! Go to
            <a href="'.admin_url('edit.php?post_type=property').'">Properties</a> to add locations,
            then place <code>[property_map]</code> in any Elementor section.</p>
        </div>';
    }
});
