import {
    WebGPURenderer, Scene, PerspectiveCamera, SphereGeometry, Mesh, Vector3,
    MeshStandardMaterial, MeshBasicMaterial, MeshBasicNodeMaterial, AmbientLight, DirectionalLight, RingGeometry,
    CanvasTexture, SRGBColorSpace, Group, LineSegments, BufferGeometry,
    Float32BufferAttribute, LineBasicMaterial, BackSide, Raycaster, Vector2,
} from 'three/webgpu';
import { normalWorld, positionWorld, cameraPosition, color } from 'three/tsl';
import { clamp, longitudeDelta, latLonToPosition, validateNodes, geoJsonPolygons, drawContinents } from './globe-geo.js';
import { DEFAULT_SETTINGS, MODE_ZOOM, SETTINGS_COOKIE, parseSettings, createFlight, sampleFlight, coverDistance } from './globe-motion.js';
import { MAJOR_CITIES, selectCityLabels } from './globe-cities.js';

const PALETTES = {
    ocean: { ocean: '#12324c', land: '#89b6bf', coast: '#c3e5e9', glow: '#63bcf4' },
    aurora: { ocean: '#103d4a', land: '#7ac9aa', coast: '#c7f5de', glow: '#71e4c3' },
    night: { ocean: '#171f40', land: '#8b9fc5', coast: '#d3dcf9', glow: '#9eafff' },
};
const TARGETS = {
    'north-america': [-98, 38], 'united-states': [-98.6, 39.8],
    europe: [12, 50], france: [2.2, 46.2], germany: [10.4, 51.2],
    'south-america': [-58, -13], brazil: [-51.9, -14.2],
    africa: [20, 4], 'south-africa': [24.9, -29], 'middle-east': [45, 25],
    india: [78.9, 22.3], 'asia-pacific': [109, 36], japan: [138.3, 36.2],
    oceania: [151, -25], australia: [133.8, -25.3],
};
const geographyRequests = new Map();
const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

async function fetchJson(url) {
    const response = await fetch(url, { credentials: 'same-origin', signal: AbortSignal.timeout(20000) });
    if (!response.ok) {
        throw new Error(`Request failed (${response.status}): ${new URL(url, location.href).pathname}`);
    }
    return response.json();
}

function loadGeography(url) {
    if (!geographyRequests.has(url)) {
        geographyRequests.set(url, fetchJson(url).then(geoJsonPolygons).catch(error => {
            geographyRequests.delete(url);
            throw error;
        }));
    }
    return geographyRequests.get(url);
}

class NetworkGlobe {
    readSettings() {
        const cookie = document.cookie.split('; ').find(value => value.startsWith(`${SETTINGS_COOKIE}=`));
        if (!cookie) return { ...DEFAULT_SETTINGS };
        try {
            return parseSettings(decodeURIComponent(cookie.slice(SETTINGS_COOKIE.length + 1)), Object.keys(TARGETS));
        } catch (error) {
            console.warn('ThemisDB globe settings:', error);
            this.message('settings', 'Saved map settings were invalid and have been reset.');
            document.cookie = `${SETTINGS_COOKIE}=; Path=/; Max-Age=0; SameSite=Lax`;
            return { ...DEFAULT_SETTINGS };
        }
    }

    saveSettings() {
        const encoded = encodeURIComponent(JSON.stringify({ version: 1, ...this.settings }));
        document.cookie = `${SETTINGS_COOKIE}=${encoded}; Path=/; Max-Age=15552000; SameSite=Lax${location.protocol === 'https:' ? '; Secure' : ''}`;
        if (!document.cookie.split('; ').includes(`${SETTINGS_COOKIE}=${encoded}`)) {
            this.message('settings', 'Browser blocked the settings cookie. Preferences will not persist.');
        } else this.message('settings', '');
    }

    hotspots() {
        const seen = new Set();
        return this.nodes.filter(node => {
            const key = `${node.lat}:${node.lon}`;
            if (seen.has(key)) return false;
            seen.add(key);
            return true;
        });
    }

    nextHotspot() {
        const hotspots = this.hotspots();
        if (!hotspots.length) return;
        this.tourIndex = (this.tourIndex + 1) % hotspots.length;
        const node = hotspots[this.tourIndex];
        this.beginFlight(node.lon, node.lat, MODE_ZOOM[this.mode], node.city || node.country);
    }

