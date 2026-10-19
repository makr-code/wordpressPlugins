(function () {
    'use strict';

    const DEFAULT_VIEW = {
        lon: 8,
        lat: 50
    };

    const DRAG_SENSITIVITY = {
        lon: 44,
        lat: 34
    };

    const ZOOM_VELOCITY = {
        drag: 0.24,
        inertia: 0.2
    };

    const MAP_CAMERA = {
        centerLon: DEFAULT_VIEW.lon,
        centerLat: DEFAULT_VIEW.lat,
        zoom: 1.1,
        scale: 1.1,
        offsetX: 0.5,
        offsetY: 0.5
    };

    const MAP_MODE_SETTINGS = {
        globe: { zoom: 1.35, maxZoom: 2.4, globeScale: 1.45, animationSpeed: 0.0045, focusZoom: 2.8 },
        region: { zoom: 3.2, maxZoom: 4.8, globeScale: 2.8, animationSpeed: 0.008, focusZoom: 6.4 },
        country: { zoom: 4.8, maxZoom: 6.2, globeScale: 4.6, animationSpeed: 0.012, focusZoom: 8.4 }
    };

    const MAP_COLOR_THEMES = {
        ocean: {
            background: '#071a2b',
            oceanStart: '#0d233a',
            oceanEnd: '#081a2a',
            land: 'rgba(157, 188, 206, 0.62)',
            landSoft: 'rgba(202, 232, 248, 0.8)',
            landDeep: 'rgba(100, 139, 172, 0.46)',
            landStroke: 'rgba(237, 245, 255, 0.58)',
            globe: 'rgba(19, 48, 68, 0.92)',
            glow: 'rgba(156, 214, 255, 0.22)',
            accent: 'rgba(153, 205, 255, 0.12)',
            atmosphere: 'rgba(126, 205, 255, 0.28)',
            shadow: 'rgba(5, 16, 28, 0.38)',
            backdropFill: 'rgba(255,255,255,0.02)',
            terminator: 'rgba(255, 255, 255, 0.2)'
        },
        aurora: {
            background: '#071d2d',
            oceanStart: '#0d3243',
            oceanEnd: '#081f2a',
            land: 'rgba(112, 197, 176, 0.68)',
            landSoft: 'rgba(186, 245, 227, 0.84)',
            landDeep: 'rgba(52, 127, 105, 0.48)',
            landStroke: 'rgba(221, 255, 243, 0.6)',
            globe: 'rgba(15, 58, 69, 0.94)',
            glow: 'rgba(121, 228, 210, 0.22)',
            accent: 'rgba(132, 244, 223, 0.14)',
            atmosphere: 'rgba(130, 249, 211, 0.24)',
            shadow: 'rgba(8, 21, 27, 0.42)',
            backdropFill: 'rgba(255,255,255,0.02)',
            terminator: 'rgba(255, 255, 255, 0.22)'
        },
        night: {
            background: '#090d1d',
            oceanStart: '#101a2f',
            oceanEnd: '#090c18',
            land: 'rgba(145, 160, 220, 0.64)',
            landSoft: 'rgba(214, 223, 255, 0.8)',
            landDeep: 'rgba(89, 101, 161, 0.46)',
            landStroke: 'rgba(228, 235, 255, 0.6)',
            globe: 'rgba(26, 28, 50, 0.94)',
            glow: 'rgba(156, 170, 255, 0.22)',
            accent: 'rgba(169, 183, 255, 0.14)',
            atmosphere: 'rgba(164, 186, 255, 0.24)',
            shadow: 'rgba(6, 7, 17, 0.42)',
            backdropFill: 'rgba(255,255,255,0.02)',
            terminator: 'rgba(255, 255, 255, 0.2)'
        }
    };

    const MAP_UI_STATE = {
        color: 'ocean',
        animationEnabled: true,
        mouseControlEnabled: true,
        dragActive: false,
        dragStartX: 0,
        dragStartY: 0,
        dragOriginLon: 0,
        dragOriginLat: 0,
        zoom: 1.1
    };

    const GLOBE_SOLAR_ANGLE = 0.6;

    const REGION_TARGETS = [
        { id: 'north-america', name: 'North America', lon: -98, lat: 38 },
        { id: 'france', name: 'France', lon: 2.2, lat: 46.2 },
        { id: 'europe', name: 'Europe', lon: 12, lat: 50 },
        { id: 'middle-east', name: 'Middle East', lon: 45, lat: 25 },
        { id: 'asia-pacific', name: 'Asia Pacific', lon: 109, lat: 36 },
        { id: 'oceania', name: 'Oceania', lon: 151, lat: -25 },
        { id: 'south-america', name: 'South America', lon: -58, lat: -13 },
        { id: 'africa', name: 'Africa', lon: 20, lat: 4 }
    ];

    const TARGET_BY_ID = REGION_TARGETS.reduce(function (accumulator, target, index) {
        accumulator[target.id] = Object.assign({ index: index }, target);
        return accumulator;
    }, {});

    const CAMERA_ANIMATION = {
        mode: 'globe',
        targetLon: 8,
        targetLat: 50,
        targetZoom: 1.15,
        lastSwitch: 0,
        globeDrift: 0,
        orbitPhase: 0,
        switchIntervalMs: 20000,
        regionIndex: 1,
        focusTarget: null,
        velocityLon: 0,
        velocityLat: 0,
        velocityZoom: 0,
        followInertia: 0.82
    };

    const GLOBE_STATE = {
        geojson: null
    };

    function getRuntimeMode() {
        const mode = window.themisdbGnmData && window.themisdbGnmData.mode ? window.themisdbGnmData.mode : 'auto';
        return mode === 'production' || mode === 'demo' ? mode : 'auto';
    }

    function getNodeGeo(node) {
        if (!node) {
            return null;
        }

        const lat = typeof node.lat === 'number' ? node.lat : parseFloat(node.lat);
        const lon = typeof node.lon === 'number' ? node.lon : parseFloat(node.lon);
        if (Number.isFinite(lat) && Number.isFinite(lon)) {
            return { lat: clamp(lat, -90, 90), lon: clamp(lon, -180, 180) };
        }

        if (node.geo && Number.isFinite(node.geo.lat) && Number.isFinite(node.geo.lon)) {
            return { lat: clamp(node.geo.lat, -90, 90), lon: clamp(node.geo.lon, -180, 180) };
        }

        if (getRuntimeMode() === 'production') {
            return null;
        }

        return deriveLocationFromNetwork(node.network || '');
    }

    function shouldDisplayLabel(node) {
        const load = clamp(node.load || 0, 0, 100);
        const instanceCount = node.instances || 0;
        const notableCities = ['New York', 'London', 'Frankfurt', 'Tokyo', 'Singapore', 'Dubai', 'São Paulo', 'Sydney'];
        return load >= 65 || instanceCount >= 10 || notableCities.indexOf(node.city) !== -1;
    }

    function toMercatorX(longitude, width) {
        const wrapped = ((longitude + 540) % 360) - 180;
        return ((wrapped + 180) / 360) * width;
    }

    function toMercatorY(latitude, height) {
        const latRad = (latitude * Math.PI) / 180;
        const y = 0.5 * Math.log((1 + Math.sin(latRad)) / (1 - Math.sin(latRad)));
        return (height * 0.5) - (y * height / (2 * Math.PI));
    }

    function projectCameraPoint(lon, lat, width, height) {
        const worldX = toMercatorX(lon, width);
        const worldY = toMercatorY(lat, height);
        const centerX = toMercatorX(MAP_CAMERA.centerLon, width);
        const centerY = toMercatorY(MAP_CAMERA.centerLat, height);

        return {
            x: (worldX - centerX) * MAP_CAMERA.zoom + width * MAP_CAMERA.offsetX,
            y: (worldY - centerY) * MAP_CAMERA.zoom + height * MAP_CAMERA.offsetY
        };
    }

    function normalizeLongitude(longitude) {
        return ((longitude + 540) % 360) - 180;
    }

    function projectOrthographicPoint(lon, lat, width, height, centerLon, centerLat) {
        const modeSettings = MAP_MODE_SETTINGS[CAMERA_ANIMATION.mode] || MAP_MODE_SETTINGS.globe;
        const focusScale = (modeSettings.globeScale || 1) * (MAP_CAMERA.zoom || 1);
        const radius = Math.min(width, height) * 0.36 * focusScale;
        const lonDelta = normalizeLongitude(lon - centerLon) * Math.PI / 180;
        const latRad = lat * Math.PI / 180;
        const centerLatRad = centerLat * Math.PI / 180;

        const x = Math.cos(latRad) * Math.sin(lonDelta);
        const y = Math.sin(latRad) * Math.cos(centerLatRad) - Math.cos(latRad) * Math.cos(lonDelta) * Math.sin(centerLatRad);
        const z = Math.sin(latRad) * Math.sin(centerLatRad) + Math.cos(latRad) * Math.cos(lonDelta) * Math.cos(centerLatRad);

        return {
            x: width * 0.5 + x * radius,
            y: height * 0.5 - y * radius,
            z: z
        };
    }

    function clamp(value, min, max) {
        return Math.min(Math.max(value, min), max);
    }

    function getActiveColorTheme() {
        return MAP_COLOR_THEMES[MAP_UI_STATE.color] || MAP_COLOR_THEMES.ocean;
    }

    function getModeSettings(mode) {
        return MAP_MODE_SETTINGS[mode] || MAP_MODE_SETTINGS.globe;
    }

    function getMaxUiZoom() {
        const modeSettings = getModeSettings(CAMERA_ANIMATION.mode);
        return Math.max(2.8, modeSettings.maxZoom || 2.8);
    }

    function getTargetZoomForMode(mode) {
        const modeSettings = getModeSettings(mode);
        return clamp(modeSettings.zoom || 1.2, 0.7, modeSettings.maxZoom || 12);
    }

    function getFocusZoomForMode(mode) {
        const modeSettings = getModeSettings(mode);
        return clamp(modeSettings.focusZoom || getTargetZoomForMode(mode), 0.7, modeSettings.maxZoom || 12);
    }

    function setFocusTarget(target) {
        CAMERA_ANIMATION.focusTarget = target || null;
    }

    function clearFocusTarget() {
        CAMERA_ANIMATION.focusTarget = null;
    }

    function getAmbientTargetZoom() {
        if (CAMERA_ANIMATION.focusTarget && CAMERA_ANIMATION.focusTarget.zoom) {
            return CAMERA_ANIMATION.focusTarget.zoom;
        }
        return getTargetZoomForMode(CAMERA_ANIMATION.mode);
    }

    function syncUiControls() {
        const zoomControl = document.getElementById('themisdb-global-network-map-zoom');
        if (zoomControl) {
            const maxZoom = getMaxUiZoom();
            zoomControl.max = String(maxZoom);
            zoomControl.value = clamp(MAP_UI_STATE.zoom, 0.7, maxZoom).toFixed(2);
        }

        const colorSelect = document.getElementById('themisdb-global-network-map-color');
        if (colorSelect) {
            colorSelect.value = MAP_UI_STATE.color;
        }

        const animationButton = document.querySelector('[data-map-animation-toggle]');
        if (animationButton) {
            animationButton.classList.toggle('is-enabled', MAP_UI_STATE.animationEnabled);
            animationButton.textContent = 'Animation: ' + (MAP_UI_STATE.animationEnabled ? 'On' : 'Off');
        }

        const mouseButton = document.querySelector('[data-map-mouse-toggle]');
        if (mouseButton) {
            mouseButton.classList.toggle('is-enabled', MAP_UI_STATE.mouseControlEnabled);
            mouseButton.textContent = 'Mouse: ' + (MAP_UI_STATE.mouseControlEnabled ? 'On' : 'Off');
        }
    }

    function lerp(start, end, amount) {
        return start + (end - start) * amount;
    }

    function easeInOutCubic(value) {
        if (value < 0.5) {
            return 4 * value * value * value;
        }

        return 1 - Math.pow(-2 * value + 2, 3) / 2;
    }

    function createFallbackContours() {
        return [
            {
                name: 'north-america',
                points: [
                    [-168, 72], [-160, 68], [-150, 70], [-142, 66], [-130, 64], [-118, 61], [-110, 54],
                    [-104, 49], [-98, 42], [-92, 35], [-86, 28], [-80, 22], [-76, 16], [-84, 12], [-92, 12],
                    [-102, 14], [-112, 17], [-122, 18], [-134, 15], [-146, 18], [-158, 23], [-168, 34],
                    [-170, 44], [-170, 56], [-168, 72]
                ]
            },
            {
                name: 'south-america',
                points: [
                    [-82, 12], [-77, 8], [-72, 2], [-68, -8], [-66, -18], [-64, -28], [-62, -38], [-60, -48],
                    [-55, -58], [-48, -56], [-46, -48], [-48, -40], [-52, -30], [-56, -20], [-62, -10],
                    [-70, -2], [-78, 4], [-82, 12]
                ]
            },
            {
                name: 'europe-africa',
                points: [
                    [-16, 72], [-4, 68], [8, 62], [22, 58], [34, 52], [40, 45], [42, 37], [38, 28], [32, 18],
                    [24, 10], [16, 2], [10, -8], [12, -20], [16, -30], [22, -36], [32, -34], [38, -24],
                    [42, -10], [44, 2], [46, 18], [43, 32], [36, 42], [28, 46], [18, 52], [4, 60], [-10, 66],
                    [-16, 72]
                ]
            },
            {
                name: 'asia',
                points: [
                    [26, 72], [36, 68], [52, 67], [68, 64], [82, 61], [98, 58], [114, 55], [128, 52], [142, 49],
                    [154, 46], [168, 40], [178, 30], [176, 20], [162, 12], [148, 8], [132, 10], [116, 12], [100, 14],
                    [84, 16], [68, 18], [54, 24], [42, 32], [34, 42], [28, 54], [26, 72]
                ]
            },
            {
                name: 'australia',
                points: [
                    [112, -10], [122, -8], [134, -10], [144, -16], [152, -24], [154, -32], [148, -40], [138, -44],
                    [124, -42], [114, -36], [112, -26], [112, -10]
                ]
            },
            {
                name: 'greenland',
                points: [
                    [-54, 83], [-42, 82], [-30, 78], [-18, 72], [-22, 62], [-34, 58], [-46, 62], [-54, 74], [-54, 83]
                ]
            }
        ];
    }

    function makePathFromCoordinates(coords, width, height, offsets, projectionMode) {
        const wrapOffsets = offsets || [0];
        return wrapOffsets.map(function (offset) {
            return coords.map(function (ring) {
                const points = ring.map(function (point) {
                    const lon = point[0] + offset;
                    const lat = point[1];
                    const projected = projectionMode === 'globe'
                        ? projectOrthographicPoint(lon, lat, width, height, MAP_CAMERA.centerLon, MAP_CAMERA.centerLat)
                        : {
                            x: toMercatorX(lon, width),
                            y: toMercatorY(lat, height)
                        };
                    return projected.x.toFixed(2) + ' ' + projected.y.toFixed(2);
                });

                return 'M ' + points.join(' L ') + ' Z';
            }).join(' ');
        }).join(' ');
    }

    function drawGeoJsonLandmass(svg, width, height, geojson, projectionMode) {
        const existing = svg.querySelector('#themisdb-land-layer');
        if (existing) {
            existing.remove();
        }

        const landGroup = document.createElementNS('http://www.w3.org/2000/svg', 'g');
        landGroup.setAttribute('id', 'themisdb-land-layer');
        landGroup.setAttribute('filter', 'url(#themisdb-land-shadow)');
        const target = svg.querySelector('#themisdb-map-group') || svg;
        const features = geojson && Array.isArray(geojson.features) ? geojson.features : [];

        features.forEach(function (feature) {
            if (!feature || !feature.geometry) {
                return;
            }

            const geometryType = feature.geometry.type;
            const coordinates = feature.geometry.coordinates;

            if (!coordinates || !Array.isArray(coordinates)) {
                return;
            }

            if (geometryType === 'Polygon') {
                const theme = getActiveColorTheme();
                const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                path.setAttribute('d', makePathFromCoordinates(coordinates, width, height, [-360, 0, 360], projectionMode));
                path.setAttribute('fill', 'url(#themisdb-land-fill)');
                path.setAttribute('stroke', theme.landStroke);
                path.setAttribute('stroke-width', projectionMode === 'globe' ? '1.05' : '1.2');
                path.setAttribute('stroke-linejoin', 'round');
                path.setAttribute('stroke-linecap', 'round');
                path.setAttribute('vector-effect', 'non-scaling-stroke');
                path.setAttribute('opacity', '0.96');
                if (projectionMode === 'globe') {
                    path.setAttribute('clip-path', 'url(#themisdb-globe-clip)');
                }
                landGroup.appendChild(path);
            }

            if (geometryType === 'MultiPolygon') {
                coordinates.forEach(function (polygon) {
                    const theme = getActiveColorTheme();
                    const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                    path.setAttribute('d', makePathFromCoordinates(polygon, width, height, [-360, 0, 360], projectionMode));
                    path.setAttribute('fill', 'url(#themisdb-land-fill)');
                    path.setAttribute('stroke', theme.landStroke);
                    path.setAttribute('stroke-width', projectionMode === 'globe' ? '1.05' : '1.2');
                    path.setAttribute('stroke-linejoin', 'round');
                    path.setAttribute('stroke-linecap', 'round');
                    path.setAttribute('vector-effect', 'non-scaling-stroke');
                    path.setAttribute('opacity', '0.96');
                    if (projectionMode === 'globe') {
                        path.setAttribute('clip-path', 'url(#themisdb-globe-clip)');
                    }
                    landGroup.appendChild(path);
                });
            }
        });

        target.appendChild(landGroup);
    }

    function drawFallbackLandmass(svg, width, height) {
        const existing = svg.querySelector('#themisdb-land-layer');
        if (existing) {
            existing.remove();
        }

        const theme = getActiveColorTheme();
        const landGroup = document.createElementNS('http://www.w3.org/2000/svg', 'g');
        landGroup.setAttribute('id', 'themisdb-land-layer');
        landGroup.setAttribute('filter', 'url(#themisdb-land-shadow)');
        const target = svg.querySelector('#themisdb-map-group') || svg;
        const continents = createFallbackContours();
        continents.forEach(function (continent) {
            const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            const d = continent.points.map(function (point, index) {
                const x = toMercatorX(point[0], width);
                const y = toMercatorY(point[1], height);
                return (index === 0 ? 'M ' : 'L ') + x.toFixed(2) + ' ' + y.toFixed(2);
            }).join(' ') + ' Z';

            path.setAttribute('d', d);
            path.setAttribute('fill', 'url(#themisdb-land-fill)');
            path.setAttribute('stroke', theme.landStroke);
            path.setAttribute('stroke-width', '1.2');
            path.setAttribute('stroke-linejoin', 'round');
            path.setAttribute('stroke-linecap', 'round');
            path.setAttribute('vector-effect', 'non-scaling-stroke');
            path.setAttribute('opacity', '0.96');
            landGroup.appendChild(path);
        });

        target.appendChild(landGroup);
    }

    function renderGlobeOverlay(svg, width, height) {
        const globeLayer = svg.querySelector('#themisdb-globe-layer');
        if (globeLayer) {
            globeLayer.remove();
        }

        const theme = getActiveColorTheme();
        const modeSettings = MAP_MODE_SETTINGS[CAMERA_ANIMATION.mode] || MAP_MODE_SETTINGS.globe;
        const globeRadius = Math.min(width, height) * 0.36 * (modeSettings.globeScale || 1) * (MAP_CAMERA.zoom || 1);
        const layer = document.createElementNS('http://www.w3.org/2000/svg', 'g');
        layer.setAttribute('id', 'themisdb-globe-layer');
        layer.setAttribute('filter', 'url(#themisdb-land-shadow)');

        const globeBackdrop = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
        globeBackdrop.setAttribute('cx', String(width / 2));
        globeBackdrop.setAttribute('cy', String(height / 2));
        globeBackdrop.setAttribute('r', String(globeRadius * 1.08));
        globeBackdrop.setAttribute('fill', theme.backdropFill);
        layer.appendChild(globeBackdrop);

        const atmosphere = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
        atmosphere.setAttribute('cx', String(width / 2));
        atmosphere.setAttribute('cy', String(height / 2));
        atmosphere.setAttribute('r', String(globeRadius * 1.1));
        atmosphere.setAttribute('fill', 'none');
        atmosphere.setAttribute('stroke', theme.atmosphere);
        atmosphere.setAttribute('stroke-width', '5');
        atmosphere.setAttribute('opacity', '0.85');
        atmosphere.setAttribute('filter', 'url(#themisdb-atmosphere-glow)');
        layer.appendChild(atmosphere);

        const globeCircle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
        globeCircle.setAttribute('cx', String(width / 2));
        globeCircle.setAttribute('cy', String(height / 2));
        globeCircle.setAttribute('r', String(globeRadius));
        globeCircle.setAttribute('fill', theme.globe);
        globeCircle.setAttribute('stroke', 'rgba(176, 225, 255, 0.35)');
        globeCircle.setAttribute('stroke-width', '1.4');
        layer.appendChild(globeCircle);

        const shadow = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
        shadow.setAttribute('cx', String(width / 2 + globeRadius * 0.32));
        shadow.setAttribute('cy', String(height / 2 + globeRadius * 0.08));
        shadow.setAttribute('r', String(globeRadius));
        shadow.setAttribute('fill', theme.shadow);
        shadow.setAttribute('opacity', '0.7');
        shadow.setAttribute('clip-path', 'url(#themisdb-globe-clip)');
        layer.appendChild(shadow);

        const terminator = document.createElementNS('http://www.w3.org/2000/svg', 'ellipse');
        terminator.setAttribute('cx', String(width / 2));
        terminator.setAttribute('cy', String(height / 2));
        terminator.setAttribute('rx', String(globeRadius));
        terminator.setAttribute('ry', String(globeRadius * 0.72));
        terminator.setAttribute('fill', 'none');
        terminator.setAttribute('stroke', theme.terminator);
        terminator.setAttribute('stroke-width', '2.2');
        terminator.setAttribute('stroke-dasharray', '2 10');
        terminator.setAttribute('opacity', '0.62');
        terminator.setAttribute('clip-path', 'url(#themisdb-globe-clip)');
        terminator.setAttribute('transform', 'rotate(' + (MAP_CAMERA.centerLon * GLOBE_SOLAR_ANGLE) + ' ' + String(width / 2) + ' ' + String(height / 2) + ')');
        layer.appendChild(terminator);

        for (let lat = -60; lat <= 60; lat += 30) {
            const arc = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            const d = [];
            for (let lon = -180; lon <= 180; lon += 5) {
                const point = projectOrthographicPoint(lon, lat, width, height, MAP_CAMERA.centerLon, MAP_CAMERA.centerLat);
                d.push((lon === -180 ? 'M' : 'L') + ' ' + point.x.toFixed(2) + ' ' + point.y.toFixed(2));
            }
            arc.setAttribute('d', d.join(' '));
            arc.setAttribute('fill', 'none');
            arc.setAttribute('stroke', 'rgba(153, 205, 255, 0.14)');
            arc.setAttribute('stroke-width', '1');
            arc.setAttribute('clip-path', 'url(#themisdb-globe-clip)');
            layer.appendChild(arc);
        }

        for (let lon = -150; lon <= 150; lon += 30) {
            const arc = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            const d = [];
            for (let lat = -90; lat <= 90; lat += 5) {
                const point = projectOrthographicPoint(lon, lat, width, height, MAP_CAMERA.centerLon, MAP_CAMERA.centerLat);
                d.push((lat === -90 ? 'M' : 'L') + ' ' + point.x.toFixed(2) + ' ' + point.y.toFixed(2));
            }
            arc.setAttribute('d', d.join(' '));
            arc.setAttribute('fill', 'none');
            arc.setAttribute('stroke', 'rgba(153, 205, 255, 0.12)');
            arc.setAttribute('stroke-width', '1');
            arc.setAttribute('clip-path', 'url(#themisdb-globe-clip)');
            layer.appendChild(arc);
        }

        const mapGroup = svg.querySelector('#themisdb-map-group');
        if (mapGroup) {
            mapGroup.appendChild(layer);
        }

        const geojson = GLOBE_STATE.geojson || null;
        if (geojson) {
            drawGeoJsonLandmass(svg, width, height, geojson, CAMERA_ANIMATION.mode);
        } else {
            drawFallbackLandmass(svg, width, height);
        }
    }

    function getLocalGeoJsonUrl() {
        const data = window.themisdbGnmData || {};
        return data.geojsonUrl || '';
    }

    function buildBaseMap(width, height) {
        const svg = document.getElementById('themisdb-global-network-map-svg');
        if (!svg) {
            return Promise.resolve();
        }

        svg.innerHTML = '';

        const theme = getActiveColorTheme();
        const background = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
        background.setAttribute('x', '0');
        background.setAttribute('y', '0');
        background.setAttribute('width', String(width));
        background.setAttribute('height', String(height));
        background.setAttribute('fill', theme.background);
        svg.appendChild(background);

        const defs = document.createElementNS('http://www.w3.org/2000/svg', 'defs');

        const oceanGradient = document.createElementNS('http://www.w3.org/2000/svg', 'linearGradient');
        oceanGradient.setAttribute('id', 'themisdb-map-ocean');
        oceanGradient.setAttribute('x1', '0');
        oceanGradient.setAttribute('x2', '0');
        oceanGradient.setAttribute('y1', '0');
        oceanGradient.setAttribute('y2', '1');
        const oceanStop1 = document.createElementNS('http://www.w3.org/2000/svg', 'stop');
        oceanStop1.setAttribute('offset', '0%');
        oceanStop1.setAttribute('stop-color', theme.oceanStart);
        const oceanStop2 = document.createElementNS('http://www.w3.org/2000/svg', 'stop');
        oceanStop2.setAttribute('offset', '100%');
        oceanStop2.setAttribute('stop-color', theme.oceanEnd);
        oceanGradient.appendChild(oceanStop1);
        oceanGradient.appendChild(oceanStop2);
        defs.appendChild(oceanGradient);

        const glow = document.createElementNS('http://www.w3.org/2000/svg', 'radialGradient');
        glow.setAttribute('id', 'themisdb-land-glow');
        glow.setAttribute('cx', '50%');
        glow.setAttribute('cy', '50%');
        glow.setAttribute('r', '75%');
        const glowStop1 = document.createElementNS('http://www.w3.org/2000/svg', 'stop');
        glowStop1.setAttribute('offset', '0%');
        glowStop1.setAttribute('stop-color', theme.glow);
        const glowStop2 = document.createElementNS('http://www.w3.org/2000/svg', 'stop');
        glowStop2.setAttribute('offset', '100%');
        glowStop2.setAttribute('stop-color', 'rgba(156, 214, 255, 0)');
        glow.appendChild(glowStop1);
        glow.appendChild(glowStop2);
        defs.appendChild(glow);

        // Recreate the gradient on every render so the active theme updates immediately for all land masses.
        const landGradient = document.createElementNS('http://www.w3.org/2000/svg', 'linearGradient');
        landGradient.setAttribute('id', 'themisdb-land-fill');
        landGradient.setAttribute('x1', '0');
        landGradient.setAttribute('x2', '1');
        landGradient.setAttribute('y1', '0');
        landGradient.setAttribute('y2', '1');
        landGradient.setAttribute('gradientUnits', 'objectBoundingBox');
        const landGradientStop1 = document.createElementNS('http://www.w3.org/2000/svg', 'stop');
        landGradientStop1.setAttribute('offset', '0%');
        landGradientStop1.setAttribute('stop-color', theme.landSoft);
        const landGradientStop2 = document.createElementNS('http://www.w3.org/2000/svg', 'stop');
        landGradientStop2.setAttribute('offset', '100%');
        landGradientStop2.setAttribute('stop-color', theme.landDeep);
        landGradient.appendChild(landGradientStop1);
        landGradient.appendChild(landGradientStop2);
        defs.appendChild(landGradient);

        const atmosphereGlow = document.createElementNS('http://www.w3.org/2000/svg', 'filter');
        atmosphereGlow.setAttribute('id', 'themisdb-atmosphere-glow');
        atmosphereGlow.setAttribute('x', '-30%');
        atmosphereGlow.setAttribute('y', '-30%');
        atmosphereGlow.setAttribute('width', '160%');
        atmosphereGlow.setAttribute('height', '160%');
        const atmosphereBlur = document.createElementNS('http://www.w3.org/2000/svg', 'feGaussianBlur');
        atmosphereBlur.setAttribute('stdDeviation', '3');
        atmosphereBlur.setAttribute('result', 'blur');
        atmosphereGlow.appendChild(atmosphereBlur);
        const atmosphereMerge = document.createElementNS('http://www.w3.org/2000/svg', 'feMerge');
        const atmosphereMergeNode1 = document.createElementNS('http://www.w3.org/2000/svg', 'feMergeNode');
        atmosphereMergeNode1.setAttribute('in', 'blur');
        const atmosphereMergeNode2 = document.createElementNS('http://www.w3.org/2000/svg', 'feMergeNode');
        atmosphereMergeNode2.setAttribute('in', 'SourceGraphic');
        atmosphereMerge.appendChild(atmosphereMergeNode1);
        atmosphereMerge.appendChild(atmosphereMergeNode2);
        atmosphereGlow.appendChild(atmosphereMerge);
        defs.appendChild(atmosphereGlow);

        const shadowFilter = document.createElementNS('http://www.w3.org/2000/svg', 'filter');
        shadowFilter.setAttribute('id', 'themisdb-land-shadow');
        shadowFilter.setAttribute('x', '-20%');
        shadowFilter.setAttribute('y', '-20%');
        shadowFilter.setAttribute('width', '140%');
        shadowFilter.setAttribute('height', '140%');
        const shadowBlur = document.createElementNS('http://www.w3.org/2000/svg', 'feDropShadow');
        shadowBlur.setAttribute('dx', '0');
        shadowBlur.setAttribute('dy', '4');
        shadowBlur.setAttribute('stdDeviation', '2.5');
        shadowBlur.setAttribute('flood-color', '#9dd8ff');
        shadowBlur.setAttribute('flood-opacity', '0.2');
        shadowFilter.appendChild(shadowBlur);
        defs.appendChild(shadowFilter);

        const clipPath = document.createElementNS('http://www.w3.org/2000/svg', 'clipPath');
        clipPath.setAttribute('id', 'themisdb-globe-clip');
        const globeClipCircle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
        const modeSettings = MAP_MODE_SETTINGS[CAMERA_ANIMATION.mode] || MAP_MODE_SETTINGS.globe;
        const globeRadius = Math.min(width, height) * 0.36 * (modeSettings.globeScale || 1) * (MAP_CAMERA.zoom || 1);
        globeClipCircle.setAttribute('cx', String(width / 2));
        globeClipCircle.setAttribute('cy', String(height / 2));
        globeClipCircle.setAttribute('r', String(globeRadius));
        clipPath.appendChild(globeClipCircle);
        defs.appendChild(clipPath);
        svg.appendChild(defs);

        const mapGroup = document.createElementNS('http://www.w3.org/2000/svg', 'g');
        mapGroup.setAttribute('id', 'themisdb-map-group');
        svg.appendChild(mapGroup);

        const ocean = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
        ocean.setAttribute('x', '0');
        ocean.setAttribute('y', '0');
        ocean.setAttribute('width', String(width));
        ocean.setAttribute('height', String(height));
        ocean.setAttribute('fill', 'url(#themisdb-map-ocean)');
        mapGroup.appendChild(ocean);

        const oceanGlow = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
        oceanGlow.setAttribute('x', '0');
        oceanGlow.setAttribute('y', '0');
        oceanGlow.setAttribute('width', String(width));
        oceanGlow.setAttribute('height', String(height));
        oceanGlow.setAttribute('fill', 'url(#themisdb-land-glow)');
        oceanGlow.setAttribute('opacity', '0.9');
        mapGroup.appendChild(oceanGlow);

        renderGlobeOverlay(svg, width, height);

        const geojsonUrl = getLocalGeoJsonUrl();
        if (!geojsonUrl) {
            return Promise.resolve();
        }

        return fetch(geojsonUrl)
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('GeoJSON not available');
                }
                return response.json();
            })
            .then(function (geojson) {
                GLOBE_STATE.geojson = geojson;
                renderGlobeOverlay(svg, width, height);
            })
            .catch(function () {
                renderGlobeOverlay(svg, width, height);
            });
    }

    function deriveLocationFromNetwork(network) {
        const ip = network.split('/')[0];
        const parts = ip.split('.').map(function (segment) {
            return parseInt(segment, 10) || 0;
        });

        const seed = ((parts[0] << 24) | (parts[1] << 16) | (parts[2] << 8) | parts[3]) >>> 0;
        const regionMap = [
            { lat: 50.1109, lon: 8.6821 },
            { lat: 40.7128, lon: -74.0060 },
            { lat: 1.3521, lon: 103.8198 },
            { lat: 35.6762, lon: 139.6503 },
            { lat: -23.5505, lon: -46.6333 },
            { lat: -33.8688, lon: 151.2093 },
            { lat: 51.5074, lon: -0.1278 },
            { lat: -26.2041, lon: 28.0473 },
            { lat: 48.8566, lon: 2.3522 },
            { lat: 52.5200, lon: 13.4050 }
        ];

        const base = regionMap[seed % regionMap.length];
        const jitterX = ((seed >> 5) % 13) / 20 - 0.3;
        const jitterY = ((seed >> 9) % 11) / 20 - 0.25;

        return {
            lat: clamp(base.lat + jitterY, -60, 75),
            lon: clamp(base.lon + jitterX, -180, 180),
        };
    }

    function projectGeoToScreen(geo, width, height) {
        return projectOrthographicPoint(geo.lon, geo.lat, width, height, MAP_CAMERA.centerLon, MAP_CAMERA.centerLat);
    }

    function buildFlightRoute(nodes, width, height) {
        if (!nodes || !nodes.length) {
            return [];
        }

        const routeOrder = ['Los Angeles', 'New York', 'São Paulo', 'London', 'Frankfurt', 'Dubai', 'Singapore', 'Tokyo', 'Sydney'];
        const ordered = [];

        routeOrder.forEach(function (city) {
            const node = nodes.find(function (item) {
                return (item.city || item.region) === city;
            });

            if (!node) {
                return;
            }

            const geo = getNodeGeo(node);
            if (!geo) {
                return;
            }

            const point = projectGeoToScreen(geo, width, height);
            ordered.push({
                ...node,
                x: point.x,
                y: point.y,
                geo: geo
            });
        });

        if (ordered.length) {
            return ordered;
        }

        return nodes.map(function (node) {
            const geo = getNodeGeo(node);
            if (!geo) {
                return null;
            }

            const point = projectGeoToScreen(geo, width, height);
            return {
                ...node,
                x: point.x,
                y: point.y,
                geo: geo
            };
        }).filter(Boolean);
    }

    function projectWorldPoint(lon, lat, width, height) {
        const x = toMercatorX(lon, width);
        const y = toMercatorY(lat, height);
        const centerX = toMercatorX(MAP_CAMERA.centerLon, width);
        const centerY = toMercatorY(MAP_CAMERA.centerLat, height);

        return {
            x: (x - centerX) * MAP_CAMERA.zoom + width * 0.5,
            y: (y - centerY) * MAP_CAMERA.zoom + height * 0.5
        };
    }

    function getStaticNodePosition(node, width, height) {
        const geo = getNodeGeo(node);
        if (!geo) {
            return null;
        }
        return projectOrthographicPoint(geo.lon, geo.lat, width, height, MAP_CAMERA.centerLon, MAP_CAMERA.centerLat);
    }

    function drawFlightPath(ctx, route, now) {
        if (!route.length) {
            return;
        }

        ctx.save();
        ctx.lineWidth = 1.15;
        ctx.strokeStyle = 'rgba(130, 214, 255, 0.24)';
        ctx.setLineDash([4, 8]);
        ctx.beginPath();
        route.forEach(function (point, index) {
            if (index === 0) {
                ctx.moveTo(point.x, point.y);
            } else {
                ctx.lineTo(point.x, point.y);
            }
        });
        ctx.stroke();

        const pulseIndex = 0;
        const current = route[pulseIndex] || route[0];
        const next = route[(pulseIndex + 1) % route.length] || route[0];
        const t = 0.35 + Math.sin(now * 0.0007) * 0.15;
        const x = lerp(current.x, next.x, t);
        const y = lerp(current.y, next.y, t);
        const radius = 8 + Math.sin(now * 0.012) * 2;
        const glow = ctx.createRadialGradient(x, y, 2, x, y, radius * 5);
        glow.addColorStop(0, 'rgba(255,255,255,0.85)');
        glow.addColorStop(0.15, 'rgba(136, 220, 255, 0.75)');
        glow.addColorStop(1, 'rgba(136, 220, 255, 0)');
        ctx.fillStyle = glow;
        ctx.beginPath();
        ctx.arc(x, y, radius * 5, 0, Math.PI * 2);
        ctx.fill();

        ctx.setLineDash([]);
        ctx.restore();
    }

    function drawHeatmap(ctx, nodes, width, height) {
        ctx.clearRect(0, 0, width, height);
        ctx.globalCompositeOperation = 'screen';

        nodes.forEach(function (node) {
            const geo = getNodeGeo(node);
            if (!geo) {
                return;
            }
            const position = projectOrthographicPoint(geo.lon, geo.lat, width, height, MAP_CAMERA.centerLon, MAP_CAMERA.centerLat);
            const x = position.x;
            const y = position.y;
            const load = clamp(node.load || 50, 0, 100);
            const color = load > 80 ? '#ff7b7b' : load > 65 ? '#ffb55a' : '#7fe7ff';
            const instanceCount = Math.max(1, node.instances || 1);
            const baseRadius = 0.7 + instanceCount * 0.06;

            for (let i = 0; i < Math.min(instanceCount, 2); i++) {
                const jitterX = (i - 0.5) * 0.55;
                const jitterY = (i - 0.5) * 0.55;
                const radius = baseRadius + (i * 0.18);
                const glow = ctx.createRadialGradient(x + jitterX, y + jitterY, 0.2, x + jitterX, y + jitterY, radius * 2.7);

                glow.addColorStop(0, color + 'd1');
                glow.addColorStop(0.22, color + '7e');
                glow.addColorStop(0.52, color + '1f');
                glow.addColorStop(1, 'rgba(0, 0, 0, 0)');

                ctx.fillStyle = glow;
                ctx.beginPath();
                ctx.arc(x + jitterX, y + jitterY, radius * 2.7, 0, Math.PI * 2);
                ctx.fill();
            }

            ctx.fillStyle = 'rgba(241, 248, 255, 0.68)';
            ctx.shadowColor = color;
            ctx.shadowBlur = 2.5;
            ctx.beginPath();
            ctx.arc(x, y, Math.max(0.9, 0.45 + (load / 130)), 0, Math.PI * 2);
            ctx.fill();
            ctx.shadowBlur = 0;

            if (shouldDisplayLabel(node)) {
                if (CAMERA_ANIMATION.mode === 'globe') {
                    ctx.fillStyle = 'rgba(228, 240, 255, 0.72)';
                    ctx.font = '600 7px sans-serif';
                    ctx.fillText(node.city || node.region || 'Node', x + 6, y - 5);
                } else {
                    ctx.fillStyle = 'rgba(230, 240, 255, 0.85)';
                    ctx.font = '600 8px sans-serif';
                    ctx.fillText(node.city || node.region || 'Node', x + 6, y - 6);
                }
            }

            ctx.beginPath();
            ctx.arc(x, y, 1.5, 0, Math.PI * 2);
            ctx.strokeStyle = 'rgba(255,255,255,0.52)';
            ctx.lineWidth = 1;
            ctx.stroke();
        });

        ctx.globalCompositeOperation = 'source-over';
    }

    function renderMap() {
        const canvas = document.getElementById('themisdb-global-network-map-canvas');
        const svg = document.getElementById('themisdb-global-network-map-svg');
        const tooltip = document.getElementById('themisdb-global-network-map-tooltip');
        if (!canvas || !svg) {
            return;
        }

        const width = canvas.width;
        const height = canvas.height;
        const rawNodes = window.themisdbGnmData && window.themisdbGnmData.nodes ? window.themisdbGnmData.nodes : [];

        MAP_UI_STATE.zoom = clamp(MAP_CAMERA.zoom, 0.7, getMaxUiZoom());
        syncUiControls();
        buildBaseMap(width, height);

        function adjustZoom(delta) {
            const previousZoom = MAP_CAMERA.zoom;
            const nextValue = clamp(previousZoom + delta, 0.7, getMaxUiZoom());
            MAP_CAMERA.zoom = nextValue;
            MAP_UI_STATE.zoom = nextValue;
            CAMERA_ANIMATION.targetZoom = nextValue;
            CAMERA_ANIMATION.velocityZoom = ((nextValue - previousZoom) * ZOOM_VELOCITY.drag) + CAMERA_ANIMATION.velocityZoom * ZOOM_VELOCITY.inertia;
            syncUiControls();
        }

        function handlePointerDrag(event) {
            if (!MAP_UI_STATE.mouseControlEnabled || !MAP_UI_STATE.dragActive) {
                return;
            }

            const previousLon = MAP_CAMERA.centerLon;
            const previousLat = MAP_CAMERA.centerLat;
            const dx = event.clientX - MAP_UI_STATE.dragStartX;
            const dy = event.clientY - MAP_UI_STATE.dragStartY;
            const nextLon = MAP_UI_STATE.dragOriginLon - (dx / width) * DRAG_SENSITIVITY.lon;
            const nextLat = clamp(MAP_UI_STATE.dragOriginLat + (dy / height) * DRAG_SENSITIVITY.lat, -80, 80);
            MAP_CAMERA.centerLon = nextLon;
            MAP_CAMERA.centerLat = nextLat;
            CAMERA_ANIMATION.targetLon = nextLon;
            CAMERA_ANIMATION.targetLat = nextLat;
            clearFocusTarget();
            CAMERA_ANIMATION.velocityLon = (nextLon - previousLon) * ZOOM_VELOCITY.drag;
            CAMERA_ANIMATION.velocityLat = (nextLat - previousLat) * ZOOM_VELOCITY.drag;
        }

        const nodes = rawNodes
            .map(function (node) {
                return Object.assign({}, node, { geo: getNodeGeo(node) });
            })
            .filter(function (node) {
                return node && node.geo && Number.isFinite(node.geo.lat) && Number.isFinite(node.geo.lon);
            });
        const flightRoute = buildFlightRoute(nodes, width, height);

        function getNodeAtPointer(clientX, clientY) {
            const rect = canvas.getBoundingClientRect();
            const x = ((clientX - rect.left) / rect.width) * width;
            const y = ((clientY - rect.top) / rect.height) * height;
            let hovered = null;

            nodes.forEach(function (node) {
                const geo = getNodeGeo(node);
                if (!geo) {
                    return;
                }
                const point = CAMERA_ANIMATION.mode === 'globe'
                    ? projectOrthographicPoint(geo.lon, geo.lat, width, height, MAP_CAMERA.centerLon, MAP_CAMERA.centerLat)
                    : { x: toMercatorX(geo.lon, width), y: toMercatorY(geo.lat, height) };
                const dx = x - point.x;
                const dy = y - point.y;
                const radius = 16 + (node.instances || 1) * 4;
                if (Math.sqrt(dx * dx + dy * dy) < radius + 7) {
                    if (!hovered || (node.load || 0) > (hovered.load || 0)) {
                        hovered = node;
                    }
                }
            });

            return hovered;
        }

        function focusOnGeo(geo, focusZoom) {
            if (!geo) {
                return;
            }

            const targetZoom = focusZoom || getTargetZoomForMode(CAMERA_ANIMATION.mode);
            CAMERA_ANIMATION.targetLon = geo.lon;
            CAMERA_ANIMATION.targetLat = geo.lat;
            CAMERA_ANIMATION.targetZoom = clamp(targetZoom, 0.7, getMaxUiZoom());
            CAMERA_ANIMATION.lastSwitch = performance.now();
            CAMERA_ANIMATION.velocityLon = 0;
            CAMERA_ANIMATION.velocityLat = 0;
            CAMERA_ANIMATION.velocityZoom = 0;
        }

        function showTooltip(event, node) {
            if (!tooltip || !node) {
                return;
            }

            const rect = canvas.getBoundingClientRect();
            const x = event.clientX - rect.left + 16;
            const y = event.clientY - rect.top + 16;
            const label = node.region || 'Network node';
            const load = clamp(node.load || 0, 0, 100);
            tooltip.innerHTML = '<strong>' + label + '</strong><br>' + (node.country || 'Region') + '<br>' + (node.instances || 0) + ' instances · ' + load + '% load';
            tooltip.style.left = x + 'px';
            tooltip.style.top = y + 'px';
            tooltip.classList.add('is-visible');
        }

        function hideTooltip() {
            if (tooltip) {
                tooltip.classList.remove('is-visible');
            }
        }

        canvas.addEventListener('mousemove', function (event) {
            const point = getNodeAtPointer(event.clientX, event.clientY);
            if (point) {
                showTooltip(event, point);
            } else {
                hideTooltip();
            }
        });

        canvas.addEventListener('click', function (event) {
            const point = getNodeAtPointer(event.clientX, event.clientY);
            if (!point) {
                return;
            }

            const geo = getNodeGeo(point);
            if (!geo) {
                return;
            }

            const focusZoom = getFocusZoomForMode(CAMERA_ANIMATION.mode);
            setFocusTarget({
                lon: geo.lon,
                lat: geo.lat,
                zoom: focusZoom
            });
            focusOnGeo(geo, focusZoom);
        });

        canvas.addEventListener('mouseleave', function () {
            hideTooltip();
            MAP_UI_STATE.dragActive = false;
        });

        canvas.addEventListener('pointerdown', function (event) {
            if (!MAP_UI_STATE.mouseControlEnabled) {
                return;
            }

            MAP_UI_STATE.dragActive = true;
            MAP_UI_STATE.dragStartX = event.clientX;
            MAP_UI_STATE.dragStartY = event.clientY;
            MAP_UI_STATE.dragOriginLon = MAP_CAMERA.centerLon;
            MAP_UI_STATE.dragOriginLat = MAP_CAMERA.centerLat;
            clearFocusTarget();
            canvas.setPointerCapture && canvas.setPointerCapture(event.pointerId);
        });

        canvas.addEventListener('pointermove', function (event) {
            if (!MAP_UI_STATE.mouseControlEnabled) {
                return;
            }

            handlePointerDrag(event);
        });

        canvas.addEventListener('pointerup', function () {
            MAP_UI_STATE.dragActive = false;
        });

        canvas.addEventListener('wheel', function (event) {
            if (!MAP_UI_STATE.mouseControlEnabled) {
                return;
            }

            event.preventDefault();
            const delta = event.deltaY > 0 ? -0.08 : 0.08;
            clearFocusTarget();
            adjustZoom(delta);
        }, { passive: false });

        function setMode(mode) {
            const nextMode = MAP_MODE_SETTINGS[mode] ? mode : 'globe';
            CAMERA_ANIMATION.mode = nextMode;
            const focusZoom = CAMERA_ANIMATION.focusTarget && CAMERA_ANIMATION.focusTarget.zoom ? CAMERA_ANIMATION.focusTarget.zoom : getTargetZoomForMode(nextMode);
            CAMERA_ANIMATION.targetZoom = focusZoom;

            const buttons = document.querySelectorAll('[data-map-mode]');
            buttons.forEach(function (button) {
                const active = button.getAttribute('data-map-mode') === nextMode;
                button.classList.toggle('is-active', active);
            });

            buildBaseMap(width, height);
        }

        function applyTarget(region) {
            if (!region) {
                return;
            }

            CAMERA_ANIMATION.regionIndex = region.index !== undefined ? region.index : CAMERA_ANIMATION.regionIndex;
            CAMERA_ANIMATION.targetLon = region.lon;
            CAMERA_ANIMATION.targetLat = region.lat;
            CAMERA_ANIMATION.orbitPhase = Math.random() * Math.PI * 2;

            const modeSettings = getModeSettings(CAMERA_ANIMATION.mode);
            const baseZoom = CAMERA_ANIMATION.mode === 'country' ? 10.6 : CAMERA_ANIMATION.mode === 'region' ? 6.2 : 2.1;
            CAMERA_ANIMATION.targetZoom = clamp(baseZoom, modeSettings.zoom, modeSettings.maxZoom);
            setFocusTarget({ lon: region.lon, lat: region.lat, zoom: CAMERA_ANIMATION.targetZoom });
        }

        function pickRandomTarget() {
            const nextIndex = (CAMERA_ANIMATION.regionIndex + 1) % REGION_TARGETS.length;
            const region = REGION_TARGETS[nextIndex] || REGION_TARGETS[0];
            CAMERA_ANIMATION.regionIndex = nextIndex;
            applyTarget(region);
        }

        function applyTargetSelection(targetId) {
            if (!targetId || targetId === 'auto') {
                pickRandomTarget();
                return;
            }

            const region = TARGET_BY_ID[targetId];
            if (!region) {
                return;
            }

            CAMERA_ANIMATION.regionIndex = region.index;
            applyTarget(region);
            CAMERA_ANIMATION.lastSwitch = performance.now();
        }

        function updateCamera(now) {
            const modeSettings = getModeSettings(CAMERA_ANIMATION.mode);
            const animationSpeed = modeSettings.animationSpeed || 0.006;
            const targetFocus = CAMERA_ANIMATION.focusTarget;

            if (!MAP_UI_STATE.animationEnabled) {
                MAP_CAMERA.zoom = lerp(MAP_CAMERA.zoom, clamp(MAP_UI_STATE.zoom, 0.7, getMaxUiZoom()), 0.08);
                return;
            }

            if (now - CAMERA_ANIMATION.lastSwitch > CAMERA_ANIMATION.switchIntervalMs && !targetFocus) {
                CAMERA_ANIMATION.lastSwitch = now;
                pickRandomTarget();
            }

            if (targetFocus) {
                const normalizedCenterLon = normalizeLongitude(MAP_CAMERA.centerLon);
                const desiredLon = normalizeLongitude(targetFocus.lon);
                const desiredLat = clamp(targetFocus.lat, -60, 68);
                const desiredZoom = clamp(targetFocus.zoom || CAMERA_ANIMATION.targetZoom, 0.7, getMaxUiZoom());
                const longitudeDelta = desiredLon - normalizedCenterLon;
                const shortestLongitudeDelta = longitudeDelta > 180 ? longitudeDelta - 360 : (longitudeDelta < -180 ? longitudeDelta + 360 : longitudeDelta);

                CAMERA_ANIMATION.velocityLon = (CAMERA_ANIMATION.velocityLon + (shortestLongitudeDelta * 0.03)) * CAMERA_ANIMATION.followInertia;
                CAMERA_ANIMATION.velocityLat = (CAMERA_ANIMATION.velocityLat + (desiredLat - MAP_CAMERA.centerLat) * 0.025) * CAMERA_ANIMATION.followInertia;
                CAMERA_ANIMATION.velocityZoom = (CAMERA_ANIMATION.velocityZoom + (desiredZoom - MAP_CAMERA.zoom) * 0.08) * CAMERA_ANIMATION.followInertia;

                MAP_CAMERA.centerLon = normalizeLongitude(MAP_CAMERA.centerLon + CAMERA_ANIMATION.velocityLon);
                MAP_CAMERA.centerLat = clamp(MAP_CAMERA.centerLat + CAMERA_ANIMATION.velocityLat, -80, 80);
                MAP_CAMERA.zoom = clamp(MAP_CAMERA.zoom + CAMERA_ANIMATION.velocityZoom, 0.7, getMaxUiZoom());
                CAMERA_ANIMATION.targetLon = MAP_CAMERA.centerLon;
                CAMERA_ANIMATION.targetLat = MAP_CAMERA.centerLat;
                CAMERA_ANIMATION.targetZoom = desiredZoom;
            } else {
                const driftScale = CAMERA_ANIMATION.mode === 'country' ? 7 : CAMERA_ANIMATION.mode === 'region' ? 10 : 13;
                const driftLon = Math.sin(CAMERA_ANIMATION.globeDrift) * driftScale + Math.cos(CAMERA_ANIMATION.orbitPhase) * (CAMERA_ANIMATION.mode === 'country' ? 3.8 : 5.5);
                const driftLat = Math.sin(CAMERA_ANIMATION.globeDrift * 0.7) * (CAMERA_ANIMATION.mode === 'country' ? 5 : 6) + Math.cos(CAMERA_ANIMATION.orbitPhase * 0.8) * (CAMERA_ANIMATION.mode === 'country' ? 2.7 : 3.8);
                const easedLon = normalizeLongitude(CAMERA_ANIMATION.targetLon + driftLon);
                const easedLat = clamp(CAMERA_ANIMATION.targetLat + driftLat, -24, 56);

                MAP_CAMERA.centerLon = lerp(MAP_CAMERA.centerLon, easedLon, animationSpeed);
                MAP_CAMERA.centerLat = lerp(MAP_CAMERA.centerLat, easedLat, animationSpeed);
                MAP_CAMERA.zoom = lerp(MAP_CAMERA.zoom, CAMERA_ANIMATION.targetZoom, animationSpeed * 1.25);
            }

            CAMERA_ANIMATION.globeDrift += animationSpeed * 1.2;
            CAMERA_ANIMATION.orbitPhase += animationSpeed * 1.2;
            renderGlobeOverlay(svg, width, height);
        }

        function tickFrame() {
            const ctx = canvas.getContext('2d');
            const now = performance.now();
            updateCamera(now);
            ctx.clearRect(0, 0, width, height);
            drawHeatmap(ctx, nodes, width, height);
            requestAnimationFrame(tickFrame);
        }

        document.querySelectorAll('[data-map-mode]').forEach(function (button) {
            button.addEventListener('click', function () {
                setMode(button.getAttribute('data-map-mode'));
                const targetSelect = document.getElementById('themisdb-global-network-map-target');
                const selectedTarget = targetSelect ? targetSelect.value : 'auto';
                if (selectedTarget && selectedTarget !== 'auto') {
                    applyTargetSelection(selectedTarget);
                } else {
                    pickRandomTarget();
                }
                CAMERA_ANIMATION.lastSwitch = performance.now();
            });
        });

        const zoomControl = document.getElementById('themisdb-global-network-map-zoom');
        if (zoomControl) {
            zoomControl.addEventListener('input', function () {
                const nextZoom = parseFloat(this.value) || 1.1;
                const maxZoom = getMaxUiZoom();
                MAP_UI_STATE.zoom = nextZoom;
                MAP_CAMERA.zoom = clamp(nextZoom, 0.7, maxZoom);
                CAMERA_ANIMATION.targetZoom = clamp(nextZoom, 0.7, maxZoom);
                syncUiControls();
            });
        }

        const colorSelect = document.getElementById('themisdb-global-network-map-color');
        if (colorSelect) {
            colorSelect.addEventListener('change', function () {
                MAP_UI_STATE.color = this.value;
                buildBaseMap(width, height);
                syncUiControls();
            });
        }

        const animationToggle = document.querySelector('[data-map-animation-toggle]');
        if (animationToggle) {
            animationToggle.addEventListener('click', function () {
                MAP_UI_STATE.animationEnabled = !MAP_UI_STATE.animationEnabled;
                syncUiControls();
            });
        }

        const mouseToggle = document.querySelector('[data-map-mouse-toggle]');
        if (mouseToggle) {
            mouseToggle.addEventListener('click', function () {
                MAP_UI_STATE.mouseControlEnabled = !MAP_UI_STATE.mouseControlEnabled;
                syncUiControls();
            });
        }

        const targetSelect = document.getElementById('themisdb-global-network-map-target');
        if (targetSelect) {
            targetSelect.addEventListener('change', function (event) {
                const targetId = event.target.value;
                CAMERA_ANIMATION.lastSwitch = performance.now();
                applyTargetSelection(targetId);
            });
        }

        const resetButton = document.querySelector('[data-map-reset]');
        if (resetButton) {
            resetButton.addEventListener('click', function () {
                clearFocusTarget();
                CAMERA_ANIMATION.velocityLon = 0;
                CAMERA_ANIMATION.velocityLat = 0;
                CAMERA_ANIMATION.velocityZoom = 0;
                CAMERA_ANIMATION.targetLon = DEFAULT_VIEW.lon;
                CAMERA_ANIMATION.targetLat = DEFAULT_VIEW.lat;
                CAMERA_ANIMATION.targetZoom = getTargetZoomForMode(CAMERA_ANIMATION.mode);
                MAP_CAMERA.centerLon = CAMERA_ANIMATION.targetLon;
                MAP_CAMERA.centerLat = CAMERA_ANIMATION.targetLat;
                MAP_CAMERA.zoom = CAMERA_ANIMATION.targetZoom;
                MAP_UI_STATE.zoom = CAMERA_ANIMATION.targetZoom;
                syncUiControls();
            });
        }

        CAMERA_ANIMATION.lastSwitch = performance.now();
        setMode('globe');
        pickRandomTarget();
        tickFrame();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', renderMap);
    } else {
        renderMap();
    }
})();
