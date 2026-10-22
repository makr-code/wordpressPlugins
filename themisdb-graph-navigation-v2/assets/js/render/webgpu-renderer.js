(function () {
    'use strict';

    window.ThemisDBGraphV2 = window.ThemisDBGraphV2 || {};

    function vec3(x, y, z) {
        return { x, y, z };
    }

    function add(a, b) {
        return vec3(a.x + b.x, a.y + b.y, a.z + b.z);
    }

    function subtract(a, b) {
        return vec3(a.x - b.x, a.y - b.y, a.z - b.z);
    }

    function multiply(a, scalar) {
        return vec3(a.x * scalar, a.y * scalar, a.z * scalar);
    }

    function cross(a, b) {
        return vec3(
            a.y * b.z - a.z * b.y,
            a.z * b.x - a.x * b.z,
            a.x * b.y - a.y * b.x
        );
    }

    function dot(a, b) {
        return a.x * b.x + a.y * b.y + a.z * b.z;
    }

    function length(a) {
        return Math.hypot(a.x, a.y, a.z) || 1;
    }

    function normalize(a) {
        const len = length(a) || 1;
        return vec3(a.x / len, a.y / len, a.z / len);
    }

    function perspectiveMatrix(fovDegrees, aspect, near, far) {
        const fov = (fovDegrees * Math.PI) / 180;
        const f = 1 / Math.tan(fov / 2);
        const nf = 1 / (near - far);
        return new Float32Array([
            f / aspect, 0, 0, 0,
            0, f, 0, 0,
            0, 0, (far + near) * nf, -1,
            0, 0, (2 * far * near) * nf, 0,
        ]);
    }

    function lookAtMatrix(eye, target, up) {
        const zAxis = normalize(subtract(eye, target));
        const xAxis = normalize(cross(up, zAxis));
        const yAxis = cross(zAxis, xAxis);

        return new Float32Array([
            xAxis.x, yAxis.x, zAxis.x, 0,
            xAxis.y, yAxis.y, zAxis.y, 0,
            xAxis.z, yAxis.z, zAxis.z, 0,
            -dot(xAxis, eye), -dot(yAxis, eye), -dot(zAxis, eye), 1,
        ]);
    }

    function multiplyMat4(a, b) {
        const result = new Float32Array(16);
        for (let row = 0; row < 4; row += 1) {
            for (let col = 0; col < 4; col += 1) {
                let value = 0;
                for (let idx = 0; idx < 4; idx += 1) {
                    value += a[row + idx * 4] * b[idx + col * 4];
                }
                result[row + col * 4] = value;
            }
        }
        return result;
    }

    class WebGPURenderer {
        constructor(canvas, graphState, options) {
            this.canvas = canvas;
            this.graphState = graphState || window.ThemisDBGraphV2.state;
            this.options = Object.assign({
                background: [0.08, 0.12, 0.18, 1.0],
            }, options || {});
            this.device = null;
            this.context = null;
            this.pipeline = null;
            this.bindGroup = null;
            this.uniformBuffer = null;
            this.ready = false;
        }

        async init() {
            const gpu = window.navigator && window.navigator.gpu;
            if (!gpu || typeof gpu.requestAdapter !== 'function') {
                return false;
            }

            try {
                const adapter = await gpu.requestAdapter();
                if (!adapter) {
                    return false;
                }

                this.device = await adapter.requestDevice();
                this.context = this.canvas.getContext('webgpu');
                if (!this.context) {
                    return false;
                }

                const format = gpu.getPreferredCanvasFormat();
                this.context.configure({
                    device: this.device,
                    format,
                    alphaMode: 'premultiplied',
                });

                const bindGroupLayout = this.device.createBindGroupLayout({
                    entries: [{
                        binding: 0,
                        visibility: GPUShaderStage.VERTEX,
                        buffer: { type: 'uniform' },
                    }],
                });

                this.uniformBuffer = this.device.createBuffer({
                    size: 64,
                    usage: GPUBufferUsage.UNIFORM | GPUBufferUsage.COPY_DST,
                });

                this.bindGroup = this.device.createBindGroup({
                    layout: bindGroupLayout,
                    entries: [{
                        binding: 0,
                        resource: { buffer: this.uniformBuffer },
                    }],
                });

                const shader = this.device.createShaderModule({
                    code: `
                        struct Uniforms {
                            viewProj: mat4x4f,
                        };

                        @group(0) @binding(0) var<uniform> uniforms: Uniforms;

                        struct VertexInput {
                            @location(0) pos: vec3f,
                            @location(1) color: vec4f,
                        };

                        struct VertexOutput {
                            @builtin(position) position: vec4f,
                            @location(0) color: vec4f,
                        };

                        @vertex
                        fn vs_main(input: VertexInput) -> VertexOutput {
                            var output: VertexOutput;
                            output.position = uniforms.viewProj * vec4f(input.pos, 1.0);
                            output.color = input.color;
                            return output;
                        }

                        @fragment
                        fn fs_main(@location(0) color: vec4f) -> @location(0) vec4f {
                            return color;
                        }
                    `,
                });

                this.pipeline = this.device.createRenderPipeline({
                    layout: this.device.createPipelineLayout({
                        bindGroupLayouts: [bindGroupLayout],
                    }),
                    vertex: {
                        module: shader,
                        entryPoint: 'vs_main',
                        buffers: [{
                            arrayStride: 28,
                            attributes: [
                                { shaderLocation: 0, offset: 0, format: 'float32x3' },
                                { shaderLocation: 1, offset: 12, format: 'float32x4' },
                            ],
                        }],
                    },
                    fragment: {
                        module: shader,
                        entryPoint: 'fs_main',
                        targets: [{ format }],
                    },
                    primitive: {
                        topology: 'triangle-list',
                    },
                });

                this.ready = true;
                return true;
            } catch (error) {
                console.warn('WebGPU initialization failed:', error);
                return false;
            }
        }

        render(graph, state) {
            if (!this.ready || !this.device || !this.context || !this.pipeline || !this.bindGroup || !this.uniformBuffer) {
                return false;
            }

            const activeState = state || this.graphState || window.ThemisDBGraphV2.state;
            const visibleNodes = Array.isArray(graph && graph.nodes) ? graph.nodes : [];
            const visibleLinks = Array.isArray(graph && graph.links) ? graph.links : [];
            if (!visibleNodes.length && !visibleLinks.length) {
                return false;
            }

            const selectedNodeId = activeState && activeState.selection ? activeState.selection.nodeId : null;
            const hoverNodeId = activeState && activeState.selection ? activeState.selection.hoverNodeId : null;
            const width = Math.max(1, this.canvas.width || 1);
            const height = Math.max(1, this.canvas.height || 1);
            const aspect = width / height;
            const baseNode = visibleNodes.find((node) => String(node.type || '').toLowerCase() === 'home')
                || visibleNodes.find((node) => String(node.id || '') === String(selectedNodeId || ''))
                || visibleNodes[0];

            const baseX = Number(baseNode && Number.isFinite(Number(baseNode.x)) ? Number(baseNode.x) : 0);
            const baseY = Number(baseNode && Number.isFinite(Number(baseNode.y)) ? Number(baseNode.y) : 0);
            const baseZ = 0;

            const worldNodes = visibleNodes.map((node, index) => {
                const nx = Number(node.x || 0) - baseX;
                const ny = Number(node.y || 0) - baseY;
                const nz = (Number(node.level || 0) * 0.8) + (Number(node.degree || 0) * 0.12) + ((index % 6) * 0.25) - 0.6;
                return Object.assign({}, node, {
                    world: vec3((nx / Math.max(1, width)) * 8, (ny / Math.max(1, height)) * 6, nz),
                });
            });

            const worldNodeMap = new Map(worldNodes.map((node) => [String(node.id), node]));
            const cameraEye = vec3(0, 0.5, 7.25);
            const cameraTarget = vec3(0, 0, 0);
            const cameraUp = vec3(0, 1, 0);
            const viewMatrix = lookAtMatrix(cameraEye, cameraTarget, cameraUp);
            const projectionMatrix = perspectiveMatrix(42, aspect, 0.1, 100);
            const viewProj = multiplyMat4(projectionMatrix, viewMatrix);
            this.device.queue.writeBuffer(this.uniformBuffer, 0, viewProj);

            const vertexData = [];
            const pushVertex = (position, color) => {
                vertexData.push(position.x, position.y, position.z, color[0], color[1], color[2], color[3]);
            };

            const pushQuad = (a, b, c, d, color) => {
                pushVertex(a, color);
                pushVertex(b, color);
                pushVertex(c, color);
                pushVertex(a, color);
                pushVertex(c, color);
                pushVertex(d, color);
            };

            const edgeColorForType = (type) => {
                const value = String(type || 'related').toLowerCase();
                if (value.includes('taxonomy')) return this.hexToRgba('#f472b6');
                if (value.includes('category')) return this.hexToRgba('#2dd4bf');
                if (value.includes('tag')) return this.hexToRgba('#fb923c');
                if (value.includes('content')) return this.hexToRgba('#60a5fa');
                return this.hexToRgba('#94a3b8');
            };

            visibleLinks.forEach((link) => {
                const source = worldNodeMap.get(String(link.source));
                const target = worldNodeMap.get(String(link.target));
                if (!source || !target) {
                    return;
                }

                const direction = normalize(subtract(target.world, source.world));
                const side = normalize(cross(direction, vec3(0, 1, 0)));
                const thickness = Math.max(0.03, Number(link.weight || 1) * 0.05);
                const offset = multiply(side, thickness);
                const a = add(source.world, offset);
                const b = subtract(source.world, offset);
                const c = add(target.world, offset);
                const d = subtract(target.world, offset);
                const color = edgeColorForType(link.type);
                pushQuad(a, b, c, d, color);
            });

            visibleNodes.forEach((node) => {
                const world = worldNodeMap.get(String(node.id)) ? (worldNodeMap.get(String(node.id)).world) : vec3(0, 0, 0);
                const size = Math.max(0.18, Number(node.size || 14) * 0.02);
                const isSelected = String(selectedNodeId || '') === String(node.id || '');
                const isHovered = String(hoverNodeId || '') === String(node.id || '');
                const color = isSelected
                    ? this.hexToRgba('#f8fafc')
                    : (isHovered ? this.hexToRgba('#7dd3fc') : this.hexToRgba(node.color || '#7c4dff'));

                const forward = normalize(subtract(cameraEye, world));
                const right = normalize(cross(forward, cameraUp));
                const up = normalize(cross(right, forward));
                const half = size * 0.65;
                const p1 = add(world, add(multiply(right, -half), multiply(up, -half)));
                const p2 = add(world, add(multiply(right, half), multiply(up, -half)));
                const p3 = add(world, add(multiply(right, half), multiply(up, half)));
                const p4 = add(world, add(multiply(right, -half), multiply(up, half)));
                pushQuad(p1, p2, p3, p4, color);
            });

            if (!vertexData.length) {
                return false;
            }

            const vertexBuffer = this.device.createBuffer({
                size: Float32Array.BYTES_PER_ELEMENT * vertexData.length,
                usage: GPUBufferUsage.VERTEX | GPUBufferUsage.COPY_DST,
            });

            this.device.queue.writeBuffer(vertexBuffer, 0, new Float32Array(vertexData));

            const encoder = this.device.createCommandEncoder();
            const pass = encoder.beginRenderPass({
                colorAttachments: [{
                    view: this.context.getCurrentTexture().createView(),
                    loadOp: 'clear',
                    clearValue: { r: this.options.background[0], g: this.options.background[1], b: this.options.background[2], a: this.options.background[3] },
                    storeOp: 'store',
                }],
            });

            pass.setPipeline(this.pipeline);
            pass.setBindGroup(0, this.bindGroup);
            pass.setVertexBuffer(0, vertexBuffer);
            pass.draw(vertexData.length / 7, 1, 0, 0);
            pass.end();

            this.device.queue.submit([encoder.finish()]);
            return true;
        }

        hexToRgba(hex) {
            const clean = String(hex || '#ffffff').replace('#', '');
            const value = clean.length === 3
                ? clean.split('').map((letter) => letter + letter).join('')
                : clean;

            const numeric = parseInt(value, 16);
            const r = ((numeric >> 16) & 255) / 255;
            const g = ((numeric >> 8) & 255) / 255;
            const b = (numeric & 255) / 255;
            return [r, g, b, 1.0];
        }
    }

    window.ThemisDBGraphV2.WebGPURenderer = WebGPURenderer;
})();
