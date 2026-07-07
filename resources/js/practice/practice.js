/**
 * Practice screen entry point (orchestration only).
 *
 * Wires the camera, detection loop and overlay, and renders status to the DOM.
 * It holds NO AI logic: match/hold/verification all come from the server's
 * DetectionResult. The browser only displays what the backend returns, mapped
 * to friendly presentation states (Excellent / Good / Almost there / Try again).
 */
import { CameraController } from './camera.js';
import { DetectionLoop } from './detector.js';
import { drawPredictions, clearOverlay } from './overlay.js';

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

/** Presentation palette per detection state (classes swapped as a set). */
const STATES = {
    idle: { dot: 'bg-gray-300', box: 'bg-gray-50', text: 'text-gray-700', live: 'bg-gray-400' },
    search: { dot: 'bg-gray-400 animate-pulse', box: 'bg-gray-50', text: 'text-gray-700', live: 'bg-gray-400' },
    wrong: { dot: 'bg-rose-500', box: 'bg-rose-50', text: 'text-rose-700', live: 'bg-rose-400' },
    almost: { dot: 'bg-amber-500', box: 'bg-amber-50', text: 'text-amber-700', live: 'bg-amber-400' },
    good: { dot: 'bg-teal-500', box: 'bg-teal-50', text: 'text-teal-700', live: 'bg-teal-400' },
    excellent: { dot: 'bg-emerald-500', box: 'bg-emerald-50', text: 'text-emerald-700', live: 'bg-emerald-400' },
    verified: { dot: 'bg-emerald-500', box: 'bg-emerald-50', text: 'text-emerald-700', live: 'bg-emerald-400' },
};

