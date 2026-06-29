/**
 * detector.js — the detection loop. Its sole job is transport + scheduling:
 * grab a frame from the camera, POST it to the detect endpoint, and hand the
 * server's result back via callbacks. It makes NO match/verification decisions
 * (that is the server's job) and does NO drawing.
 */
function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

export class DetectionLoop {
    /**
     * @param {import('./camera.js').CameraController} camera
     * @param {string} detectUrl
     * @param {{ intervalMs?: number, onResult?: Function, onError?: Function }} [options]
     */
    constructor(camera, detectUrl, options = {}) {
        this.camera = camera;
        this.detectUrl = detectUrl;
        this.intervalMs = options.intervalMs ?? 1000;
        this.onResult = options.onResult ?? null;
        this.onError = options.onError ?? null;
        this.running = false;
        this.busy = false;
        this.timer = null;
    }

    start() {
        if (this.running) {
            return;
        }
        this.running = true;
        this.tick();
    }

    stop() {
        this.running = false;
        if (this.timer) {
            clearTimeout(this.timer);
            this.timer = null;
        }
    }

    async tick() {
        if (!this.running) {
            return;
        }

        if (!this.busy) {
            this.busy = true;
            try {
                await this.detectOnce();
            } catch (error) {
                this.onError?.(error);
            } finally {
                this.busy = false;
            }
        }

        if (this.running) {
            this.timer = setTimeout(() => this.tick(), this.intervalMs);
        }
    }

    async detectOnce() {
        const blob = await this.camera.captureFrame();
        if (!blob) {
            return;
        }

        const body = new FormData();
        body.append('image', blob, 'frame.jpg');

        const response = await fetch(this.detectUrl, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken(), Accept: 'application/json' },
            body,
        });

        if (!response.ok) {
            let message = `Detection failed (HTTP ${response.status})`;
            try {
                const body = await response.json();
                if (body?.message) message = body.message;
            } catch {
                // non-JSON error body; keep the generic message
            }
            this.onError?.(new Error(message), response.status);
            return;
        }

        this.onResult?.(await response.json());
    }
}
