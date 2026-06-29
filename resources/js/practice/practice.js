/**
 * Practice screen entry point (orchestration only).
 *
 * Wires together the camera, the detection loop, and the overlay, and renders
 * status to the DOM. It holds no AI logic — match decisions come from the
 * server's DetectionResult; hold/verification arrive in Phase 3.
 */
import { CameraController } from './camera.js';
import { DetectionLoop } from './detector.js';
import { drawPredictions, clearOverlay } from './overlay.js';

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

function boot() {
    const root = document.getElementById('practice-root');
    if (!root) {
        return;
    }

    const video = document.getElementById('practice-video');
    const overlay = document.getElementById('practice-overlay');
    const statusEl = document.getElementById('practice-status');
    const startBtn = document.getElementById('practice-start');
    const stopBtn = document.getElementById('practice-stop');
    const holdBar = document.getElementById('practice-hold-bar');
    const holdLabel = document.getElementById('practice-hold-label');

    const startUrl = root.dataset.startUrl;
    const detectTemplate = root.dataset.detectTemplate; // contains __SESSION__
    const target = root.dataset.target ?? 'the prescribed mudra';
    const intervalMs = parseInt(root.dataset.detectionIntervalMs ?? '1000', 10);
    const jpegQuality = parseFloat(root.dataset.jpegQuality ?? '0.7');

    const setStatus = (message) => {
        if (statusEl) statusEl.textContent = message;
    };

    const setHold = (heldSeconds, holdSeconds) => {
        if (!holdBar || !holdSeconds) return;
        const pct = Math.max(0, Math.min(100, (heldSeconds / holdSeconds) * 100));
        holdBar.style.width = `${pct.toFixed(0)}%`;
        if (holdLabel) holdLabel.textContent = `${pct.toFixed(0)}%`;
    };

    const toggleButtons = (running) => {
        startBtn?.classList.toggle('hidden', running);
        stopBtn?.classList.toggle('hidden', !running);
    };

    let starting = false;
    let loop = null;

    const teardown = () => {
        loop?.stop();
        loop = null;
        camera.stop();
        clearOverlay(overlay);
        toggleButtons(false);
    };

    const camera = new CameraController(video, {
        jpegQuality,
        onDisconnect: () => {
            teardown();
            setStatus('Camera disconnected. Press Start to try again.');
        },
    });

    async function createSession() {
        const response = await fetch(startUrl, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken(), Accept: 'application/json' },
        });
        if (!response.ok) {
            throw new Error('Could not start the practice session.');
        }
        return response.json();
    }

    function handleResult(data) {
        if (data.error) {
            setStatus(data.message ?? 'Detection is temporarily unavailable.');
            return;
        }

        drawPredictions(overlay, video, data.predictions ?? []);
        setHold(data.held_seconds ?? 0, data.hold_seconds ?? 0);

        // The backend is the single source of truth for completion.
        if (data.verified) {
            setHold(1, 1);
            setStatus('✓ Verified! Your session has been recorded.');
            loop?.stop();
            loop = null;
            camera.stop();
            toggleButtons(false);
            return;
        }

        if (data.matched) {
            setStatus(`Holding ${target} — ${(data.confidence * 100).toFixed(0)}% ✓`);
        } else if (data.detected_class) {
            setStatus(`Detected ${data.detected_class}. Show your ${target} mudra.`);
        } else {
            setStatus(`Searching… show your ${target} mudra.`);
        }
    }

    async function start() {
        if (starting || camera.isRunning) {
            return; // guard against multiple Start clicks
        }
        starting = true;
        startBtn?.setAttribute('disabled', 'disabled');
        setStatus('Requesting camera…');

        try {
            await camera.start();
        } catch (error) {
            setStatus('Camera unavailable. Please allow camera access and try again.');
            starting = false;
            startBtn?.removeAttribute('disabled');
            return;
        }

        try {
            const session = await createSession();
            toggleButtons(true);

            if (session.verified) {
                setStatus('You have already completed this today. Great work!');
            } else {
                const detectUrl = detectTemplate.replace('__SESSION__', session.session_id);
                loop = new DetectionLoop(camera, detectUrl, {
                    intervalMs,
                    onResult: handleResult,
                    onError: (error) => setStatus(error?.message ? `⚠ ${error.message}` : 'Reconnecting…'),
                });
                loop.start();
                setStatus(`Camera ready. Show your ${target} mudra.`);
            }
        } catch (error) {
            teardown();
            setStatus(error.message);
        } finally {
            starting = false;
            startBtn?.removeAttribute('disabled');
        }
    }

    startBtn?.addEventListener('click', start);
    stopBtn?.addEventListener('click', () => {
        teardown();
        setStatus('Camera stopped.');
    });

    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'hidden') {
            teardown();
        }
    });
    window.addEventListener('pagehide', teardown);
}

document.addEventListener('DOMContentLoaded', boot);