    beginFlight(lon, lat, zoom, label = '') {
        this.flight = createFlight(this.view, { lon, lat: clamp(lat, -85, 85) }, zoom);
        this.target = { ...this.flight.to };
        this.holdTime = 0;
        this.setFocusLocation(lon, lat);
        this.root.querySelector('[data-map-hotspot]').textContent = label ? `Focus: ${label}` : '';
        this.requestFrame();
    }

    updateTour(dt) {
        if (this.drag) return;
        if (this.flight) {
            this.flight.elapsed += dt * this.speed;
            const sample = sampleFlight(this.flight, this.flight.elapsed);
            this.view = { lon: sample.lon, lat: sample.lat, zoom: sample.zoom };
            this.root.dataset.gnmFlightPhase = sample.phase;
            if (sample.done) {
                this.flight = null;
                this.holdTime = 0;
            }
        } else if (this.autoRotate && !this.focused) {
            const hotspots = this.hotspots();
            if (!hotspots.length) {
                this.target.lon += dt * this.speed * 5;
                this.root.dataset.gnmFlightPhase = 'rotate';
            } else {
                this.holdTime += dt * this.speed;
                if (this.tourIndex < 0 || (hotspots.length > 1 && this.holdTime >= 5)) this.nextHotspot();
            }
        }
    }

    manualView() {
        this.flight = null;
        this.target = { ...this.view };
        this.focused = true;
        this.holdTime = 0;
        this.root.dataset.gnmFlightPhase = 'manual';
        this.focusNode = null;
        this.root.querySelector('[data-map-focus-card]').hidden = true;
    }

    setFocusLocation(lon, lat) {
        this.focusNode = this.nodes.find(node => Math.abs(node.lat - lat) < 0.0001 && Math.abs(longitudeDelta(node.lon, lon)) < 0.0001) || null;
        const card = this.root.querySelector('[data-map-focus-card]');
        card.hidden = true;
        if (!this.focusNode) return;
        const node = this.focusNode;
        card.querySelector('[data-focus-source]').textContent = this.dataSource === 'demo' ? 'DEMO / example location' : 'REPORTED LOCATION';
        card.querySelector('[data-focus-title]').textContent = node.city || node.region || node.country;
        card.querySelector('[data-focus-metrics]').textContent = `${node.instances} instances / ${node.load}% load / ${node.status}`;
        card.querySelector('[data-focus-location]').textContent = `${node.country} / ${node.lat.toFixed(2)}, ${node.lon.toFixed(2)}`;
    }

    constructor(root) {
        this.root = root;
        this.config = JSON.parse(root.dataset.gnmConfig);
        this.canvas = root.querySelector('canvas');
        this.viewport = root.querySelector('.themisdb-global-network-map__canvas-wrap');
        this.tooltip = root.querySelector('.themisdb-global-network-map__tooltip');
        this.messages = new Map();
        this.settings = this.readSettings();
        this.mode = this.settings.mode;
        this.view = { lon: 12, lat: 25, zoom: this.settings.zoom };
        this.target = { ...this.view };
        this.autoRotate = this.settings.animation && !reducedMotion.matches;
        this.mouseEnabled = this.settings.drag;
        this.speed = this.settings.speed;
        this.focused = this.settings.target !== 'auto';
        this.tourIndex = -1;
        this.holdTime = 0;
        if (this.focused) {
            const [lon, lat] = TARGETS[this.settings.target];
            this.view = { lon, lat, zoom: this.settings.zoom };
            this.target = { ...this.view };
        }
        this.nodes = [];
        this.visible = true;
        this.disposed = false;
        this.frame = null;
        this.lastFrame = 0;
        this.events = new AbortController();
        this.raycaster = new Raycaster();
        this.pointer = new Vector2();
        this.scene = new Scene();
        this.camera = new PerspectiveCamera(38, 1, 0.05, 100);
        this.markers = new Group();
        this.scene.add(this.markers);
        this.halos = new Group();
        this.scene.add(this.halos);
        this.cityLayer = root.querySelector('.themisdb-global-network-map__city-layer');
        this.cityLabels = MAJOR_CITIES.map(city => {
            const element = document.createElement('span');
            element.className = 'themisdb-global-network-map__city-label';
            element.textContent = city.name;
            element.dataset.city = city.name;
            element.style.visibility = 'hidden';
            this.cityLayer.append(element);
            return { ...city, element, position: new Vector3().fromArray(latLonToPosition(city.lon, city.lat, 1.001)), previouslyVisible: false };
        });
        this.projectedCity = new Vector3();
        this.bindControls();
        this.start().catch(error => {
            if (this.disposed) return;
            console.error('ThemisDB network globe:', error);
            this.message('rendering', `Globe unavailable: ${error.message}`);
            root.dataset.gnmState = 'error';
            root.querySelector('[data-map-renderer]').textContent = '3D renderer unavailable';
        });
    }

