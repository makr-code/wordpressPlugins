import { clamp, longitudeDelta, latLonToPosition } from './globe-geo.js';

export const MODE_ZOOM = { globe: 1.12, region: 2, country: 3.2 };
export const DEFAULT_SETTINGS = { mode: 'globe', animation: true, drag: true, cities: true, speed: 1, target: 'auto', zoom: 1.12 };
export const SETTINGS_COOKIE = 'themisdb_gnm_settings';

export function parseSettings(raw, targetIds) {
    const value = JSON.parse(raw);
    if (!value || value.version !== 1 || !Object.hasOwn(MODE_ZOOM, value.mode)
        || typeof value.animation !== 'boolean' || typeof value.drag !== 'boolean'
        || (value.cities !== undefined && typeof value.cities !== 'boolean')
        || !Number.isFinite(value.speed) || value.speed < 0.5 || value.speed > 2
        || !Number.isFinite(value.zoom) || value.zoom < 1 || value.zoom > 4
        || (value.target !== 'auto' && !targetIds.includes(value.target))) {
        throw new Error('Invalid saved globe settings.');
    }
    return { mode: value.mode, animation: value.animation, drag: value.drag, cities: value.cities ?? true, speed: value.speed, target: value.target, zoom: value.zoom };
}

function ease(value) {
    const t = clamp(value, 0, 1);
    return t * t * t * (t * (t * 6 - 15) + 10);
}

export function sphericalRoute(from, to, progress) {
    const t = clamp(progress, 0, 1);
    if (t === 0) return { lon: from.lon, lat: from.lat };
    if (t === 1) return { lon: to.lon, lat: to.lat };
    const a = latLonToPosition(from.lon, from.lat);
    const b = latLonToPosition(to.lon, to.lat);
    const dot = clamp(a.reduce((sum, value, i) => sum + value * b[i], 0), -1, 1);
    let tangent = b.map((value, i) => value - dot * a[i]);
    let length = Math.hypot(...tangent);
    if (length < 1e-8) {
        if (dot > 0) return { lon: from.lon, lat: from.lat };
        // Antipodal points have no unique shortest arc; choose a deterministic perpendicular.
        const axis = Math.abs(a[1]) < 0.9 ? [0, 1, 0] : [1, 0, 0];
        const projection = a.reduce((sum, value, i) => sum + value * axis[i], 0);
        tangent = axis.map((value, i) => value - projection * a[i]);
        length = Math.hypot(...tangent);
    }
    const angle = Math.acos(dot) * t;
    const point = a.map((value, i) => value * Math.cos(angle) + tangent[i] / length * Math.sin(angle));
    const lon = Math.atan2(-point[2], point[0]) * 180 / Math.PI;
    const reference = from.lon + longitudeDelta(from.lon, to.lon) * t;
    return { lon: reference + longitudeDelta(reference, lon), lat: Math.asin(clamp(point[1], -1, 1)) * 180 / Math.PI };
}

export function createFlight(view, destination, zoom) {
    return {
        from: { ...view },
        to: { lon: view.lon + longitudeDelta(view.lon, destination.lon), lat: destination.lat, zoom },
        elapsed: 0,
    };
}

export function sampleFlight(flight, elapsed) {
    const { from, to } = flight;
    if (elapsed < 1.8) {
        const t = ease(elapsed / 1.8);
        return { lon: from.lon, lat: from.lat, zoom: from.zoom + (1 - from.zoom) * t, phase: 'depart', done: false };
    }
    if (elapsed < 5.6) {
        const t = ease((elapsed - 1.8) / 3.8);
        return { ...sphericalRoute(from, to, t), zoom: 1, phase: 'travel', done: false };
    }
    const t = ease((elapsed - 5.6) / 2.2);
    return { lon: to.lon, lat: to.lat, zoom: 1 + (to.zoom - 1) * t, phase: elapsed < 7.8 ? 'arrive' : 'hold', done: elapsed >= 7.8 };
}

export function coverDistance(verticalFov, aspect, zoom) {
    // Cover both canvas dimensions; wide/tall viewports intentionally crop the sphere.
    const halfFov = Math.max(verticalFov / 2, Math.atan(Math.tan(verticalFov / 2) * aspect));
    const distance = 1 / Math.sin(halfFov);
    return 1.04 + (Math.max(1.05, distance) - 1.04) / zoom;
}
