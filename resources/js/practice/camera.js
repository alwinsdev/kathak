/**
 * CameraController — owns the webcam lifecycle and frame capture.
 *
 * Responsibility is strictly the camera/media: start, capture JPEG frames, and
 * release tracks. It contains NO detection or AI logic. It is defensive about
 * the lifecycle: double-start is ignored, device disconnect is surfaced via a
 * callback, and stop() is idempotent so tracks are always released exactly once.
 */
export class CameraController {
    /**
     * @param {HTMLVideoElement} video
     * @param {{ jpegQuality?: number, onDisconnect?: () => void }} [options]
     */
    constructor(video, options = {}) {
        this.video = video;
        this.jpegQuality = options.jpegQuality ?? 0.7;
        this.onDisconnect = options.onDisconnect ?? null;
        this.stream = null;
        this.starting = false;
        this.canvas = document.createElement('canvas');
    }

    get isRunning() {
        return this.stream !== null;
    }

    /**
     * Request the camera and begin streaming. No-op if already running or
     * mid-start (guards against multiple Start clicks). Throws on
     * permission denial / no device so the caller can show a message.
     */
    async start() {
        if (this.isRunning || this.starting) {
            return;
        }

        this.starting = true;
        try {
            this.stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: 'user' },
                audio: false,
            });

            // Surface a hardware/permission disconnect (e.g. unplugged webcam).
            this.stream.getTracks().forEach((track) => {
                track.addEventListener('ended', () => this.handleDisconnect());
            });

            this.video.srcObject = this.stream;
            await this.video.play();

            await new Promise((resolve) => {
                if (this.video.readyState >= 1) {
                    resolve();
                } else {
                    this.video.addEventListener('loadedmetadata', resolve, { once: true });
                }
            });

            this.canvas.width = this.video.videoWidth;
            this.canvas.height = this.video.videoHeight;
        } catch (error) {
            this.stop(); // ensure no partial stream lingers
            throw error;
        } finally {
            this.starting = false;
        }
    }

    /**
     * Capture the current frame as a JPEG Blob (or null if not running).
     * @returns {Promise<Blob|null>}
     */
    async captureFrame() {
        if (!this.isRunning || this.canvas.width === 0) {
            return null;
        }

        const ctx = this.canvas.getContext('2d');
        ctx.drawImage(this.video, 0, 0, this.canvas.width, this.canvas.height);

        return new Promise((resolve) => {
            this.canvas.toBlob((blob) => resolve(blob), 'image/jpeg', this.jpegQuality);
        });
    }

    /** Stop all tracks and release the camera. Safe to call repeatedly. */
    stop() {
        if (this.stream) {
            this.stream.getTracks().forEach((track) => track.stop());
            this.stream = null;
        }
        if (this.video) {
            this.video.srcObject = null;
        }
    }

    handleDisconnect() {
        this.stop();
        if (this.onDisconnect) {
            this.onDisconnect();
        }
    }
}
