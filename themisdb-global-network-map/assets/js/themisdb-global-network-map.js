(function () {
    'use strict';

    const DEFAULT_VIEW = {
        lon: 12,
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
        globe: { zoom: 3.8, maxZoom: 16, globeScale: 12.8, animationSpeed: 0.0078, focusZoom: 14.5 },
        region: { zoom: 20, maxZoom: 42, globeScale: 36, animationSpeed: 0.0134, focusZoom: 42 },
        country: { zoom: 28, maxZoom: 64, globeScale: 52, animationSpeed: 0.0188, focusZoom: 58 }
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
            oceanStart: '#144d63',
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
            oceanStart: '#1d2646',
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

    const REDUCED_MOTION_QUERY = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : null;

    const MAP_UI_STATE = {
        color: 'ocean',
        animationEnabled: true,
        mouseControlEnabled: true,
        dragActive: false,
        dragStartX: 0,
        dragStartY: 0,
        dragOriginLon: 0,
        dragOriginLat: 0,
        zoom: 1.1,
        rotationSpeed: 0.1
    };

    const EDGE_RENDER_LIMITS = {
        routeNodes: 4,
        heatmapNodes: 80,
        heatmapNodesReduced: 60
    };

    const SVG_RENDER_INTERVAL_MS = 80;

    const GLOBE_SOLAR_ANGLE = 0.6;
    const FOCUS_SENSITIVITY = {
        globe: { lonInfluence: 0.032, orbitInfluence: 0.018, focusStrength: 0.12 },
        region: { lonInfluence: 0.035, orbitInfluence: 0.02, focusStrength: 0.18 },
        country: { lonInfluence: 0.04, orbitInfluence: 0.024, focusStrength: 0.24 }
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

    const COUNTRY_TARGETS = {
        france: { id: 'france', name: 'France', lon: 2.2, lat: 46.2 },
        germany: { id: 'germany', name: 'Germany', lon: 10.4, lat: 51.2 },
        'united-states': { id: 'united-states', name: 'United States', lon: -98.6, lat: 39.8 },
        brazil: { id: 'brazil', name: 'Brazil', lon: -51.9, lat: -14.2 },
        india: { id: 'india', name: 'India', lon: 78.9, lat: 22.3 },
        japan: { id: 'japan', name: 'Japan', lon: 138.3, lat: 36.2 },
        australia: { id: 'australia', name: 'Australia', lon: 133.8, lat: -25.3 },
        'south-africa': { id: 'south-africa', name: 'South Africa', lon: 24.9, lat: -29.0 }
    };

    const TARGET_BY_ID = REGION_TARGETS.reduce(function (accumulator, target, index) {
        accumulator[target.id] = Object.assign({ index: index }, target);
        return accumulator;
    }, {});

    Object.keys(COUNTRY_TARGETS).forEach(function (key) {
        const target = COUNTRY_TARGETS[key];
        TARGET_BY_ID[target.id] = Object.assign({ index: REGION_TARGETS.length + Object.keys(COUNTRY_TARGETS).indexOf(key) }, target);
    });

    const CAMERA_ANIMATION = {
        mode: 'globe',
        targetLon: 12,
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

    const WEBGL_GLOBE = {
        enabled: false,
        renderer: null,
        scene: null,
        camera: null,
        cameraRig: null,
        earthRoot: null,
        earthPivot: null,
        globe: null,
        grid: null,
        rafId: null,
        pointer: null,
        lastTime: 0,
        earthSpin: 0,
        earthPitch: 0,
        postEffects: {
            composer: null,
            bloomPass: null,
            enabled: false,
            loading: false
        }
    };

    function supportsWebGPU() {
        const gpu = navigator && navigator.gpu;
        return !!(gpu && typeof gpu.requestAdapter === 'function');
    }

    function shouldUseWebGPUPath() {
        return supportsWebGPU() && !window.__themisdbLegacyForceWebGL;
    }

    function shouldReduceMotion() {
        return !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
    }

    function initializePostEffects(renderer, scene, camera, canvas) {
        if (!renderer || !scene || !camera || !canvas || !shouldUseWebGPUPath() || shouldReduceMotion()) {
            return;
        }

        const postEffects = WEBGL_GLOBE.postEffects || {};
        if (postEffects.enabled || postEffects.loading) {
            return;
        }

        postEffects.loading = true;
        const THREE = window.THREE;
        if (!THREE || !window.Promise) {
            postEffects.loading = false;
            return;
        }

        Promise.all([
            import('https://cdn.jsdelivr.net/npm/three@0.160.0/examples/jsm/postprocessing/EffectComposer.js'),
            import('https://cdn.jsdelivr.net/npm/three@0.160.0/examples/jsm/postprocessing/RenderPass.js'),
            import('https://cdn.jsdelivr.net/npm/three@0.160.0/examples/jsm/postprocessing/UnrealBloomPass.js')
        ]).then(function (modules) {
            const composerModule = modules[0];
            const renderPassModule = modules[1];
            const bloomPassModule = modules[2];
            const composer = new composerModule.EffectComposer(renderer);
            const renderPass = new renderPassModule.RenderPass(scene, camera);
            const bloomPass = new bloomPassModule.UnrealBloomPass(
                new THREE.Vector2(canvas.clientWidth || 1200, canvas.clientHeight || 540),
                0.5,
                0.35,
                0.1
            );

            bloomPass.threshold = 0.18;
            bloomPass.strength = 0.62;
            bloomPass.radius = 0.4;

            composer.addPass(renderPass);
            composer.addPass(bloomPass);

            postEffects.composer = composer;
            postEffects.bloomPass = bloomPass;
            postEffects.enabled = true;
            postEffects.loading = false;
        }).catch(function () {
            postEffects.enabled = false;
            postEffects.loading = false;
        });
    }

    function ensureThreeLoaded(callback) {
        if (window.THREE) {
            if (callback) {
                callback();
            }
            return;
        }

        const configuredThreeUrl = window.themisdbGnmData && window.themisdbGnmData.threeUrl ? window.themisdbGnmData.threeUrl : null;
        const modernThreeUrl = 'https://cdn.jsdelivr.net/npm/three@0.160.0/build/three.min.js';
        const fallbackThreeUrl = 'http://localhost/wordpress/wp-content/plugins/themisdb-global-network-map/assets/vendor/three.min.js';
        const candidateUrl = shouldUseWebGPUPath() ? (configuredThreeUrl || modernThreeUrl) : (configuredThreeUrl || fallbackThreeUrl);

        if (document.querySelector('script[data-themisdb-three-loader="true"]')) {
            window.setTimeout(function () {
                if (callback) {
                    callback();
                }
            }, 150);
            return;
        }

        const loader = document.createElement('script');
        loader.src = candidateUrl;
        loader.async = false;
        loader.setAttribute('data-themisdb-three-loader', 'true');
        loader.onload = function () {
            if (callback) {
                callback();
            }
        };
        loader.onerror = function () {
            const fallbackUrl = 'https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js';
            if (fallbackUrl !== candidateUrl && !document.querySelector('script[data-themisdb-three-fallback-loader="true"]')) {
                const fallbackLoader = document.createElement('script');
                fallbackLoader.src = fallbackUrl;
                fallbackLoader.async = false;
                fallbackLoader.setAttribute('data-themisdb-three-fallback-loader', 'true');
                fallbackLoader.onload = function () {
                    if (callback) {
                        callback();
                    }
                };
                fallbackLoader.onerror = function () {
                    if (callback) {
                        callback();
                    }
                };
                document.head.appendChild(fallbackLoader);
                return;
            }
            if (callback) {
                callback();
            }
        };
        document.head.appendChild(loader);
    }

    let animationFrameHandle = null;
    let lastSvgRenderStamp = 0;
    let svgSceneDirty = true;
    let lastSvgSignature = '';

    window.themisdbGnmData = Object.assign({}, window.themisdbGnmData || {});
    const debugProjectionParam = window.location && window.location.search ? window.location.search : '';

    function readQueryFlag(name, query) {
        const source = query === undefined ? debugProjectionParam : query;
        const match = new RegExp('(?:[?&])' + name + '(?:=([^&]*))?(?:&|$)').exec(source);
        if (!match) {
            return false;
        }

        const rawValue = match[1] === undefined ? '1' : decodeURIComponent(match[1]).replace(/\+/g, ' ');
        const normalized = String(rawValue).trim().toLowerCase();
        if (!normalized || normalized === '1' || normalized === 'true' || normalized === 'yes' || normalized === 'on') {
            return true;
        }
        return !['0', 'false', 'no', 'off', 'disabled'].includes(normalized);
    }

    function readQueryValue(name, query) {
        const source = query === undefined ? debugProjectionParam : query;
        const match = new RegExp('(?:[?&])' + name + '=([^&]+)').exec(source);
        if (!match) {
            return null;
        }
        return decodeURIComponent(match[1]).replace(/\+/g, ' ');
    }

    function hasCleanResetQuery(query) {
        const source = query === undefined ? debugProjectionParam : query;
        return !!readQueryFlag('clean', source);
    }

    function getExplicitDebugProjectionState(query) {
        const source = query === undefined ? (window.location && window.location.search ? window.location.search : '') : query;
        return !!(
            readQueryFlag('debugProjection', source) ||
            readQueryFlag('debug', source) ||
            readQueryFlag('checkerboard', source) ||
            readQueryFlag('feature', source) ||
            readQueryFlag('featureFilter', source) ||
            readQueryFlag('geojson', source)
        );
    }

    function sanitizeDebugProjectionState() {
        const currentState = window.themisdbGnmData || {};
        if (hasCleanResetQuery()) {
            window.__themisdbGlobeDebug = Object.assign({}, window.__themisdbGlobeDebug || {}, {
                featureFilter: null,
                rebuiltAt: 0
            });
            window.themisdbGnmData = Object.assign({}, currentState, {
                debugProjection: false
            });
            return false;
        }

        const nextState = getExplicitDebugProjectionState();
        window.themisdbGnmData = Object.assign({}, currentState, {
            debugProjection: nextState
        });
        return nextState;
    }

    const featureFilterValue = hasCleanResetQuery() ? null : (getExplicitDebugProjectionState() ? (readQueryValue('featureFilter') || readQueryValue('feature')) : null);
    window.__themisdbGlobeDebug = {
        disableLod: !!(readQueryFlag('disableLod') || readQueryFlag('lodOff')),
        disableAntimeridian: !!(readQueryFlag('disableAntimeridian') || readQueryFlag('antiOff')),
        featureFilter: featureFilterValue || null,
        rebuiltAt: 0
    };
    window.themisdbGnmData = Object.assign({}, window.themisdbGnmData || {}, {
        debugProjection: sanitizeDebugProjectionState()
    });

    function getLandmassDebugState() {
        const debugState = window.__themisdbGlobeDebug || {};
        return {
            disableLod: !!debugState.disableLod,
            disableAntimeridian: !!debugState.disableAntimeridian,
            featureFilter: debugState.featureFilter || null,
            rebuildKey: debugState.rebuildKey || null
        };
    }

    function getFeatureBounds(feature) {
        if (!feature || !feature.geometry) {
            return null;
        }

        const polygonSet = feature.geometry.type === 'MultiPolygon' ? feature.geometry.coordinates : [feature.geometry.coordinates];
        let minLon = Infinity;
        let maxLon = -Infinity;
        let minLat = Infinity;
        let maxLat = -Infinity;
        let hasPoint = false;

        polygonSet.forEach(function (polygon) {
            if (!Array.isArray(polygon)) {
                return;
            }

            polygon.forEach(function (ring) {
                if (!Array.isArray(ring)) {
                    return;
                }

                ring.forEach(function (vertex) {
                    if (!Array.isArray(vertex) || vertex.length < 2) {
                        return;
                    }
                    const lon = Number(vertex[0]);
                    const lat = Number(vertex[1]);
                    if (!Number.isFinite(lon) || !Number.isFinite(lat)) {
                        return;
                    }
                    hasPoint = true;
                    minLon = Math.min(minLon, lon);
                    maxLon = Math.max(maxLon, lon);
                    minLat = Math.min(minLat, lat);
                    maxLat = Math.max(maxLat, lat);
                });
            });
        });

        if (!hasPoint) {
            return null;
        }

        return { lonMin: minLon, lonMax: maxLon, latMin: minLat, latMax: maxLat };
    }

    function getRingArea(ring) {
        if (!Array.isArray(ring) || ring.length < 3) {
            return 0;
        }

        let area = 0;
        for (let index = 0; index < ring.length; index += 1) {
            const current = ring[index];
            const next = ring[(index + 1) % ring.length];
            if (!Array.isArray(current) || !Array.isArray(next) || current.length < 2 || next.length < 2) {
                continue;
            }
            const x1 = Number(current[0]);
            const y1 = Number(current[1]);
            const x2 = Number(next[0]);
            const y2 = Number(next[1]);
            if (!Number.isFinite(x1) || !Number.isFinite(y1) || !Number.isFinite(x2) || !Number.isFinite(y2)) {
                continue;
            }
            area += x1 * y2 - x2 * y1;
        }
        return Math.abs(area) * 0.5;
    }

    function getPolygonArea(polygon) {
        if (!Array.isArray(polygon) || !polygon.length) {
            return 0;
        }

        const rings = Array.isArray(polygon[0]) && Array.isArray(polygon[0][0]) ? polygon : [polygon];
        let total = 0;
        rings.forEach(function (ring) {
            if (!Array.isArray(ring)) {
                return;
            }
            total += getRingArea(ring);
        });
        return total;
    }

    function getDominantPolygon(feature) {
        if (!feature || !feature.geometry) {
            return null;
        }

        const polygonSet = feature.geometry.type === 'MultiPolygon' ? feature.geometry.coordinates : [feature.geometry.coordinates];
        let bestPolygon = null;
        let bestArea = -Infinity;

        polygonSet.forEach(function (polygon) {
            if (!Array.isArray(polygon)) {
                return;
            }
            const area = getPolygonArea(polygon);
            if (area > bestArea) {
                bestArea = area;
                bestPolygon = polygon;
            }
        });

        return bestPolygon;
    }

    function getFeatureMatchScore(feature, requested) {
        if (!feature || !feature.properties) {
            return -Infinity;
        }

        const request = String(requested || '').trim().toLowerCase();
        const names = [];
        if (feature.properties.name) {
            names.push(String(feature.properties.name));
        }
        if (feature.properties['ISO3166-1-Alpha-3']) {
            names.push(String(feature.properties['ISO3166-1-Alpha-3']));
        }
        if (feature.properties['ISO3166-1-Alpha-2']) {
            names.push(String(feature.properties['ISO3166-1-Alpha-2']));
        }

        let score = 0;
        names.forEach(function (name) {
            const normalized = String(name).trim().toLowerCase();
            if (!normalized) {
                return;
            }
            if (normalized === request) {
                score += 10000;
            } else if (normalized.indexOf(request) >= 0 || request.indexOf(normalized) >= 0) {
                score += 1000;
            }
        });

        score += getPolygonArea(getDominantPolygon(feature)) * 10;
        return score;
    }

    function getDebugProjectionMode() {
        const url = window.location && window.location.search ? window.location.search : '';
        if (hasCleanResetQuery(url)) {
            return 'none';
        }
        if (!getExplicitDebugProjectionState(url)) {
            return 'none';
        }
        if (readQueryFlag('checkerboard', url)) {
            return 'checkerboard';
        }
        if (readQueryFlag('feature', url)) {
            return 'feature';
        }
        if (readQueryFlag('geojson', url)) {
            return 'geojson';
        }
        if (readQueryFlag('debugProjection', url) || readQueryFlag('debug', url)) {
            return 'all';
        }
        return 'none';
    }

    function getDebugProjectionLabel(mode) {
        switch (mode) {
            case 'checkerboard':
                return 'Checkerboard grid';
            case 'feature':
                return 'Feature surface mesh';
            case 'geojson':
                return 'GeoJSON outlines';
            case 'all':
                return 'All debug overlays';
            case 'none':
                return 'Global';
            default:
                return 'Global';
        }
    }

    function syncDebugProjectionLabel() {
        const debugPanel = document.getElementById('themisdb-global-network-map-debug');
        if (!debugPanel) {
            return;
        }

        const overlayNode = debugPanel.querySelector('[data-debug="overlay"]');
        if (!overlayNode) {
            return;
        }

        overlayNode.textContent = getDebugProjectionLabel(getDebugProjectionMode());
    }

    function isDebugProjectionEnabled() {
        const url = window.location && window.location.search ? window.location.search : '';
        if (hasCleanResetQuery(url)) {
            window.__themisdbGlobeDebug = Object.assign({}, window.__themisdbGlobeDebug || {}, {
                featureFilter: null,
                rebuiltAt: 0
            });
            window.themisdbGnmData = Object.assign({}, window.themisdbGnmData || {}, {
                debugProjection: false
            });
            return false;
        }

        const queryDebug = getExplicitDebugProjectionState(url);
        if (queryDebug) {
            window.themisdbGnmData = Object.assign({}, window.themisdbGnmData || {}, {
                debugProjection: true
            });
            return true;
        }

        window.themisdbGnmData = Object.assign({}, window.themisdbGnmData || {}, {
            debugProjection: false
        });
        return false;
    }

    function lonLatToSpherePoint(lon, lat, radius) {
        if (!window.THREE) {
            return { x: 0, y: 0, z: radius };
        }

        const THREE = window.THREE;
        const latRad = lat * Math.PI / 180;
        const lonRad = normalizeLongitude(lon) * Math.PI / 180;
        const x = radius * Math.cos(latRad) * Math.sin(lonRad);
        const y = radius * Math.sin(latRad);
        const z = radius * Math.cos(latRad) * Math.cos(lonRad);
        return new THREE.Vector3(x, y, z);
    }

    function createCheckerboardTexture(size) {
        const THREE = window.THREE;
        if (!THREE) {
            return null;
        }

        const canvas = document.createElement('canvas');
        canvas.width = size || 1024;
        canvas.height = size || 1024;
        const context = canvas.getContext('2d');
        const tileSize = 64;

        for (let y = 0; y < canvas.height; y += tileSize) {
            for (let x = 0; x < canvas.width; x += tileSize) {
                context.fillStyle = ((x / tileSize + y / tileSize) % 2 === 0) ? '#f3f9ff' : '#a8c8ff';
                context.fillRect(x, y, tileSize, tileSize);
            }
        }

        const texture = new THREE.CanvasTexture(canvas);
        texture.wrapS = THREE.RepeatWrapping;
        texture.wrapT = THREE.ClampToEdgeWrapping;
        texture.needsUpdate = true;
        texture.colorSpace = THREE.SRGBColorSpace;
        return texture;
    }

    function buildCheckerboardProjectionDebugMesh(radius, options) {
        const THREE = window.THREE;
        if (!THREE) {
            return null;
        }

        const config = Object.assign({ cellsLon: 24, cellsLat: 12 }, options || {});
        const lonCells = Math.max(2, Number(config.cellsLon) || 24);
        const latCells = Math.max(2, Number(config.cellsLat) || 12);

        const positions = [];
        const colors = [];
        const indices = [];
        const latMin = -88.5;
        const latMax = 88.5;
        const colorA = new THREE.Color('#dff7ff');
        const colorB = new THREE.Color('#6ab5ff');

        for (let lonIndex = 0; lonIndex < lonCells; lonIndex += 1) {
            const lon0 = -180 + (lonIndex / lonCells) * 360;
            const lon1 = -180 + ((lonIndex + 1) / lonCells) * 360;
            for (let latIndex = 0; latIndex < latCells; latIndex += 1) {
                const lat0 = latMin + (latIndex / latCells) * (latMax - latMin);
                const lat1 = latMin + ((latIndex + 1) / latCells) * (latMax - latMin);
                const cellColor = ((lonIndex + latIndex) % 2 === 0) ? colorA : colorB;

                const p00 = lonLatToSpherePoint(lon0, lat0, radius);
                const p10 = lonLatToSpherePoint(lon1, lat0, radius);
                const p11 = lonLatToSpherePoint(lon1, lat1, radius);
                const p01 = lonLatToSpherePoint(lon0, lat1, radius);

                const startIndex = positions.length / 3;
                [p00, p10, p11, p01].forEach(function (point) {
                    positions.push(point.x, point.y, point.z);
                    colors.push(cellColor.r, cellColor.g, cellColor.b);
                });

                indices.push(startIndex, startIndex + 1, startIndex + 2, startIndex, startIndex + 2, startIndex + 3);
            }
        }

        const geometry = new THREE.BufferGeometry();
        geometry.setAttribute('position', new THREE.Float32BufferAttribute(positions, 3));
        geometry.setAttribute('color', new THREE.Float32BufferAttribute(colors, 3));
        geometry.setIndex(indices);
        geometry.computeVertexNormals();

        const material = new THREE.MeshBasicMaterial({
            vertexColors: true,
            transparent: false,
            opacity: 1,
            side: THREE.FrontSide,
            depthWrite: true,
            depthTest: true,
            polygonOffset: false
        });

        const mesh = new THREE.Mesh(geometry, material);
        mesh.name = 'debug-checkerboard-projection';
        mesh.userData = mesh.userData || {};
        mesh.userData.debugProjectionMesh = true;
        mesh.renderOrder = 3;
        mesh.position.set(0, 0, 0);
        return mesh;
    }

    function projectRingToSphericalSurface(ring, radius) {
        const THREE = window.THREE;
        if (!THREE || !Array.isArray(ring) || ring.length < 3) {
            return null;
        }

        const normalizedRing = normalizeRingForReference(ring).filter(function (vertex) {
            return Array.isArray(vertex) && vertex.length >= 2 && Number.isFinite(Number(vertex[0])) && Number.isFinite(Number(vertex[1]));
        });
        if (normalizedRing.length < 3) {
            return null;
        }

        const unitVectors = normalizedRing.map(function (vertex) {
            const lon = Number(vertex[0]);
            const lat = Number(vertex[1]);
            return lonLatToSpherePoint(lon, lat, 1).normalize();
        });

        const centroid = new THREE.Vector3();
        unitVectors.forEach(function (point) {
            centroid.add(point);
        });
        if (centroid.lengthSq() < 1e-8) {
            return null;
        }
        centroid.normalize();

        let tangentU = new THREE.Vector3(0, 1, 0).cross(centroid);
        if (tangentU.lengthSq() < 1e-8) {
            tangentU = new THREE.Vector3(1, 0, 0).cross(centroid);
        }
        tangentU.normalize();

        const tangentV = new THREE.Vector3().crossVectors(centroid, tangentU).normalize();
        const planarPoints = unitVectors.map(function (point) {
            return {
                x: point.dot(tangentU),
                y: point.dot(tangentV)
            };
        });

        const centroid2D = planarPoints.reduce(function (sum, point) {
            sum.x += point.x;
            sum.y += point.y;
            return sum;
        }, { x: 0, y: 0 });
        centroid2D.x /= planarPoints.length;
        centroid2D.y /= planarPoints.length;

        const positions = [];
        const indices = [];
        const toSphere = function (point) {
            return centroid.clone()
                .add(tangentU.clone().multiplyScalar(point.x - centroid2D.x))
                .add(tangentV.clone().multiplyScalar(point.y - centroid2D.y))
                .normalize()
                .multiplyScalar(radius);
        };

        const centerVertex = toSphere({ x: centroid2D.x, y: centroid2D.y });
        positions.push(centerVertex.x, centerVertex.y, centerVertex.z);

        planarPoints.forEach(function (point) {
            const onSphere = toSphere(point);
            positions.push(onSphere.x, onSphere.y, onSphere.z);
        });

        for (let index = 0; index < planarPoints.length; index += 1) {
            const nextIndex = (index + 1) % planarPoints.length;
            indices.push(0, index + 1, nextIndex + 1);
        }

        const geometry = new THREE.BufferGeometry();
        geometry.setAttribute('position', new THREE.Float32BufferAttribute(positions, 3));
        geometry.setIndex(indices);
        geometry.computeVertexNormals();
        geometry.computeBoundingSphere();
        return geometry;
    }

    function buildFeatureProjectionDebugMesh(geojson, radius, options) {
        const THREE = window.THREE;
        if (!THREE || !geojson || !Array.isArray(geojson.features)) {
            return null;
        }

        const debugState = getLandmassDebugState();
        const config = Object.assign({ featureName: null }, options || {});

        function normalizeFeatureName(value) {
            if (value === null || value === undefined) {
                return '';
            }
            let normalized = String(value).trim();
            if (typeof normalized.normalize === 'function') {
                normalized = normalized.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
            }
            return normalized.toLowerCase();
        }

        function matchesFeature(feature) {
            if (!feature || !feature.properties) {
                return false;
            }
            const filterValue = normalizeFeatureName(debugState.featureFilter || config.featureName);
            if (!filterValue) {
                return true;
            }

            const candidates = [];
            if (feature.properties.name) {
                candidates.push(feature.properties.name);
            }
            if (feature.properties['ISO3166-1-Alpha-3']) {
                candidates.push(feature.properties['ISO3166-1-Alpha-3']);
            }
            if (feature.properties['ISO3166-1-Alpha-2']) {
                candidates.push(feature.properties['ISO3166-1-Alpha-2']);
            }
            return candidates.some(function (candidate) {
                const value = normalizeFeatureName(candidate);
                return value.indexOf(filterValue) >= 0 || filterValue.indexOf(value) >= 0;
            });
        }

        function getCandidateFeature() {
            const requested = debugState.featureFilter || config.featureName || null;
            if (!requested) {
                return geojson.features.find(function (feature) {
                    return feature && feature.properties && feature.properties.name;
                }) || null;
            }

            const names = normalizeFeatureName(requested);
            const matches = geojson.features.filter(function (feature) {
                return matchesFeature(feature) && feature && feature.properties && feature.properties.name;
            }).sort(function (left, right) {
                return getFeatureMatchScore(right, requested) - getFeatureMatchScore(left, requested);
            });
            if (matches.length) {
                return matches[0];
            }

            const fallbackMappings = {
                france: 'europe',
                german: 'europe',
                germany: 'europe',
                europe: 'europe',
                uk: 'europe',
                unitedkingdom: 'europe',
                unitedstates: 'north america',
                usa: 'north america',
                america: 'north america',
                canada: 'north america',
                brazil: 'south america',
                india: 'asia',
                japan: 'asia',
                australia: 'australia',
                afrika: 'africa',
                africa: 'africa',
                southafrica: 'africa'
            };

            const fallbackTarget = fallbackMappings[names] || fallbackMappings[Object.keys(fallbackMappings).find(function (key) {
                return names.indexOf(key) >= 0 || key.indexOf(names) >= 0;
            })] || null;

            if (fallbackTarget) {
                const fallbackMatch = geojson.features.find(function (feature) {
                    if (!feature || !feature.properties || !feature.properties.name) {
                        return false;
                    }
                    const candidate = normalizeFeatureName(feature.properties.name);
                    return candidate.indexOf(fallbackTarget) >= 0 || fallbackTarget.indexOf(candidate) >= 0;
                });
                if (fallbackMatch) {
                    return fallbackMatch;
                }
            }

            return geojson.features.find(function (feature) {
                if (!feature || !feature.properties || !feature.properties.name) {
                    return false;
                }
                const candidate = normalizeFeatureName(feature.properties.name);
                return candidate.indexOf(names) >= 0 || names.indexOf(candidate) >= 0;
            }) || null;
        }

        function getOuterRingForFeature(feature) {
            if (!feature || !feature.geometry) {
                return null;
            }

            const polygonSet = feature.geometry.type === 'MultiPolygon' ? feature.geometry.coordinates : [feature.geometry.coordinates];
            let bestRing = null;
            let bestScore = -Infinity;

            polygonSet.forEach(function (polygon) {
                if (!Array.isArray(polygon)) {
                    return;
                }

                const rings = Array.isArray(polygon[0]) && Array.isArray(polygon[0][0]) ? polygon : [polygon];
                rings.forEach(function (ring) {
                    if (!Array.isArray(ring) || ring.length < 3) {
                        return;
                    }

                    const ringArea = getRingArea(ring);
                    const bounds = getFeatureBounds({ geometry: { type: 'MultiPolygon', coordinates: [polygon] } });
                    const boxScore = bounds ? (bounds.lonMax - bounds.lonMin) * (bounds.latMax - bounds.latMin) : 0;
                    const score = ringArea + boxScore;
                    if (score > bestScore) {
                        bestScore = score;
                        bestRing = ring;
                    }
                });
            });

            return bestRing;
        }

        const targetFeature = getCandidateFeature();
        const targetRing = targetFeature ? getOuterRingForFeature(targetFeature) : null;
        if (!targetRing) {
            return null;
        }

        const geometry = projectRingToSphericalSurface(targetRing, radius);
        if (!geometry) {
            return null;
        }

        const mesh = new THREE.Mesh(
            geometry,
            new THREE.MeshBasicMaterial({
                color: 0x8dd2ff,
                transparent: false,
                opacity: 0.96,
                side: THREE.FrontSide,
                depthWrite: true,
                depthTest: true
            })
        );
        mesh.name = 'debug-feature-surface-mesh';
        mesh.userData = mesh.userData || {};
        mesh.userData.debugProjectionMesh = true;
        mesh.renderOrder = 3;
        return mesh;
    }

    function buildGeoJsonProjectionDebugMesh(geojson, radius, options) {
        const THREE = window.THREE;
        if (!THREE || !geojson || !Array.isArray(geojson.features)) {
            return null;
        }

        const debugOptions = Object.assign({ lodFactor: getLandmassLodFactor(), offset: 0.02 }, options || {});
        const debugGroup = buildGeoJsonLandmass(geojson, radius, debugOptions);
        if (!debugGroup) {
            return null;
        }

        debugGroup.name = 'debug-geojson-projection-group';
        debugGroup.scale.setScalar(1.015);
        debugGroup.traverse(function (child) {
            if (!child.material) {
                return;
            }

            if (child.isMesh) {
                child.material.transparent = false;
                child.material.opacity = 0.96;
                child.material.depthWrite = true;
                child.material.depthTest = true;
                child.material.side = THREE.FrontSide;
                if (child.material.color) {
                    child.material.color.set('#fbbf24');
                }
            }

            if (child.isLine && child.material) {
                child.material.transparent = false;
                child.material.opacity = 1;
                child.material.depthWrite = true;
                child.material.depthTest = true;
                child.material.color = new THREE.Color('#f97316');
            }
        });

        return debugGroup;
    }

    function shouldUseTextureLandmass() {
        return isDebugProjectionEnabled();
    }

    function normalizeLongitudeForTexture(lon) {
        let value = Number(lon) || 0;
        while (value > 180) {
            value -= 360;
        }
        while (value < -180) {
            value += 360;
        }
        return value;
    }

    function getSunDirection(timestamp) {
        const THREE = window.THREE;
        if (!THREE) {
            return { x: 0.32, y: 0.7, z: 0.62 };
        }

        const t = (timestamp || 0) * 0.00008;
        const x = Math.sin(t * 1.2);
        const y = 0.7;
        const z = Math.cos(t * 1.2);
        const direction = new THREE.Vector3(x, y, z);
        direction.normalize();
        return direction;
    }

    function applyEarthLighting(timestamp) {
        const THREE = window.THREE;
        if (!THREE || !WEBGL_GLOBE.globe) {
            return;
        }

        const sunDirection = getSunDirection(timestamp);
        const daylight = clamp(0.5 + (sunDirection.y * 0.5), 0, 1);
        const nightTint = new THREE.Color(0x071a2c);
        const dayWater = new THREE.Color(0x245b85);
        const nightWater = new THREE.Color(0x071a2c);
        const dayLand = new THREE.Color(0xdfeefe);
        const nightLand = new THREE.Color(0x203f54);

        WEBGL_GLOBE.globe.traverse(function (child) {
            if (!child.material) {
                return;
            }

            if (child.name === 'globe-ocean-shell' || child.userData && child.userData.earthWater) {
                if (child.material && child.material.color) {
                    child.material.color.copy(nightWater.clone().lerp(dayWater, daylight));
                    child.material.opacity = 0.82 + daylight * 0.22;
                }
                return;
            }

            if (child.userData && child.userData.globeLandmass) {
                if (child.material && child.material.color) {
                    child.material.color.copy(nightLand.clone().lerp(dayLand, daylight));
                    child.material.opacity = 0.52 + daylight * 0.34;
                    if (child.material.emissive && child.material.emissive instanceof THREE.Color) {
                        child.material.emissive.copy(dayLand.clone().multiplyScalar(0.12 + daylight * 0.16));
                    }
                }
                return;
            }

            if (child.material && child.material.color && child.material.type === 'LineBasicMaterial') {
                const lineBlend = clamp(0.18 + daylight * 0.7, 0.18, 0.8);
                child.material.opacity = lineBlend;
            }
        });

        if (WEBGL_GLOBE.atmosphereLayer) {
            WEBGL_GLOBE.atmosphereLayer.traverse(function (child) {
                if (child.material && child.material.opacity !== undefined) {
                    child.material.opacity = 0.08 + daylight * 0.12;
                }
            });
        }
    }

    function lonLatToEquirectUV(lon, lat) {
        const normalizedLon = normalizeLongitudeForTexture(lon);
        const normalizedLat = clamp(Number(lat) || 0, -90, 90);
        return {
            u: clamp((normalizedLon + 180) / 360, 0, 1),
            v: clamp((90 - normalizedLat) / 180, 0, 1)
        };
    }

    function createGeoJsonGlobeTextures(geojson) {
        const THREE = window.THREE;
        if (!THREE) {
            return null;
        }

        if (isDebugProjectionEnabled()) {
            const debugTexture = createCheckerboardTexture(2048);
            if (debugTexture) {
                return {
                    texture: debugTexture,
                    alphaTexture: null
                };
            }
        }

        const textureCanvas = document.createElement('canvas');
        textureCanvas.width = 4096;
        textureCanvas.height = 2048;
        const textureCtx = textureCanvas.getContext('2d');

        const alphaCanvas = document.createElement('canvas');
        alphaCanvas.width = 4096;
        alphaCanvas.height = 2048;
        const alphaCtx = alphaCanvas.getContext('2d');

        textureCtx.clearRect(0, 0, textureCanvas.width, textureCanvas.height);
        const oceanGradient = textureCtx.createRadialGradient(
            textureCanvas.width * 0.5,
            textureCanvas.height * 0.45,
            150,
            textureCanvas.width * 0.5,
            textureCanvas.height * 0.5,
            textureCanvas.width * 0.72
        );
        oceanGradient.addColorStop(0, '#1d496b');
        oceanGradient.addColorStop(0.5, '#0c223a');
        oceanGradient.addColorStop(1, '#061826');
        textureCtx.fillStyle = oceanGradient;
        textureCtx.fillRect(0, 0, textureCanvas.width, textureCanvas.height);

        for (let i = 0; i <= 20; i += 1) {
            const x = (i / 20) * textureCanvas.width;
            textureCtx.strokeStyle = 'rgba(166, 219, 255, 0.08)';
            textureCtx.lineWidth = 1;
            textureCtx.beginPath();
            textureCtx.moveTo(x, 0);
            textureCtx.lineTo(x, textureCanvas.height);
            textureCtx.stroke();
        }
        for (let j = 0; j <= 12; j += 1) {
            const y = (j / 12) * textureCanvas.height;
            textureCtx.beginPath();
            textureCtx.moveTo(0, y);
            textureCtx.lineTo(textureCanvas.width, y);
            textureCtx.stroke();
        }

        alphaCtx.clearRect(0, 0, alphaCanvas.width, alphaCanvas.height);
        alphaCtx.fillStyle = '#000000';
        alphaCtx.fillRect(0, 0, alphaCanvas.width, alphaCanvas.height);

        function drawPolygonRing(points, textureFillStyle, alphaFillStyle, useEvenOdd) {
            if (!Array.isArray(points) || !points.length) {
                return;
            }

            const drawRing = function (context, fillStyle) {
                context.beginPath();
                let started = false;
                points.forEach(function (vertex) {
                    if (!Array.isArray(vertex) || vertex.length < 2) {
                        return;
                    }

                    const lon = Number(vertex[0]);
                    const lat = Number(vertex[1]);
                    if (!Number.isFinite(lon) || !Number.isFinite(lat)) {
                        return;
                    }

                    const uv = lonLatToEquirectUV(lon, lat);
                    const x = uv.u * context.canvas.width;
                    const y = uv.v * context.canvas.height;

                    if (!started) {
                        context.moveTo(x, y);
                        started = true;
                        return;
                    }

                    context.lineTo(x, y);
                });

                if (!started) {
                    return;
                }

                context.closePath();
                context.fillStyle = fillStyle;
                if (useEvenOdd) {
                    context.fill('evenodd');
                    return;
                }
                context.fill();
            };

            if (textureFillStyle) {
                drawRing(textureCtx, textureFillStyle);
            }
            if (alphaFillStyle) {
                drawRing(alphaCtx, alphaFillStyle);
            }
        }

        const sourceGeojson = geojson && Array.isArray(geojson.features) ? geojson : createFallbackGeoJson();
        sourceGeojson.features.forEach(function (feature) {
            if (!feature || !feature.geometry) {
                return;
            }

            const geometry = feature.geometry;
            const polygons = geometry.type === 'MultiPolygon' ? geometry.coordinates : [geometry.coordinates];
            polygons.forEach(function (polygon) {
                if (!Array.isArray(polygon) || !polygon.length) {
                    return;
                }

                const rings = Array.isArray(polygon[0]) && Array.isArray(polygon[0][0]) ? polygon : [polygon];
                rings.forEach(function (ring, ringIndex) {
                    if (!Array.isArray(ring) || !ring.length) {
                        return;
                    }

                    const textureFillStyle = ringIndex === 0 ? 'rgba(153, 214, 175, 0.94)' : 'rgba(118, 176, 140, 0.8)';
                    const alphaFillStyle = '#ffffff';
                    drawPolygonRing(ring, textureFillStyle, alphaFillStyle, rings.length > 1);
                });
            });
        });

        const globeTexture = new THREE.CanvasTexture(textureCanvas);
        globeTexture.wrapS = THREE.ClampToEdgeWrapping;
        globeTexture.wrapT = THREE.ClampToEdgeWrapping;
        globeTexture.repeat.set(1, 1);
        globeTexture.center.set(0, 0);
        globeTexture.rotation = 0;
        globeTexture.flipY = true;
        globeTexture.anisotropy = 8;
        globeTexture.needsUpdate = true;
        globeTexture.colorSpace = THREE.SRGBColorSpace;

        const globeAlphaTexture = new THREE.CanvasTexture(alphaCanvas);
        globeAlphaTexture.wrapS = THREE.ClampToEdgeWrapping;
        globeAlphaTexture.wrapT = THREE.ClampToEdgeWrapping;
        globeAlphaTexture.repeat.set(1, 1);
        globeAlphaTexture.center.set(0, 0);
        globeAlphaTexture.rotation = 0;
        globeAlphaTexture.flipY = true;
        globeAlphaTexture.needsUpdate = true;

        textureCtx.globalAlpha = 0.12;
        textureCtx.beginPath();
        textureCtx.rect(0, 0, textureCanvas.width, textureCanvas.height);
        textureCtx.fillStyle = '#ffffff';
        textureCtx.fill();
        textureCtx.globalAlpha = 1;

        return {
            texture: globeTexture,
            alphaTexture: globeAlphaTexture
        };
    }

    function resampleRingForDetail(ring, detailFactor) {
        if (!Array.isArray(ring) || !ring.length) {
            return [];
        }

        const resampled = [];
        const factor = Math.max(1, Number(detailFactor) || 1);

        for (let index = 0; index < ring.length; index += 1) {
            const current = ring[index];
            const next = ring[(index + 1) % ring.length];

            if (!Array.isArray(current) || !Array.isArray(next) || current.length < 2 || next.length < 2) {
                continue;
            }

            const currentLon = Number(current[0]);
            const currentLat = Number(current[1]);
            const nextLon = Number(next[0]);
            const nextLat = Number(next[1]);

            if (!Number.isFinite(currentLon) || !Number.isFinite(currentLat) || !Number.isFinite(nextLon) || !Number.isFinite(nextLat)) {
                continue;
            }

            resampled.push([currentLon, currentLat]);

            const lonDelta = Math.abs(nextLon - currentLon);
            const latDelta = Math.abs(nextLat - currentLat);
            const steps = Math.max(1, Math.ceil((lonDelta + latDelta * 1.5) * factor));

            for (let step = 1; step <= steps; step += 1) {
                const ratio = step / (steps + 1);
                resampled.push([
                    currentLon + (nextLon - currentLon) * ratio,
                    currentLat + (nextLat - currentLat) * ratio
                ]);
            }
        }

        return resampled.length ? resampled : ring.slice();
    }

    function getLandmassLodFactor() {
        const debugState = getLandmassDebugState();
        if (debugState.disableLod) {
            return 1;
        }
        const zoom = clamp(Number(CAMERA_ANIMATION.targetZoom || MAP_CAMERA.zoom || 1.1), 0.7, 64);
        return clamp(2.5 + (zoom - 1) * 0.9, 2.5, 18);
    }

    function normalizeLongitudeForRing(lon, referenceLon) {
        let value = Number(lon) || 0;
        while (value - referenceLon > 180) {
            value -= 360;
        }
        while (value - referenceLon < -180) {
            value += 360;
        }
        return value;
    }

    function normalizeRingForReference(ring) {
        if (!Array.isArray(ring) || ring.length < 3) {
            return [];
        }

        let sum = 0;
        let count = 0;
        ring.forEach(function (vertex) {
            if (!Array.isArray(vertex) || vertex.length < 2) {
                return;
            }
            const lon = Number(vertex[0]);
            if (Number.isFinite(lon)) {
                sum += lon;
                count += 1;
            }
        });

        const referenceLon = count ? (sum / count) : 0;
        return ring.map(function (vertex) {
            if (!Array.isArray(vertex) || vertex.length < 2) {
                return null;
            }
            const lon = Number(vertex[0]);
            const lat = Number(vertex[1]);
            if (!Number.isFinite(lon) || !Number.isFinite(lat)) {
                return null;
            }
            return [normalizeLongitudeForRing(lon, referenceLon), lat];
        }).filter(Boolean);
    }

    function splitRingAcrossAntimeridian(ring) {
        const debugState = getLandmassDebugState();
        if (debugState.disableAntimeridian) {
            return [normalizeRingForReference(ring).filter(function (vertex) {
                return Array.isArray(vertex) && vertex.length >= 2 && Number.isFinite(Number(vertex[0])) && Number.isFinite(Number(vertex[1]));
            })];
        }

        if (!Array.isArray(ring) || ring.length < 3) {
            return [];
        }

        const points = normalizeRingForReference(ring).filter(function (vertex) {
            return Array.isArray(vertex) && vertex.length >= 2 && Number.isFinite(Number(vertex[0])) && Number.isFinite(Number(vertex[1]));
        });

        if (points.length < 3) {
            return [];
        }

        let splitIndex = -1;
        let maxDelta = 0;
        for (let index = 1; index < points.length; index += 1) {
            const previousLon = Number(points[index - 1][0]);
            const currentLon = Number(points[index][0]);
            const delta = Math.abs(currentLon - previousLon);
            if (delta > maxDelta && delta > 150) {
                maxDelta = delta;
                splitIndex = index;
            }
        }

        if (splitIndex < 0) {
            return [points];
        }

        const groups = [];
        let currentGroup = [];
        points.forEach(function (vertex, index) {
            if (index > 0) {
                const previousLon = Number(points[index - 1][0]);
                const currentLon = Number(vertex[0]);
                if (Math.abs(currentLon - previousLon) > 150 && currentGroup.length > 0) {
                    if (currentGroup.length >= 3) {
                        groups.push(currentGroup);
                    }
                    currentGroup = [];
                }
            }
            currentGroup.push(vertex);
        });

        if (currentGroup.length >= 3) {
            groups.push(currentGroup);
        }

        if (!groups.length) {
            return [points];
        }

        return groups.map(function (group) {
            return group.map(function (vertex) {
                const lon = Number(vertex[0]);
                const lat = Number(vertex[1]);
                return [lon, lat];
            });
        }).filter(function (group) {
            return group.length >= 3;
        });
    }

    function pointInRing(point, ring) {
        let inside = false;
        if (!Array.isArray(ring) || ring.length < 3) {
            return false;
        }

        const normalizedRing = normalizeRingForReference(ring);
        if (!normalizedRing.length) {
            return false;
        }

        const referenceLon = normalizedRing.reduce(function (sum, vertex) {
            return sum + Number(vertex[0]);
        }, 0) / normalizedRing.length;
        const normalizedPointX = normalizeLongitudeForRing(point.x, referenceLon);

        for (let index = 0, j = normalizedRing.length - 1; index < normalizedRing.length; j = index, index += 1) {
            const xi = Number(normalizedRing[index][0]);
            const yi = Number(normalizedRing[index][1]);
            const xj = Number(normalizedRing[j][0]);
            const yj = Number(normalizedRing[j][1]);

            if (!Number.isFinite(xi) || !Number.isFinite(yi) || !Number.isFinite(xj) || !Number.isFinite(yj)) {
                continue;
            }

            const intersects = ((yi > point.y) !== (yj > point.y)) &&
                (normalizedPointX < (xj - xi) * (point.y - yi) / (yj - yi + 1e-9) + xi);
            if (intersects) {
                inside = !inside;
            }
        }

        return inside;
    }

    function polygonContainsPoint(polygonCoords, lon, lat) {
        if (!Array.isArray(polygonCoords) || !polygonCoords.length) {
            return false;
        }

        let inside = false;
        polygonCoords.forEach(function (ring) {
            if (!Array.isArray(ring) || ring.length < 3) {
                return;
            }

            if (pointInRing({ x: lon, y: lat }, ring)) {
                inside = !inside;
            }
        });

        return inside;
    }

    function buildGeoJsonLandmass(geojson, radius, options) {
        const THREE = window.THREE;
        if (!THREE || !geojson || !Array.isArray(geojson.features)) {
            return null;
        }

        const lodFactor = options && Number.isFinite(options.lodFactor) ? Number(options.lodFactor) : getLandmassLodFactor();
        const offsetAmount = options && Number.isFinite(options.offset) ? Number(options.offset) : 0.016;
        const landRadius = radius + offsetAmount;
        const landGroup = new THREE.Group();
        landGroup.name = 'geojson-land-group';
        landGroup.renderOrder = 2;
        landGroup.userData = landGroup.userData || {};
        landGroup.userData.globeLandmass = true;

        const debugState = getLandmassDebugState();
        const features = geojson.features.filter(function (feature) {
            if (!feature || !feature.properties || !feature.properties.name) {
                return true;
            }
            if (!debugState.featureFilter) {
                return true;
            }
            return String(feature.properties.name).toLowerCase().indexOf(String(debugState.featureFilter).toLowerCase()) >= 0;
        });
        let added = 0;
        const maxLandPatches = lodFactor >= 6 ? 1700 : 1200;

        function buildSphericalRingCenter(ringPoints) {
            const center = new THREE.Vector3();
            if (!Array.isArray(ringPoints) || !ringPoints.length) {
                return null;
            }

            ringPoints.forEach(function (point) {
                if (!point || !Number.isFinite(point.x) || !Number.isFinite(point.y)) {
                    return;
                }
                const spherePoint = lonLatToSpherePoint(point.x, point.y, 1).normalize();
                center.add(spherePoint);
            });

            if (center.lengthSq() < 1e-6) {
                return null;
            }

            return center.normalize();
        }

        features.forEach(function (feature) {
            if (!feature || !feature.geometry || added >= maxLandPatches) {
                return;
            }

            const geometry = feature.geometry;
            const polygonSet = geometry.type === 'MultiPolygon' ? geometry.coordinates : [geometry.coordinates];

            polygonSet.forEach(function (polygon) {
                if (!Array.isArray(polygon) || !polygon.length || added >= maxLandPatches) {
                    return;
                }

                if (debugState.featureFilter && feature && feature.properties && feature.properties.name && String(feature.properties.name).toLowerCase().indexOf(String(debugState.featureFilter).toLowerCase()) < 0) {
                    return;
                }

                const rings = Array.isArray(polygon[0]) && Array.isArray(polygon[0][0]) ? polygon : [polygon];
                const outerRing = rings[0];
                if (!Array.isArray(outerRing) || outerRing.length < 3 || added >= maxLandPatches) {
                    return;
                }

                const buildShapeForRingPair = function (outerVariant) {
                    const detailedOuterRing = resampleRingForDetail(normalizeRingForReference(outerVariant), lodFactor * 1.8);
                    return projectRingToSphericalSurface(detailedOuterRing, landRadius);
                };

                const outerVariants = splitRingAcrossAntimeridian(outerRing);
                const holeVariants = rings.slice(1).reduce(function (allHoles, holeRing) {
                    return allHoles.concat(splitRingAcrossAntimeridian(holeRing));
                }, []);

                if (!outerVariants.length) {
                    return;
                }

                outerVariants.forEach(function (outerVariant) {
                    const geometry = buildShapeForRingPair(outerVariant, holeVariants);
                    if (!geometry) {
                        return;
                    }

                    const material = new THREE.MeshBasicMaterial({
                        color: 0xdff7ff,
                        transparent: true,
                        opacity: 0.82,
                        side: THREE.DoubleSide,
                        depthWrite: false
                    });

                    const mesh = new THREE.Mesh(geometry, material);
                    mesh.name = 'geojson-land-patch';
                    mesh.position.set(0, 0, 0);
                    mesh.renderOrder = 2;
                    landGroup.add(mesh);

                    const outlinePoints = outerVariant.map(function (point) {
                        const lon = Number(point[0]);
                        const lat = Number(point[1]);
                        return lonLatToSpherePoint(lon, lat, landRadius);
                    });
                    if (outlinePoints.length > 1) {
                        const outline = new THREE.Line(
                            new THREE.BufferGeometry().setFromPoints(outlinePoints),
                            new THREE.LineBasicMaterial({
                                color: 0xf2fbff,
                                transparent: true,
                                opacity: 0.9,
                                depthWrite: false
                            })
                        );
                        outline.name = 'geojson-land-outline';
                        outline.renderOrder = 3;
                        landGroup.add(outline);
                    }

                    added += 1;
                });

            });
        });

        return landGroup.children.length ? landGroup : null;
    }

    function rebuildGeoJsonLandmassAtCurrentLod() {
        if (!WEBGL_GLOBE.globe || !GLOBE_STATE.geojson || !WEBGL_GLOBE.landLayer) {
            return;
        }

        const existing = [];
        WEBGL_GLOBE.landLayer.children.forEach(function (child) {
            if (child && child.name === 'geojson-land-group') {
                existing.push(child);
            }
        });
        existing.forEach(function (child) {
            WEBGL_GLOBE.landLayer.remove(child);
        });

        const lodFactor = getLandmassLodFactor();
        const nextLandmass = buildGeoJsonLandmass(GLOBE_STATE.geojson, 1.66, { lodFactor: lodFactor });
        if (nextLandmass) {
            nextLandmass.userData = nextLandmass.userData || {};
            nextLandmass.userData.globeLandmass = true;
            WEBGL_GLOBE.landLayer.add(nextLandmass);
        }
    }

    function getGlobeViewportSize(canvas) {
        if (!canvas) {
            return { width: 1200, height: 540 };
        }

        const fallbackWidth = Number(canvas.getAttribute('width')) || Number(canvas.width) || 1200;
        const fallbackHeight = Number(canvas.getAttribute('height')) || Number(canvas.height) || 540;
        const rect = canvas.getBoundingClientRect ? canvas.getBoundingClientRect() : null;

        let width = rect && Number.isFinite(rect.width) ? rect.width : (Number(canvas.clientWidth) || fallbackWidth);
        let height = rect && Number.isFinite(rect.height) ? rect.height : (Number(canvas.clientHeight) || fallbackHeight);

        if (!Number.isFinite(width) || width <= 0) {
            width = fallbackWidth;
        }
        if (!Number.isFinite(height) || height <= 0) {
            height = fallbackHeight;
        }

        width = Math.max(Math.round(width), 240);
        height = Math.max(Math.round(height), 180);

        return { width: width, height: height };
    }

    function initWebGLGlobe() {
        if (WEBGL_GLOBE.enabled || !window.THREE) {
            return;
        }

        const canvas = document.getElementById('themisdb-global-network-map-canvas');
        if (!canvas) {
            return;
        }

        const THREE = window.THREE;
        const shouldUseWebGpuRenderer = shouldUseWebGPUPath() && !!THREE.WebGPURenderer && !!(canvas.getContext && canvas.getContext('webgpu'));

        if (!shouldUseWebGpuRenderer) {
            const glContext = canvas.getContext('webgl') || canvas.getContext('experimental-webgl');
            if (!glContext) {
                canvas.style.display = 'block';
                renderFallbackGlobe();
                return;
            }
        }

        const desiredSize = getGlobeViewportSize(canvas);
        const renderer = shouldUseWebGpuRenderer ? new THREE.WebGPURenderer({
            canvas: canvas,
            alpha: true,
            antialias: true,
            powerPreference: 'high-performance'
        }) : new THREE.WebGLRenderer({
            canvas: canvas,
            alpha: true,
            antialias: true,
            powerPreference: 'high-performance'
        });
        window.__themisdbRendererMode = shouldUseWebGpuRenderer ? 'webgpu' : 'webgl';
        const pixelRatio = Math.min(window.devicePixelRatio || 1, 1.5);
        renderer.setPixelRatio(pixelRatio);
        renderer.setClearColor(0x000000, 0);
        renderer.setSize(desiredSize.width, desiredSize.height, false);

        const scene = new THREE.Scene();
        const cameraRig = new THREE.Group();
        const earthRoot = new THREE.Group();
        const earthPivot = new THREE.Group();
        const camera = new THREE.PerspectiveCamera(28, desiredSize.width / Math.max(desiredSize.height, 1), 0.1, 1000);
        camera.position.z = 4.5;
        cameraRig.add(camera);
        scene.add(cameraRig);
        scene.add(earthRoot);
        earthRoot.add(earthPivot);

        const ambientLight = new THREE.AmbientLight(0xbfe8ff, 1.6);
        scene.add(ambientLight);

        const keyLight = new THREE.DirectionalLight(0xffffff, 1.5);
        keyLight.position.set(3.8, 2.9, 4.5);
        scene.add(keyLight);

        const glowLight = new THREE.PointLight(0x66ccff, 1.4, 20);
        glowLight.position.set(-3.2, 2.2, 4.2);
        scene.add(glowLight);

        syncDebugProjectionLabel();

        const globeGroup = new THREE.Group();
        const waterLayer = new THREE.Group();
        const wireframeLayer = new THREE.Group();
        const landLayer = new THREE.Group();
        const atmosphereLayer = new THREE.Group();
        const debugProjectionEnabled = isDebugProjectionEnabled();
        waterLayer.name = 'globe-water-layer';
        wireframeLayer.name = 'globe-wireframe-layer';
        landLayer.name = 'globe-land-layer';
        atmosphereLayer.name = 'globe-atmosphere-layer';
        waterLayer.renderOrder = 0;
        wireframeLayer.renderOrder = 1;
        landLayer.renderOrder = 2;
        atmosphereLayer.renderOrder = 10;
        globeGroup.add(waterLayer);
        globeGroup.add(wireframeLayer);
        globeGroup.add(landLayer);
        globeGroup.add(atmosphereLayer);
        earthPivot.add(globeGroup);

        const textureInfo = !shouldUseTextureLandmass() ? { texture: null, alphaTexture: null } : (createGeoJsonGlobeTextures(GLOBE_STATE.geojson || null) || {
            texture: null,
            alphaTexture: null
        });

        if (shouldUseTextureLandmass() && !textureInfo.texture) {
            const debugTexture = createCheckerboardTexture(2048);
            if (debugTexture) {
                textureInfo.texture = debugTexture;
            }
        }

        const globeGeometry = new THREE.SphereGeometry(1.65, 128, 128);
        const globeMaterial = new THREE.MeshStandardMaterial({
            color: 0x7bb7e3,
            transparent: true,
            opacity: 0.44,
            depthWrite: false,
            side: THREE.DoubleSide,
            roughness: 0.72,
            metalness: 0.08
        });
        if (textureInfo && textureInfo.texture) {
            globeMaterial.map = textureInfo.texture;
        }
        globeMaterial.alphaMap = null;

        if (textureInfo.texture) {
            textureInfo.texture.needsUpdate = true;
        }
        if (textureInfo.alphaTexture) {
            textureInfo.alphaTexture.needsUpdate = true;
        }

        const oceanShell = new THREE.Mesh(
            new THREE.SphereGeometry(1.69, 96, 96),
            new THREE.MeshBasicMaterial({
                color: 0x74caff,
                transparent: true,
                opacity: 0.38,
                depthWrite: false,
                side: THREE.DoubleSide
            })
        );
        oceanShell.name = 'globe-ocean-shell';
        oceanShell.renderOrder = 0;
        waterLayer.add(oceanShell);

        const globe = new THREE.Mesh(globeGeometry, globeMaterial);
        globe.visible = true;
        globe.renderOrder = 0;
        waterLayer.add(globe);

        if (!GLOBE_STATE.geojson) {
            GLOBE_STATE.geojson = createFallbackGeoJson();
        }

        if (debugProjectionEnabled) {
            waterLayer.visible = false;
            wireframeLayer.visible = false;
            landLayer.visible = false;
            atmosphereLayer.visible = false;
            const debugGroup = new THREE.Group();
            debugGroup.name = 'debug-projection-group';
            const debugMode = getDebugProjectionMode();

            if (debugMode === 'checkerboard' || debugMode === 'all') {
                const checkerMesh = buildCheckerboardProjectionDebugMesh(1.67, { cellsLon: 36, cellsLat: 18 });
                if (checkerMesh) {
                    checkerMesh.userData = checkerMesh.userData || {};
                    checkerMesh.userData.debugOverlay = 'checkerboard';
                    debugGroup.add(checkerMesh);
                }
            }

            if (debugMode === 'feature' || debugMode === 'all') {
                const featureMesh = buildFeatureProjectionDebugMesh(GLOBE_STATE.geojson, 1.67, {
                    featureName: window.__themisdbGlobeDebug && window.__themisdbGlobeDebug.featureFilter ? window.__themisdbGlobeDebug.featureFilter : null
                });
                if (featureMesh) {
                    featureMesh.userData = featureMesh.userData || {};
                    featureMesh.userData.debugOverlay = 'feature';
                    debugGroup.add(featureMesh);
                }
            }

            if (debugMode === 'geojson' || debugMode === 'all') {
                const geoJsonMesh = buildGeoJsonProjectionDebugMesh(GLOBE_STATE.geojson, 1.67, { lodFactor: getLandmassLodFactor() });
                if (geoJsonMesh) {
                    geoJsonMesh.userData = geoJsonMesh.userData || {};
                    geoJsonMesh.userData.debugOverlay = 'geojson';
                    debugGroup.add(geoJsonMesh);
                }
            }

            if (debugGroup.children.length) {
                globeGroup.add(debugGroup);
            }
        }

        const shouldUseTextureLandmassForScene = shouldUseTextureLandmass() && !!(textureInfo && textureInfo.texture && GLOBE_STATE.geojson && Array.isArray(GLOBE_STATE.geojson.features));
        if (!shouldUseTextureLandmassForScene) {
            const landmass = buildGeoJsonLandmass(GLOBE_STATE.geojson || null, 1.66, { lodFactor: getLandmassLodFactor() });
            if (landmass) {
                landmass.userData.globeLandmass = true;
                landmass.renderOrder = 2;
                landLayer.add(landmass);
            }
        }

        const atmosphere = new THREE.Mesh(
            new THREE.SphereGeometry(1.86, 48, 48),
            new THREE.MeshBasicMaterial({
                color: 0xdff5ff,
                transparent: true,
                opacity: 0.12,
                side: THREE.BackSide,
                depthWrite: false,
                depthTest: false
            })
        );
        atmosphere.renderOrder = 10;
        atmosphereLayer.add(atmosphere);

        const grid = new THREE.Group();
        grid.renderOrder = 1;
        const latBands = [-75, -60, -45, -30, -15, 0, 15, 30, 45, 60, 75];
        const lonBands = [-150, -120, -90, -60, -30, 0, 30, 60, 90, 120, 150];
        latBands.forEach(function (lat) {
            const points = [];
            for (let lon = -180; lon <= 180; lon += 7) {
                const latRad = lat * Math.PI / 180;
                const lonRad = lon * Math.PI / 180;
                const x = Math.cos(latRad) * Math.sin(lonRad) * 1.72;
                const y = Math.sin(latRad) * 1.72;
                const z = Math.cos(latRad) * Math.cos(lonRad) * 1.72;
                points.push(new THREE.Vector3(x, y, z));
            }
            const line = new THREE.Line(
                new THREE.BufferGeometry().setFromPoints(points),
                new THREE.LineBasicMaterial({ color: 0xa9d8ff, transparent: true, opacity: 0.3, depthWrite: false })
            );
            grid.add(line);
        });
        lonBands.forEach(function (lon) {
            const points = [];
            for (let lat = -90; lat <= 90; lat += 7) {
                const latRad = lat * Math.PI / 180;
                const lonRad = lon * Math.PI / 180;
                const x = Math.cos(latRad) * Math.sin(lonRad) * 1.72;
                const y = Math.sin(latRad) * 1.72;
                const z = Math.cos(latRad) * Math.cos(lonRad) * 1.72;
                points.push(new THREE.Vector3(x, y, z));
            }
            const line = new THREE.Line(
                new THREE.BufferGeometry().setFromPoints(points),
                new THREE.LineBasicMaterial({ color: 0x8fc8ff, transparent: true, opacity: 0.22, depthWrite: false })
            );
            grid.add(line);
        });
        wireframeLayer.add(grid);

        const cityLayer = buildCityMarkerGroup();
        if (cityLayer) {
            cityLayer.renderOrder = 3;
            landLayer.add(cityLayer);
        }

        window.__themisdbGlobeDebug = Object.assign({}, window.__themisdbGlobeDebug || {}, {
            cityCount: cityLayer ? cityLayer.children.length : 0,
            visibleCities: cityLayer ? getVisibleImportantCities().length : 0,
            mode: CAMERA_ANIMATION.mode,
            centerLon: MAP_CAMERA.centerLon,
            centerLat: MAP_CAMERA.centerLat,
            animationEnabled: MAP_UI_STATE.animationEnabled,
            renderCount: 0,
            hasScene: !!scene,
            hasCamera: !!camera,
            hasRenderer: !!renderer,
            cameraZ: camera ? camera.position.z : null,
            globeChildren: globeGroup ? globeGroup.children.length : 0,
            globeChildrenState: globeGroup ? globeGroup.children.map(function (child) {
                return {
                    name: child && child.name ? child.name : child && child.type ? child.type : 'unknown',
                    visible: !!(child && child.visible !== false),
                    scale: child && child.scale ? { x: child.scale.x, y: child.scale.y, z: child.scale.z } : null,
                    position: child && child.position ? { x: child.position.x, y: child.position.y, z: child.position.z } : null
                };
            }) : []
        });

        WEBGL_GLOBE.enabled = true;
        WEBGL_GLOBE.renderer = renderer;
        WEBGL_GLOBE.scene = scene;
        WEBGL_GLOBE.camera = camera;
        WEBGL_GLOBE.cameraRig = cameraRig;
        WEBGL_GLOBE.earthRoot = earthRoot;
        WEBGL_GLOBE.earthPivot = earthPivot;
        WEBGL_GLOBE.globe = globeGroup;
        WEBGL_GLOBE.waterLayer = waterLayer;
        WEBGL_GLOBE.wireframeLayer = wireframeLayer;
        WEBGL_GLOBE.landLayer = landLayer;
        WEBGL_GLOBE.atmosphereLayer = atmosphereLayer;
        WEBGL_GLOBE.grid = grid;
        WEBGL_GLOBE.cityLayer = cityLayer;
        WEBGL_GLOBE.pointer = new THREE.Vector2();
        initializePostEffects(renderer, scene, camera, canvas);
        window.__themisdbGlobe = WEBGL_GLOBE;
        if (!WEBGL_GLOBE.cityLayer || !WEBGL_GLOBE.cityLayer.children.length) {
            renderFallbackGlobe();
        }

        if (!GLOBE_STATE.geojson) {
            const geojsonUrl = getLocalGeoJsonUrl();
            if (geojsonUrl) {
                loadGeoJson(geojsonUrl, function (geojson) {
                    if (!geojson || !WEBGL_GLOBE.globe) {
                        return;
                    }
                    GLOBE_STATE.geojson = geojson;
                    const globeMesh = WEBGL_GLOBE.globe.children.find(function (child) {
                        return child && child.isMesh && child.geometry && child.geometry.type === 'SphereGeometry';
                    });
                    if (globeMesh && globeMesh.material) {
                        const updatedTextures = createGeoJsonGlobeTextures(geojson);
                        if (updatedTextures) {
                            globeMesh.material.map = updatedTextures.texture;
                            globeMesh.material.alphaMap = null;
                            globeMesh.material.transparent = false;
                            globeMesh.material.map.needsUpdate = true;
                            globeMesh.material.needsUpdate = true;
                        }
                    }

                    const hasTextureLandmass = shouldUseTextureLandmass() && !!(updatedTextures && updatedTextures.texture);
                    if (!hasTextureLandmass) {
                        const existingLandmass = WEBGL_GLOBE.globe && WEBGL_GLOBE.globe.children.find(function (child) {
                            return child && child.userData && child.userData.globeLandmass;
                        });
                        if (existingLandmass) {
                            WEBGL_GLOBE.globe.remove(existingLandmass);
                        }

                        const newLandmass = buildGeoJsonLandmass(geojson, 1.66, { lodFactor: getLandmassLodFactor() });
                        if (newLandmass) {
                            newLandmass.userData.globeLandmass = true;
                            newLandmass.renderOrder = 2;
                            if (WEBGL_GLOBE.landLayer) {
                                WEBGL_GLOBE.landLayer.add(newLandmass);
                            } else {
                                WEBGL_GLOBE.globe.add(newLandmass);
                            }
                        }
                    }
                });
            }
        }

        globeGroup.lookAt(new THREE.Vector3(0, 0, 0));
        canvas.style.display = 'block';
        renderer.render(scene, camera);
    }

    function updateWebGLGlobe(timestamp) {
        syncDebugProjectionLabel();

        if (!WEBGL_GLOBE.enabled || !WEBGL_GLOBE.renderer || !WEBGL_GLOBE.scene || !WEBGL_GLOBE.camera) {
            renderFallbackGlobe();
            return;
        }

        const canvas = document.getElementById('themisdb-global-network-map-canvas');
        if (!canvas) {
            return;
        }

        const desiredSize = getGlobeViewportSize(canvas);
        const width = desiredSize.width;
        const height = desiredSize.height;
        if (width < 160 || height < 120) {
            return;
        }

        WEBGL_GLOBE.camera.aspect = width / Math.max(height, 1);
        WEBGL_GLOBE.camera.updateProjectionMatrix();
        WEBGL_GLOBE.renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 1.5));
        WEBGL_GLOBE.renderer.setSize(width, height, false);

        if (WEBGL_GLOBE.postEffects && WEBGL_GLOBE.postEffects.composer) {
            WEBGL_GLOBE.postEffects.composer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 1.1));
            WEBGL_GLOBE.postEffects.composer.setSize(width, height, false);
        }

        const earthObject = WEBGL_GLOBE.earthPivot || WEBGL_GLOBE.globe;
        if (!earthObject) {
            return;
        }

        const focusTarget = CAMERA_ANIMATION.focusTarget;
        const targetLon = CAMERA_ANIMATION.targetLon || MAP_CAMERA.centerLon || DEFAULT_VIEW.lon;
        const targetLat = CAMERA_ANIMATION.targetLat || MAP_CAMERA.centerLat || DEFAULT_VIEW.lat;

        if (focusTarget) {
            const desiredLon = normalizeLongitude(focusTarget.lon);
            const desiredLat = clamp(focusTarget.lat, -80, 80);
            const desiredZoom = clamp(focusTarget.zoom || CAMERA_ANIMATION.targetZoom || MAP_CAMERA.zoom || getTargetZoomForMode(CAMERA_ANIMATION.mode), 0.7, getMaxUiZoom());
            MAP_CAMERA.centerLon = lerp(MAP_CAMERA.centerLon || desiredLon, desiredLon, 0.12);
            MAP_CAMERA.centerLat = lerp(MAP_CAMERA.centerLat || desiredLat, desiredLat, 0.12);
            MAP_CAMERA.zoom = lerp(MAP_CAMERA.zoom || desiredZoom, desiredZoom, 0.12);
            CAMERA_ANIMATION.targetLon = MAP_CAMERA.centerLon;
            CAMERA_ANIMATION.targetLat = MAP_CAMERA.centerLat;
            CAMERA_ANIMATION.targetZoom = desiredZoom;
            MAP_UI_STATE.zoom = MAP_CAMERA.zoom;
        } else {
            MAP_CAMERA.centerLon = lerp(MAP_CAMERA.centerLon || targetLon, targetLon, 0.04);
            MAP_CAMERA.centerLat = lerp(MAP_CAMERA.centerLat || targetLat, targetLat, 0.04);
        }

        const rotationSpeed = Number(MAP_UI_STATE.rotationSpeed) || 0.35;
        const spinTime = (timestamp || 0) * 0.00012 * rotationSpeed;
        const lon = (MAP_CAMERA.centerLon || 0) * Math.PI / 180;
        const lat = (MAP_CAMERA.centerLat || 0) * Math.PI / 180;
        const earthAutoSpin = MAP_UI_STATE.animationEnabled && !focusTarget ? spinTime * 18 : 0;
        WEBGL_GLOBE.earthSpin = earthAutoSpin;
        const earthTilt = focusTarget ? 0 : Math.sin((timestamp || 0) * 0.00035) * 0.18;
        const earthRoll = MAP_UI_STATE.animationEnabled && !focusTarget ? Math.sin((timestamp || 0) * 0.00018) * 0.12 : 0;

        earthObject.rotation.set(earthTilt, earthAutoSpin, earthRoll, 'YXZ');

        const radius = 4.2 + ((MAP_CAMERA.zoom || 1) - 1) * 0.08;
        const focusVector = lonLatToSpherePoint(MAP_CAMERA.centerLon || targetLon, MAP_CAMERA.centerLat || targetLat, radius);

        if (WEBGL_GLOBE.cameraRig) {
            WEBGL_GLOBE.cameraRig.position.set(0, 0, 0);
            WEBGL_GLOBE.cameraRig.rotation.set(0, 0, 0);
        }

        WEBGL_GLOBE.camera.position.copy(focusVector);
        WEBGL_GLOBE.camera.lookAt(0, 0, 0);

        const currentLod = getLandmassLodFactor();
        if (WEBGL_GLOBE.landLod === undefined || Math.abs(WEBGL_GLOBE.landLod - currentLod) > 1.3) {
            rebuildGeoJsonLandmassAtCurrentLod();
            WEBGL_GLOBE.landLod = currentLod;
        }

        applyEarthLighting(timestamp);

        if (WEBGL_GLOBE.postEffects && WEBGL_GLOBE.postEffects.enabled && WEBGL_GLOBE.postEffects.composer) {
            WEBGL_GLOBE.postEffects.composer.render();
        } else {
            WEBGL_GLOBE.renderer.render(WEBGL_GLOBE.scene, WEBGL_GLOBE.camera);
        }
        if (window.__themisdbGlobeDebug) {
            window.__themisdbGlobeDebug.renderCount = (window.__themisdbGlobeDebug.renderCount || 0) + 1;
        }
        syncCityMarkers();
    }

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

    function getNodeIdentity(node) {
        if (!node) {
            return '';
        }

        return [node.city, node.region, node.country, node.network, node.instance_id, node.id].filter(Boolean).join('|') || 'node';
    }

    function getNodeLoadLevel(node) {
        const load = clamp(node.load || 0, 0, 100);
        if (load >= 80) {
            return 'high';
        }
        if (load >= 65) {
            return 'mid';
        }
        return 'low';
    }

    function getNodeStatusLabel(node) {
        const status = String(node.status || 'active').toLowerCase();
        if (status === 'warning' || status === 'degraded' || status === 'busy') {
            return 'warning';
        }
        if (status === 'critical' || status === 'offline' || status === 'down') {
            return 'critical';
        }
        return 'active';
    }

    function shouldDisplayLabel(node, interaction) {
        const load = clamp(node.load || 0, 0, 100);
        const instanceCount = node.instances || 0;
        const notableCities = ['New York', 'London', 'Frankfurt', 'Tokyo', 'Singapore', 'Dubai', 'São Paulo', 'Sydney'];
        const selected = !!(interaction && interaction.selected);
        const hovered = !!(interaction && interaction.hovered);
        return selected || hovered || load >= 65 || instanceCount >= 10 || notableCities.indexOf(node.city) !== -1;
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

    function getVisualGlobeScale() {
        const modeSettings = MAP_MODE_SETTINGS[CAMERA_ANIMATION.mode] || MAP_MODE_SETTINGS.globe;
        const zoomFactor = clamp(MAP_CAMERA.zoom || 1, 0.7, 64);
        const sceneBoost = CAMERA_ANIMATION.focusTarget ? (CAMERA_ANIMATION.mode === 'country' ? 1.65 : CAMERA_ANIMATION.mode === 'region' ? 1.4 : 1.18) : 1;
        const focusBoost = clamp((modeSettings.globeScale || 1) * sceneBoost, 8, 58);
        const rawScale = 1.85 + (zoomFactor - 1) * 0.72 + focusBoost * 0.28;
        return clamp(rawScale, 2.6, 30);
    }

    function getGlobeRenderRadius(width, height) {
        const viewportMin = Math.max(1, Math.min(width || 1, height || 1));
        const baseRadius = viewportMin * 0.42;
        const visualScale = getVisualGlobeScale();
        const targetRadius = baseRadius * visualScale;
        const maxRadius = viewportMin * 0.78;
        return clamp(targetRadius, viewportMin * 0.34, maxRadius);
    }

    function projectOrthographicPoint(lon, lat, width, height, centerLon, centerLat) {
        const radius = getGlobeRenderRadius(width, height);
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

    const IMPORTANT_CITY_CATALOG = {
        version: 1,
        updatedAt: null,
        cities: [
            { id: 'los-angeles', name: 'Los Angeles', lat: 34.0522, lon: -118.2437, priority: 2, tz: 'UTC-8' },
            { id: 'new-york', name: 'New York', lat: 40.7128, lon: -74.0060, priority: 2, tz: 'UTC-5' },
            { id: 'mexico-city', name: 'Mexico City', lat: 19.4326, lon: -99.1332, priority: 1, tz: 'UTC-6' },
            { id: 'sao-paulo', name: 'São Paulo', lat: -23.5505, lon: -46.6333, priority: 2, tz: 'UTC-3' },
            { id: 'london', name: 'London', lat: 51.5074, lon: -0.1278, priority: 3, tz: 'UTC+0' },
            { id: 'paris', name: 'Paris', lat: 48.8566, lon: 2.3522, priority: 3, tz: 'UTC+1' },
            { id: 'frankfurt', name: 'Frankfurt', lat: 50.1109, lon: 8.6821, priority: 3, tz: 'UTC+1' },
            { id: 'cairo', name: 'Cairo', lat: 30.0444, lon: 31.2357, priority: 2, tz: 'UTC+2' },
            { id: 'johannesburg', name: 'Johannesburg', lat: -26.2041, lon: 28.0473, priority: 2, tz: 'UTC+2' },
            { id: 'dubai', name: 'Dubai', lat: 25.2048, lon: 55.2708, priority: 2, tz: 'UTC+4' },
            { id: 'mumbai', name: 'Mumbai', lat: 19.0760, lon: 72.8777, priority: 3, tz: 'UTC+5:30' },
            { id: 'singapore', name: 'Singapore', lat: 1.3521, lon: 103.8198, priority: 3, tz: 'UTC+8' },
            { id: 'hong-kong', name: 'Hong Kong', lat: 22.3193, lon: 114.1694, priority: 2, tz: 'UTC+8' },
            { id: 'tokyo', name: 'Tokyo', lat: 35.6762, lon: 139.6503, priority: 3, tz: 'UTC+9' },
            { id: 'seoul', name: 'Seoul', lat: 37.5665, lon: 126.9780, priority: 2, tz: 'UTC+9' },
            { id: 'sydney', name: 'Sydney', lat: -33.8688, lon: 151.2093, priority: 3, tz: 'UTC+10' },
            { id: 'auckland', name: 'Auckland', lat: -36.8485, lon: 174.7633, priority: 2, tz: 'UTC+12' }
        ]
    };

    const CITY_CATALOG_STORE = {
        data: IMPORTANT_CITY_CATALOG,
        getCities: function () {
            return Array.isArray(this.data && this.data.cities) ? this.data.cities : [];
        },
        replaceFromPayload: function (payload) {
            const cities = Array.isArray(payload && payload.cities) ? payload.cities : (Array.isArray(payload) ? payload : []);
            this.data = {
                version: 1,
                updatedAt: new Date().toISOString(),
                cities: cities.filter(function (city) {
                    return city && city.name && Number.isFinite(Number(city.lat)) && Number.isFinite(Number(city.lon));
                })
            };
            return this.data;
        },
        createCity: function (city) {
            const list = this.getCities();
            const item = Object.assign({ id: String(city && city.id ? city.id : city.name).toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, ''), priority: 1 }, city);
            list.push(item);
            this.data.cities = list;
            return item;
        },
        updateCity: function (id, patch) {
            const list = this.getCities();
            const index = list.findIndex(function (city) {
                return city && (city.id === id || city.name === id);
            });
            if (index === -1) {
                return null;
            }
            list[index] = Object.assign({}, list[index], patch);
            this.data.cities = list;
            return list[index];
        },
        deleteCity: function (id) {
            const list = this.getCities().filter(function (city) {
                return !(city && (city.id === id || city.name === id));
            });
            this.data.cities = list;
            return list;
        },
        fetchAjax: function (options) {
            const config = Object.assign({
                url: window.themisdbGnmData && window.themisdbGnmData.cityApiEndpoint ? window.themisdbGnmData.cityApiEndpoint : null,
                method: 'GET',
                headers: { Accept: 'application/json' }
            }, options || {});

            if (!config.url) {
                return Promise.resolve(this.getCities());
            }

            return fetch(config.url, {
                method: config.method || 'GET',
                headers: config.headers || { Accept: 'application/json' },
                body: config.method && config.method.toUpperCase() !== 'GET' && config.body ? JSON.stringify(config.body) : undefined
            }).then(function (response) {
                if (!response.ok) {
                    throw new Error('City catalog request failed');
                }
                return response.json();
            }).then(function (payload) {
                return CITY_CATALOG_STORE.replaceFromPayload(payload);
            }).then(function () {
                return CITY_CATALOG_STORE.getCities();
            });
        },
        saveAjax: function (options) {
            const config = Object.assign({
                url: window.themisdbGnmData && window.themisdbGnmData.cityApiEndpoint ? window.themisdbGnmData.cityApiEndpoint : null,
                method: 'POST',
                body: { cities: this.getCities() }
            }, options || {});

            if (!config.url) {
                return Promise.resolve(this.getCities());
            }

            return fetch(config.url, {
                method: config.method || 'POST',
                headers: Object.assign({ Accept: 'application/json', 'Content-Type': 'application/json' }, config.headers || {}),
                body: JSON.stringify(config.body || { cities: this.getCities() })
            }).then(function (response) {
                if (!response.ok) {
                    throw new Error('City catalog save failed');
                }
                return response.json();
            }).then(function (payload) {
                return CITY_CATALOG_STORE.replaceFromPayload(payload);
            }).then(function () {
                return CITY_CATALOG_STORE.getCities();
            });
        }
    };

    function getVisibleImportantCities() {
        const cityPool = CITY_CATALOG_STORE.getCities();
        if (!cityPool.length) {
            return [];
        }

        const focusLon = normalizeLongitude(MAP_CAMERA.centerLon || 8);
        const focusLat = clamp(MAP_CAMERA.centerLat || 50, -90, 90);
        const inEuropeView = focusLon >= -30 && focusLon <= 40 && focusLat >= 30 && focusLat <= 72;

        if (inEuropeView) {
            return cityPool.filter(function (city) {
                return city.lon >= -30 && city.lon <= 40 && city.lat >= 35 && city.lat <= 72;
            });
        }

        const frontFacing = cityPool.filter(function (city) {
            const normal = lonLatToSpherePoint(city.lon, city.lat, 1.0).normalize();
            return normal.z >= -0.1;
        });

        return frontFacing.length ? frontFacing : cityPool;
    }

    function createCityDotTexture() {
        if (!window.THREE || !document) {
            return null;
        }

        const canvas = document.createElement('canvas');
        canvas.width = 64;
        canvas.height = 64;
        const context = canvas.getContext('2d');
        if (!context) {
            return null;
        }

        const gradient = context.createRadialGradient(32, 32, 2, 32, 32, 30);
        gradient.addColorStop(0, '#f0fdff');
        gradient.addColorStop(0.2, '#9fe8ff');
        gradient.addColorStop(0.55, '#5db9ff');
        gradient.addColorStop(1, 'rgba(93, 185, 255, 0)');
        context.fillStyle = gradient;
        context.fillRect(0, 0, 64, 64);

        return new window.THREE.CanvasTexture(canvas);
    }

    function createCityLabelTexture(cityName) {
        if (!window.THREE || !document) {
            return null;
        }

        const canvas = document.createElement('canvas');
        const textureWidth = 256;
        const textureHeight = 64;
        canvas.width = textureWidth;
        canvas.height = textureHeight;
        const context = canvas.getContext('2d');
        if (!context) {
            return null;
        }

        const labelWidth = 190;
        const radius = 14;
        context.clearRect(0, 0, canvas.width, canvas.height);
        context.fillStyle = 'rgba(6, 18, 28, 0.68)';
        context.strokeStyle = 'rgba(145, 212, 255, 0.42)';
        context.lineWidth = 1.5;
        context.beginPath();
        context.moveTo(radius, 0);
        context.lineTo(labelWidth - radius, 0);
        context.quadraticCurveTo(labelWidth, 0, labelWidth, radius);
        context.lineTo(labelWidth, textureHeight - radius);
        context.quadraticCurveTo(labelWidth, textureHeight, labelWidth - radius, textureHeight);
        context.lineTo(radius, textureHeight);
        context.quadraticCurveTo(0, textureHeight, 0, textureHeight - radius);
        context.lineTo(0, radius);
        context.quadraticCurveTo(0, 0, radius, 0);
        context.closePath();
        context.fill();
        context.stroke();

        context.font = '600 18px Arial';
        context.textAlign = 'center';
        context.textBaseline = 'middle';
        context.fillStyle = '#eff9ff';
        context.fillText(cityName, canvas.width * 0.5, canvas.height * 0.5);

        const texture = new window.THREE.CanvasTexture(canvas);
        texture.generateMipmaps = false;
        texture.minFilter = window.THREE.LinearFilter;
        texture.magFilter = window.THREE.LinearFilter;
        texture.needsUpdate = true;
        return texture;
    }

    function buildCityMarkerGroup() {
        if (!window.THREE) {
            return null;
        }

        const THREE = window.THREE;
        const dotTexture = createCityDotTexture();
        const group = new THREE.Group();
        const visibleCities = getVisibleImportantCities();

        visibleCities.forEach(function (city) {
            const anchor = lonLatToSpherePoint(city.lon, city.lat, 1.74);
            const normal = anchor.clone().normalize();
            const offset = 1.77 + (city.priority === 2 ? 0.02 : 0);

            const dotMaterial = new THREE.MeshBasicMaterial({
                color: 0x9fe8ff,
                transparent: true,
                opacity: 0.98,
                depthWrite: false,
                side: THREE.FrontSide
            });
            const dot = new THREE.Mesh(new THREE.SphereGeometry(city.priority === 2 ? 0.045 : 0.032, 12, 12), dotMaterial);
            dot.position.copy(normal.multiplyScalar(offset));
            dot.userData.cityVector = normal.clone();
            dot.userData.type = 'dot';
            group.add(dot);

            const labelTexture = createCityLabelTexture(city.name);
            if (labelTexture) {
                const labelMaterial = new THREE.SpriteMaterial({
                    map: labelTexture,
                    transparent: true,
                    opacity: 0.98,
                    depthTest: false,
                    depthWrite: false,
                    alphaTest: 0.1,
                    side: THREE.DoubleSide
                });
                const label = new THREE.Sprite(labelMaterial);
                label.position.copy(normal.multiplyScalar(1.96));
                label.scale.set(city.priority === 2 ? 1.1 : 0.9, city.priority === 2 ? 0.24 : 0.2, 1);
                label.userData.cityVector = normal.clone();
                label.userData.type = 'label';
                group.add(label);
            }
        });

        return group;
    }

    function syncCityMarkers() {
        const cityLayer = WEBGL_GLOBE.cityLayer;
        if (!cityLayer || !cityLayer.children.length) {
            return;
        }

        const europeStaticView = !MAP_UI_STATE.animationEnabled && CAMERA_ANIMATION.mode === 'region' && Math.abs(normalizeLongitude(MAP_CAMERA.centerLon - 12)) < 18 && Math.abs(MAP_CAMERA.centerLat - 50) < 20;

        cityLayer.children.forEach(function (marker) {
            const cityVector = marker && marker.userData && marker.userData.cityVector ? marker.userData.cityVector : null;
            if (!cityVector) {
                marker.visible = false;
                return;
            }

            if (europeStaticView) {
                marker.visible = true;
                return;
            }

            const onFront = cityVector.z >= -0.08;
            marker.visible = onFront;
            if (marker.userData && marker.userData.type === 'label') {
                marker.visible = onFront && cityVector.z > 0.06;
            }
        });
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

        if (!target) {
            return;
        }

        const nextLon = normalizeLongitude(target.lon);
        const nextLat = clamp(target.lat, -80, 80);
        const nextZoom = clamp(target.zoom || getTargetZoomForMode(CAMERA_ANIMATION.mode), 0.7, getMaxUiZoom());

        MAP_CAMERA.centerLon = nextLon;
        MAP_CAMERA.centerLat = nextLat;
        MAP_CAMERA.zoom = nextZoom;
        MAP_UI_STATE.zoom = nextZoom;
        CAMERA_ANIMATION.targetLon = nextLon;
        CAMERA_ANIMATION.targetLat = nextLat;
        CAMERA_ANIMATION.targetZoom = nextZoom;
        CAMERA_ANIMATION.velocityLon = 0;
        CAMERA_ANIMATION.velocityLat = 0;
        CAMERA_ANIMATION.velocityZoom = 0;

        MAP_UI_STATE.animationEnabled = false;
        syncUiControls();
        if (window.__themisdbGlobeDebug) {
            window.__themisdbGlobeDebug.focusTarget = { lon: nextLon, lat: nextLat, zoom: nextZoom };
            window.__themisdbGlobeDebug.animationEnabled = false;
        }
    }

    function focusRegionByName(regionName, zoomOverride) {
        const normalized = String(regionName || '').trim().toLowerCase();
        const match = REGION_TARGETS.find(function (region) {
            return region && (region.id === normalized || region.name.toLowerCase() === normalized || region.name.toLowerCase().indexOf(normalized) >= 0);
        });
        if (!match) {
            return false;
        }

        setFocusTarget({
            lon: match.lon,
            lat: match.lat,
            zoom: clamp(Number(zoomOverride) || getFocusZoomForMode('region'), 0.7, getMaxUiZoom())
        });
        return true;
    }

    function clearFocusTarget() {
        CAMERA_ANIMATION.focusTarget = null;
        MAP_UI_STATE.animationEnabled = true;
        syncUiControls();
        if (window.__themisdbGlobeDebug) {
            window.__themisdbGlobeDebug.focusTarget = null;
            window.__themisdbGlobeDebug.animationEnabled = true;
        }
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

        const rotationControl = document.getElementById('themisdb-global-network-map-rotation-speed');
        if (rotationControl) {
            rotationControl.value = String(clamp(Number(MAP_UI_STATE.rotationSpeed) || 0.35, 0.1, 1.5));
        }

        const colorSelect = document.getElementById('themisdb-global-network-map-color');
        if (colorSelect) {
            colorSelect.value = MAP_UI_STATE.color;
        }

        const animationButton = document.querySelector('[data-map-animation-toggle]');
        if (animationButton) {
            const isCheckbox = animationButton.matches && animationButton.matches('input[type="checkbox"]');
            if (isCheckbox) {
                animationButton.checked = MAP_UI_STATE.animationEnabled;
            } else {
                animationButton.classList.toggle('is-enabled', MAP_UI_STATE.animationEnabled);
                animationButton.textContent = 'Animation: ' + (MAP_UI_STATE.animationEnabled ? 'On' : 'Off');
            }
        }

        const mouseButton = document.querySelector('[data-map-mouse-toggle]');
        if (mouseButton) {
            const isCheckbox = mouseButton.matches && mouseButton.matches('input[type="checkbox"]');
            if (isCheckbox) {
                mouseButton.checked = MAP_UI_STATE.mouseControlEnabled;
            } else {
                mouseButton.classList.toggle('is-enabled', MAP_UI_STATE.mouseControlEnabled);
                mouseButton.textContent = 'Mouse: ' + (MAP_UI_STATE.mouseControlEnabled ? 'On' : 'Off');
            }
        }
    }

    function updateDebugOverlay(nodes, edges, fps) {
        const debugPanel = document.getElementById('themisdb-global-network-map-debug');
        if (!debugPanel) {
            return;
        }

        const nodeValue = debugPanel.querySelector('[data-debug="nodes"]');
        const edgeValue = debugPanel.querySelector('[data-debug="edges"]');
        const fpsValue = debugPanel.querySelector('[data-debug="fps"]');
        const overlayValue = debugPanel.querySelector('[data-debug="overlay"]');
        if (nodeValue) {
            nodeValue.textContent = String(Math.max(0, Number(nodes) || 0));
        }
        if (edgeValue) {
            edgeValue.textContent = String(Math.max(0, Number(edges) || 0));
        }
        if (fpsValue) {
            fpsValue.textContent = String(Math.max(0, Number(fps) || 0));
        }
        if (overlayValue) {
            overlayValue.textContent = getDebugProjectionLabel(getDebugProjectionMode());
        }
    }

    function lerp(start, end, amount) {
        return start + (end - start) * amount;
    }

    function pyramidSpeedCurve(value) {
        const t = clamp(value, 0, 1);
        return 0.25 + Math.pow(t, 1.7) * 1.75;
    }

    function getDynamicPyramidVelocity(targetDistance, mode) {
        const modeFactor = mode === 'country' ? 1.55 : mode === 'region' ? 1.25 : 1;
        return pyramidSpeedCurve(targetDistance) * modeFactor;
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
                name: 'france',
                points: [
                    [-6, 51], [-5, 49], [-3, 47], [-1, 45], [2, 43], [5, 43], [7, 45], [8, 48], [7, 50], [5, 52],
                    [2, 51], [0, 49], [-2, 50], [-4, 52], [-6, 51]
                ]
            },
            {
                name: 'europe-africa',
                points: [
                    [-16, 72], [-8, 70], [0, 68], [8, 64], [18, 60], [26, 56], [34, 52], [40, 45], [42, 37],
                    [38, 28], [34, 22], [28, 15], [22, 10], [16, 2], [12, -6], [14, -18], [20, -30], [30, -34],
                    [38, -24], [42, -10], [44, 2], [46, 18], [43, 32], [36, 42], [28, 46], [18, 52], [4, 60],
                    [-10, 66], [-16, 72]
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

    function createFallbackGeoJson() {
        const contours = createFallbackContours();
        return {
            type: 'FeatureCollection',
            features: contours.map(function (continent, index) {
                return {
                    type: 'Feature',
                    properties: {
                        name: continent && continent.name ? continent.name : ('fallback-land-' + index)
                    },
                    geometry: {
                        type: 'Polygon',
                        coordinates: [
                            (continent && Array.isArray(continent.points) ? continent.points : []).map(function (point) {
                                return [Number(point[0]), Number(point[1])];
                            }).filter(function (point) {
                                return Number.isFinite(point[0]) && Number.isFinite(point[1]);
                            })
                        ]
                    }
                };
            }).filter(function (feature) {
                return !!feature && Array.isArray(feature.geometry.coordinates) && feature.geometry.coordinates.length;
            })
        };
    }

    function makePathFromCoordinates(coords, width, height, offsets, projectionMode) {
        const wrapOffsets = offsets || [0];
        return wrapOffsets.map(function (offset) {
            return coords.map(function (ring) {
                const projectedPoints = ring.map(function (point) {
                    const lon = point[0] + offset;
                    const lat = point[1];
                    return projectOrthographicPoint(lon, lat, width, height, MAP_CAMERA.centerLon, MAP_CAMERA.centerLat);
                }).filter(function (point) {
                    return point.z >= 0.01;
                });

                if (!projectedPoints.length) {
                    return '';
                }

                const points = projectedPoints.map(function (point) {
                    return point.x.toFixed(2) + ' ' + point.y.toFixed(2);
                });

                return 'M ' + points.join(' L ') + ' Z';
            }).filter(Boolean).join(' ');
        }).join(' ');
    }

    function drawGeoJsonLandmass(svg, width, height, geojson, projectionMode) {
        const existing = svg.querySelector('#themisdb-land-layer');
        if (existing) {
            existing.remove();
        }

        const landGroup = document.createElementNS('http://www.w3.org/2000/svg', 'g');
        landGroup.setAttribute('id', 'themisdb-land-layer');
        const target = svg.querySelector('#themisdb-map-group') || svg;
        const features = geojson && Array.isArray(geojson.features) ? geojson.features : [];
        const theme = getActiveColorTheme();

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
                const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                path.setAttribute('d', makePathFromCoordinates(coordinates, width, height, [-360, 0, 360], projectionMode));
                path.setAttribute('fill', 'rgba(148, 202, 255, 0.14)');
                path.setAttribute('stroke', 'rgba(180, 224, 255, 0.34)');
                path.setAttribute('stroke-width', projectionMode === 'globe' ? '0.9' : '1.0');
                path.setAttribute('stroke-linejoin', 'round');
                path.setAttribute('stroke-linecap', 'round');
                path.setAttribute('vector-effect', 'non-scaling-stroke');
                path.setAttribute('opacity', '0.9');
                if (projectionMode === 'globe') {
                    path.setAttribute('clip-path', 'url(#themisdb-globe-clip)');
                }
                landGroup.appendChild(path);
            }

            if (geometryType === 'MultiPolygon') {
                coordinates.forEach(function (polygon) {
                    const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                    path.setAttribute('d', makePathFromCoordinates(polygon, width, height, [-360, 0, 360], projectionMode));
                    path.setAttribute('fill', 'rgba(148, 202, 255, 0.14)');
                    path.setAttribute('stroke', 'rgba(180, 224, 255, 0.34)');
                    path.setAttribute('stroke-width', projectionMode === 'globe' ? '0.9' : '1.0');
                    path.setAttribute('stroke-linejoin', 'round');
                    path.setAttribute('stroke-linecap', 'round');
                    path.setAttribute('vector-effect', 'non-scaling-stroke');
                    path.setAttribute('opacity', '0.9');
                    if (projectionMode === 'globe') {
                        path.setAttribute('clip-path', 'url(#themisdb-globe-clip)');
                    }
                    landGroup.appendChild(path);
                });
            }
        });

        if (landGroup.childNodes.length) {
            target.appendChild(landGroup);
        }
    }

    function simplifyGeoRing(ring, stride) {
        if (!Array.isArray(ring) || !ring.length) {
            return [];
        }

        const step = Math.max(1, Number(stride) || 6);
        const simplified = [];
        for (let index = 0; index < ring.length; index += step) {
            const point = ring[index];
            if (!point || !Array.isArray(point) || point.length < 2) {
                continue;
            }
            simplified.push([Number(point[0]), Number(point[1])]);
        }

        const last = ring[ring.length - 1];
        if (last && simplified.length && (simplified[simplified.length - 1][0] !== Number(last[0]) || simplified[simplified.length - 1][1] !== Number(last[1]))) {
            simplified.push([Number(last[0]), Number(last[1])]);
        }

        return simplified.filter(function (point) {
            return Number.isFinite(point[0]) && Number.isFinite(point[1]);
        });
    }

    function createFallbackContoursFromGeoJson(geojson) {
        if (!geojson || !Array.isArray(geojson.features)) {
            return createFallbackContours();
        }

        const contours = [];
        geojson.features.forEach(function (feature) {
            if (!feature || !feature.geometry) {
                return;
            }

            const geometryType = feature.geometry.type;
            const geometryCoords = feature.geometry.coordinates;
            if (!geometryCoords || !Array.isArray(geometryCoords)) {
                return;
            }

            const polygons = geometryType === 'MultiPolygon' ? geometryCoords : [geometryCoords];
            polygons.forEach(function (polygon) {
                if (!Array.isArray(polygon) || !polygon.length) {
                    return;
                }

                const ring = Array.isArray(polygon[0]) && Array.isArray(polygon[0][0]) ? polygon[0] : polygon;
                const simplified = simplifyGeoRing(ring, Math.max(4, Math.floor(ring.length / 30)));
                if (simplified.length > 6) {
                    contours.push({ name: feature.properties && feature.properties.name ? feature.properties.name : 'land', points: simplified });
                }
            });
        });

        return contours.length ? contours.slice(0, 24) : createFallbackContours();
    }

    function drawFallbackLandmass(svg, width, height) {
        const existing = svg.querySelector('#themisdb-land-layer');
        if (existing) {
            existing.remove();
        }

        const landGroup = document.createElementNS('http://www.w3.org/2000/svg', 'g');
        landGroup.setAttribute('id', 'themisdb-land-layer');
        const target = svg.querySelector('#themisdb-map-group') || svg;
        const contours = GLOBE_STATE.geojson ? createFallbackContoursFromGeoJson(GLOBE_STATE.geojson) : createFallbackContours();

        contours.forEach(function (continent) {
            if (!continent || !Array.isArray(continent.points) || !continent.points.length) {
                return;
            }

            const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            const d = makePathFromCoordinates([continent.points], width, height, [-360, 0, 360], 'globe');
            path.setAttribute('d', d);
            path.setAttribute('fill', 'rgba(145, 206, 255, 0.26)');
            path.setAttribute('stroke', 'rgba(202, 230, 255, 0.52)');
            path.setAttribute('stroke-width', '0.9');
            path.setAttribute('stroke-linejoin', 'round');
            path.setAttribute('stroke-linecap', 'round');
            path.setAttribute('vector-effect', 'non-scaling-stroke');
            path.setAttribute('opacity', '1');
            path.setAttribute('clip-path', 'url(#themisdb-globe-clip)');
            landGroup.appendChild(path);
        });

        if (landGroup.childNodes.length) {
            target.appendChild(landGroup);
        }
    }

    function renderGlobeOverlay(svg, width, height) {
        let globeLayer = svg.querySelector('#themisdb-globe-layer');
        if (!globeLayer) {
            globeLayer = document.createElementNS('http://www.w3.org/2000/svg', 'g');
            globeLayer.setAttribute('id', 'themisdb-globe-layer');
            svg.appendChild(globeLayer);
        }

        const theme = getActiveColorTheme();
        const radius = getGlobeRenderRadius(width, height);
        const cx = width / 2;
        const cy = height / 2;

        let baseCircle = globeLayer.querySelector('circle[data-globe-base]');
        if (!baseCircle) {
            baseCircle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
            baseCircle.setAttribute('data-globe-base', 'true');
            globeLayer.appendChild(baseCircle);
        }
        baseCircle.setAttribute('cx', String(cx));
        baseCircle.setAttribute('cy', String(cy));
        baseCircle.setAttribute('r', String(radius));
        baseCircle.setAttribute('fill', theme.globe);
        baseCircle.setAttribute('stroke', 'rgba(176, 225, 255, 0.25)');
        baseCircle.setAttribute('stroke-width', '1');

        let ring = globeLayer.querySelector('circle[data-globe-ring]');
        if (!ring) {
            ring = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
            ring.setAttribute('data-globe-ring', 'true');
            globeLayer.appendChild(ring);
        }
        ring.setAttribute('cx', String(cx));
        ring.setAttribute('cy', String(cy));
        ring.setAttribute('r', String(radius * 1.06));
        ring.setAttribute('fill', 'none');
        ring.setAttribute('stroke', 'rgba(147, 214, 255, 0.15)');
        ring.setAttribute('stroke-width', '1.2');
        ring.setAttribute('clip-path', 'url(#themisdb-globe-clip)');

        const latitudeBands = [-75, -60, -45, -30, -15, 0, 15, 30, 45, 60, 75];
        const longitudeBands = [-165, -135, -105, -75, -45, -15, 15, 45, 75, 105, 135, 165];

        let latPaths = globeLayer.__latPaths;
        if (!latPaths) {
            latPaths = [];
            latitudeBands.forEach(function () {
                const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                path.setAttribute('fill', 'none');
                path.setAttribute('stroke', 'rgba(160, 214, 255, 0.20)');
                path.setAttribute('stroke-width', '0.85');
                path.setAttribute('clip-path', 'url(#themisdb-globe-clip)');
                globeLayer.appendChild(path);
                latPaths.push(path);
            });
            globeLayer.__latPaths = latPaths;
        }

        let lonPaths = globeLayer.__lonPaths;
        if (!lonPaths) {
            lonPaths = [];
            longitudeBands.forEach(function () {
                const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                path.setAttribute('fill', 'none');
                path.setAttribute('stroke', 'rgba(160, 214, 255, 0.15)');
                path.setAttribute('stroke-width', '0.85');
                path.setAttribute('clip-path', 'url(#themisdb-globe-clip)');
                globeLayer.appendChild(path);
                lonPaths.push(path);
            });
            globeLayer.__lonPaths = lonPaths;
        }

        latitudeBands.forEach(function (lat, index) {
            const path = latPaths[index];
            if (!path) {
                return;
            }
            const d = [];
            for (let lon = -180; lon <= 180; lon += 5) {
                const point = projectOrthographicPoint(lon, lat, width, height, MAP_CAMERA.centerLon, MAP_CAMERA.centerLat);
                d.push((lon === -180 ? 'M' : 'L') + ' ' + point.x.toFixed(2) + ' ' + point.y.toFixed(2));
            }
            path.setAttribute('d', d.join(' '));
        });

        longitudeBands.forEach(function (lon, index) {
            const path = lonPaths[index];
            if (!path) {
                return;
            }
            const d = [];
            for (let lat = -90; lat <= 90; lat += 5) {
                const point = projectOrthographicPoint(lon, lat, width, height, MAP_CAMERA.centerLon, MAP_CAMERA.centerLat);
                d.push((lat === -90 ? 'M' : 'L') + ' ' + point.x.toFixed(2) + ' ' + point.y.toFixed(2));
            }
            path.setAttribute('d', d.join(' '));
        });

        const mapGroup = svg.querySelector('#themisdb-map-group');
        if (mapGroup && !mapGroup.contains(globeLayer)) {
            mapGroup.appendChild(globeLayer);
        }
    }

    function getLocalGeoJsonPayload() {
        const data = window.themisdbGnmData || {};
        if (data.geojson && data.geojson.type === 'FeatureCollection' && Array.isArray(data.geojson.features)) {
            return data.geojson;
        }
        return null;
    }

    function getLocalGeoJsonUrl() {
        const data = window.themisdbGnmData || {};
        return data.geojsonAjaxUrl || data.geojsonUrl || '';
    }

    function loadGeoJson(url, callback) {
        const payload = getLocalGeoJsonPayload();
        if (payload) {
            GLOBE_STATE.geojson = payload;
            if (callback) {
                callback(payload);
            }
            return;
        }

        if (!url) {
            GLOBE_STATE.geojson = GLOBE_STATE.geojson || createFallbackGeoJson();
            if (callback) {
                callback(GLOBE_STATE.geojson);
            }
            return;
        }

        if (typeof window.fetch === 'function') {
            window.fetch(url, { credentials: 'same-origin' })
                .then(function (response) {
                    if (!response || !response.ok) {
                        throw new Error('GeoJSON request failed');
                    }
                    return response.json();
                })
                .then(function (geojson) {
                    const finalGeojson = geojson && Array.isArray(geojson.features) ? geojson : createFallbackGeoJson();
                    GLOBE_STATE.geojson = finalGeojson;
                    if (callback) {
                        callback(finalGeojson);
                    }
                })
                .catch(function () {
                    GLOBE_STATE.geojson = GLOBE_STATE.geojson || createFallbackGeoJson();
                    if (callback) {
                        callback(GLOBE_STATE.geojson);
                    }
                });
            return;
        }

        const request = new XMLHttpRequest();
        request.open('GET', url, true);
        request.onreadystatechange = function () {
            if (request.readyState !== 4) {
                return;
            }
            if (request.status >= 200 && request.status < 300) {
                try {
                    const parsedGeojson = JSON.parse(request.responseText);
                    const validGeojson = parsedGeojson && Array.isArray(parsedGeojson.features) ? parsedGeojson : createFallbackGeoJson();
                    GLOBE_STATE.geojson = validGeojson;
                    if (callback) {
                        callback(validGeojson);
                    }
                } catch (error) {
                    GLOBE_STATE.geojson = GLOBE_STATE.geojson || createFallbackGeoJson();
                    if (callback) {
                        callback(GLOBE_STATE.geojson);
                    }
                }
                return;
            }
            GLOBE_STATE.geojson = GLOBE_STATE.geojson || createFallbackGeoJson();
            if (callback) {
                callback(GLOBE_STATE.geojson);
            }
        };
        request.send();
    }

    function buildBaseMap(width, height) {
        const svg = document.getElementById('themisdb-global-network-map-svg');
        if (!svg) {
            return Promise.resolve();
        }

        const signature = width + 'x' + height + '|' + MAP_UI_STATE.color + '|' + CAMERA_ANIMATION.mode;
        if (signature === lastSvgSignature && !svgSceneDirty) {
            return Promise.resolve();
        }
        lastSvgSignature = signature;
        svgSceneDirty = false;

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
        const globeRadius = getGlobeRenderRadius(width, height);
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
        drawFallbackLandmass(svg, width, height);

        renderGlobeOverlay(svg, width, height);
        drawFallbackLandmass(svg, width, height);
        return Promise.resolve();
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
            return ordered.slice(0, EDGE_RENDER_LIMITS.routeNodes);
        }

        return nodes.slice(0, EDGE_RENDER_LIMITS.routeNodes).map(function (node) {
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
        return;
    }

    function drawHeatmap(ctx, nodes, width, height, interactionState) {
        return;
    }

    function projectFallbackGlobePoint(lon, lat, cx, cy, radius) {
        const lonRad = normalizeLongitude(lon) * Math.PI / 180;
        const latRad = clamp(Number(lat) || 0, -90, 90) * Math.PI / 180;
        return {
            x: cx + Math.cos(latRad) * Math.sin(lonRad) * radius,
            y: cy - Math.sin(latRad) * radius
        };
    }

    function renderFallbackGlobe() {
        const canvas = document.getElementById('themisdb-global-network-map-canvas');
        if (!canvas) {
            return;
        }

        const context = canvas.getContext('2d');
        if (!context) {
            return;
        }

        const width = canvas.width || canvas.clientWidth || 1200;
        const height = canvas.height || canvas.clientHeight || 540;
        const cx = width * 0.5;
        const cy = height * 0.52;
        const radius = Math.min(width, height) * 0.28;

        context.clearRect(0, 0, width, height);
        const ocean = context.createRadialGradient(cx, cy - 20, 40, cx, cy, radius * 1.65);
        ocean.addColorStop(0, '#183d5e');
        ocean.addColorStop(0.5, '#0d2940');
        ocean.addColorStop(1, '#061722');
        context.fillStyle = ocean;
        context.fillRect(0, 0, width, height);

        context.beginPath();
        context.fillStyle = '#0c2233';
        context.arc(cx, cy, radius, 0, Math.PI * 2);
        context.fill();

        context.beginPath();
        context.fillStyle = 'rgba(142, 201, 247, 0.82)';
        context.arc(cx, cy, radius, 0, Math.PI * 2);
        context.fill();

        context.strokeStyle = 'rgba(169, 218, 255, 0.22)';
        context.lineWidth = 1;
        for (let i = 0; i <= 11; i += 1) {
            const lat = -75 + i * 15;
            const y = cy - Math.sin(lat * Math.PI / 180) * radius;
            const pathRadius = Math.cos(lat * Math.PI / 180) * radius;
            context.beginPath();
            context.ellipse(cx, cy, pathRadius, radius, 0, 0, Math.PI * 2);
            context.stroke();
        }

        for (let lon = -165; lon <= 180; lon += 30) {
            context.beginPath();
            for (let lat = -90; lat <= 90; lat += 5) {
                const point = projectFallbackGlobePoint(lon, lat, cx, cy, radius);
                if (lat === -90) {
                    context.moveTo(point.x, point.y);
                } else {
                    context.lineTo(point.x, point.y);
                }
            }
            context.stroke();
        }

        const nodes = (window.themisdbGnmData && Array.isArray(window.themisdbGnmData.nodes) ? window.themisdbGnmData.nodes : []);
        nodes.forEach(function (node) {
            const lat = Number(node && node.lat);
            const lon = Number(node && node.lon);
            if (!Number.isFinite(lat) || !Number.isFinite(lon)) {
                return;
            }
            const point = projectFallbackGlobePoint(lon, lat, cx, cy, radius);
            const distanceFromCenter = Math.hypot(point.x - cx, point.y - cy);
            if (distanceFromCenter > radius + 2) {
                return;
            }

            const intensity = clamp(node.load || 0, 0, 100) / 100;
            context.beginPath();
            context.fillStyle = 'rgba(' + Math.round(120 + intensity * 95) + ', ' + Math.round(200 + intensity * 45) + ', 255, 0.95)';
            context.arc(point.x, point.y, 2.5 + intensity * 4, 0, Math.PI * 2);
            context.fill();

            if (node.city && ['New York', 'London', 'Frankfurt', 'Tokyo', 'Singapore', 'Dubai', 'São Paulo', 'Sydney'].indexOf(node.city) !== -1) {
                context.font = '600 12px Arial';
                context.fillStyle = 'rgba(235, 245, 255, 0.9)';
                context.fillText(node.city, point.x + 7, point.y - 7);
            }
        });

        context.beginPath();
        context.strokeStyle = 'rgba(167, 229, 255, 0.4)';
        context.lineWidth = 2;
        context.arc(cx, cy, radius + 4, 0, Math.PI * 2);
        context.stroke();
    }

    function renderMap() {
        const canvas = document.getElementById('themisdb-global-network-map-canvas');
        const tooltip = document.getElementById('themisdb-global-network-map-tooltip');
        if (window.THREE) {
            initWebGLGlobe();
        } else {
            renderFallbackGlobe();
        }
        if (!canvas) {
            return;
        }

        if (!window.__themisdbGlobe || !window.__themisdbGlobe.enabled) {
            renderFallbackGlobe();
        }

        const width = canvas.width;
        const height = canvas.height;
        const rawNodes = window.themisdbGnmData && window.themisdbGnmData.nodes ? window.themisdbGnmData.nodes : [];
        const interactionState = {
            hovered: null,
            selected: null
        };

        const refreshViewport = function () {
            if (!canvas) {
                return;
            }
        };

        MAP_UI_STATE.zoom = clamp(MAP_CAMERA.zoom, 0.7, getMaxUiZoom());
        syncUiControls();
        window.addEventListener('resize', refreshViewport);

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
            const label = node.city || node.region || 'Network node';
            const load = clamp(node.load || 0, 0, 100);
            const status = getNodeStatusLabel(node);
            const statusLabel = status === 'critical' ? 'Critical' : status === 'warning' ? 'Warning' : 'Active';
            tooltip.innerHTML = '<strong>' + label + '</strong><br>' + (node.country || 'Region') + '<br>' + statusLabel + ' · ' + (node.instances || 0) + ' instances · ' + load + '% load';
            tooltip.style.left = x + 'px';
            tooltip.style.top = y + 'px';
            tooltip.classList.add('is-visible');
        }

        function hideTooltip() {
            if (tooltip) {
                tooltip.classList.remove('is-visible');
            }
        }

        function syncInteractionState(nextHovered, updateSelection) {
            interactionState.hovered = nextHovered;

            if (updateSelection) {
                interactionState.selected = nextHovered;
            }
        }

        canvas.addEventListener('mousemove', function (event) {
            const point = getNodeAtPointer(event.clientX, event.clientY);
            syncInteractionState(point, false);
            if (point) {
                showTooltip(event, point);
            } else {
                hideTooltip();
            }
        });

        canvas.addEventListener('click', function (event) {
            const point = getNodeAtPointer(event.clientX, event.clientY);
            if (!point) {
                interactionState.selected = null;
                clearFocusTarget();
                hideTooltip();
                return;
            }

            syncInteractionState(point, true);
            showTooltip(event, point);

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
            return;
        });

        canvas.addEventListener('mouseleave', function () {
            hideTooltip();
            interactionState.hovered = null;
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
            svgSceneDirty = true;
            adjustZoom(delta);
        }, { passive: false });

        function setMode(mode) {
            const nextMode = MAP_MODE_SETTINGS[mode] ? mode : 'globe';
            CAMERA_ANIMATION.mode = nextMode;
            const focusZoom = CAMERA_ANIMATION.focusTarget && CAMERA_ANIMATION.focusTarget.zoom ? CAMERA_ANIMATION.focusTarget.zoom : getTargetZoomForMode(nextMode);
            const nextZoom = clamp(focusZoom, 0.7, getMaxUiZoom());
            CAMERA_ANIMATION.targetZoom = nextZoom;
            MAP_CAMERA.zoom = nextZoom;
            MAP_UI_STATE.zoom = nextZoom;
            svgSceneDirty = true;

            const buttons = document.querySelectorAll('[data-map-mode]');
            buttons.forEach(function (button) {
                const active = button.getAttribute('data-map-mode') === nextMode;
                button.classList.toggle('is-active', active);
                button.setAttribute('aria-pressed', active ? 'true' : 'false');
            });

            syncUiControls();
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
            const baseZoom = getFocusZoomForMode(CAMERA_ANIMATION.mode);
            const nextZoom = clamp(baseZoom, modeSettings.zoom, modeSettings.maxZoom);
            CAMERA_ANIMATION.targetZoom = nextZoom;
            MAP_CAMERA.zoom = nextZoom;
            MAP_UI_STATE.zoom = nextZoom;
            svgSceneDirty = true;
            setFocusTarget({ lon: region.lon, lat: region.lat, zoom: nextZoom });
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

        function updateCamera(now, deltaScale) {
            const modeSettings = getModeSettings(CAMERA_ANIMATION.mode);
            const animationSpeed = (modeSettings.animationSpeed || 0.006) * deltaScale * 1.8;
            const targetFocus = CAMERA_ANIMATION.focusTarget;

            if (!MAP_UI_STATE.animationEnabled) {
                MAP_CAMERA.zoom = lerp(MAP_CAMERA.zoom, clamp(MAP_UI_STATE.zoom, 0.7, getMaxUiZoom()), 0.08);
                MAP_UI_STATE.zoom = clamp(MAP_CAMERA.zoom, 0.7, getMaxUiZoom());
                syncUiControls();
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

                const focusSettings = FOCUS_SENSITIVITY[CAMERA_ANIMATION.mode] || FOCUS_SENSITIVITY.globe;
                const distanceRatio = clamp(
                    ((Math.abs(shortestLongitudeDelta) / 180) + (Math.abs(desiredLat - MAP_CAMERA.centerLat) / 90) + (Math.abs(desiredZoom - MAP_CAMERA.zoom) / Math.max(0.1, getMaxUiZoom()))) / 3,
                    0,
                    1
                );
                const pyramidVelocity = getDynamicPyramidVelocity(distanceRatio, CAMERA_ANIMATION.mode) * 2.4;

                CAMERA_ANIMATION.velocityLon = (CAMERA_ANIMATION.velocityLon + (shortestLongitudeDelta * focusSettings.lonInfluence * pyramidVelocity)) * CAMERA_ANIMATION.followInertia;
                CAMERA_ANIMATION.velocityLat = (CAMERA_ANIMATION.velocityLat + (desiredLat - MAP_CAMERA.centerLat) * (0.032 + focusSettings.orbitInfluence) * pyramidVelocity) * CAMERA_ANIMATION.followInertia;
                CAMERA_ANIMATION.velocityZoom = (CAMERA_ANIMATION.velocityZoom + (desiredZoom - MAP_CAMERA.zoom) * (focusSettings.focusStrength * 1.5) * pyramidVelocity) * CAMERA_ANIMATION.followInertia;

                MAP_CAMERA.centerLon = normalizeLongitude(MAP_CAMERA.centerLon + CAMERA_ANIMATION.velocityLon);
                MAP_CAMERA.centerLat = clamp(MAP_CAMERA.centerLat + CAMERA_ANIMATION.velocityLat, -80, 80);
                MAP_CAMERA.zoom = clamp(MAP_CAMERA.zoom + CAMERA_ANIMATION.velocityZoom, 0.7, getMaxUiZoom());
                CAMERA_ANIMATION.targetLon = MAP_CAMERA.centerLon;
                CAMERA_ANIMATION.targetLat = MAP_CAMERA.centerLat;
                CAMERA_ANIMATION.targetZoom = desiredZoom;
            } else {
                const driftScale = CAMERA_ANIMATION.mode === 'country' ? 10 : CAMERA_ANIMATION.mode === 'region' ? 14 : 18;
                const driftLon = Math.sin(CAMERA_ANIMATION.globeDrift) * driftScale + Math.cos(CAMERA_ANIMATION.orbitPhase) * (CAMERA_ANIMATION.mode === 'country' ? 5 : 7);
                const driftLat = Math.sin(CAMERA_ANIMATION.globeDrift * 0.7) * (CAMERA_ANIMATION.mode === 'country' ? 6 : 8) + Math.cos(CAMERA_ANIMATION.orbitPhase * 0.8) * (CAMERA_ANIMATION.mode === 'country' ? 3.5 : 4.8);
                const easedLon = normalizeLongitude(CAMERA_ANIMATION.targetLon + driftLon);
                const easedLat = clamp(CAMERA_ANIMATION.targetLat + driftLat, -24, 56);

                MAP_CAMERA.centerLon = lerp(MAP_CAMERA.centerLon, easedLon, animationSpeed);
                MAP_CAMERA.centerLat = lerp(MAP_CAMERA.centerLat, easedLat, animationSpeed);
                MAP_CAMERA.zoom = lerp(MAP_CAMERA.zoom, CAMERA_ANIMATION.targetZoom, animationSpeed * 1.45);
            }

            MAP_UI_STATE.zoom = clamp(MAP_CAMERA.zoom, 0.7, getMaxUiZoom());
            CAMERA_ANIMATION.globeDrift += animationSpeed * 1.2;
            CAMERA_ANIMATION.orbitPhase += animationSpeed * 1.2;

            if (now - lastSvgRenderStamp > SVG_RENDER_INTERVAL_MS) {
                renderGlobeOverlay(svg, width, height);
                lastSvgRenderStamp = now;
            }
        }

        function tickFrame(timestamp) {
            if (WEBGL_GLOBE.enabled) {
                updateWebGLGlobe(timestamp);
                if (MAP_UI_STATE.animationEnabled) {
                    animationFrameHandle = requestAnimationFrame(tickFrame);
                }
                return;
            }

            const ctx = canvas.getContext('2d');
            if (!ctx) {
                return;
            }

            if (!tickFrame.lastTimestamp) {
                tickFrame.lastTimestamp = timestamp;
            }

            const deltaMs = Math.max(16, timestamp - tickFrame.lastTimestamp);
            const deltaScale = clamp(deltaMs / 16.67, 0.5, 3.5);
            tickFrame.lastTimestamp = timestamp;

            updateCamera(timestamp, deltaScale);
            ctx.clearRect(0, 0, width, height);
            updateDebugOverlay(nodes.length, 0, Math.max(1, Math.round(1000 / deltaMs)));

            if (MAP_UI_STATE.animationEnabled) {
                animationFrameHandle = requestAnimationFrame(tickFrame);
            } else {
                tickFrame.lastTimestamp = 0;
                if (animationFrameHandle) {
                    cancelAnimationFrame(animationFrameHandle);
                    animationFrameHandle = null;
                }
            }
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
                svgSceneDirty = true;
                syncUiControls();
            });
        }

        const rotationControl = document.getElementById('themisdb-global-network-map-rotation-speed');
        if (rotationControl) {
            rotationControl.addEventListener('input', function () {
                const nextSpeed = parseFloat(this.value) || 0.35;
                MAP_UI_STATE.rotationSpeed = clamp(nextSpeed, 0.1, 1.5);
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
            const toggleEvent = animationToggle.matches && animationToggle.matches('input[type="checkbox"]') ? 'change' : 'click';
            animationToggle.addEventListener(toggleEvent, function () {
                const nextValue = animationToggle.matches && animationToggle.matches('input[type="checkbox"]') ? animationToggle.checked : !MAP_UI_STATE.animationEnabled;
                MAP_UI_STATE.animationEnabled = nextValue;
                syncUiControls();

                if (MAP_UI_STATE.animationEnabled) {
                    if (animationFrameHandle) {
                        cancelAnimationFrame(animationFrameHandle);
                    }
                    tickFrame.lastTimestamp = 0;
                    animationFrameHandle = requestAnimationFrame(tickFrame);
                } else if (animationFrameHandle) {
                    cancelAnimationFrame(animationFrameHandle);
                    animationFrameHandle = null;
                }
            });
        }

        const mouseToggle = document.querySelector('[data-map-mouse-toggle]');
        if (mouseToggle) {
            const toggleEvent = mouseToggle.matches && mouseToggle.matches('input[type="checkbox"]') ? 'change' : 'click';
            mouseToggle.addEventListener(toggleEvent, function () {
                const nextValue = mouseToggle.matches && mouseToggle.matches('input[type="checkbox"]') ? mouseToggle.checked : !MAP_UI_STATE.mouseControlEnabled;
                MAP_UI_STATE.mouseControlEnabled = nextValue;
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

        const canvasWrap = document.querySelector('.themisdb-global-network-map__canvas-wrap');
        if (canvasWrap) {
            canvasWrap.setAttribute('tabindex', '0');
            canvasWrap.addEventListener('keydown', function (event) {
                const step = event.shiftKey ? 0.12 : 0.08;
                const panStep = event.shiftKey ? 14 : 8;
                if (event.key === 'ArrowLeft') {
                    MAP_CAMERA.centerLon = normalizeLongitude(MAP_CAMERA.centerLon - panStep);
                } else if (event.key === 'ArrowRight') {
                    MAP_CAMERA.centerLon = normalizeLongitude(MAP_CAMERA.centerLon + panStep);
                } else if (event.key === 'ArrowUp') {
                    MAP_CAMERA.centerLat = clamp(MAP_CAMERA.centerLat + (event.shiftKey ? 8 : 5), -80, 80);
                } else if (event.key === 'ArrowDown') {
                    MAP_CAMERA.centerLat = clamp(MAP_CAMERA.centerLat - (event.shiftKey ? 8 : 5), -80, 80);
                } else if (event.key === '+' || event.key === 'PageUp') {
                    adjustZoom(step);
                } else if (event.key === '-' || event.key === 'PageDown') {
                    adjustZoom(-step);
                } else if (event.key === 'Home') {
                    MAP_CAMERA.centerLon = 8;
                    MAP_CAMERA.centerLat = 50;
                    MAP_CAMERA.zoom = 1.1;
                    CAMERA_ANIMATION.targetZoom = 1.1;
                } else {
                    return;
                }

                event.preventDefault();
            });
        }

        const mapSvg = document.getElementById('themisdb-global-network-map-svg');
        if (mapSvg) {
            mapSvg.setAttribute('focusable', 'false');
        }
        lastSvgRenderStamp = 0;
        CAMERA_ANIMATION.lastSwitch = performance.now();
        syncUiControls();
        if (animationFrameHandle) {
            cancelAnimationFrame(animationFrameHandle);
        }
        animationFrameHandle = requestAnimationFrame(tickFrame);
    }

    function initializeMap() {
        ensureThreeLoaded(function () {
            renderMap();
        });
    }

    window.CAMERA_ANIMATION = CAMERA_ANIMATION;
    window.setFocusTarget = setFocusTarget;
    window.clearFocusTarget = clearFocusTarget;
    window.focusRegionByName = focusRegionByName;
    window.focusEurope = function () {
        return focusRegionByName('europe', 18);
    };
    window.focusNorthAmerica = function () {
        return focusRegionByName('north-america', 18);
    };
    window.focusAsiaPacific = function () {
        return focusRegionByName('asia-pacific', 18);
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeMap);
    } else {
        initializeMap();
    }
})();
