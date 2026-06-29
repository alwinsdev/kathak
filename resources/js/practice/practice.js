/**
 * Practice screen entry point (orchestration only).
 *
 * Wires the camera, detection loop and overlay, and renders status to the DOM.
 * It holds NO AI logic: match/hold/verification all come from the server's
 * DetectionResult. The browser only displays what the backend returns.
 */
import { CameraController } from './camera.js';
import { DetectionLoop } from './detector.js';
import { drawPredictions, clearOverlay } from './overlay.js';

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

const DOT = { matched: '#22c55e', seen: '#f59e0b', none: '#d1d5db', verified: '#0d9488' };

function boot() {
    const root = document.getElementById('practice-root');
    if (!root) return;

    const el = (id) => document.getElementById(id);
    const video = el('practice-video');
    const overlay = el('practice-overlay');
    const startBtn = el('practice-start');
    const stopBtn = el('practice-stop');
    const pill = el('practice-camera-pill');
    const resolution = el('practice-resolution');
    const dot = el('practice-status-dot');
    const detected = el('practice-detected');
    const confidence = el('practice-confidence');
    const holdBar = el('practice-hold-bar');
    const holdLabel = el('practice-hold-label');
    const message = el('practice-message');
    const sessionStarted = el('practice-session-started');
    const sessionId = el('practice-session-id');

    const startUrl = root.dataset.startUrl;
    const detectTemplate = root.dataset.detectTemplate;
    const target = root.dataset.target ?? 'the prescribed mudra';
    const intervalMs = parseInt(root.dataset.detectionIntervalMs ?? '1000', 10);
    const jpegQuality = parseFloat(root.dataset.jpegQuality ?? '0.7');

    const setMessage = (text) => { if (message) message.textContent = text; };
    const setDot = (color) => { if (dot) dot.style.backgroundColor = color; };
    const setDetected = (text, conf) => {
        if (detected) detected.textContent = text;
        if (confidence) confidence.textContent = conf == null ? '' : `${(conf * 100).toFixed(0)}%`;
    };
    const setHold = (held, hold) => {
        const pct = hold ? Math.max(0, Math.min(100, (held / hold) * 100)) : 0;
        if (holdBar) holdBar.style.width = `${pct.toFixed(0)}%`;
        if (holdLabel) holdLabel.textContent = `${(held ?? 0).toFixed(1)}s / ${hold ?? 0}s`;
    };
    const toggleButtons = (running) => {
        startBtn?.classList.toggle('hidden', running);
        stopBtn?.classList.toggle('hidden', !running);
        pill?.classList.toggle('hidden', !running);
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
            setDot(DOT.none);
            setMessage('Camera disconnected. Press Start to try again.');
        },
    });

    async function createSession() {
        const response = await fetch(startUrl, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken(), Accept: 'application/json' },
        });
        if (!response.ok) throw new Error('Could not start the practice session.');
        return response.json();
    }

    function handleResult(data) {
        if (data.error) {
            setDot(DOT.none);
            setMessage(`⚠ ${data.message ?? 'Detection is temporarily unavailable.'}`);
            return;
        }

        drawPredictions(overlay, video, data.predictions ?? []);
        setHold(data.held_seconds ?? 0, data.hold_seconds ?? 0);

        if (data.verified) {
            setDot(DOT.verified);
            setDetected('Verified', data.confidence);
            setHold(data.hold_seconds, data.hold_seconds);
            setMessage('✓ Verified! Your session has been recorded.');
            loop?.stop();
            loop = null;
            camera.stop();
            toggleButtons(false);
            return;
        }

        const pct = data.hold_seconds ? ((data.held_seconds ?? 0) / data.hold_seconds) * 100 : 0;

        if (data.matched) {
            setDot(DOT.matched);
            setDetected(data.detected_class, data.top_confidence);
            if (pct >= 80) setMessage('Almost there — keep holding!');
            else if (pct >= 30) setMessage('Great! Keep holding… steady hands are the key.');
            else setMessage('Detected! Hold it steady…');
        } else if (data.detected_class) {
            setDot(DOT.seen);
            setDetected(data.detected_class, data.top_confidence);
            setMessage(`Not quite — show your ${target} mudra.`);
        } else {
            setDot(DOT.none);
            setDetected('Searching…', null);
            setMessage(`Searching… show your ${target} mudra clearly.`);
        }
    }

    async function start() {
        if (starting || camera.isRunning) return;
        starting = true;
        startBtn?.setAttribute('disabled', 'disabled');
        setMessage('Requesting camera…');

        try {
            await camera.start();
        } catch {
            setMessage('Camera unavailable. Please allow camera access and try again.');
            starting = false;
            startBtn?.removeAttribute('disabled');
            return;
        }

        if (resolution) resolution.textContent = `${video.videoWidth}×${video.videoHeight}`;

        try {
            const session = await createSession();
            if (sessionId) sessionId.textContent = `#PS-${session.session_id}`;
            if (sessionStarted && session.started_at) sessionStarted.textContent = session.started_at;
            toggleButtons(true);

            if (session.verified) {
                setDot(DOT.verified);
                setDetected('Completed', null);
                setHold(parseInt(root.dataset.holdSeconds, 10), parseInt(root.dataset.holdSeconds, 10));
                setMessage('You have already completed this today. Great work!');
            } else {
                const detectUrl = detectTemplate.replace('__SESSION__', session.session_id);
                loop = new DetectionLoop(camera, detectUrl, {
                    intervalMs,
                    onResult: handleResult,
                    onError: (error) => {
                        setDot(DOT.none);
                        setMessage(error?.message ? `⚠ ${error.message}` : 'Reconnecting…');
                    },
                });
                loop.start();
                setMessage(`Camera ready. Show your ${target} mudra.`);
            }
        } catch (error) {
            teardown();
            setDot(DOT.none);
            setMessage(error.message);
        } finally {
            starting = false;
            startBtn?.removeAttribute('disabled');
        }
    }

    startBtn?.addEventListener('click', start);
    stopBtn?.addEventListener('click', () => {
        teardown();
        setDot(DOT.none);
        setMessage('Practice stopped. Press Start to resume.');
    });

    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'hidden') teardown();
    });
    window.addEventListener('pagehide', teardown);
}

document.addEventListener('DOMContentLoaded', boot);
