/**
 * ControlPointMarker
 * Shared Leaflet marker + popup builder for control points, used by both
 * map.php's Control Points layer and control_points.php's Map view so the
 * two don't maintain separate copies that drift out of sync with each other.
 * Depends on Leaflet (L) being loaded first.
 */
const ControlPointMarker = (() => {
    const STATUS_COLOR = {
        Proposed:  '#6b7280',
        Set:       '#1d4ed8',
        Verified:  '#047857',
        Destroyed: '#b91c1c',
        Lost:      '#c2410c'
    };

    const statusColor = (status) => STATUS_COLOR[status] || '#6b7280';

    const esc = (s) => String(s || '')
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');

    const fmtNum = (v, decimals = 2) =>
        (v === null || v === undefined || v === '') ? '—' : Number(v).toFixed(decimals);

    // Solid triangle (apex up) — the standard NGS/USGS cartographic symbol for
    // a horizontal control station. Leaflet has no built-in triangle marker,
    // so this draws one as an inline SVG div icon.
    function triangleIcon(status) {
        const color = statusColor(status);
        return L.divIcon({
            className: 'cp-triangle-icon',
            html: `<svg width="18" height="18" viewBox="0 0 18 18">
                       <polygon points="9,1 17,16 1,16" fill="${color}" stroke="#1f2937" stroke-width="1"/>
                   </svg>`,
            iconSize: [18, 18],
            iconAnchor: [9, 13],
            popupAnchor: [0, -13]
        });
    }

    // projectHref: page-relative path to the Control Points page, since
    // map.php and control_points.php may not sit at the same depth in the
    // future — each caller says where "its" control_points.php is.
    // editable: when true, adds an Edit button calling the caller's own
    // global openPointModal(control_point_id) — only control_points.php has
    // that function, so map.php should leave this off.
    function popupHtml(p, { projectHref = './control_points.php', editable = false } = {}) {
        const projectLabel = p.project_name ? `${p.project_name} (${p.project_id})` : p.project_id;
        const projectLink = p.project_id
            ? `<a href="${projectHref}?project_id=${encodeURIComponent(p.project_id)}" class="cp-popup-project-link">${esc(projectLabel)}</a>`
            : '';
        const editBtn = editable
            ? `<button type="button" class="btn btn-secondary btn-sm cp-popup-edit-btn" onclick="openPointModal(${p.control_point_id})">
                   <i class="fas fa-edit"></i> Edit
               </button>`
            : '';

        return `
            <div class="cp-popup">
                <div class="cp-popup-title">${esc(p.point_number)}</div>
                ${p.point_name ? `<div class="cp-popup-row"><i class="fas fa-circle-dot"></i>${esc(p.point_name)}</div>` : ''}
                <div class="cp-popup-row"><i class="fas fa-circle-dot"></i>${esc(p.point_type || 'Control')} · ${esc(p.status || 'Unknown')}</div>
                ${projectLink ? `<div class="cp-popup-row"><i class="fas fa-diagram-project"></i>${projectLink}</div>` : ''}
                ${p.monument_type ? `<div class="cp-popup-row"><i class="fas fa-circle-dot"></i>${esc(p.monument_type)}</div>` : ''}
                <div class="cp-popup-row"><i class="fas fa-circle-dot"></i>N ${fmtNum(p.northing)}  E ${fmtNum(p.easting)}</div>
                <div class="cp-popup-row"><i class="fas fa-circle-dot"></i>Elev ${fmtNum(p.elevation)}</div>
                ${p.latitude && p.longitude ? `<div class="cp-popup-row"><i class="fas fa-circle-dot"></i>${Number(p.latitude).toFixed(7)}, ${Number(p.longitude).toFixed(7)}</div>` : ''}
                ${editBtn}
            </div>
        `;
    }

    function createMarker(p, options = {}) {
        const marker = L.marker([parseFloat(p.latitude), parseFloat(p.longitude)], { icon: triangleIcon(p.status) });
        marker.bindPopup(popupHtml(p, options), { maxWidth: 300 });
        return marker;
    }

    return { STATUS_COLOR, statusColor, triangleIcon, popupHtml, createMarker };
})();
