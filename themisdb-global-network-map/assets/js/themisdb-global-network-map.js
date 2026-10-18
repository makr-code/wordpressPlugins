(function () {
    'use strict';

    const MAP_CAMERA = {
        centerLon: 8,
        centerLat: 50,
        zoom: 1.1,
        scale: 1.1,
        offsetX: 0.5,
        offsetY: 0.5
    };

    const MAP_MODE_SETTINGS = {
        globe: { zoom: 1.35, maxZoom: 2.4, globeScale: 1.45, animationSpeed: 0.0045 },
        region: { zoom: 3.2, maxZoom: 4.8, globeScale: 2.8, animationSpeed: 0.008 },
        country: { zoom: 4.8, maxZoom: 6.2, globeScale: 4.6, animationSpeed: 0.012 }
    };

    const MAP_COLOR_THEMES = {
        ocean: {
            background: '#071a2b',
            oceanStart: '#0d233a',
            oceanEnd: '#081a2a',
            land: 'rgba(157, 188, 206, 0.62)',
            landStroke: 'rgba(237, 245, 255, 0.58)',
            globe: 'rgba(19, 48, 68, 0.92)',
            glow: 'rgba(156, 214, 255, 0.22)',
            accent: 'rgba(153, 205, 255, 0.12)'
        },
        aurora: {
            background: '#071d2d',
            oceanStart: '#0d3243',
            oceanEnd: '#081f2a',
            land: 'rgba(112, 197, 176, 0.68)',
            landStroke: 'rgba(221, 255, 243, 0.6)',
            globe: 'rgba(15, 58, 69, 0.94)',
            glow: 'rgba(121, 228, 210, 0.22)',
            accent: 'rgba(132, 244, 223, 0.14)'
        },
        night: {
            background: '#090d1d',
            oceanStart: '#101a2f',
            oceanEnd: '#090c18',
            land: 'rgba(145, 160, 220, 0.64)',
            landStroke: 'rgba(228, 235, 255, 0.6)',
            globe: 'rgba(26, 28, 50, 0.94)',
            glow: 'rgba(156, 170, 255, 0.22)',
            accent: 'rgba(169, 183, 255, 0.14)'
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
        regionIndex: 1
    };

    const GLOBE_STATE = {
        geojson: null
    };

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

    function getMaxUiZoom() {
        const modeSettings = MAP_MODE_SETTINGS[CAMERA_ANIMATION.mode] || MAP_MODE_SETTINGS.globe;
        return Math.max(2.8, modeSettings.maxZoom || 2.8);
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
                path.setAttribute('fill', theme.land);
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
                    path.setAttribute('fill', theme.land);
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
            path.setAttribute('fill', theme.land);
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

        const globeCircle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
        globeCircle.setAttribute('cx', String(width / 2));
        globeCircle.setAttribute('cy', String(height / 2));
        globeCircle.setAttribute('r', String(globeRadius));
        globeCircle.setAttribute('fill', theme.globe);
        globeCircle.setAttribute('stroke', 'rgba(176, 225, 255, 0.35)');
        globeCircle.setAttribute('stroke-width', '1.4');
        layer.appendChild(globeCircle);

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

        return fetch('https://raw.githubusercontent.com/holtzy/D3-graph-gallery/master/DATA/world.geojson')
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

            if (node) {
                const geo = node.geo || deriveLocationFromNetwork(node.network);
                const point = projectGeoToScreen(geo, width, height);
                ordered.push({
                    ...node,
                    x: point.x,
                    y: point.y,
                    geo: geo
                });
            }
        });

        return ordered.length ? ordered : nodes.map(function (node) {
            const geo = node.geo || deriveLocationFromNetwork(node.network);
            const point = projectGeoToScreen(geo, width, height);
            return {
                ...node,
                x: point.x,
                y: point.y,
                geo: geo
            };
        });
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
        const geo = node.geo || deriveLocationFromNetwork(node.network);
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
            const position = getStaticNodePosition(node, width, height);
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
            const nextValue = clamp(MAP_CAMERA.zoom + delta, 0.7, getMaxUiZoom());
            MAP_CAMERA.zoom = nextValue;
            MAP_UI_STATE.zoom = nextValue;
            CAMERA_ANIMATION.targetZoom = nextValue;
            syncUiControls();
        }

        function handlePointerDrag(event) {
            if (!MAP_UI_STATE.mouseControlEnabled || !MAP_UI_STATE.dragActive) {
                return;
            }

            const dx = event.clientX - MAP_UI_STATE.dragStartX;
            const dy = event.clientY - MAP_UI_STATE.dragStartY;
            MAP_CAMERA.centerLon = MAP_UI_STATE.dragOriginLon - (dx / width) * 40;
            MAP_CAMERA.centerLat = clamp(MAP_UI_STATE.dragOriginLat + (dy / height) * 30, -80, 80);
        }

        const nodes = rawNodes.map(function (node) {
            return Object.assign({}, node, {
                geo: deriveLocationFromNetwork(node.network)
            });
        });
        const flightRoute = buildFlightRoute(nodes, width, height);

        function getNodeAtPointer(clientX, clientY) {
            const rect = canvas.getBoundingClientRect();
            const x = ((clientX - rect.left) / rect.width) * width;
            const y = ((clientY - rect.top) / rect.height) * height;
            let hovered = null;

            nodes.forEach(function (node) {
                const geo = node.geo || deriveLocationFromNetwork(node.network);
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
            adjustZoom(delta);
        }, { passive: false });

        function setMode(mode) {
            const nextMode = MAP_MODE_SETTINGS[mode] ? mode : 'globe';
            CAMERA_ANIMATION.mode = nextMode;
            CAMERA_ANIMATION.targetZoom = MAP_MODE_SETTINGS[nextMode].zoom;

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

            const modeSettings = MAP_MODE_SETTINGS[CAMERA_ANIMATION.mode] || MAP_MODE_SETTINGS.globe;
            const baseZoom = CAMERA_ANIMATION.mode === 'country' ? 10.6 : CAMERA_ANIMATION.mode === 'region' ? 6.2 : 2.1;
            CAMERA_ANIMATION.targetZoom = clamp(baseZoom, modeSettings.zoom, modeSettings.maxZoom);
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
            if (!MAP_UI_STATE.animationEnabled) {
                MAP_CAMERA.zoom = lerp(MAP_CAMERA.zoom, clamp(MAP_UI_STATE.zoom, 0.7, getMaxUiZoom()), 0.08);
                return;
            }

            if (now - CAMERA_ANIMATION.lastSwitch > CAMERA_ANIMATION.switchIntervalMs) {
                CAMERA_ANIMATION.lastSwitch = now;
                pickRandomTarget();
            }

            const modeSettings = MAP_MODE_SETTINGS[CAMERA_ANIMATION.mode] || MAP_MODE_SETTINGS.globe;
            const animationSpeed = modeSettings.animationSpeed || 0.006;
            const driftScale = CAMERA_ANIMATION.mode === 'country' ? 7 : CAMERA_ANIMATION.mode === 'region' ? 10 : 13;
            const driftLon = Math.sin(CAMERA_ANIMATION.globeDrift) * driftScale + Math.cos(CAMERA_ANIMATION.orbitPhase) * (CAMERA_ANIMATION.mode === 'country' ? 3.8 : 5.5);
            const driftLat = Math.sin(CAMERA_ANIMATION.globeDrift * 0.7) * (CAMERA_ANIMATION.mode === 'country' ? 5 : 6) + Math.cos(CAMERA_ANIMATION.orbitPhase * 0.8) * (CAMERA_ANIMATION.mode === 'country' ? 2.7 : 3.8);
            const easedLon = normalizeLongitude(CAMERA_ANIMATION.targetLon + driftLon);
            const easedLat = clamp(CAMERA_ANIMATION.targetLat + driftLat, -24, 56);

            MAP_CAMERA.centerLon = lerp(MAP_CAMERA.centerLon, easedLon, animationSpeed);
            MAP_CAMERA.centerLat = lerp(MAP_CAMERA.centerLat, easedLat, animationSpeed);
            MAP_CAMERA.zoom = lerp(MAP_CAMERA.zoom, CAMERA_ANIMATION.targetZoom, animationSpeed * 1.25);
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
