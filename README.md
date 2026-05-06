# Dynamic Property Map

Manage property locations from WP Admin and display them on an interactive map. Use shortcode `[property_map]` in any Elementor section.

---

## Features

- 📍 Custom **Properties** post type with a dedicated WP Admin menu
- 🖼 Property photo via **Featured Image** — displays in the sidebar
- 🗺 Interactive **Leaflet.js** map with custom branded pins
- 🔄 **Prev / Next** navigation cycles through properties in the sidebar
- 🔗 Arrow button opens the selected property's **Google Maps / external URL** in a new tab
- 👥 Works for **all visitors** — no login required (data injected directly from PHP, no REST API dependency)
- 📱 **Mobile responsive** — stacks vertically on small screens
- ⚡ **No external dependencies** beyond Leaflet (loaded from CDN)

---

## Installation

1. Download `property-map-v2.zip`
2. Go to **WordPress Admin → Plugins → Add New → Upload Plugin**
3. Choose the zip file and click **Install Now**
4. Click **Activate Plugin**

---

## Adding Properties

1. Go to **WordPress Admin → Properties → Add New**
2. Fill in the fields:

| Field | Description |
|---|---|
| **Title** | Property name (e.g. The Village At Copper Mountain) |
| **Address** | Street address shown in the sidebar description |
| **Property Type** | e.g. Retail, Office, Industrial |
| **Total Square Feet** | e.g. 101,506 SF |
| **Latitude** | Decimal latitude coordinate |
| **Longitude** | Decimal longitude coordinate |
| **Property URL** | Google Maps link or any external URL — opens when the arrow button is clicked |
| **Featured Image** | Property photo displayed at the top of the sidebar |

3. Click **Publish** — the property appears on the map immediately

> **Finding Coordinates:** Right-click any location in [Google Maps](https://maps.google.com) → the coordinates appear at the top of the context menu. Click them to copy. Or use [latlong.net](https://www.latlong.net/).

---

## Embedding the Map

Place this shortcode in any **Elementor Shortcode widget**, page, or post:

```
[property_map]
```

### Optional Parameters

| Parameter | Default | Description |
|---|---|---|
| `height` | `540px` | Height of the map section |

**Example with custom height:**
```
[property_map height="650px"]
```

---

## How the Sidebar Works

| Element | Behaviour |
|---|---|
| **Property photo** | Loaded from the Featured Image. Fades in when ready. |
| **← (left arrow)** | Cycles to the previous property and pans the map |
| **Property name** | Displays the title of the currently selected property |
| **→ (arrow button)** | Opens the selected property's URL in a new tab. Fades out if no URL is set. |
| **Name / Description / Type / Sq Ft** | Pulled from the property's meta fields |
| **Map pins** | Click any pin to load that property in the sidebar |

---

## Modifying the Plugin

All code lives in a single file:

```
wp-content/plugins/property-map-v2/property-map.php
```

| What to change | Where to find it |
|---|---|
| Sidebar & map CSS | Search for `<style>` inside the shortcode function |
| Map tile style | Search for `L.tileLayer(` |
| Pin icon SVG | Search for `function makePin(` |
| Sidebar HTML layout | Search for `<!-- ── LEFT SIDEBAR ── -->` |
| Default map height | Search for `'height' => '540px'` |
| Admin meta box fields | Search for `function dpm_render_meta_box(` |

> **Tip:** Use the **File Manager** in your hosting cPanel or the free [Code Editor](https://wordpress.org/plugins/code-editor/) plugin to edit the file without FTP.

---

## Shortcode Reference

```
[property_map height="540px"]
```

---

## Requirements

- WordPress 5.8+
- PHP 7.4+
- Elementor (any version) — or any page builder / classic editor that supports shortcodes

---

## Changelog

### v2.0.0
- Redesigned sidebar to match exact client design (photo + nav bar + details panel)
- Featured Image support for property photos
- Arrow button now opens the selected property's URL (not next property)
- Data injected directly from PHP — no REST API call, works for logged-out visitors
- Custom branded SVG map pins

### v1.0.0
- Initial release
- Custom post type, meta boxes, REST API endpoint, Leaflet map