    message(key, value) {
        if (value) this.messages.set(key, value);
        else this.messages.delete(key);
        this.root.querySelector('[data-map-message]').textContent = [...this.messages.values()].join(' ');
    }

    async start() {
        this.refreshNodes();
        const geography = loadGeography(this.config.geojsonUrl);
        // Handle geography errors immediately, even while the GPU initializes.
        geography.catch(error => {
            if (!this.disposed) this.message('geography', `Continents unavailable: ${error.message}`);
        });
        this.renderer = new WebGPURenderer({ canvas: this.canvas, alpha: true, antialias: true });
        await this.renderer.init();
        if (this.disposed) {
            this.renderer.dispose();
            return;
        }
        this.rendererReady = true;
        this.renderer.onDeviceLost = info => {
            this.renderStopped = true;
            this.message('rendering', `Graphics device lost: ${info.message || info.reason}. Reload the page to retry.`);
            this.root.dataset.gnmState = 'error';
        };
        const backend = this.renderer.backend.isWebGPUBackend ? 'WebGPU' : 'WebGL2 fallback';
        this.root.dataset.gnmRenderer = backend;
        this.root.querySelector('[data-map-renderer]').textContent = backend;
        this.mobileQuality = this.viewport.clientWidth < 640;
        this.renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, this.mobileQuality ? 1.5 : 2));
        this.scene.add(new AmbientLight(0x91b6d2, 1.15));
        this.sun = new DirectionalLight(0xffefd7, 3.1);
        this.scene.add(this.sun);
        const palette = PALETTES[this.config.theme] || PALETTES.ocean;
        this.earthMaterial = new MeshStandardMaterial({ color: palette.ocean, roughness: 0.7, metalness: 0.12 });
        this.scene.add(new Mesh(new SphereGeometry(1, 96, 64), this.earthMaterial));
        const atmosphereMaterial = new MeshBasicNodeMaterial({ transparent: true, side: BackSide, depthWrite: false });
        atmosphereMaterial.colorNode = color(palette.glow);
        atmosphereMaterial.opacityNode = normalWorld.dot(cameraPosition.sub(positionWorld).normalize()).abs().oneMinus().pow(3).mul(0.65);
        const atmosphere = new Mesh(new SphereGeometry(1.025, 64, 48), atmosphereMaterial);
        this.scene.add(atmosphere);
        this.addGraticule();
        this.resizeObserver = new ResizeObserver(() => this.resize());
        this.resizeObserver.observe(this.viewport);
        this.intersectionObserver = new IntersectionObserver(entries => {
            this.visible = entries[0].isIntersecting;
            this.lastFrame = 0;
            this.requestFrame();
        }, { rootMargin: '150px' });
        this.intersectionObserver.observe(this.root);
        this.resize();
        try {
            const polygons = await geography;
            if (this.disposed) return;
            const textureCanvas = document.createElement('canvas');
            textureCanvas.width = this.mobileQuality ? 2048 : 4096;
            textureCanvas.height = textureCanvas.width / 2;
            const context = textureCanvas.getContext('2d');
            if (!context) throw new Error('Cannot create the continent texture.');
            drawContinents(context, polygons, textureCanvas.width, textureCanvas.height, palette);
            this.texture = new CanvasTexture(textureCanvas);
            this.texture.colorSpace = SRGBColorSpace;
            this.texture.anisotropy = 4;
            this.earthMaterial.color.set(0xffffff);
            this.earthMaterial.map = this.texture;
            const mask = document.createElement('canvas');
            mask.width = this.mobileQuality ? 1024 : 2048;
            mask.height = mask.width / 2;
            const maskContext = mask.getContext('2d');
            if (!maskContext) throw new Error('Cannot create the surface material mask.');
            drawContinents(maskContext, polygons, mask.width, mask.height, { ocean: '#666666', land: '#ededed', coast: '#ededed' });
            this.roughnessTexture = new CanvasTexture(mask);
            this.earthMaterial.roughnessMap = this.roughnessTexture;
            this.earthMaterial.roughness = 1;
            this.earthMaterial.needsUpdate = true;
            this.root.dataset.gnmFeatures = String(polygons.length);
            this.message('geography', '');
            this.root.dataset.gnmState = 'ready';
            this.requestFrame();
        } catch (error) {
            console.error('ThemisDB continent data:', error);
            this.message('geography', `Continents unavailable: ${error.message}`);
            this.root.dataset.gnmState = 'error';
        }
    }

    addGraticule() {
        const points = [];
        const segment = (lon1, lat1, lon2, lat2) => {
            points.push(...latLonToPosition(lon1, lat1, 1.002), ...latLonToPosition(lon2, lat2, 1.002));
        };
        for (let lat = -60; lat <= 60; lat += 30) {
            for (let lon = -180; lon < 180; lon += 3) segment(lon, lat, lon + 3, lat);
        }
        for (let lon = -180; lon < 180; lon += 30) {
            for (let lat = -90; lat < 90; lat += 3) segment(lon, lat, lon, lat + 3);
        }
        const geometry = new BufferGeometry();
        geometry.setAttribute('position', new Float32BufferAttribute(points, 3));
        this.scene.add(new LineSegments(geometry, new LineBasicMaterial({
            color: 0x8bcbd9, transparent: true, opacity: 0.12, depthWrite: false,
        })));
    }

    updateCityLabels() {
        this.cityLayer.hidden = !this.settings.cities;
        if (!this.settings.cities) return;
        const width = this.viewport.clientWidth;
        const height = this.viewport.clientHeight;
        const rootRect = this.viewport.getBoundingClientRect();
        const obstacles = [...this.root.querySelectorAll('.themisdb-global-network-map__status, .themisdb-global-network-map__menu, .themisdb-global-network-map__legend, .themisdb-global-network-map__title, [data-map-focus-card]:not([hidden])')].map(element => {
            const rect = element.getBoundingClientRect();
            return { left: rect.left - rootRect.left, right: rect.right - rootRect.left, top: rect.top - rootRect.top, bottom: rect.bottom - rootRect.top };
        });
        const candidates = [];
        for (const city of this.cityLabels) {
            if (city.position.dot(this.camera.position) <= 1.003) continue;
            const projected = this.projectedCity.copy(city.position).project(this.camera);
            if (projected.z < -1 || projected.z > 1 || Math.abs(projected.x) > 1 || Math.abs(projected.y) > 1) continue;
            city.width ??= city.element.offsetWidth;
            candidates.push({
                ...city,
                x: (projected.x + 1) * width / 2 + 10,
                y: (1 - projected.y) * height / 2,
                distance: projected.x * projected.x + projected.y * projected.y,
            });
        }
        const labelMode = this.root.dataset.gnmFlightPhase === 'travel' ? 'globe' : this.mode;
        const selected = selectCityLabels(candidates, labelMode, width, height, obstacles);
        const visible = new Set(selected.map(city => city.name));
        for (const city of this.cityLabels) {
            city.previouslyVisible = visible.has(city.name);
            city.element.style.visibility = city.previouslyVisible ? 'visible' : 'hidden';
        }
        for (const city of selected) city.element.style.transform = `translate(${city.x.toFixed(1)}px, ${(city.y - 10).toFixed(1)}px)`;
        this.root.dataset.gnmCityLabels = String(selected.length);
    }

    async refreshNodes() {
        window.clearTimeout(this.refreshTimer);
        try {
            const payload = validateNodes(await fetchJson(this.config.nodesUrl));
            if (this.disposed) return;
            this.nodes = payload.nodes;
            this.dataSource = payload.source;
            this.disposeMarkers();
            this.hideTooltip();
            const markerGeometry = new SphereGeometry(1, 10, 8);
            const colors = [0x64e6da, 0x82bfff, 0xffac78];
            const materials = colors.map(color => new MeshBasicMaterial({ color }));
            this.haloGeometry = new RingGeometry(0.65, 1, 32);
            this.haloMaterials = colors.map(color => new MeshBasicMaterial({ color, transparent: true, opacity: 0.26, depthWrite: false }));
            for (const node of this.nodes) {
                const material = materials[node.load >= 75 ? 2 : node.load >= 40 ? 1 : 0];
                const marker = new Mesh(markerGeometry, material);
                marker.position.fromArray(latLonToPosition(node.lon, node.lat, 1.012));
                marker.userData.node = node;
                marker.userData.pixelRadius = 3 + Math.min(Math.sqrt(node.instances), 8) * 0.7;
                this.markers.add(marker);
                const halo = new Mesh(this.haloGeometry, this.haloMaterials[node.load >= 75 ? 2 : node.load >= 40 ? 1 : 0]);
                halo.position.copy(marker.position).normalize().multiplyScalar(1.006);
                halo.lookAt(halo.position.clone().multiplyScalar(2));
                halo.userData.marker = marker;
                this.halos.add(halo);
            }
            this.markerGeometry = markerGeometry;
            this.markerMaterials = materials;
            const source = this.root.querySelector('[data-map-source]');
            source.dataset.source = payload.source;
            const instances = this.nodes.reduce((sum, node) => sum + node.instances, 0);
            source.textContent = payload.source === 'demo'
                ? `DEMO - ${instances} example instances, not live homecalls`
                : `${instances} reported instances at ${this.nodes.length} locations`;
            this.root.querySelector('[data-map-count]').textContent = `(${this.nodes.length})`;
            const list = this.root.querySelector('[data-map-locations]');
            list.replaceChildren();
            for (const node of this.nodes) {
                const item = document.createElement('li');
                const button = document.createElement('button');
                button.type = 'button';
                button.textContent = this.nodeLabel(node);
                button.addEventListener('click', () => {
                    this.focus(node.lon, node.lat, MODE_ZOOM[this.mode], node.city || node.country);
                    this.closeMenu();
                });
                item.append(button);
                list.append(item);
            }
            if (!this.nodes.length) {
                const item = document.createElement('li');
                item.textContent = 'No geolocated homecalls available.';
                list.append(item);
            }
            this.message('telemetry', '');
            if (this.focusNode) this.setFocusLocation(this.focusNode.lon, this.focusNode.lat);
            this.requestFrame();
        } catch (error) {
            if (this.disposed) return;
            console.error('ThemisDB network telemetry:', error);
            this.message('telemetry', `Telemetry unavailable: ${error.message}`);
            this.root.querySelector('[data-map-source]').textContent = this.nodes.length
                ? 'Last received data - refresh failed'
                : 'No telemetry available';
        } finally {
            if (!this.disposed) {
                this.refreshTimer = window.setTimeout(() => this.refreshNodes(), this.config.refreshSeconds * 1000);
            }
        }
    }

    nodeLabel(node) {
        return `${node.city || node.region || 'Unknown city'}, ${node.country || 'Unknown country'} - ${node.instances} instances - ${node.load}% load - ${node.status}`;
    }

    disposeMarkers() {
        this.markers.clear();
        this.halos.clear();
        this.haloGeometry?.dispose();
        this.haloMaterials?.forEach(material => material.dispose());
        this.markerGeometry?.dispose();
        this.markerMaterials?.forEach(material => material.dispose());
    }

    focus(lon, lat, zoom = MODE_ZOOM[this.mode], label = '') {
        this.focused = true;
        this.root.querySelector('[data-map-hotspot]').textContent = label ? `Focus: ${label}` : '';
        if (reducedMotion.matches) {
            this.flight = null;
            this.target = { lon, lat: clamp(lat, -85, 85), zoom: clamp(zoom, 1, 4) };
            this.setFocusLocation(lon, lat);
            this.requestFrame();
        } else this.beginFlight(lon, lat, zoom, label);
    }

    syncZoom() {
        const input = this.root.querySelector('[data-map-zoom]');
        if (input) input.value = String(this.settings.zoom);
    }

    closeMenu() {
        const menu = this.root.querySelector('.themisdb-global-network-map__menu');
        if (menu.open && menu.contains(document.activeElement)) menu.querySelector('summary').focus();
        menu.open = false;
    }

    bindControls() {
        const listen = (element, type, callback, options = {}) => {
            element?.addEventListener(type, callback, { ...options, signal: this.events.signal });
        };
        const animationToggle = this.root.querySelector('[data-map-animation-toggle]');
        if (animationToggle) animationToggle.checked = this.autoRotate;
        const menu = this.root.querySelector('.themisdb-global-network-map__menu');
        listen(document, 'pointerdown', event => {
            if (menu.open && !menu.contains(event.target)) this.closeMenu();
        });
        listen(menu, 'keydown', event => {
            if (event.key === 'Escape') {
                event.preventDefault();
                this.closeMenu();
            }
        });
        this.root.querySelectorAll('[data-map-mode]').forEach(button => {
            button.setAttribute('aria-pressed', String(button.dataset.mapMode === this.mode));
            listen(button, 'click', () => {
                this.mode = button.dataset.mapMode;
                this.settings.mode = this.mode;
                this.settings.zoom = MODE_ZOOM[this.mode];
                if (this.flight) {
                    this.flight = createFlight(this.view, this.flight.to, MODE_ZOOM[this.mode]);
                    this.target = { ...this.flight.to };
                } else this.target.zoom = MODE_ZOOM[this.mode];
                this.root.querySelectorAll('[data-map-mode]').forEach(other => other.setAttribute('aria-pressed', String(other === button)));
                this.syncZoom();
                this.saveSettings();
                this.requestFrame();
            });
        });
        const targetSelect = this.root.querySelector('[data-map-target]');
        if (targetSelect) targetSelect.value = this.settings.target;
        listen(targetSelect, 'change', event => {
            this.settings.target = event.target.value;
            const target = TARGETS[event.target.value];
            if (target) this.focus(...target, MODE_ZOOM[this.mode], event.target.selectedOptions[0].textContent);
            else {
                this.manualView();
                this.focused = false;
                this.tourIndex = -1;
                this.requestFrame();
            }
            this.saveSettings();
        });
        this.syncZoom();
        listen(this.root.querySelector('[data-map-zoom]'), 'input', event => {
            this.manualView();
            this.target.zoom = Number(event.target.value);
            this.settings.zoom = this.target.zoom;
            this.saveSettings();
            this.requestFrame();
        });
        const speedInput = this.root.querySelector('[data-map-speed]');
        if (speedInput) speedInput.value = String(this.speed);
        listen(speedInput, 'input', event => {
            this.speed = Number(event.target.value);
            this.settings.speed = this.speed;
            this.saveSettings();
        });
        listen(animationToggle, 'change', event => {
            this.autoRotate = event.target.checked;
            this.settings.animation = this.autoRotate;
            if (this.autoRotate) {
                this.focused = false;
                this.settings.target = 'auto';
                this.tourIndex = -1;
                const target = this.root.querySelector('[data-map-target]');
                if (target) target.value = 'auto';
            } else this.manualView();
            this.saveSettings();
            this.requestFrame();
        });
        const mouseToggle = this.root.querySelector('[data-map-mouse-toggle]');
        if (mouseToggle) mouseToggle.checked = this.mouseEnabled;
        listen(mouseToggle, 'change', event => {
            this.mouseEnabled = event.target.checked;
            this.settings.drag = this.mouseEnabled;
            this.canvas.dataset.draggable = String(this.mouseEnabled);
            this.endDrag();
            this.saveSettings();
        });
        const citiesToggle = this.root.querySelector('[data-map-cities-toggle]');
        if (citiesToggle) citiesToggle.checked = this.settings.cities;
        listen(citiesToggle, 'change', event => {
            this.settings.cities = event.target.checked;
            this.saveSettings();
            this.requestFrame();
        });
        listen(menu, 'toggle', () => this.requestFrame());
        listen(this.root.querySelector('[data-map-reset]'), 'click', () => {
            this.settings = { ...DEFAULT_SETTINGS };
            this.mode = this.settings.mode;
            this.autoRotate = !reducedMotion.matches;
            this.mouseEnabled = true;
            this.speed = 1;
            this.flight = null;
            this.focusNode = null;
            this.root.querySelector('[data-map-focus-card]').hidden = true;
            this.tourIndex = -1;
            this.target = { lon: 12, lat: 25, zoom: 1 };
            this.focused = false;
            const target = this.root.querySelector('[data-map-target]');
            if (target) target.value = 'auto';
            if (animationToggle) animationToggle.checked = this.autoRotate;
            if (mouseToggle) mouseToggle.checked = true;
            if (citiesToggle) citiesToggle.checked = true;
            if (speedInput) speedInput.value = '1';
            this.canvas.dataset.draggable = 'true';
            this.root.querySelectorAll('[data-map-mode]').forEach(button => button.setAttribute('aria-pressed', String(button.dataset.mapMode === 'globe')));
            this.syncZoom();
            this.saveSettings();
            this.requestFrame();
        });
        this.canvas.dataset.draggable = String(this.mouseEnabled);
        listen(this.canvas, 'pointerdown', event => {
            if (!this.mouseEnabled || !event.isPrimary || event.button !== 0) return;
            this.manualView();
            this.drag = { id: event.pointerId, x: event.clientX, y: event.clientY, lon: this.target.lon, lat: this.target.lat, moved: false };
            this.canvas.setPointerCapture(event.pointerId);
            this.focused = true;
        });
        listen(this.canvas, 'pointermove', event => {
            if (this.drag && this.drag.id === event.pointerId) {
                const dx = event.clientX - this.drag.x;
                const dy = event.clientY - this.drag.y;
                this.drag.moved ||= Math.hypot(dx, dy) > 4;
                this.target.lon = this.drag.lon - dx / this.viewport.clientHeight * 150 / this.target.zoom;
                this.target.lat = clamp(this.drag.lat + dy / this.viewport.clientHeight * 150 / this.target.zoom, -85, 85);
                this.hideTooltip();
                this.requestFrame();
            } else this.showTooltip(event);
        });
        listen(this.canvas, 'pointerup', event => {
            if (this.drag && !this.drag.moved) this.showTooltip(event);
            this.endDrag();
        });
        listen(this.canvas, 'pointercancel', () => this.endDrag());
        listen(this.canvas, 'lostpointercapture', () => { this.drag = null; });
        listen(this.canvas, 'pointerleave', () => this.hideTooltip());
        listen(this.canvas, 'wheel', event => {
            // Do not trap normal page scrolling, especially in the front-page layout.
            if (!this.mouseEnabled || (!event.ctrlKey && !event.metaKey)) return;
            event.preventDefault();
            this.manualView();
            this.target.zoom = clamp(this.target.zoom * Math.exp(-event.deltaY * 0.002), 1, 4);
            this.settings.zoom = this.target.zoom;
            this.saveSettings();
            this.syncZoom();
            this.requestFrame();
        }, { passive: false });
        listen(this.canvas, 'keydown', event => {
            const actions = {
                ArrowLeft: () => { this.target.lon -= 8; },
                ArrowRight: () => { this.target.lon += 8; },
                ArrowUp: () => { this.target.lat = clamp(this.target.lat + 8, -85, 85); },
                ArrowDown: () => { this.target.lat = clamp(this.target.lat - 8, -85, 85); },
                '+': () => { this.target.zoom = clamp(this.target.zoom + 0.15, 1, 4); },
                '=': () => { this.target.zoom = clamp(this.target.zoom + 0.15, 1, 4); },
                '-': () => { this.target.zoom = clamp(this.target.zoom - 0.15, 1, 4); },
            };
            if (!actions[event.key]) return;
            event.preventDefault();
            this.manualView();
            actions[event.key]();
            this.focused = true;
            this.settings.zoom = this.target.zoom;
            this.saveSettings();
            this.syncZoom();
            this.requestFrame();
        });
        listen(document, 'visibilitychange', () => {
            this.lastFrame = 0;
            this.hideTooltip();
            this.requestFrame();
        });
        listen(reducedMotion, 'change', () => {
            if (reducedMotion.matches) {
                this.autoRotate = false;
                this.manualView();
                if (animationToggle) animationToggle.checked = false;
            }
            this.requestFrame();
        });
    }

    endDrag() {
        if (this.drag && this.canvas.hasPointerCapture(this.drag.id)) this.canvas.releasePointerCapture(this.drag.id);
        this.drag = null;
        this.requestFrame();
    }

    resize() {
        if (!this.renderer || this.disposed) return;
        const width = Math.max(1, this.viewport.clientWidth);
        const height = Math.max(1, this.viewport.clientHeight);
        this.renderer.setSize(width, height, false);
        this.camera.aspect = width / height;
        this.camera.updateProjectionMatrix();
        this.hideTooltip();
        this.requestFrame();
    }

    requestFrame() {
        if (this.frame !== null || !this.rendererReady || this.renderStopped || this.disposed || document.hidden || !this.visible) return;
        this.frame = requestAnimationFrame(now => this.render(now));
    }

    render(now) {
        this.frame = null;
        if (this.disposed || document.hidden || !this.visible) {
            this.lastFrame = 0;
            return;
        }
        if (!this.root.isConnected) {
            this.dispose();
            return;
        }
        const dt = this.lastFrame ? Math.max(0, (now - this.lastFrame) / 1000) : 1 / 60;
        this.lastFrame = now;
        this.updateTour(dt);
        const factor = reducedMotion.matches ? 1 : 1 - Math.exp(-dt * 9);
        const lonDifference = longitudeDelta(this.view.lon, this.target.lon);
        if (!this.flight) {
            this.view.lon += lonDifference * factor;
            this.view.lat += (this.target.lat - this.view.lat) * factor;
            this.view.zoom += (this.target.zoom - this.view.zoom) * factor;
        }
        const verticalFov = this.camera.fov * Math.PI / 180;
        const distance = coverDistance(verticalFov, this.camera.aspect, this.view.zoom);
        const orbit = this.autoRotate && !this.flight && !this.focused && !reducedMotion.matches
            ? Math.sin(this.holdTime * 0.35) * 0.7 : 0;
        this.camera.position.fromArray(latLonToPosition(this.view.lon + orbit, this.view.lat, distance));
        this.camera.lookAt(0, 0, 0);
        const bank = this.flight && !reducedMotion.matches
            ? Math.sin(Math.PI * clamp((this.flight.elapsed - 1.8) / 3.8, 0, 1)) * 0.025 : 0;
        this.camera.rotateZ(bank);
        this.camera.updateMatrixWorld();
        this.sun.position.copy(this.camera.localToWorld(new Vector3(-2.5, 1.5, 1))).normalize().multiplyScalar(5);
        const pixelToWorld = 2 * Math.tan(verticalFov / 2) / this.viewport.clientHeight;
        for (const marker of this.markers.children) {
            const depth = -this.projectedCity.copy(marker.position).applyMatrix4(this.camera.matrixWorldInverse).z;
            marker.scale.setScalar(marker.userData.pixelRadius * Math.max(0.01, depth) * pixelToWorld);
        }
        for (const halo of this.halos.children) {
            const marker = halo.userData.marker;
            const focused = marker.userData.node === this.focusNode;
            const breathing = this.autoRotate && !reducedMotion.matches && focused ? Math.sin(now / 900) * 0.15 : 0;
            halo.scale.setScalar(marker.scale.x * (focused ? 2.8 + breathing : 1.9));
        }
        const card = this.root.querySelector('[data-map-focus-card]');
        card.hidden = !this.focusNode || (this.flight && this.root.dataset.gnmFlightPhase !== 'arrive');
        try {
            this.renderer.render(this.scene, this.camera);
            this.updateCityLabels();
        } catch (error) {
            console.error('ThemisDB globe rendering:', error);
            this.message('rendering', `Rendering stopped: ${error.message}`);
            this.renderStopped = true;
            this.root.dataset.gnmState = 'error';
            return;
        }
        this.root.dataset.gnmLongitude = this.view.lon.toFixed(2);
        this.root.dataset.gnmZoom = this.view.zoom.toFixed(3);
        this.root.dataset.gnmMode = this.mode;
        const moving = Math.abs(lonDifference) > 0.01 || Math.abs(this.target.lat - this.view.lat) > 0.01 || Math.abs(this.target.zoom - this.view.zoom) > 0.001;
        if (moving || this.flight || (this.autoRotate && !this.focused)) this.requestFrame();
        else this.lastFrame = 0;
    }

    showTooltip(event) {
        if (!this.renderer || !this.nodes.length) return;
        const rect = this.canvas.getBoundingClientRect();
        this.pointer.set((event.clientX - rect.left) / rect.width * 2 - 1, -(event.clientY - rect.top) / rect.height * 2 + 1);
        this.raycaster.setFromCamera(this.pointer, this.camera);
        const hit = this.raycaster.intersectObjects(this.markers.children, false).find(intersection => {
            const point = intersection.object.position;
            // Reject the far hemisphere rather than showing markers through the Earth.
            return point.dot(this.camera.position) > 1;
        });
        if (!hit) {
            this.hideTooltip();
            return;
        }
        this.tooltip.textContent = this.nodeLabel(hit.object.userData.node);
        this.tooltip.hidden = false;
        const left = clamp(event.clientX - rect.left + 12, 8, Math.max(8, rect.width - this.tooltip.offsetWidth - 8));
        const top = clamp(event.clientY - rect.top + 12, 8, Math.max(8, rect.height - this.tooltip.offsetHeight - 8));
        this.tooltip.style.left = `${left}px`;
        this.tooltip.style.top = `${top}px`;
    }

    hideTooltip() {
        this.tooltip.hidden = true;
    }

    dispose() {
        this.disposed = true;
        this.events.abort();
        cancelAnimationFrame(this.frame);
        clearTimeout(this.refreshTimer);
        this.resizeObserver?.disconnect();
        this.intersectionObserver?.disconnect();
        this.disposeMarkers();
        this.scene.traverse(object => {
            object.geometry?.dispose();
            object.material?.dispose();
        });
        this.texture?.dispose();
        this.roughnessTexture?.dispose();
        this.renderer?.dispose();
    }
}

function initialize() {
    document.querySelectorAll('[data-gnm-config]').forEach(root => {
        if (root.dataset.gnmInitialized) return;
        root.dataset.gnmInitialized = 'true';
        try {
            const globe = new NetworkGlobe(root);
            window.addEventListener('pagehide', event => {
                if (!event.persisted) globe.dispose();
            }, { once: true });
        } catch (error) {
            console.error('ThemisDB globe configuration:', error);
            root.querySelector('[data-map-message]').textContent = `Map initialization failed: ${error.message}`;
            root.dataset.gnmState = 'error';
        }
    });
}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initialize, { once: true });
else initialize();
