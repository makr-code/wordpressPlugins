export function clamp(value, min, max) {
    return Math.max(min, Math.min(max, value));
}

export function longitudeDelta(from, to) {
    return ((to - from + 540) % 360 + 360) % 360 - 180;
}

export function latLonToPosition(lon, lat, radius = 1) {
    const longitude = lon * Math.PI / 180;
    const latitude = lat * Math.PI / 180;
    const horizontal = radius * Math.cos(latitude);
    return [horizontal * Math.cos(longitude), radius * Math.sin(latitude), -horizontal * Math.sin(longitude)];
}

export function validCoordinates(lat, lon) {
    return typeof lat === 'number' && typeof lon === 'number'
        && Number.isFinite(lat) && Number.isFinite(lon)
        && Math.abs(lat) <= 90 && Math.abs(lon) <= 180;
}

export function validateNodes(payload) {
    if (!payload || !Array.isArray(payload.nodes) || !['demo', 'production'].includes(payload.source)) {
        throw new Error('Invalid telemetry response.');
    }
    for (const node of payload.nodes) {
        if (!node || !validCoordinates(node.lat, node.lon)
            || !Number.isInteger(node.instances) || node.instances < 1
            || !Number.isFinite(node.load) || node.load < 0 || node.load > 100
            || typeof node.city !== 'string' || typeof node.country !== 'string') {
            throw new Error('Telemetry contains an invalid instance location.');
        }
    }
    return payload;
}

export function geoJsonPolygons(geojson) {
    if (!geojson || geojson.type !== 'FeatureCollection' || !Array.isArray(geojson.features)) {
        throw new Error('Invalid continent GeoJSON.');
    }
    const polygons = [];
    for (const feature of geojson.features) {
        const geometry = feature.geometry;
        if (!geometry) continue;
        const parts = geometry.type === 'Polygon' ? [geometry.coordinates]
            : geometry.type === 'MultiPolygon' ? geometry.coordinates : [];
        for (const polygon of parts) {
            if (!Array.isArray(polygon) || !polygon.length) throw new Error('Invalid GeoJSON polygon.');
            for (const ring of polygon) {
                if (!Array.isArray(ring) || ring.length < 4
                    || ring.some(point => !Array.isArray(point) || !validCoordinates(point[1], point[0]))) {
                    throw new Error('Invalid GeoJSON ring.');
                }
            }
            polygons.push(polygon);
        }
    }
    if (!polygons.length) throw new Error('The GeoJSON contains no continents.');
    return polygons;
}

export function unwrapRing(ring) {
    let previous = ring[0][0];
    return ring.map(([lon, lat], index) => {
        if (index) {
            lon = previous + longitudeDelta(previous, lon);
        }
        previous = lon;
        return [lon, lat];
    });
}

export function drawContinents(context, polygons, width, height, palette) {
    context.fillStyle = palette.ocean;
    context.fillRect(0, 0, width, height);
    context.fillStyle = palette.land;
    context.strokeStyle = palette.coast;
    context.lineWidth = 0.7;
    for (const polygon of polygons) {
        const rings = polygon.map(unwrapRing);
        // Repeat across the texture seam. Holes stay aligned with their outer ring.
        const outerCenter = rings[0].reduce((sum, point) => sum + point[0], 0) / rings[0].length;
        const aligned = rings.map(ring => {
            const center = ring.reduce((sum, point) => sum + point[0], 0) / ring.length;
            const offset = Math.round((outerCenter - center) / 360) * 360;
            return ring.map(([lon, lat]) => [lon + offset, lat]);
        });
        for (const shift of [-360, 0, 360]) {
            context.beginPath();
            for (const ring of aligned) {
                ring.forEach(([lon, lat], index) => {
                    const x = (lon + shift + 180) / 360 * width;
                    const y = (90 - lat) / 180 * height;
                    if (index === 0) context.moveTo(x, y);
                    else context.lineTo(x, y);
                });
                context.closePath();
            }
            context.fill('evenodd');
            context.stroke();
        }
    }
}