const classesOf = (key) => Object.values(STATES).flatMap((s) => s[key].split(' '));
const swap = (element, key, state) => {
    if (!element) return;
    element.classList.remove(...classesOf(key));
    element.classList.add(...STATES[state][key].split(' '));
};

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
    const idle = el('practice-idle');
    const stateBox = el('practice-state');
    const dot = el('practice-status-dot');
    const detected = el('practice-detected');
    const confidence = el('practice-confidence');
    const compare = el('practice-compare');
    const compareDetected = el('practice-compare-detected');
    const livePill = el('practice-live-pill');
    const liveDot = el('practice-live-dot');
    const liveText = el('practice-live-text');
    const holdBar = el('practice-hold-bar');
    const holdStrip = el('practice-hold-strip');
    const holdLabel = el('practice-hold-label');
    const message = el('practice-message');
    const sessionStarted = el('practice-session-started');
    const sessionId = el('practice-session-id');

    const startUrl = root.dataset.startUrl;
    const detectTemplate = root.dataset.detectTemplate;
    const target = root.dataset.target ?? 'the prescribed mudra';
    const targetLabel = (root.dataset.targetLabel ?? '').toLowerCase();
    const threshold = parseFloat(root.dataset.confidenceThreshold ?? '0.75');
    const intervalMs = parseInt(root.dataset.detectionIntervalMs ?? '1000', 10);
    const jpegQuality = parseFloat(root.dataset.jpegQuality ?? '0.7');

    const setMessage = (text) => { if (message) message.textContent = text; };

    /** One call renders the whole detection state (card + on-video pill). */
    const setState = (state, label, confidencePct = null) => {
        swap(stateBox, 'box', state);
        swap(dot, 'dot', state);
        swap(detected, 'text', state);
        swap(liveDot, 'live', state);
        if (detected) detected.textContent = label;
        if (liveText) liveText.textContent = label;
        if (confidence) confidence.textContent = confidencePct === null ? '' : `${confidencePct}%`;
    };

    const setCompare = (detectedName) => {
        if (!compare) return;
        if (!detectedName) {
            compare.classList.add('hidden');
            compare.classList.remove('flex');
            return;
        }
        if (compareDetected) compareDetected.textContent = detectedName;
        compare.classList.remove('hidden');
        compare.classList.add('flex');
        // retrigger the gentle shake
        compare.classList.remove('shake');
        void compare.offsetWidth;
        compare.classList.add('shake');
    };

    // Prescribed durations are minutes long — show m:ss there, seconds for short holds.
    const formatHold = (seconds, total) => {
        if ((total ?? 0) < 60) return `${(seconds ?? 0).toFixed(1)}s`;
        const whole = Math.floor(seconds ?? 0);
        return `${Math.floor(whole / 60)}:${String(whole % 60).padStart(2, '0')}`;
    };

    const setHold = (held, hold) => {
        const pct = hold ? Math.max(0, Math.min(100, (held / hold) * 100)) : 0;
        if (holdBar) holdBar.style.width = `${pct.toFixed(0)}%`;
        if (holdStrip) holdStrip.style.width = `${pct.toFixed(0)}%`;
        if (holdLabel) holdLabel.textContent = `${formatHold(held, hold)} / ${formatHold(hold, hold)}`;
    };

    const toggleButtons = (running) => {
        startBtn?.classList.toggle('hidden', running);
        stopBtn?.classList.toggle('hidden', !running);
        pill?.classList.toggle('hidden', !running);
        idle?.classList.toggle('hidden', running);
        livePill?.classList.toggle('hidden', !running);
        livePill?.classList.toggle('flex', running);
    };

    let starting = false;
    let loop = null;
    let celebrated = false;

    // Success celebration: reveal the overlay, wire the "next mudra" link and
    // rain a little confetti over the camera area. Pure presentation.
    function celebrate(data) {
        if (celebrated) return;
        celebrated = true;

        const successOverlay = el('practice-success');
        if (!successOverlay) return;

        const sub = el('practice-success-sub');
        if (sub) {
            const pct = ((data.confidence ?? 0) * 100).toFixed(0);
            sub.textContent = `${target} · ${pct}% confidence`;
        }

        const nextUrl = root.dataset.nextUrl;
        const nextLink = el('practice-next');
        if (nextLink && nextUrl) {
            nextLink.href = nextUrl;
            const nextName = el('practice-next-name');
            if (nextName) nextName.textContent = root.dataset.nextName ?? '';
            nextLink.classList.remove('hidden');
            nextLink.classList.add('inline-flex');
        }

        const holder = el('practice-confetti');
        if (holder) {
            const colors = ['#14b8a6', '#f59e0b', '#f43f5e', '#8b5cf6', '#22c55e', '#0ea5e9'];
            for (let i = 0; i < 28; i += 1) {
                const piece = document.createElement('span');
                piece.className = 'confetti-piece';
                piece.style.left = `${Math.random() * 100}%`;
                piece.style.backgroundColor = colors[i % colors.length];
                piece.style.animationDuration = `${1.8 + Math.random() * 1.6}s`;
                piece.style.animationDelay = `${Math.random() * 0.4}s`;
                holder.appendChild(piece);
            }
        }

        successOverlay.classList.remove('hidden');
        successOverlay.classList.add('flex');
    }

    const teardown = () => {
        loop?.stop();
        loop = null;
        camera.stop();
        clearOverlay(overlay);
        toggleButtons(false);
        setHold(0, parseInt(root.dataset.holdSeconds, 10) || 0);
        setCompare(null);
    };

    const camera = new CameraController(video, {
        jpegQuality,
        onDisconnect: () => {
            teardown();
            setState('idle', 'Camera disconnected');
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
            setState('idle', 'Detection unavailable');
            setCompare(null);
            setMessage(`⚠ ${data.message ?? 'Detection is temporarily unavailable.'}`);
            return;
        }

        drawPredictions(overlay, video, data.predictions ?? [], targetLabel, target);
        setHold(data.held_seconds ?? 0, data.hold_seconds ?? 0);

        if (data.verified) {
            setState('verified', 'Verified', Math.round((data.confidence ?? 0) * 100));
            setCompare(null);
            setHold(data.hold_seconds, data.hold_seconds);
            setMessage('✓ Verified! Your session has been recorded.');
            loop?.stop();
            loop = null;
            camera.stop();
            toggleButtons(false);
            celebrate(data);
            return;
        }

        const detectedToken = (data.detected_class ?? '').toLowerCase();
        const targetPct = Math.round((data.confidence ?? 0) * 100);
        const topPct = Math.round((data.top_confidence ?? 0) * 100);
        const holdPct = data.hold_seconds ? ((data.held_seconds ?? 0) / data.hold_seconds) * 100 : 0;

        if (data.matched) {
            // Friendly tiers: Excellent >= 90%, Good >= threshold.
            const excellent = (data.confidence ?? 0) >= 0.9;
            setState(excellent ? 'excellent' : 'good', `${excellent ? 'Excellent' : 'Good'} — ${target}`, targetPct);
            setCompare(null);
            if (holdPct >= 80) setMessage('Almost there — keep holding!');
            else if (holdPct >= 30) setMessage('Great! Keep holding… steady hands are the key.');
            else setMessage('Detected! Hold it steady…');
        } else if (detectedToken && detectedToken === targetLabel) {
            // Right mudra, but below the confidence bar.
            setState('almost', `Almost there — ${target}`, targetPct);
            setCompare(null);
            setMessage(`Right mudra! Adjust your pose to pass ${Math.round(threshold * 100)}% confidence.`);
        } else if (detectedToken) {
            // A hand was recognised but it is not the prescribed mudra. The
            // internal class name is never shown — only a generic state.
            setState('wrong', 'Try again', topPct);
            setCompare('Incorrect mudra');
            setMessage(`That doesn't look right — adjust your hand to match the ${target}.`);
        } else {
            setState('search', 'Searching…');
            setCompare(null);
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
                setState('verified', 'Completed');
                setHold(parseInt(root.dataset.holdSeconds, 10), parseInt(root.dataset.holdSeconds, 10));
                setMessage('You have already completed this today. Great work!');
            } else {
                setState('search', 'Searching…');
                const detectUrl = detectTemplate.replace('__SESSION__', session.session_id);
                loop = new DetectionLoop(camera, detectUrl, {
                    intervalMs,
                    onResult: handleResult,
                    onError: (error, status) => {
                        setState('idle', 'Connection issue');
                        if (status === 419) {
                            setMessage('⚠ Your session expired. Please refresh the page to continue.');
                            loop?.stop();
                        } else {
                            setMessage(error?.message ? `⚠ ${error.message}` : 'Reconnecting…');
                        }
                    },
                });
                loop.start();
                setMessage(`Camera ready. Show your ${target} mudra.`);
            }
        } catch (error) {
            teardown();
            setState('idle', 'Press Start to begin');
            setMessage(error.message);
        } finally {
            starting = false;
            startBtn?.removeAttribute('disabled');
        }
    }

    startBtn?.addEventListener('click', start);
    stopBtn?.addEventListener('click', () => {
        teardown();
        setState('idle', 'Press Start to begin');
        setMessage('Practice stopped. Press Start to resume.');
    });

    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'hidden') teardown();
    });
    window.addEventListener('pagehide', teardown);
}

document.addEventListener('DOMContentLoaded', boot);
