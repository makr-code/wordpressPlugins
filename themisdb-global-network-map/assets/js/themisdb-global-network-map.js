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
        rotationSpeed: 0.35
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

    const WEBGL_GLOBE = {
        enabled: false,
        renderer: null,
        scene: null,
        camera: null,
        globe: null,
        grid: null,
        rafId: null,
        pointer: null,
        lastTime: 0
    };

    let animationFrameHandle = null;
    let lastSvgRenderStamp = 0;
    let svgSceneDirty = true;
    let lastSvgSignature = '';

    function lonLatToSpherePoint(lon, lat, radius) {
        if (!window.THREE) {
            return { x: 0, y: 0, z: radius };
        }

        const THREE = window.THREE;
        const latRad = lat * Math.PI / 180;
        const lonRad = lon * Math.PI / 180;
        const x = radius * Math.cos(latRad) * Math.sin(lonRad);
        const y = radius * Math.sin(latRad);
        const z = radius * Math.cos(latRad) * Math.cos(lonRad);
        return new THREE.Vector3(x, y, z);
    }

    function buildGeoJsonLandmass(geojson, radius) {
        const THREE = window.THREE;
        if (!THREE || !geojson || !Array.isArray(geojson.features)) {
            return null;
        }

        const landGroup = new THREE.Group();
        const features = geojson.features.slice(0, 48);
        let added = 0;

        features.forEach(function (feature) {
            if (!feature || !feature.geometry || added >= 32) {
                return;
            }

            const geometry = feature.geometry;
            const polygonSet = geometry.type === 'MultiPolygon' ? geometry.coordinates : [geometry.coordinates];

            polygonSet.forEach(function (polygon) {
                if (!Array.isArray(polygon) || !polygon.length || added >= 32) {
                    return;
                }

                const rings = Array.isArray(polygon[0]) && Array.isArray(polygon[0][0]) ? polygon : [polygon];
                rings.forEach(function (ring) {
                    if (!Array.isArray(ring) || ring.length < 3 || added >= 32) {
                        return;
                    }

                    const points = [];
                    let centerLon = 0;
                    let centerLat = 0;
                    let validVertices = 0;

                    ring.forEach(function (vertex) {
                        if (!Array.isArray(vertex) || vertex.length < 2) {
                            return;
                        }

                        const lon = Number(vertex[0]);
                        const lat = Number(vertex[1]);
                        if (!Number.isFinite(lon) || !Number.isFinite(lat)) {
                            return;
                        }

                        centerLon += lon;
                        centerLat += lat;
                        validVertices += 1;
                        points.push(new THREE.Vector2(lon, lat));
                    });

                    if (points.length < 3 || validVertices < 3) {
                        return;
                    }

                    const shape = new THREE.Shape();
                    points.forEach(function (point, pointIndex) {
                        if (pointIndex === 0) {
                            shape.moveTo(point.x, point.y);
                            return;
                        }
                        shape.lineTo(point.x, point.y);
                    });
                    shape.closePath();

                    const geometryOut = new THREE.ShapeGeometry(shape);
                    const positions = [];
                    const attr = geometryOut.attributes.position;
                    for (let index = 0; index < attr.count; index += 1) {
                        const lon = attr.getX(index);
                        const lat = attr.getY(index);
                        const point = lonLatToSpherePoint(lon, lat, radius);
                        positions.push(point.x, point.y, point.z);
                    }

                    const sphereGeometry = new THREE.BufferGeometry();
                    sphereGeometry.setAttribute('position', new THREE.Float32BufferAttribute(positions, 3));
                    if (geometryOut.index && geometryOut.index.count) {
                        sphereGeometry.setIndex(geometryOut.index.array);
                    }
                    sphereGeometry.computeVertexNormals();

                    const mesh = new THREE.Mesh(
                        sphereGeometry,
                        new THREE.MeshStandardMaterial({
                            color: 0xe8f7ff,
                            emissive: 0x99d9ff,
                            emissiveIntensity: 0.4,
                            transparent: true,
                            opacity: 0.96,
                            side: THREE.DoubleSide,
                            roughness: 0.7,
                            metalness: 0.05
                        })
                    );
                    mesh.name = 'geojson-land-patch';
                    mesh.position.set(0, 0, 0);
                    landGroup.add(mesh);
                    added += 1;
                });
            });
        });

        return landGroup.children.length ? landGroup : null;
    }

    function initWebGLGlobe() {
        if (WEBGL_GLOBE.enabled || !window.THREE) {
            return;
        }

        const canvas = document.getElementById('themisdb-global-network-map-canvas');
        const svg = document.getElementById('themisdb-global-network-map-svg');
        if (!canvas) {
            return;
        }

        const glContext = canvas.getContext('webgl') || canvas.getContext('experimental-webgl');
        if (!glContext) {
            if (svg) {
                svg.style.display = 'block';
            }
            canvas.style.display = 'none';
            return;
        }

        const THREE = window.THREE;
        const renderer = new THREE.WebGLRenderer({
            canvas: canvas,
            alpha: true,
            antialias: true
        });
        renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
        renderer.setClearColor(0x000000, 0);
        renderer.setSize(canvas.clientWidth || canvas.width || 1200, canvas.clientHeight || canvas.height || 540, false);

        const scene = new THREE.Scene();
        const camera = new THREE.PerspectiveCamera(32, (canvas.clientWidth || canvas.width || 1200) / Math.max(canvas.clientHeight || canvas.height || 1, 1), 0.1, 1000);
        camera.position.z = 4.2;

        const ambientLight = new THREE.AmbientLight(0xbfe8ff, 1.6);
        scene.add(ambientLight);

        const keyLight = new THREE.DirectionalLight(0xffffff, 1.5);
        keyLight.position.set(3.8, 2.9, 4.5);
        scene.add(keyLight);

        const glowLight = new THREE.PointLight(0x66ccff, 1.35, 20);
        glowLight.position.set(-3.2, 2.2, 4.2);
        scene.add(glowLight);

        const globeGroup = new THREE.Group();
        scene.add(globeGroup);

        const globeGeometry = new THREE.SphereGeometry(1.6, 72, 72);
        const globeMaterial = new THREE.MeshStandardMaterial({
            color: 0x73c9ff,
            emissive: 0x153d5b,
            metalness: 0.08,
            roughness: 0.9,
            transparent: true,
            opacity: 0.68
        });
        const globe = new THREE.Mesh(globeGeometry, globeMaterial);
        globeGroup.add(globe);

        const atmosphere = new THREE.Mesh(
            new THREE.SphereGeometry(1.78, 32, 32),
            new THREE.MeshBasicMaterial({
                color: 0xcfeaff,
                transparent: true,
                opacity: 0.28,
                side: THREE.BackSide
            })
        );
        globeGroup.add(atmosphere);

        const grid = new THREE.Group();
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
                new THREE.LineBasicMaterial({ color: 0xa9d8ff, transparent: true, opacity: 0.42 })
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
                new THREE.LineBasicMaterial({ color: 0x8fc8ff, transparent: true, opacity: 0.28 })
            );
            grid.add(line);
        });
        const geojson = GLOBE_STATE.geojson || null;
        const landGroup = geojson ? buildGeoJsonLandmass(geojson, 1.62) : null;
        if (landGroup) {
            globeGroup.add(landGroup);
        }
        globeGroup.add(grid);

        WEBGL_GLOBE.enabled = true;
        WEBGL_GLOBE.renderer = renderer;
        WEBGL_GLOBE.scene = scene;
        WEBGL_GLOBE.camera = camera;
        WEBGL_GLOBE.globe = globeGroup;
        WEBGL_GLOBE.grid = grid;
        WEBGL_GLOBE.land = landGroup;
        WEBGL_GLOBE.pointer = new THREE.Vector2();

        if (!GLOBE_STATE.geojson) {
            const geojsonUrl = getLocalGeoJsonUrl();
            if (geojsonUrl) {
                fetch(geojsonUrl)
                    .then(function (response) {
                        if (!response.ok) {
                            return null;
                        }
                        return response.json();
                    })
                    .then(function (geojson) {
                        if (!geojson || !WEBGL_GLOBE.globe) {
                            return;
                        }
                        GLOBE_STATE.geojson = geojson;
                        const worldLand = buildGeoJsonLandmass(geojson, 1.62);
                        if (worldLand) {
                            WEBGL_GLOBE.globe.add(worldLand);
                            WEBGL_GLOBE.land = worldLand;
                        }
                    })
                    .catch(function () {
                        return null;
                    });
            }
        }

        const worldTarget = new THREE.Vector3(0, 0, 0);
        globeGroup.lookAt(worldTarget);

        if (svg) {
            svg.style.display = 'none';
        }
        canvas.style.display = 'block';
    }

    function updateWebGLGlobe(timestamp) {
        if (!WEBGL_GLOBE.enabled || !WEBGL_GLOBE.renderer || !WEBGL_GLOBE.scene || !WEBGL_GLOBE.camera) {
            return;
        }

        const THREE = window.THREE;
        if (!THREE) {
            return;
        }

        const globe = WEBGL_GLOBE.globe;
        if (!globe) {
            return;
        }

        const canvas = document.getElementById('themisdb-global-network-map-canvas');
        if (canvas) {
            const width = canvas.clientWidth || canvas.width || 1200;
            const height = canvas.clientHeight || canvas.height || 540;
            WEBGL_GLOBE.camera.aspect = width / Math.max(height, 1);
            WEBGL_GLOBE.camera.updateProjectionMatrix();
            WEBGL_GLOBE.renderer.setSize(width, height, false);
        }

        const rotationSpeed = Number(MAP_UI_STATE.rotationSpeed) || 0.35;
        const spinTime = (timestamp || 0) * 0.00012 * rotationSpeed;
        const lon = (MAP_CAMERA.centerLon || 0) * Math.PI / 180;
        const lat = (MAP_CAMERA.centerLat || 0) * Math.PI / 180;

        globe.rotation.y = -lon + spinTime * 10;
        globe.rotation.x = -lat * 0.35;
        globe.scale.setScalar(1 + (MAP_CAMERA.zoom - 1) * 0.14);

        const radius = 4.2 + ((MAP_CAMERA.zoom || 1) - 1) * 0.08;
        WEBGL_GLOBE.camera.position.x = Math.sin(lon) * radius;
        WEBGL_GLOBE.camera.position.y = -Math.sin(lat) * radius * 0.8;
        WEBGL_GLOBE.camera.position.z = Math.cos(lat) * radius + 1.2;
        WEBGL_GLOBE.camera.lookAt(0, 0, 0);
        globe.rotation.z = Math.sin((timestamp || 0) * 0.00018) * 0.12;

        WEBGL_GLOBE.renderer.render(WEBGL_GLOBE.scene, WEBGL_GLOBE.camera);
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
            animationButton.classList.toggle('is-enabled', MAP_UI_STATE.animationEnabled);
            animationButton.textContent = 'Animation: ' + (MAP_UI_STATE.animationEnabled ? 'On' : 'Off');
        }

        const mouseButton = document.querySelector('[data-map-mouse-toggle]');
        if (mouseButton) {
            mouseButton.classList.toggle('is-enabled', MAP_UI_STATE.mouseControlEnabled);
            mouseButton.textContent = 'Mouse: ' + (MAP_UI_STATE.mouseControlEnabled ? 'On' : 'Off');
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

        if (nodeValue) {
            nodeValue.textContent = String(Math.max(0, Number(nodes) || 0));
        }

        if (edgeValue) {
            edgeValue.textContent = String(Math.max(0, Number(edges) || 0));
        }

        if (fpsValue) {
            fpsValue.textContent = String(Math.max(0, Number(fps) || 0));
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

    function getLocalGeoJsonUrl() {
        const data = window.themisdbGnmData || {};
        return data.geojsonUrl || '';
    }

    function buildBaseMap(width, height) {
        if (WEBGL_GLOBE.enabled) {
            return Promise.resolve();
        }

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

    function renderMap() {
        const canvas = document.getElementById('themisdb-global-network-map-canvas');
        const svg = document.getElementById('themisdb-global-network-map-svg');
        const tooltip = document.getElementById('themisdb-global-network-map-tooltip');
        if (window.THREE) {
            initWebGLGlobe();
        }
        if (!canvas || !svg) {
            return;
        }

        const width = canvas.width;
        const height = canvas.height;
        const rawNodes = window.themisdbGnmData && window.themisdbGnmData.nodes ? window.themisdbGnmData.nodes : [];
        const interactionState = {
            hovered: null,
            selected: null
        };

        const refreshViewport = function () {
            if (!canvas || !svg) {
                return;
            }

            const nextWidth = canvas.width;
            const nextHeight = canvas.height;
            if (nextWidth && nextHeight) {
                buildBaseMap(nextWidth, nextHeight);
            }
        };

        MAP_UI_STATE.zoom = clamp(MAP_CAMERA.zoom, 0.7, getMaxUiZoom());
        syncUiControls();
        buildBaseMap(width, height);
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
            animationToggle.addEventListener('click', function () {
                MAP_UI_STATE.animationEnabled = !MAP_UI_STATE.animationEnabled;
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
        setMode('globe');
        pickRandomTarget();
        if (animationFrameHandle) {
            cancelAnimationFrame(animationFrameHandle);
        }
        animationFrameHandle = requestAnimationFrame(tickFrame);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', renderMap);
    } else {
        renderMap();
    }
})();
